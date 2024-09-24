<?php
/**
 * Template for the GDPR checkbox in register form
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/templates/gdpr-register.php
 *
 * Page: "Register"
 * Call: function display_option()
 *
 * @version 2.6.1
 *
 * @var object $um_content_query
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) exit; ?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="um-field um-field-type_terms_conditions" data-key="use_terms_conditions_agreement" style="display:block;padding:0;">
	<div class="um-field-area">
		<div class="um-gdpr-content" style="display:none;">
			<?php if ( ! empty( $args['use_gdpr_content_id'] ) ) {
				$um_content_query = get_post( $args['use_gdpr_content_id'] );
				if ( ! empty( $um_content_query ) && ! is_wp_error( $um_content_query ) ) {
					$content = apply_filters( 'um_gdpr_policies_page_content', $um_content_query->post_content, $args );
					echo apply_filters( 'the_content', $content, $um_content_query->ID );
				}
			} ?>
		</div>

		<?php $toggle_show = ! empty( $args['use_gdpr_toggle_show'] ) ? $args['use_gdpr_toggle_show'] : __( 'Show privacy policy', 'ultimate-member' );
		$toggle_hide = ! empty( $args['use_gdpr_toggle_hide'] ) ? $args['use_gdpr_toggle_hide'] : __( 'Hide privacy policy', 'ultimate-member' );
		$toggle_title = ! empty( $args['use_gdpr_toggle_show'] ) ? $args['use_gdpr_toggle_show'] : __( 'Show privacy policy', 'ultimate-member' ); ?>

		<a href="javascript:void(0);" class="um-toggle-gdpr" data-toggle-state="hidden"
		   data-toggle-show="<?php echo esc_attr( $toggle_show ); ?>"
		   data-toggle-hide="<?php echo esc_attr( $toggle_hide ); ?>">
			<?php echo esc_html( $toggle_title ); ?>
		</a>
	</div>
	<div class="um-field-area">

		<?php $confirm = ! empty( $args['use_gdpr_agreement'] ) ? $args['use_gdpr_agreement'] :  __( 'Please confirm that you agree to our privacy policy', 'ultimate-member' ); ?>

		<label class="um-field-checkbox">
			<input type="checkbox" name="use_gdpr_agreement" value="1">
			<span class="um-field-checkbox-state"><i class="um-icon-android-checkbox-outline-blank"></i></span>
			<span class="um-field-checkbox-option"><?php echo esc_html( $confirm ); ?></span>
		</label>
		<div class="um-clear"></div>

		<?php $errors = UM()->form()->errors;

		if ( isset( $errors['use_gdpr_agreement'] ) ) {

			$error_message = ! empty( $args['use_gdpr_error_text'] ) ? $args['use_gdpr_error_text'] :  __( 'Please confirm your acceptance of our privacy policy', 'ultimate-member' ); ?>

			<p class="um-notice err">
				<i class="um-icon-ios-close-empty" onclick="jQuery(this).parent().fadeOut();"></i><?php echo esc_html( $error_message ) ?>
			</p>
			<br/>
		<?php } ?>

		<div class="um-clear"></div>
	</div>
</div>
