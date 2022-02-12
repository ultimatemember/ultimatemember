<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( $form_id ); ?>">

	<div class="um-postmessage">
		<?php
		// translators: %s: The message after registration process based on a role data and user status after registration
		printf( __( '%s', 'ultimate-member' ), $this->custom_message ); ?>
	</div>

</div>