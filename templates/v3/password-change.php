<?php
/**
 * Template for the password change
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/templates/password-change.php
 *
 * Call: function ultimatemember_password()
 *
 * @version 2.8.3
 *
 * @var string $rp_mode 'pw_set' or 'pw_change' for display it differently.
 * @var string $rp_key  Reset password key.
 * @var array  $args    Change password arguments
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="um <?php echo esc_attr( $this->get_class( 'password' ) ); ?> um-um_password_id">
	<form method="post" action="" class="um-form-new">
		<input type="hidden" name="_um_password_change" id="_um_password_change" value="1" />
		<input type="hidden" name="login" value="<?php echo esc_attr( $args['login'] ); ?>" />
		<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
		<?php
		/**
		 * Fires at the start of the password change form. Just after hiddens.
		 *
		 * @since 1.3.x
		 * @hook um_change_password_page_hidden_fields
		 *
		 * @param {array} $cp_args Change password form arguments.
		 *
		 * @example <caption>Add hidden field at the start of the change password form.</caption>
		 * function my_custom_change_password_page_hidden_fields( $cp_args ) {
		 *     echo '<input type="hidden" name="my_custom_cp_input" value="1" />'
		 * }
		 * add_action( 'um_change_password_page_hidden_fields', 'my_custom_change_password_page_hidden_fields' );
		 */
		do_action( 'um_change_password_page_hidden_fields', $args );
		?>
		<div class="um-form-rows">
			<div class="um-form-row">
				<div class="um-form-cols um-form-cols-1">
					<div class="um-form-col um-form-col-1">
						<?php
						$fields = UM()->builtin()->get_specific_fields( 'user_password' );

						UM()->fields()->set_mode = 'password';

						$output = null;
						foreach ( $fields as $key => $data ) {
							$output .= UM()->fields()->edit_field( $key, $data );
						}
						if ( $output ) {
							echo wp_kses( $output, UM()->get_allowed_html( 'templates' ) );
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<div class="um-form-submit">
			<?php
			$button_title = 'pw_set' === $rp_mode ? __( 'Set password', 'ultimate-member' ) : __( 'Change password', 'ultimate-member' );
			echo UM()->frontend()::layouts()::button(
				$button_title,
				array(
					'type'   => 'submit',
					'design' => 'primary',
					'width'  => 'full',
					'id'     => 'um-submit-btn',
				)
			);
			?>
		</div>

		<?php
		/**
		 * Fires at the end of the password change form.
		 *
		 * @since 1.3.x
		 * @hook um_change_password_form
		 *
		 * @param {array} $cp_args Change password form arguments.
		 *
		 * @example <caption>Add hidden field at the start of the change password form.</caption>
		 * function my_custom_change_password_form( $cp_args ) {
		 *     echo '<input type="hidden" name="my_custom_cp_input" value="1" />'
		 * }
		 * add_action( 'um_change_password_form', 'my_custom_change_password_form' );
		 */
		do_action( 'um_change_password_form', $args );
		/** This action is documented in includes/core/um-actions-profile.php */
		do_action( 'um_after_form_fields', $args );
		?>
	</form>
</div>
