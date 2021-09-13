<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( $form_id ); ?>">

	<div class="um-postmessage">
		<?php
		// translators: The message after registration process based on a role data and user status after registration
		echo translate( $this->custom_message, 'ultimate-member' ); ?>
	</div>

</div>