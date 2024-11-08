<?php

/**
 * EO4WP: EmailOctopus for WordPress
 *
 * @package  EmailOctopus_Plugin_Settings
 * @category Options
 * @author   Olaf Lederer
 */

if ( ! defined( 'ABSPATH' ) ) exit;


class FWEO_EmailOctopus_Plugin_Settings {

	private $file;
	private $settings_base;
	private $settings;

	public function __construct( $file ) {
		$this->file = $file;
		$this->settings_base = 'fweo_emailoctopus_';
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_init' , array( $this, 'register_settings' ) );
		add_action( 'admin_menu' , array( $this, 'add_menu_item' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ) , array( $this, 'add_settings_link' ) );

	}

	public function init() {
		$this->settings = $this->settings_fields();

	}

	public function add_menu_item() {
		$page = add_options_page(
			__( 'EO4WP: EmailOctopus for WordPress Settings', 'fw-integration-for-emailoctopus' ),
			__( 'EO4WP', 'fw-integration-for-emailoctopus' ),
			'manage_options',
			'fweo-emailoctopus-settings',
			array($this, 'settings_page')
		);
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=fweo-emailoctopus-settings">' . __( 'Settings', 'fw-integration-for-emailoctopus' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	private function settings_fields() {
		$settings['standard'] = array(
			'title'					=> __( 'General', 'fw-integration-for-emailoctopus' ),
			'description'			=> __( 'General settings and options for the EmailOctopus for WordPress plugin.', 'fw-integration-for-emailoctopus' ),
			'fields'				=> array(
				array(
					'id' 			=> 'api_key',
					'label'			=> __( 'EmailOctopus API Key' , 'fw-integration-for-emailoctopus' ),
					'description'	=> __( 'You can find this key in your EmailOctopus account on the profile settings page.', 'fw-integration-for-emailoctopus' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> '',
					'css_class'		=> 'regular-text'
				),
				array(
					'id' 			=> 'list_id',
					'label'			=> __( 'Default mailing list' , 'fw-integration-for-emailoctopus' ),
					'description'	=> __( 'The default mailing list for your subscribers. You need to enter/save the API EmailOctopus API key first.', 'fw-integration-for-emailoctopus' ),
					'type'			=> 'select_lists',
					'options'		=> '',
					'default'		=> ''
				),
				array(
					'id' 			=> 'extra_fields',
					'label'			=> __( 'Custom fields' , 'fw-integration-for-emailoctopus' ),
					'description'	=> __( 'Do you use custom fields for your mailing list? Add the fields below. Each field name must be exact the same as used for the field name in EmailOctopus. If you add such a <strong>field name</strong> as a shortcode <code>extra_fields</code> attribute, a text field including the label from here will be created.', 'fw-integration-for-emailoctopus' ),
					'type'			=> 'textarea',
					'default'		=> '',
					'placeholder'	=> 'field name|form label'
				),
				array(
					'id' 			=> 'text_newsletter',
					'label'			=> __( 'Text for subscribe checkbox', 'fw-integration-for-emailoctopus' ),
					'description'	=> __( 'Add here your text for the additional newsletter subscription checkbox. If the subscriber has checked the newsletter box a tag with the name "newsletter" will be created for a subscriber in EmailOctopus.', 'fw-integration-for-emailoctopus' ),
					'type'			=> 'text',
					'default'		=> __('Yes, please add me to your mailing list.', 'fw-integration-for-emailoctopus'),
					'placeholder'	=> '',
					'css_class'		=> 'regular-text'
				),
				array(
					'id' 			=> 'include_css',
					'label'			=> __( 'Include CSS', 'fw-integration-for-emailoctopus' ),
					'description'	=> __( 'Include the plugin\'s stylesheet for your subscribtion forms.', 'fw-integration-for-emailoctopus' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'include_font_awesome',
					'label'			=> __( 'Incl. Font Awesome', 'fw-integration-for-emailoctopus' ),
					'description'	=> __( 'Include the CSS stylesheet for Font Awesome v.6.5.', 'fw-integration-for-emailoctopus' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'show_all_pages',
					'label'			=> __( 'Incl. JS/CSS sitewide', 'fw-integration-for-emailoctopus' ),
					'description'	=> __( 'Use this option if you like to use the form on all your posts and pages.', 'fw-integration-for-emailoctopus' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'gdpr_text',
					'label'			=> __( 'GDPR text' , 'fw-integration-for-emailoctopus' ),
					'description'	=> __( 'Place here your GDPR info text. Don\'t change the link HTML code, we use the privacy URL which is set via "Settings > Privacy".', 'fw-integration-for-emailoctopus' ),
					'type'			=> 'textarea',
					/* translators: %s - the global privacy URL */
					'default'		=>  __('We use your personal data according our <a href="%s">privacy statement</a>.', 'fw-integration-for-emailoctopus'), 
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'google_analytics',
					'label'			=> __( 'Track in Google Analytics' , 'fw-integration-for-emailoctopus' ),
					'description'	=> __( 'Track an event in Google analytics after the form is submitted. We use the event action "generate_lead" and the event category" "Web forms")', 'fw-integration-for-emailoctopus' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'clicky',
					'label'			=> __( 'Track in Clicky' , 'fw-integration-for-emailoctopus' ),
					'description'	=> __( 'Add here the goal ID for a manual goal you\'ve already defined in Clicky.', 'fw-integration-for-emailoctopus' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> ''
				)
			)
		);
		$settings = apply_filters( 'plugin_settings_fields', $settings );
		return $settings;
	}

	public function register_settings() {
		if( is_array( $this->settings ) ) {
			foreach( $this->settings as $section => $data ) {
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), 'fweo_emailoctopus_plugin_settings' );
				foreach( $data['fields'] as $field ) {
					$option_name = $this->settings_base . $field['id'];
					register_setting( 'fweo_emailoctopus_plugin_settings', $option_name );
					add_settings_field( $field['id'], $field['label'], array( $this, 'display_field' ), 'fweo_emailoctopus_plugin_settings', $section, array( 'field' => $field ) );
				}
			}
		}
	}

	public function settings_section( $section ) {
		$html =   '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo wp_kses( $html, 'post' );
	}

	public function create_list_items() {
		if ($api_key = get_option('fweo_emailoctopus_api_key')) {
			$mainObj = new FWEO_EmailOctopus_integration();
			$lists = $mainObj->get_lists();

			if ($lists) {
				if (!update_option('fweo_mailing_lists', $lists)) {
					add_option('fweo_mailing_lists', $lists);
				}
				return $lists;
			}
		} else {
			return array('' => 'API key is missing.');
		}
	}

	public function display_field( $args ) {
		$field = $args['field'];
		$option_name = $this->settings_base . $field['id'];
		$option = get_option( $option_name );
		$data = '';
		if( isset( $field['default'] ) ) {
			$data = $field['default'];
			if( $option ) {
				$data = $option;
			}
		}
		switch( $field['type'] ) {
			case 'textarea':
				echo '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . esc_textarea( $data ) . '</textarea>';
			break;
			case 'checkbox':
				$checked = '';
				if( $option && 'on' == $option ){
					$checked = 'checked="checked"';
				}
				echo '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" ' . esc_attr( $checked ) . '/>';
			break;
			case 'select_lists':
				$options = $this->create_list_items();
				echo '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				if (is_array($options)) {
					foreach( $options as $k => $v ) {
						$selected = false;
						if( $k == $data ) {
							$selected = true;
						}
						echo '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . esc_attr( $v ) . '</option>';
					}
				}
				echo '</select> ';
			break;
			default:
				$css = (isset($field['css_class'])) ? $field['css_class'] : '';
				echo '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" class="'.esc_attr( $css ).'" />';
			break;

		}
		echo '<label for="' . esc_attr( $field['id'] ) . '"><br><span class="description">' .  wp_kses_post( $field['description'] ) . '</span></label>';
	}

	public function settings_page() {
		$screen = get_current_screen();
		if ( $screen->id != 'settings_page_fweo-emailoctopus-settings' ) {
			return;
		}
		$settings = $this->settings;
		$option_name = $this->settings_base . 'api_key';
		$is_api_key = (get_option($option_name)) ? true : false;
		include_once FWEO_DIR.'include/tpl-options.php';
	}
}
