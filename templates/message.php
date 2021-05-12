<?php
/**
 * Template for the message shown after the registration
 *
 * Used:  Register page (after successful registration if there is no redirect)
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/message.php
 */
if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="um <?php echo esc_attr( $um_classes ); ?> um-<?php echo esc_attr( $form_id ); ?>">

	<div class="um-postmessage">
		<?php
		// translators: %s: The message after registration process based on a role data and user status after registration
		printf( __( '%s', 'ultimate-member' ), $custom_message ); ?>
	</div>

</div>