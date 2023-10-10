<?php
/**
 * Template for the password change
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/password-change.php
 *
 * Call: function ultimatemember_password()
 *
 * @version 2.7.0
 *
 * @var string $mode
 * @var string $rp_key
 * @var int    $form_id
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( $form_id ); ?>">

	<div class="um-form">

		<form method="post" action="">
			<input type="hidden" name="_um_password_change" id="_um_password_change" value="1" />
			<input type="hidden" name="login" value="<?php echo esc_attr( $args['login'] ); ?>" />
			<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_change_password_page_hidden_fields
			 * @description Password change hidden fields
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Password change shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_change_password_page_hidden_fields', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_change_password_page_hidden_fields', 'my_change_password_page_hidden_fields', 10, 1 );
			 * function my_change_password_page_hidden_fields( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_change_password_page_hidden_fields', $args );

			$fields = UM()->builtin()->get_specific_fields( 'user_password' );

			UM()->fields()->set_mode = 'password';

			$output = null;
			foreach ( $fields as $key => $data ) {
				$output .= UM()->fields()->edit_field( $key, $data );
			}
			echo $output; ?>

			<div class="um-col-alt um-col-alt-b">

				<div class="um-center">
					<input type="submit" value="<?php esc_attr_e( 'Change password', 'ultimate-member' ); ?>" class="um-button" id="um-submit-btn" />
				</div>

				<div class="um-clear"></div>

			</div>

			<?php

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_change_password_form
			 * @description Password change form content
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Password change shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_change_password_form', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_change_password_form', 'my_change_password_form', 10, 1 );
			 * function my_change_password_form( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_change_password_form', $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( 'um_after_form_fields', $args );
			?>
		</form>
	</div>
</div>
