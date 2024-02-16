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
			__( 'EmailOctopus Subscriptions Settings', 'fw_emailoctopus_integration' ),
			__( 'EmailOctopus Subscriptions', 'fw_emailoctopus_integration' ),
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
			'description'			=> __( 'General settings and options for the EmailOctopus Subscriptions plugin.', 'fw_emailoctopus_integration' ),
			'fields'				=> array(
				array(
					'id' 			=> 'api_key',
					'label'			=> __( 'EmailOctopus API Key' , 'fw_emailoctopus_integration' ),
					'description'	=> __( '<br />You can find this key in your EmailOctopus account on the profile settings page.', 'fw_emailoctopus_integration' ),
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> '',
					'css_class'		=> 'regular-text'
				),
				array(
					'id' 			=> 'list_id',
					'label'			=> __( 'Default mailing list' , 'fw_emailoctopus_integration' ),
					'description'	=> __( '<br />The default mailing list for your subscribers. You need to enter/save the API EmailOctopus API key first.', 'fw_emailoctopus_integration' ),
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
					'description'	=> __( '<br />Add here your text for the additional newsletter subscription checkbox. If the subscriber has checked the newsletter box a tag with the name "newsletter" will be created for a subscriber in EmailOctopus.', 'fw_emailoctopus_integration' ),
					'type'			=> 'text',
					'default'		=> __('Yes, please add me to your mailing list.', 'fw_emailoctopus_integration'),
					'placeholder'	=> '',
					'css_class'		=> 'regular-text'
				),
				array(
					'id' 			=> 'include_css',
					'label'			=> __( 'Include CSS', 'fw_emailoctopus_integration' ),
					'description'	=> __( 'Include the plugin\'s stylesheet for your subscribtion forms.', 'fw_emailoctopus_integration' ),
					'type'			=> 'checkbox',
					'default'		=> ''
				),
				array(
					'id' 			=> 'gdpr_text',
					'label'			=> __( 'GDPR text' , 'fw_emailoctopus_integration' ),
					'description'	=> __( 'Place here your GDPR info text. Don\'t change the link HTML code, we use the privacy URL which is set via "Settings > Privacy".', 'fw_emailoctopus_integration' ),
					'type'			=> 'textarea',
					'default'		=> __('We use your personal data according our <a href="%s">privacy statement</a>.'),
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
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
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
		$html = '';
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
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . $data . '</textarea><br />'. "\n";
			break;
			case 'checkbox':
				$checked = '';
				if( $option && 'on' == $option ){
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/>' . "\n";
			break;
			case 'select_lists':
				$options = $this->create_list_items();
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				if (is_array($options)) {
					foreach( $options as $k => $v ) {
						$selected = false;
						if( $k == $data ) {
							$selected = true;
						}
						$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
					}
				}
				$html .= '</select> ';
			break;
			default:
				$css = (isset($field['css_class'])) ? $field['css_class'] : '';
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $data . '" class="'.$css.'" />' . "\n";
			break;

		}
		$html .= '<label for="' . esc_attr( $field['id'] ) . '"><span class="description">' . $field['description'] . '</span></label>' . "\n";
		echo $html;
	}

	public function settings_page() {
		$screen = get_current_screen();
		if ( $screen->id != 'settings_page_fws-emailoctopus-settings' ) {
			return;
		}
		$html = '<div class="wrap" id="plugin_settings">' . "\n";
			$html .= '<h2>WP EmailOctopus Integration</h2>' . "\n";
			$html .= '<p>'.sprintf ( __( 'To use this plugin you need a working EmailOctopus account. Subcribe for a new account here: <a href="%s" target="_blank">EmailOctopus, create email marketing your way</a>.', 'fw_emailoctopus_integration' ), esc_url( 'https://emailoctopus.com/' ) ).'</p>' . "\n";
			$html .= '<form method="post" action="options.php">' . "\n";
				$html .= '<ul id="settings-sections" class="subsubsub hide-if-no-js">' . "\n";
					$html .= '<li><a class="tab all current" href="#all">' . __( 'All' , 'fw_emailoctopus_integration' ) . '</a></li>' . "\n";
					foreach( $this->settings as $section => $data ) {
						$html .= '<li>| <a class="tab" href="#' . $section . '">' . $data['title'] . '</a></li>' . "\n";
					}
				$html .= '</ul>' . "\n";
				$html .= '<div class="clear"></div>' . "\n";
				ob_start();
				settings_fields( 'fw_emailoctopus_plugin_settings' );
				do_settings_sections( 'fw_emailoctopus_plugin_settings' );
				$html .= ob_get_clean();
				$html .= '<p class="submit">' . "\n";
					$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'fw_emailoctopus_integration' ) ) . '" />' . "\n";
				$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";
			$option_name = $this->settings_base . 'api_key';
			if (get_option($option_name)) $html .= '
			<h3>'.__( 'How to use the shortcode?', 'fw_emailoctopus_integration' ).'</h3>
			<p>'.__( 'Add a shortcode to your pages and posts, here are some examples.', 'fw_emailoctopus_integration' ).'</p>
			<p><code>[FWEmailOctopusSubForm]</code></p>
			<p><code>[FWEmailOctopusSubForm source="blogpost" title="Subscribe today" description="Subscribe now and get future updates in your mailbox."]</code></p>
			<p><code>[FWEmailOctopusSubForm source="blogpost" extra_fields="LastName" newsletter="y"]</code></p>
			<p>&nbsp;</p>';
		$html .= '</div>' . "\n";
		echo $html;
	}
}
