<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a form identifier to form
 *
 * @param $args
 */
function um_add_form_identifier( $args ) {
	// Ignore wp-admin preview.
	if ( is_admin() ) {
		return;
	}

	// Ignore UM:Profile in view mode.
	if ( 'profile' === UM()->fields()->set_mode && true !== UM()->fields()->editing ) {
		return;
	}
	?>
	<input type="hidden" name="form_id" id="form_id_<?php echo esc_attr( $args['form_id'] ); ?>" value="<?php echo esc_attr( $args['form_id'] ); ?>" />
	<?php
}
add_action( 'um_after_form_fields', 'um_add_form_identifier' );

/**
 * Adds a spam timestamp
 *
 * @param $args
 */
function um_add_security_checks( $args ) {
	// Ignore wp-admin preview.
	if ( is_admin() ) {
		return;
	}

	// Ignore UM:Profile in view mode.
	if ( 'profile' === UM()->fields()->set_mode && true !== UM()->fields()->editing ) {
		return;
	}
	?>
	<p class="<?php echo esc_attr( UM()->honeypot ); ?>_name">
		<label for="<?php echo esc_attr( UM()->honeypot . '_' . $args['form_id'] ); ?>"><?php esc_html_e( 'Only fill in if you are not human' ); ?></label>
		<input type="hidden" name="<?php echo esc_attr( UM()->honeypot ); ?>" id="<?php echo esc_attr( UM()->honeypot . '_' . $args['form_id'] ); ?>" class="input" value="" size="25" autocomplete="off" />
	</p>
	<?php
}
add_action( 'um_after_form_fields', 'um_add_security_checks' );
add_action( 'um_account_page_hidden_fields', 'um_add_security_checks' );
