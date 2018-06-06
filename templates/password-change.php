<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo esc_attr( $form_id ); ?>">

	<div class="um-form">
	
		<form method="post" action="">
		
			<?php if ( !isset( UM()->password()->reset_request ) ) {

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

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_after_form_fields
				 * @description Password change after form content
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Password change shortcode arguments"}]
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
			
			} ?>

		</form>
	</div>
</div>