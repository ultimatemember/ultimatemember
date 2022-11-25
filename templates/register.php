<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="um um-register-wrapper um-<?php echo esc_attr( $form_id ); ?>">
	<?php $register_form->display(); ?>

	<p class="register-sign-in">
		<span><?php esc_html_e( 'Already have account?', 'ultimate-member' ); ?></span>
		<a class="um-link um-link-always-active" href="<?php echo esc_url( um_get_predefined_page_url( 'login' ) ); ?>">
			<?php esc_html_e( 'Sign in', 'ultimate-member' ); ?>
		</a>
	</p>
</div>
<!---->
<!--<div class="um --><?php //echo esc_attr( $this->get_class( $mode ) ); ?><!-- um---><?php //echo esc_attr( $form_id ); ?><!--">-->
<!---->
<!--	<div class="um-form" data-mode="--><?php //echo esc_attr( $mode ) ?><!--">-->
<!---->
<!--		<form method="post" action="">-->
<!---->
<!--			--><?php
//
//			do_action( "um_before_form", $args );
//
//
//			do_action( "um_before_{$mode}_fields", $args );
//
//
//			do_action( "um_main_{$mode}_fields", $args );
//
//
//			do_action( 'um_after_form_fields', $args );
//
//
//			do_action( "um_after_{$mode}_fields", $args );
//
//
//			do_action( 'um_after_form', $args ); ?>
<!--		-->
<!--		</form>-->
<!---->
<!--	</div>-->
<!---->
<!--</div>-->
