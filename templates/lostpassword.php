<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="um um-lostpassword-wrapper">
	<?php
	if ( isset( $_GET['checkemail'] ) && 'confirm' === sanitize_key( $_GET['checkemail'] ) ) {
		?>
		<span class="um-frontend-form-notice">
			<?php echo wp_kses( sprintf( __( 'If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the <a href="%s">login page</a>.', 'ultimate-member' ), um_get_predefined_page_url( 'login' ) ), UM()->get_allowed_html( 'templates' ) ); ?>
		</span>
		<?php
	} elseif ( isset( $_GET['checklogin'] ) && 'password_changed' === sanitize_key( $_GET['checklogin'] ) ) {
		?>
		<span class="um-frontend-form-notice">
			<?php echo wp_kses( sprintf( __( 'Your password has been reset. <a href="%s">Log in</a>.', 'ultimate-member' ), um_get_predefined_page_url( 'login' ) ), UM()->get_allowed_html( 'templates' ) ); ?>
		</span>
		<?php
	} else {
		$lostpassword_form->display();
	}
	?>
</div>
