<?php

class Create_EmailOctopus_Forms extends EmailOctopus_integration {

	public function __construct() {
		parent::__construct();

		add_shortcode('FWEmailOctopusSubForm', array($this, 'create_emailoctopus_subform'));
	}

	public function create_emailoctopus_subform($atts = null) {

		$atts = shortcode_atts(
			array(
				'title' => __( 'Subscribe now!', 'fw_emailoctopus_integration' ),
				'description' => __( 'Subscribe today and get future blog posts your email.', 'fw_emailoctopus_integration' ),
				'gdpr_text' => get_option('fw_emailoctopus_gdpr_text'),
				'btnlabel' => __('Subscribe', 'fw_emailoctopus_integration'),
				'thank_you_text' => '',
				'btnclass' => '',
				'title_select' => __('Select Mailing List', 'fw_emailoctopus_integration'),
				'listid' => '',
				'bs_icon' => '',
				'fsize' => '',
				'newsletter' => 'n',
				'container_class' => 'emailoctopus-optin',
				'form_class' => 'form-inline',
				'source' => '',
				'extra_fields' => '',
				'hidden_fields' => '',
				'clicky' => get_option('fw_emailoctopus_clicky'),
				'cookie_name' => 'fw_ml_subscribed'
			),
			$atts
		);
		$field_size = '';
		$btn_size = '';
		if ($atts['fsize'] != '') {
			$field_size = ' input-'.$atts['fsize'];
			$btn_size = ' btn-'.$atts['fsize'];
		}
		if ($atts['bs_icon'] != '') {
			$btn_lbl = $atts['btnlabel'].' <span class="glyphicon glyphicon-'.$atts['bs_icon'].'" aria-hidden="true"></span>';
		} else {
			$btn_lbl = $atts['btnlabel'];
		}
		$list_radio_html = '';
		$extra_fields_html = '';
		$list_hidden_html = '
		<input type="hidden" name="listid" value="" />';
		if ($atts['listid'] != '') {
			$lists = explode(',', $atts['listid']);
			if (is_array($lists) && count($lists) > 1) {
				$all_lists = get_option('fw_mailing_lists');
				$default = get_option('fw_emailoctopus_list_id'); // dit klopt niet
				if ($atts['title_select'] != '') $list_radio_html .= '
				<h4>'.$atts['title_select'].'</h4>';
				$list_radio_html .= '
				<div class="mailing_lists">';
				foreach ($all_lists as $list) {
					if (in_array($list->id, $lists)) {
						$list_radio_html .= '
					<div class="radio">
					  <label>
					    <input type="radio" name="listid" value="'.$list->id.'"';
						if ($default == $list->id) $list_radio_html .= ' checked';
						$name_parts = explode('|', $list->name);
						if (count($name_parts) == 2) {
							$name = '<strong>'.trim($name_parts[0]).':</strong> '.trim($name_parts[1]);
						} else {
							$name = $list->name;
						}
						$list_radio_html .= '>
						   	'.$name.'
					  </label>
					</div>';
					}
				}
				$list_radio_html .= '
				</div>';
				$list_hidden_html = '';
			} else {
				$list_hidden_html = '
				<input type="hidden" name="listid" value="'.$lists[0].'" />';
			}
		}
		$last_name = false;
		if ($atts['extra_fields'] != '') {
			$fields = explode('|', $atts['extra_fields']);
			$extra_fields = explode(PHP_EOL, get_option('fw_emailoctopus_extra_fields'));
			foreach ($extra_fields as $extra) {
				$parts = explode('|', $extra);
				if ($parts[0] == 'LastName') {
					$last_name = true;
					continue;
				}
				if (in_array($parts[0], $fields)) {
					$extra_fields_html .= '
				<div class="form-group">
					<label class="sr-only" for="'.$parts[0].'-'.$unique_id.'">'.$parts[1].'</label>
					<input type="text" class="form-control'.$field_size.'" placeholder="'.$parts[1].'" id="'.$parts[0].'-'.$unique_id.'" name="'.$parts[0].'" autocomplete="off">
				</div>';
				}
			}
		}
		$unique_id = wp_unique_id();
		if ($atts['gdpr_text'] == '') {
			$gdpr_info = '';
		} else {
			$gdpr_info = sprintf( wp_kses( $atts['gdpr_text'], array(  'a' => array( 'href' => array() ), 'br' ) ), esc_url( get_privacy_policy_url() ) );
		}
		$html = '
		<div class="'.$atts['container_class'].'">';
		if ($atts['title'] != '') $html .= '
			<h3>'.$atts['title'].'</h3>';
		if ($atts['description']) $html .= '
			<p>'.$atts['description'].'</p>';
		$html .= '
			<form id="fw-subscribeform-'.$unique_id.'" role="form" class="'.$atts['form_class'].'">
				<div class="form-group">
					<label class="sr-only" for="FirstName-'.$unique_id.'">'.__( 'Your first name', 'fw_emailoctopus_integration' ).'</label>
					<input type="text" class="form-control'.$field_size.'" placeholder="'.__( 'Your first name', 'fw_emailoctopus_integration' ).'" id="FirstName-'.$unique_id.'" name="FirstName" autocomplete="off">
				</div>';
		if ($last_name) {
			$html .= '
				<div class="form-group">
					<label class="sr-only" for="LastName-'.$unique_id.'">'.__( 'Your last name', 'fw_emailoctopus_integration' ).'</label>
					<input type="text" class="form-control'.$field_size.'" placeholder="'.__( 'Your last name', 'fw_emailoctopus_integration' ).'" id="LastName-'.$unique_id.'" name="LastName" autocomplete="off">
				</div>';
		}
		$html .= '
				<div class="form-group">
					<label class="sr-only" for="email-'.$unique_id.'">'.__( 'Your email address', 'fw_emailoctopus_integration' ).'</label>
					<input type="text" class="form-control'.$field_size.'" placeholder="'.__( 'Your email address', 'fw_emailoctopus_integration' ).'" id="email-'.$unique_id.'" name="email" autocomplete="off">
				</div>';
		$html .= $extra_fields_html;
		if ($atts['newsletter'] == 'y') {
			if (function_exists('pll_register_string')) {
				$newsletter_text = pll__( 'Yes, please add me to your mailing list.' );
			} else {
				$newsletter_text = get_option('fw_emailoctopus_text_newsletter');
			}
			$html .= '
				<div class="checkbox">
					<label>
						<input type="checkbox" value="1" name="newsletter">
						'.$newsletter_text.'
					</label>
				</div>';
		}
		$html .= $list_radio_html;
		$html .= $list_hidden_html;
		$html .= wp_nonce_field('fwsml_subform', '_fwsml_subnonce', true, false);
		$html .= '
				<input type="hidden" name="action" value="emailoctopus_subscribeform_action" />
				<input type="hidden" name="thank_you" value="'.$atts['thank_you_text'].'" />
				<input type="hidden" name="source" value="'.$atts['source'].'" />
				<input type="hidden" name="cookie_name" value="'.$atts['cookie_name'].'" />
				<input type="hidden" name="clicky" value="'.$atts['clicky'].'" />';
		if ($atts['hidden_fields'] != '') {
			$hidden = explode(',', $atts['hidden_fields']);
			foreach ($hidden as $field) {
				$hiddenparts = explode('|', trim($field));
				$html .= '
				<input type="hidden" name="hidden['.$hiddenparts[0].']" value="'.$hiddenparts[1].'" />';
			}
		}
		$html .= '
				<button class="btn btn-primary emailoctopus-subscr-fw'.$btn_size.' '.$atts['btnclass'].'" type="button">'.$btn_lbl.'</button>
			</form>
			<p class="privacy">'.$gdpr_info.'</p>
			<div class="error-message"></div>
		</div>
		';
		return $html;
	}

}
