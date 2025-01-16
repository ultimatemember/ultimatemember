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
	if ( UM()->is_new_ui() ) {
		if ( ! array_key_exists( 'form_id', $args ) ) {
			return;
		}
		if ( array_key_exists( 'mode', $args ) && 'profile' === $args['mode'] && ! um_is_on_edit_profile() ) {
			// If profile form then display only when edit mode.
			return;
		}
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
	if ( is_admin() ) {
		return;
	}

	if ( ! array_key_exists( 'form_id', $args ) ) {
		return;
	}
	if ( UM()->is_new_ui() ) {
		if ( array_key_exists( 'mode', $args ) && 'profile' === $args['mode'] && ! um_is_on_edit_profile() ) {
			// If profile form then display only when edit mode.
			return;
		}
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

if ( ! UM()->is_new_ui() ) {
	/**
	 * Makes the honeypot invisible
	 */
	function um_add_form_honeypot_css() {
		?>
		<style>
			.<?php echo esc_attr( UM()->honeypot ); ?>_name {
				display: none !important;
			}
		</style>
		<?php
	}
	add_action( 'wp_head', 'um_add_form_honeypot_css' );

	/**
	 * Empty the honeypot value
	 */
	function um_add_form_honeypot_js() {
		?>
		<script type="text/javascript">
			jQuery( window ).on( 'load', function() {
				jQuery('input[name="<?php echo esc_js( UM()->honeypot ); ?>"]').val('');
			});
		</script>
		<?php
	}
	add_action( 'wp_footer', 'um_add_form_honeypot_js', 99999999999999999 );
}
