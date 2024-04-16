<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap" id="plugin_settings">
	<h2><?php esc_html_e( 'Integration for EmailOctopus', 'fw-integration-for-emailoctopus' ); ?></h2>
	<p>
		<?php 
		/* translators: %s - https://emailoctopus.com/  */ 
		printf ( wp_kses_post( 'To use this plugin you need a working EmailOctopus account. Subcribe for a new account here: <a href="%s" target="_blank">EmailOctopus, create email marketing your way</a>.', 'fw-integration-for-emailoctopus' ), esc_url( 'https://emailoctopus.com/' ) ); 
		?>
	</p>
	<form method="post" action="options.php">
		<ul id="settings-sections" class="subsubsub hide-if-no-js">
			<li>
				<a class="tab all current" href="#all">
					<?php esc_html_e( 'All' , 'fw-integration-for-emailoctopus' );?>
				</a></li>
			<?php
			foreach ($settings as $section => $data ) {
				echo '
			<li>| <a class="tab" href="' .esc_attr('#' . $section ) . '">' . esc_attr( $data['title'] ) . '</a></li>';
			}
			?>
		</ul>
		<div class="clear"></div>
		<?php
		settings_fields( 'fweo_emailoctopus_plugin_settings' );
		do_settings_sections( 'fweo_emailoctopus_plugin_settings' );
		?>
		<p class="submit">
			<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings' , 'fw-integration-for-emailoctopus' ); ?>" />
		</p>
	</form>
	<?php if ($is_api_key) { ?>
	<h3><?php esc_html_e( 'How to use the shortcode?', 'fw-integration-for-emailoctopus' ); ?></h3>
	<p><?php esc_html_e( 'Add a shortcode to your pages and posts, here are some examples.', 'fw-integration-for-emailoctopus' ); ?></p>
	<p><code>[FWEO_EmailOctopusSubForm]</code></p>
	<p><code>[FWEO_EmailOctopusSubForm source="blogpost" title="Subscribe today" description="Subscribe now and get future updates in your mailbox."]</code></p>
	<p><code>[FWEO_EmailOctopusSubForm source="blogpost" extra_fields="LastName" newsletter="y"]</code></p>
	<p>&nbsp;</p>
	<?php } ?>
</div>