<?php
/**
 * Template for the login form
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/login.php
 *
 * Page: "Login"
 *
 * @version 2.7.0
 *
 * @var string $mode
 * @var int    $form_id
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( $form_id ); ?>">
	<div class="um-form">
		<form method="post" action="" autocomplete="off">
			<?php
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( 'um_before_form', $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( "um_before_{$mode}_fields", $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( "um_main_{$mode}_fields", $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( 'um_after_form_fields', $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( "um_after_{$mode}_fields", $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( 'um_after_form', $args );
			?>
		</form>
	</div>
</div>
