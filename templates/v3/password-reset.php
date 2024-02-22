<?php
/**
 * Template for the password reset
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/password-reset.php
 *
 * Call: function ultimatemember_password()
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

// phpcs:ignore WordPress.Security.NonceVerification
$updated_attr = isset( $_GET['updated'] ) ? sanitize_key( $_GET['updated'] ) : '';
?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( $form_id ); ?>">
	<form method="post" action="" class="um-form-new">
		<?php if ( 'checkemail' === $updated_attr ) { ?>
			<div class="um-form-supporting-text">
				<?php esc_html_e( 'If an account matching the provided details exists, we will send a password reset link. Please check your inbox.', 'ultimate-member' ); ?>
			</div>
		<?php } elseif ( 'password_changed' === $updated_attr ) { ?>
			<div class="um-form-supporting-text">
				<?php esc_html_e( 'You have successfully changed password.', 'ultimate-member' ); ?>
			</div>
		<?php } else { ?>

			<input type="hidden" name="_um_password_reset" id="_um_password_reset" value="1" />

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_reset_password_page_hidden_fields
			 * @description Password reset hidden fields
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Password reset shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_reset_password_page_hidden_fields', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_reset_password_page_hidden_fields', 'my_reset_password_page_hidden_fields', 10, 1 );
			 * function my_reset_password_page_hidden_fields( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_reset_password_page_hidden_fields', $args );

			if ( ! empty( $updated_attr ) ) {
				?>
				<div class="um-form-supporting-text">
					<?php
					if ( 'expiredkey' === $updated_attr ) {
						esc_html_e( 'Your password reset link has expired. Please request a new link below.', 'ultimate-member' );
					} elseif ( 'invalidkey' === $updated_attr ) {
						esc_html_e( 'Your password reset link appears to be invalid. Please request a new link below.', 'ultimate-member' );
					}
					?>
				</div>
				<?php
			} else {
				?>
				<div class="um-form-supporting-text">
					<?php esc_html_e( 'To reset your password, please enter your email address or username below.', 'ultimate-member' ); ?>
				</div>
				<?php
			}
			?>

			<div class="um-form-rows">
				<div class="um-form-row">
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<?php
							$fields = UM()->builtin()->get_specific_fields( 'username_b' );

							$output = null;
							foreach ( $fields as $key => $data ) {
								$output .= UM()->fields()->edit_field( $key, $data );
							}
							echo $output;
							?>
						</div>
					</div>
				</div>
				<?php
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_after_password_reset_fields
				 * @description Hook that runs after user reset their password
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Form data"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_after_password_reset_fields', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_after_password_reset_fields', 'my_after_password_reset_fields', 10, 1 );
				 * function my_after_password_reset_fields( $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_after_password_reset_fields', $args );
				?>
			</div>

			<div class="um-form-submit">
				<?php
				$login_page_url = um_get_predefined_page_url( 'login' );

				echo UM()->frontend()::layouts()::button(
					__( 'Reset password', 'ultimate-member' ),
					array(
						'type'   => 'submit',
						'design' => 'primary',
						'width'  => 'full',
						'id'     => 'um-submit-btn',
					)
				);
				echo UM()->frontend()::layouts()::button(
					__( 'Back to login', 'ultimate-member' ),
					array(
						'type'   => 'link',
						'url'    => $login_page_url,
						'design' => 'link-gray',
						'width'  => 'full',
					)
				);
				?>
			</div>

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_reset_password_form
			 * @description Password reset display form
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Password reset shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_reset_password_form', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_reset_password_form', 'my_reset_password_form', 10, 1 );
			 * function my_reset_password_form( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_reset_password_form', $args );
			/** This action is documented in includes/core/um-actions-profile.php */
			do_action( 'um_after_form_fields', $args );
		}
		?>
	</form>
</div>
