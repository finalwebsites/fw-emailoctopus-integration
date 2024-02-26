function isValidEmailAddress(emailAddress) {
	var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
	return pattern.test(emailAddress);
}


jQuery(document).ready(function($) {

	$('.emailoctopus-optin form').each(function(i, obj) {
		var curr_id = $(this).attr('id');
	    $('#' + curr_id + ' input[name="action"]').after('<input type="hidden" name="aftersubmit" value="1">');
	});

	$('.emailoctopus-subscr-fw').click(function() {
		var subform = $(this).closest('form');
		var msgContainer = subform.nextAll('.error-message');
		var formID = subform.attr('id');

		msgContainer.html('<img src="' + eo_ajax_object.plugin_base_path + 'include/loading.gif" alt="' + eo_ajax_object.js_alt_loading + '">');
		$.ajax({
			type: 'POST',
			url: eo_ajax_object.ajax_url,
			data: subform.serialize(),
			dataType: 'json',
			beforeSend: function() {
				var name = $('#' + formID + ' :input[name="FirstName"]').val();
				var email = $('#' + formID + ' :input[name="email"]').val();
				
				if (!name || !email) {
					msgContainer.html(eo_ajax_object.js_msg_enter_email_name);
					return false;
				}
				if (!isValidEmailAddress(email)) {
					msgContainer.html(eo_ajax_object.js_msg_invalid_email);
					return false;
				}
			},
			success: function(response) {
				//alert(response);
				if (response.status == 'success') {
					subform[0].reset();
					$('.mailing_lists input[type="radio"]').prop('checked', false);
					if (eo_ajax_object.googleanalytics) {
						if (typeof gtag != 'undefined') {
							gtag('event', 'generate_lead', {
								'event_category': 'Opt-ins',
								'event_label': eo_ajax_object.googleanalytics
							});
						}
					}

					if (typeof clicky !== 'undefined'){
						if (response.clickyanalytics) {
							clicky.goal( response.clickyanalytics );
						}
					}
				}
				msgContainer.html(response.errmessage);
			}
		});
	});
});
