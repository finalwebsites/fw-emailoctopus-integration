<?php

/*
 * This option page is based on the class script from Hugh Lashbrooke
 * https://gist.github.com/hlashbrooke/9267467
*/

if ( ! defined( 'ABSPATH' ) ) exit;


class EmailOctopus_Plugin_Settings {

	private $file;
	private $settings_base;
	private $settings;

	public function __construct( $file ) {
		$this->file = $file;
		$this->settings_base = 'fw_emailoctopus_';
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
			__( 'EmailOctopus Integration Settings', 'fw_emailoctopus_integration' ),
			__( 'EmailOctopus Integration', 'fw_emailoctopus_integration' ),
			'manage_options',
			'fws-emailoctopus-settings',
			array($this, 'settings_page')
		);
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=fws-emailoctopus-settings">' . __( 'Settings', 'fw_emailoctopus_integration' ) . '</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

	private function settings_fields() {
		$settings['standard'] = array(
			'title'					=> __( 'General', 'fw_emailoctopus_integration' ),
			'description'			=> __( 'General settings and options for the EmailOctopus Integration plugin.', 'fw_emailoctopus_integration' ),
			'fields'				=> array(
				array(
					'id' 			=> 'api_key',
					'label'			=> __( 'EmailOctopus API Key' , 'fw_emailoctopus_integration' ),
					'description'	=> __( 'You can find this key in your EmailOctopus account on the profile settings page.', 'fw_emailoctopus_integration' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> '',
					'css_class'		=> 'regular-text'
				),
				array(
					'id' 			=> 'list_id',
					'label'			=> __( 'Default mailing list' , 'fw_emailoctopus_integration' ),
					'description'	=> __( 'The default mailing list for your subscribers. You need to enter/save the API EmailOctopus API key first.', 'fw_emailoctopus_integration' ),
					'type'			=> 'select_lists',
					'options'		=> '',
					'default'		=> ''
				),
				array(
					'id' 			=> 'extra_fields',
					'label'			=> __( 'Custom fields' , 'fw_emailoctopus_integration' ),
					'description'	=> __( 'Do you use custom fields for your mailing list? Add the fields below. Each field name must be exact the same as used for the field name in EmailOctopus. If you add such a <strong>field name</strong> as a shortcode <code>extra_fields</code> attribute, a text field including the label from here will be created.', 'fw_emailoctopus_integration' ),
					'type'			=> 'textarea',
					'default'		=> '',
					'placeholder'	=> 'field name|form label'
				),
				array(
					'id' 			=> 'text_newsletter',
					'label'			=> __( 'Text for subscribe checkbox', 'fw_emailoctopus_integration' ),
					'description'	=> __( 'Add here your text for the additional newsletter subscription checkbox. If the subscriber has checked the newsletter box a tag with the name "newsletter" will be created for a subscriber in EmailOctopus.', 'fw_emailoctopus_integration' ),
					'type'			=> 'text',
					'default'		=> __('Yes, please add me to your mailing list.', 'fw_emailoctopus_integration'),
					'placeholder'	=> '',
					'css_class'		=> 'regular-text'
				),
				array(
					'id' 			=> 'include_css',
					'label'			=> __( 'Include CSS', 'fw_emailoctopus_integration' ),
					'description'	=> __( 'Include the plugin\'s stylesheet for your subscribtion forms. Add this (spam trap) rule to your CSS file if you don\'t use our CSS file: <code>input[name=Salutation] {display:none; }</code>', 'fw_emailoctopus_integration' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'show_all_pages',
					'label'			=> __( 'Incl. JS/CSS sitewide', 'fws_mailerlite_subscribe' ),
					'description'	=> __( 'Use this option if you like to use the form on all your posts and pages.', 'fw_emailoctopus_integration' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'gdpr_text',
					'label'			=> __( 'GDPR text' , 'fw_emailoctopus_integration' ),
					'description'	=> __( 'Place here your GDPR info text. Don\'t change the link HTML code, we use the privacy URL which is set via "Settings > Privacy".', 'fw_emailoctopus_integration' ),
					'type'			=> 'textarea',
					/* translators: %s - the global privacy URL */
					'default'		=>  __('We use your personal data according our <a href="%s">privacy statement</a>.', 'fw_emailoctopus_integration'), 
					'placeholder'	=> ''
				),
				array(
					'id' 			=> 'google_analytics',
					'label'			=> __( 'Track in Google Analytics' , 'fw_emailoctopus_integration' ),
					'description'	=> __( 'Track a page view in Google Analytics after the subscription form is submitted.', 'fw_emailoctopus_integration' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> '/subscription/submitted.html'
				),
				array(
					'id' 			=> 'clicky',
					'label'			=> __( 'Track in Clicky' , 'fw_emailoctopus_integration' ),
					'description'	=> __( 'Add here the goal ID for a manual goal you\'ve already defined in Clicky.', 'fw_emailoctopus_integration' ),
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
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), 'fw_emailoctopus_plugin_settings' );
				foreach( $data['fields'] as $field ) {
					$option_name = $this->settings_base . $field['id'];
					register_setting( 'fw_emailoctopus_plugin_settings', $option_name );
					add_settings_field( $field['id'], $field['label'], array( $this, 'display_field' ), 'fw_emailoctopus_plugin_settings', $section, array( 'field' => $field ) );
				}
			}
		}
	}

	public function settings_section( $section ) {
		$html =   '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo wp_kses( $html, 'post' );
	}

	public function create_list_items() {
		if ($api_key = get_option('fw_emailoctopus_api_key')) {
			$mainObj = new EmailOctopus_integration();
			$lists = $mainObj->get_lists();

			if ($lists) {
				if (!update_option('fw_mailing_lists', $lists)) {
					add_option('fw_mailing_lists', $lists);
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
				echo '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . esc_html( $data ) . '</textarea>';
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
				$html .= '</select> ';
			break;
			default:
				$css = (isset($field['css_class'])) ? $field['css_class'] : '';
				echo '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" class="'.esc_attr( $css ).'" />';
			break;

		}
		echo '<label for="' . esc_attr( $field['id'] ) . '"><br><span class="description">' .  esc_html( $field['description'] ) . '</span></label>';
	}

	public function settings_page() {
		$screen = get_current_screen();
		if ( $screen->id != 'settings_page_fws-emailoctopus-settings' ) {
			return;
		}
		$settings = $this->settings;
		$option_name = $this->settings_base . 'api_key';
		$is_api_key = (get_option($option_name)) ? true : false;
		include_once FWEO_DIR.'include/tpl-options.php';
	}
}
