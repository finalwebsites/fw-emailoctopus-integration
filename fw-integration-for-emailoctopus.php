<?php
/*
Plugin Name: EO4WP: EmailOctopus for WordPress
Version: 1.0.1
Plugin URI: https://www.finalwebsites.com
Description: Increase the count of new subscribers for your blog or website by using EmailOctopus and this integration plugin.
Author: Olaf Lederer
Author URI: https://www.olaflederer.com/
Text Domain: fw-integration-for-emailoctopus
Domain Path: /languages/
License: GPL v3

EO4WP: EmailOctopus for WordPress
Copyright (C) 2024, Olaf Lederer - https://www.olaflederer.com/

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly      


define('FWEO_DIR', plugin_dir_path( __FILE__ ));
define('FW_EO_VER', '1.0.1');

include_once FWEO_DIR.'include/options.php';
include_once FWEO_DIR.'include/form-shortcodes.php';




if ( ! defined( 'ABSPATH' ) ) exit;

class FWEO_EmailOctopus_integration {

	private $api_key;
	private $api_url;

	public function __construct($apikey = '') {
		if ($apikey != '') {
			$this->api_key = $apikey;
		} else {
			$this->api_key = get_option('fweo_emailoctopus_api_key');
		}
		$this->api_url = 'https://emailoctopus.com/api/1.6/';

		if (empty($this->api_key)) {

			add_action('admin_notices', function() {
				global $pagenow;
    			if ( $pagenow != 'options-general.php' ) echo '
	<div class="notice notice-warning">
        <p>'.esc_html__( 'To use the WP EmailOctopus integration plugin, you need to enter a valid API key.', 'fw-integration-for-emailoctopus' ).' <span class="dashicons dashicons-edit"></span> <a href="'.esc_attr ( admin_url( 'options-general.php?page=fweo-emailoctopus-settings') ).'">'.esc_html__('Plugin settings', 'fw-integration-for-emailoctopus').'</a></p>
    </div>';
    		});
		} else {
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}
		
	}

	public function init() {
		load_plugin_textdomain( 'fw-integration-for-emailoctopus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		add_action('wp_enqueue_scripts', array($this, 'add_assets'));

		add_action( 'wp_ajax_emailoctopus_subscribeform_action', array($this, 'subform_action_callback') );
		add_action( 'wp_ajax_nopriv_emailoctopus_subscribeform_action', array($this, 'subform_action_callback') );

		add_action( 'wp_ajax_eo_loadtime', array($this, 'subform_loadtime_callback' ));
		add_action( 'wp_ajax_nopriv_eo_loadtime', array($this, 'subform_loadtime_callback' ));

		if ( class_exists( 'WooCommerce') ) {
			// Include the integration class.
			include_once FWEO_DIR . 'woo-emailoctopus-integration.php';
			$FWEO_Woo_EmailOctopus = new FWEO_Woo_EmailOctopus();
			$FWEO_Woo_EmailOctopus->init();
		}
		add_action( 'elementor_pro/forms/actions/register', array($this, 'add_emailoctopus_form_action') );
		
		if (function_exists('pll_register_string')) {
			pll_register_string( 'woo_text_newsletter', 'Please send me your newsletter.', 'fw-integration-for-emailoctopus', false );
			pll_register_string( 'emailoctopus_subscribe_text', 'Yes, please add me to your mailing list.', 'fw-integration-for-emailoctopus', false );
		}
	}
	
	public function add_emailoctopus_form_action( $form_actions_registrar ) {
		include_once FWEO_DIR.'form-actions/emailoctopus.php';
		$form_actions_registrar->register( new FWEO_EmailOctopus_Action_After_Submit() );

	}
	
	public function add_assets() {
		global $post;
		$show = false;
		
		if (get_option('fweo_emailoctopus_show_all_pages')) {
			$show = true;
		} else {
			if (is_singular(array('post', 'page'))) {
				if (is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'FWEO_EmailOctopusSubForm')) ) {
					$show = true;
				}
			}
			$show = apply_filters( 'fweo_emailoctopus_show_static', $show, $post );
		}
		if ($show) {
			wp_enqueue_script('fw-emailoctopus', plugin_dir_url(__FILE__).'include/emailoctopus.js', array('jquery'), FW_EO_VER, true );
			wp_localize_script( 'fw-emailoctopus', 'eo_ajax_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'plugin_base_path' => plugin_dir_url(__FILE__),
					'js_alt_loading' => __( 'Loading...', 'fw-integration-for-emailoctopus' ),
					'js_msg_enter_email_name' => __( 'Please enter your name and email address.', 'fw-integration-for-emailoctopus' ),
					'js_msg_enter_email' => __( 'Please enter your email address.', 'fw-integration-for-emailoctopus' ),
					'js_msg_invalid_email' => __( 'The entered email address is invalid.', 'fw-integration-for-emailoctopus' ),
					'googleanalytics' => get_option('fweo_emailoctopus_google_analytics')
				)
			);
			if (get_option('fweo_emailoctopus_include_css')) {
				wp_enqueue_style( 'fw-emailoctopus-style', plugin_dir_url(__FILE__).'include/style.css', array(), FW_EO_VER );
			}
		}
	}

	private function is_valid_api_key() {
		$lists = $this->get_lists();
		if ( ! empty( $lists ) ) {
			return true;
		}
		$this->api_key = '';
		return false;
	}

	private function check_subscriber_exists($email_adr, $list_id) {
		
		$id = md5(strtolower($email_adr));
		$url = $this->api_url.'lists/'.$list_id.'/contacts/'.$id.'?api_key='.$this->api_key;
		$response = wp_remote_get( esc_url_raw( $url ) );
		$api_response = json_decode( wp_remote_retrieve_body( $response ), true );
		if (isset($api_response['error']['code']) && $api_response['error']['code'] == 'MEMBER_NOT_FOUND') {
			return 'new';
		} else {
			// Subscriber exists > return member ID
			return $api_response['id'];
		}
	}

	public function get_lists() {
		$url = $this->api_url.'lists?api_key='.$this->api_key;
		$raw = wp_remote_get( esc_url_raw( $url ) );		
		$response = json_decode( wp_remote_retrieve_body( $raw ), true );
		if (isset($response['data'])) {
			$lists = array();
			foreach ($response['data'] as $list) {
				$lists[$list['id']] = $list['name'];
			}
			return $lists;
		} else {
			return null;
		}
	}

	public function get_list_fields($list_id, $return_all = false) {
		$url = $this->api_url.'lists/'.$list_id.'?api_key='.$this->api_key;
		$response = wp_remote_get( esc_url_raw( $url ) );
		$api_response = json_decode( wp_remote_retrieve_body( $response ), true );
		if ($return_all) {
			return $api_response['fields'];
		} else {
			$fields = array();
			// store all the field tags in one array
			foreach($api_response['fields'] as $field) {
				$fields[] = $field['tag'];
			}
			return $fields;
		}
	}

	public function create_list_field($fields, $list) {
		$fields['api_key'] = $this->api_key;
		$url = $this->api_url.'lists/'.$list.'/fields';
    	$data = wp_remote_post($url, array(
		    'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
		    'body'        => wp_json_encode($fields),
		    'method'      => 'POST',
		    'data_format' => 'body',
		));
	}


	public function add_subscriber($email, $list, $data, $return_all = false) {

		if (!empty($email)) {
			
			$test = $this->check_subscriber_exists($email, $list);
			$valid_fields = array();
			$tags = array();
			$eo_fields = $this->get_list_fields($list);
			// check which form fields exists on your EmailOctopus list
			foreach ($data as $key => $val) {
				if (in_array($key, $eo_fields)) {
					$valid_fields[$key] = $val;
				} elseif ($key == 'tags') {
					$tags = array_map('trim', explode(',', $val));
					if ($test != 'new') {
						$update_tags = array();
						foreach ($tags as $tag) {
							$update_tags[$tag] = true;
						}
						$tags = $update_tags;
					}
				}
			}
			if (!empty($data['extra'])) {
				foreach ($data['extra'] as $key => $value) {
					$key = strtolower($key);
					$valid_fields['fields'][$key] = $value;
				}
			}
		
			$post_array = array(
				'api_key' => $this->api_key,
				'email_address' => $email,
				'fields' => $valid_fields,
				'tags' => $tags,
				'status' => 'SUBSCRIBED'
			);
			
			$method = 'POST';
			$url = $this->api_url.'lists/'.$list.'/contacts';
			
			if ($test != 'new') {
				$url .= '/'.$test;
				$method = 'PUT';
			} 
			
			$raw_response = wp_remote_post($url, array(
			    'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
			    'body'        => wp_json_encode($post_array),
			    'method'      => $method,
			    'data_format' => 'body',
			));
			$response_body = wp_remote_retrieve_body( $raw_response );
			$response = json_decode( $response_body, true );

			if ($return_all) {
				return $response;
			} else {
				//print_r($response);
				if (isset($response['error']['code'])) {
					return false;
				} else { 
					return true;
				}
			}
		}
	}

	public function subform_loadtime_callback() {
		if (isset($_COOKIE['eosub_loadtime'])) {
			return;
		} else {
			$host = sanitize_text_field($_SERVER['HTTP_HOST']);
			setcookie("eosub_loadtime", time(), 0, '/', $host);
		}
	}


	public function subform_action_callback() {
		$error = '';
		$status = 'error';
		$goal = '';

		if (empty($_POST['FirstName']) || empty($_POST['email'])) {
			$error = __( 'Both fields are required to enter.', 'fw-integration-for-emailoctopus' );
		} else {
			if ( !isset( $_POST['_fwseo_subnonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['_fwseo_subnonce'] ) ) , 'fwseo_subform' ) ) {
				$error = __( 'Verification error, try again.', 'fw-integration-for-emailoctopus' );
			} else {
				$valid_captcha = true;
				if (empty($_COOKIE['eosub_loadtime']) || (int)$_COOKIE['eosub_loadtime'] > (time()-15)) {
                    $valid_captcha = false;
                    $error = __( 'Invalid form submission, please try again.', 'fw-integration-for-emailoctopus' );
                } 
                if (empty($_POST['aftersubmit'])) {
					$error = __( 'The form is currently closed for an unknown reason.', 'fw-integration-for-emailoctopus' );
					$valid_captcha = false;
				}
	            if ($valid_captcha) {
					$email = sanitize_email($_POST['email']);
					$data = array();
					$data['FirstName'] = sanitize_text_field($_POST['FirstName']);
					$data['Source'] = sanitize_text_field($_POST['source']);
					$thank_you = sanitize_text_field($_POST['thank_you']);
					$cookie_name = sanitize_text_field($_POST['cookie_name']);
					if (!empty($_POST['clicky'])) $goal = intval($_POST['clicky']);
					if (!empty($_POST['newsletter'])) $data['tags'] = 'newsletter';

					$list = get_option('fweo_emailoctopus_list_id');
					if (!empty($_POST['listid'])) {
						$list = sanitize_text_field($_POST['listid']);
					}

			
					if ($extra_fields_list = get_option('fweo_emailoctopus_extra_fields')) {
						$extra_fields = explode(PHP_EOL,$extra_fields_list);
						foreach ($extra_fields as $extra) {
							$parts = explode('|', $extra);
							if (!empty($_POST[$parts[0]])) {
								$data[$parts[0]] = sanitize_text_field($_POST[$parts[0]]);
							}
						}
					}
					if (!empty($_POST['hidden'])) {
						foreach ($_POST['hidden'] as $key => $val) {
							$data[$key] = sanitize_text_field($val);
						}
					}
	                $data = apply_filters( 'fweo_add_extra_data_fields', $data );
	                //print_r($data);
					if ($_POST['report_only'] == 'o') {
						$response = $this->report_submission($email, $list, $data);
						if ($response == 'send') {
							$status = 'success';
							$error = __( 'Thanks, for your interest.', 'fw-integration-for-emailoctopus' );
							if ($thank_you != '') $error = $thank_you;
						} elseif ($response == 'invalidmail') {
							$error = __( 'The entered email address is not valid.', 'fw-integration-for-emailoctopus' );
						} else {
							$error = __( 'An unknown error occurred.', 'fw-integration-for-emailoctopus' );
						}
					} else {
						if ($_POST['report_only'] == 'y') {
							$this->report_submission($email, $list, $data);
						}
						if ($result = $this->add_subscriber($email, $list, $data)) {
	                        
							$status = 'success';
							if ($cookie_name != '') {
								setcookie( $cookie_name, 'yes', strtotime( '+365 days' ) );
							}
							$error = __( 'Thanks, for joining our mailing list!', 'fw-integration-for-emailoctopus' );
							if ($thank_you != '') $error = $thank_you;
						} else {
							$error = __( 'An unknown error occurred.', 'fw-integration-for-emailoctopus' );
						}
					}
				}
			}
		}
		$resp = array('status' => $status, 'errmessage' => $error, 'clickyanalytics' => $goal);
		header( "Content-Type: application/json" );
		echo wp_json_encode($resp);
		die();
	}

	public function report_submission($email, $list, $data) {
		if ( is_email( $email ) ) {
			
			$msg = __('Email address: ', 'fw-integration-for-emailoctopus').$email.PHP_EOL;
			foreach ($data as $key => $val) {
				$msg .= $key.': '.$val.PHP_EOL;
			}
			$all_lists = $this->get_lists();
			$msg .= __('List name: ', 'fw-integration-for-emailoctopus').$all_lists[$list].PHP_EOL;
			
			$msg .= 'IP address: '.$this->get_client_ip();
			$subject = __('A form submission from your website', 'fw-integration-for-emailoctopus');
			$mailto = get_option('admin_email');
			if (wp_mail( $mailto, $subject, $msg )) {
				return 'send';
			} else {
				return false;
			}
		} else {
			return 'invalidmail';
		}
	}


	public function get_client_ip() {
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
			if (array_key_exists($key, $_SERVER) === true){
				foreach (explode(',', $_SERVER[$key]) as $ip){
					$ip = trim($ip); // just to be safe

					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
						return $ip;
					}
				}
			}
		}
	}

}

$fw_emailoctopus_settings = new FWEO_EmailOctopus_Plugin_Settings( __FILE__ );
$fw_emailoctopus = new FWEO_Create_EmailOctopus_Forms();
