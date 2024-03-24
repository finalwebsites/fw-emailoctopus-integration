<?php
/**
 * Integration for EmailOctopus
 *
 * @package  FWS_Woo_EmailOctopus_Integration
 * @category Integration
 * @author   Olaf Lederer
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class FWEO_Woo_EmailOctopus_Integration extends WC_Integration {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id = 'fws-woo-emailoctopus';
		$this->method_title = __( 'EmailOctopus', 'fw_emailoctopus_integration' );
		$this->method_description = __( 'Add newsletter subscribers to a specific EmailOctopus list', 'fw_emailoctopus_integration' );
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();	
		// Actions.
		add_action( 'woocommerce_update_options_integration_'.$this->id, array( $this, 'custom_process_admin_options' ) ); // callback from parent class
  		add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_subscriber_callback' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'list' => array(
                'title' 		=> __( 'List', 'fw_emailoctopus_integration' ),
                'type' 			=> 'select',
                'class'         => 'wc-enhanced-select',
                'description' => __( 'The default list which will be taken for new subscribers', 'fw_emailoctopus_integration' ),
                'default' 		=> '',
                'options'		=> $this->get_list_options(),
                'desc_tip' => true
            ),
            'checkout_position' => array(
				'title' 		=> __( 'Position', 'fw_emailoctopus_integration' ),
				'type' 			=> 'select',
				'class'         => 'wc-enhanced-select',
				'default' 		=> 'checkout_billing',
				'options'		=> array(
					'checkout_billing' => __( 'After billing details', 'fw_emailoctopus_integration' ),
					'checkout_shipping' => __( 'After shipping details', 'fw_emailoctopus_integration' ),
					'checkout_after_customer_details' => __( 'After customer details', 'fw_emailoctopus_integration' ),
					'checkout_after_terms_and_conditions' => __( 'After terms and conditions', 'fw_emailoctopus_integration' )
				),
			),
			'emailoctopus_subscribe_text' => array(
				'title'             => __( 'Subscription label', 'fw_emailoctopus_integration' ),
				'type'              => 'text',
				'default'           => 'Please send me your newsletter.',
				'desc_tip'          => true,
				'description'       => __( 'The text for the subscription on the checkout page.', 'fw_emailoctopus_integration' ),
			),
			'em_store_categories' => array(
				'title'             => __( 'Store product categories', 'fw_emailoctopus_integration' ),
				'type'              => 'checkbox',
				'default'           => '',
				'label'       => __( 'Use the product categories from an order as tags in EmailOctopus.', 'fw_emailoctopus_integration' ),
			),
			'em_store_used_coupon' => array(
				'title'             => __( 'Store coupon', 'fw_emailoctopus_integration' ),
				'type'              => 'checkbox',
				'default'           => '',
				'label'       => __( 'Send the "coupon" tag to EmailOctopus whenever a coupon is used during a checkout.', 'fw_emailoctopus_integration' ),
			),
			'em_store_last_purchase' => array(
				'title'             => __( 'Last order', 'fw_emailoctopus_integration' ),
				'type'              => 'checkbox',
				'default'           => '',
				'label'       => __( 'Store the last order date in EmailOctopus.', 'fw_emailoctopus_integration' ),
			),
			'em_store_all_customers' => array(
				'title'             => __( 'Subscribe everyone', 'fw_emailoctopus_integration' ),
				'type'              => 'checkbox',
				'default'           => '',
				'label'       => __( 'Check this option if you need to send one or more after-sales emails to your customers. For commercial emails it\'s still required to ask for their consent first!', 'fw_emailoctopus_integration' ),
			),
			'em_send_language' => array(
				'title'             => __( 'Submit language', 'fw_emailoctopus_integration' ),
				'type'              => 'checkbox',
				'default'           => '',
				'label'       => __( 'Submit the language code to Emailoctopus if your shop supports multiple languages. You can use that code to send emails in different languages.', 'fw_emailoctopus_integration' ),
			)
		);
	}

	public function custom_process_admin_options() {    
        parent::process_admin_options();
     	$settings = get_option('woocommerce_fweo-woo-emailoctopus_settings');
     	if (isset($settings['list']) && (!empty($settings['em_store_last_purchase']) || !empty($settings['em_send_language']))) {
     		$eo = new FWEO_EmailOctopus_integration();
			if ($api_response = $eo->get_list_fields($settings['list'], true)) {
				$last_purchase = false;
				$language = false;
				foreach ($api_response as $field) {
					
					if ($field['tag'] == 'LastPurchase') {
						$last_purchase = true;
					}
					if ($field['tag'] == 'Language') {
						$language = true;
					}
				}
				if (!$last_purchase) {
					$data = array(
    					'label' => 'Last purchase',
    					'tag' => 'LastPurchase',
    					'type' => 'DATE'
					);
					$eo->create_list_field($data, $settings['list']);
				}
				if (!$language) {
					$data = array(
    					'label' => 'Language',
    					'tag' => 'Language',
    					'type' => 'TEXT'
					);
					$eo->create_list_field($data, $settings['list']);
				}	
			}
		}
    }

	public function get_list_options() {
		$eo = new FWEO_EmailOctopus_integration();
		$first = array( '' => __('Choose one...', 'fw_emailoctopus_integration' ) );
		$resp = $eo->get_lists();
		if (is_array($resp)) {
			$options = array_merge($first, $resp);
			return $options;
		} else {
			return __('Can\'t retrieve any list.', 'fw_emailoctopus_integration' );
		}
	}

	public function get_product_categories($order) {
		$cats = array();
		foreach ( $order->get_items() as $item_id => $item ) {
   			$product_id = $item->get_product_id();
   			$terms = get_the_terms( $product_id, 'product_cat' );
        	foreach ( $terms as $term ) {
            	$cats[] = $term->slug;
            }
        }
        return $cats;
	}

	public function add_subscriber_callback( $order_id) {
		$subscribed = get_post_meta($order_id, 'fweo_emailoctopus_subscribed', true);
		if (empty($subscribed)) return; // don't subscribe again if the order status is changed
		$order = wc_get_order( $order_id );
		

		$settings = get_option('woocommerce_fweo-woo-emailoctopus_settings');
		$billing_email  = $order->get_billing_email();
		$first_name = $order->get_billing_first_name();
		$last_name = $order->get_billing_last_name();

		if (function_exists('pll_get_post_language')) {
			$language = pll_get_post_language($order_id);
		}
		
		$tags = array();
		if ($settings['em_store_categories'] == 'yes') {
			$categs = $this->get_product_categories($order);
			$tags = $categs;
		}
		if ($settings['em_store_used_coupon'] == 'yes') {
			$coupons = $order->get_coupon_codes();
			if (count($coupons) > 0) {
				$tags[] = 'coupon';
			}
		}
		if ($subscribed == 'check') {
			$tags[] = 'newsletter';
		}

		
		$fields = array('FirstName' => $first_name, 'LastName' => $last_name);

		if ($settings['em_send_language'] == 'yes' && !empty($language)) {
			$fields['Language'] = $language;
		}
		if ($settings['em_store_last_purchase'] == 'yes') {
			$fields['LastPurchase'] = gmdate('Y-m-d');
		}
		if (count($tags) > 0) $fields['tags'] = implode(',', $tags);
		
		$handler = new FWEO_EmailOctopus_integration();
		$response = $handler->add_subscriber($billing_email, $settings['list'], $fields, true);
		
		if (isset($response['error']['code'])) {
			$order->add_order_note('EmailOctopus error: '. $response['error']['message']);
		} else {
			$order->add_order_note( $billing_email.' added to the mailing list');
		}
	}
	
}
