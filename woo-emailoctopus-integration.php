<?php

/**
 * Integration for EmailOctopus
 *
 * @package  FWS_Woo_EmailOctopus
 * @category Integration
 * @author   Olaf Lederer
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'FWEO_Woo_EmailOctopus' ) ) {

	class FWEO_Woo_EmailOctopus {

		public $eo_settings;

		/**
		* Construct the plugin.
		*/
		public function __construct() {

		}

		/**
		* Initialize the plugin.
		*/
		public function init() {
			

			// Include the integration class.
			include_once FWEO_DIR . 'include/class-woo-emailoctopus-integration.php';

			$this->eo_settings = get_option('woocommerce_fws-woo-emailoctopus_settings');

			// Register the integration.
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );

			if (isset($this->eo_settings['checkout_position'])) {
				add_action('woocommerce_'.$this->eo_settings['checkout_position'], array( $this, 'subscribe_checkbox_field'));
			}

			add_action('woocommerce_checkout_update_order_meta', array( $this, 'checkout_order_meta'));

		}

		/**
		 * Add a new integration to WooCommerce.
		 */
		public function add_integration( $integrations ) {
			$integrations[] = 'FWEO_Woo_EmailOctopus_Integration';
			return $integrations;
		}

		/**
		 * Create a checkbox field for the checkout page.
		 */
		public function subscribe_checkbox_field() {
			if (function_exists('pll_register_string')) {
				$label = pll__( 'Please send me your newsletter.' );
			} else {
				if (!empty($this->eo_settings['emailoctopus_subscribe_text'])) {
					$label = $this->eo_settings['emailoctopus_subscribe_text'];
				} else {
					$label = __( 'Please send me your newsletter.', 'fw_emailoctopus_integration' );
				}
			}
			
			echo '<div class="fws_custom_class">';
			woocommerce_form_field( 'fws_emailoctopus_checkbox', array(
				'type'          => 'checkbox',
				'label'         => $label,
				'required'  => false
			), null);
			echo '</div>';
		}

		public function checkout_order_meta( $order_id ) {
			if (!empty($_POST['fws_emailoctopus_checkbox'])) { // phpcs:ignore WordPress.Security.NonceVerification
				update_post_meta( $order_id, 'fweo_emailoctopus_subscribed', 'check');
			} else {
				if ($this->eo_settings['em_store_all_customers'] == 'yes') {
					update_post_meta( $order_id, 'fweo_emailoctopus_subscribed', 'all');
				}
			}
		}
	}
}



