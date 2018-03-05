<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo esc_attr( $form_id ); ?>">

	<div class="um-form">
	
		<form method="post" action="">
		
			<?php
			
			if ( ! isset( UM()->password()->reset_request ) ) {

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

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_after_form_fields
				 * @description Password reset after display form
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Password reset shortcode arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_after_form_fields', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_after_form_fields', 'my_after_form_fields', 10, 1 );
				 * function my_after_form_fields( $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_after_form_fields", $args );
			
			} else { ?>
			
				<div class="um-field-block">
					<p><?php _e( 'We have sent you a password reset link to your e-mail. Please check your inbox.', 'ultimate-member' ) ?></p>
				</div>

			<?php } ?>

		</form>
	</div>
</div>