<?php
/**
 * Extend EmailOctopus for WooCommerce
 *
 * @package  FWS_Woo_EmailOctopus_Integration
 * @category Integration
 * @author   Olaf Lederer
 */

class FWS_Woo_EmailOctopus_Integration extends WC_Integration {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->id = 'fws-woo-emailoctopus';
		$this->method_title = __( 'EmailOctopus', 'fws-woo-emailoctopus' );
		$this->method_description = __( 'Add newsletter subscribers to a specific EmailOctopus list', 'fws-woo-emailoctopus' );
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
                'title' 		=> __( 'List', 'fws-woo-emailoctopus' ),
                'type' 			=> 'select',
                'class'         => 'wc-enhanced-select',
                'description' => __( 'The default list which will be taken for new subscribers', 'fws-woo-emailoctopus' ),
                'default' 		=> '',
                'options'		=> $this->get_list_options(),
                'desc_tip' => true
            ),
            'checkout_position' => array(
				'title' 		=> __( 'Position', 'fws-woo-emailoctopus' ),
				'type' 			=> 'select',
				'class'         => 'wc-enhanced-select',
				'default' 		=> 'checkout_billing',
				'options'		=> array(
					'checkout_billing' => __( 'After billing details', 'fws-woo-emailoctopus' ),
					'checkout_shipping' => __( 'After shipping details', 'fws-woo-emailoctopus' ),
					'checkout_after_customer_details' => __( 'After customer details', 'fws-woo-emailoctopus' ),
					'checkout_after_terms_and_conditions' => __( 'After terms and conditions', 'fws-woo-emailoctopus' )
				),
			),
			'emailoctopus_subscribe_text' => array(
				'title'             => __( 'Subscription label', 'fws-woo-emailoctopus' ),
				'type'              => 'text',
				'default'           => '',
				'desc_tip'          => true,
				'description'       => __( 'The text for the subscription on the checkout page.', 'fws-woo-emailoctopus' ),
			),
			'em_store_categories' => array(
				'title'             => __( 'Store product categories', 'fws-woo-emailoctopus' ),
				'type'              => 'checkbox',
				'default'           => '',
				'label'       => __( 'Use the product categories from an order as tags in EmailOctopus.', 'fws-woo-emailoctopus' ),
			),
			'em_store_used_coupon' => array(
				'title'             => __( 'Store coupon', 'fws-woo-emailoctopus' ),
				'type'              => 'checkbox',
				'default'           => '',
				'label'       => __( 'Send the "coupon" tag to EmailOctopus whenever a coupon is used during a checkout.', 'fws-woo-emailoctopus' ),
			),
			'em_store_last_purchase' => array(
				'title'             => __( 'Last order', 'fws-woo-emailoctopus' ),
				'type'              => 'checkbox',
				'default'           => '',
				'label'       => __( 'Store the last order date in EmailOctopus.', 'fws-woo-emailoctopus' ),
			),
			'em_store_all_customers' => array(
				'title'             => __( 'Subscribe everyone', 'fws-woo-emailoctopus' ),
				'type'              => 'checkbox',
				'default'           => '',
				'label'       => __( 'Check this option if you need to send one or more after-sales emails to your customers. For commercial emails it\'s still required to ask for their consent first!', 'fws-woo-emailoctopus' ),
			),
			'em_send_language' => array(
				'title'             => __( 'Submit language', 'fws-woo-emailoctopus' ),
				'type'              => 'checkbox',
				'default'           => '',
				'label'       => __( 'Submit the language code to Emailoctopus if your shop supports multiple languages. You can use that code to send emails in different languages.', 'fws-woo-emailoctopus' ),
			)
		);
	}

	public function custom_process_admin_options() {    
        parent::process_admin_options();
     	$settings = get_option('woocommerce_fws-woo-emailoctopus_settings');
     	if (isset($settings['list']) && (!empty($settings['em_store_last_purchase']) || !empty($settings['em_send_language']))) {
     		$eo = new EmailOctopus_subscriptions();
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
		$eo = new EmailOctopus_subscriptions();
		$first = array( '' => __('Choose one...', 'fws-woo-emailoctopus' ) );
		$resp = $eo->get_lists();
		$options = array_merge($first, $resp);
		return $options;
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
		$subscribed = get_post_meta($order_id, 'emailoctopus_subscribed', true);
		if (empty($subscribed)) return; // don't subscribe again if the order status is changed
		$order = wc_get_order( $order_id );
		//error_log(print_r($order, true));
		

		$settings = get_option('woocommerce_fws-woo-emailoctopus_settings');
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
			$fields['LastPurchase'] = date('Y-m-d');
		}
		if (count($tags) > 0) $fields['tags'] = implode(',', $tags);
		
		$handler = new EmailOctopus_subscriptions();
		$response = $handler->add_subscriber($billing_email, $settings['list'], $fields, true);
		
		if (isset($response['error']['code'])) {
			$order->add_order_note('EmailOctopus error: '. $response['error']['message']);
		} else {
			$order->add_order_note( $billing_email.' added to the mailing list');
		}
	}
	

	/*
    public function get_response($response) {
        if ( ! is_wp_error($response) ) {
            $this->response = wp_remote_retrieve_body($response);
            $this->response_code = wp_remote_retrieve_response_code($response);
            if ( ! is_wp_error($this->response)) {
                $response = json_decode($this->response);
                if (json_last_error() == JSON_ERROR_NONE) {
                    if ( ! isset($response->error)) {
                        return $response;
                    }
                }
            }
        } else {
            $this->response = $response->get_error_message();
            $this->response_code = 0;
        }
    }
    */
}
