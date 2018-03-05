<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo esc_attr( $form_id ); ?>">

	<div class="um-form">

		<?php
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_members_directory_search
		 * @description Member directory search
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Member directory shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_members_directory_search', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_members_directory_search', 'my_members_directory_search', 10, 1 );
		 * function my_members_directory_search( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_members_directory_search', $args );

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_members_directory_head
		 * @description Member directory header
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Member directory shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_members_directory_head', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_members_directory_head', 'my_members_directory_head', 10, 1 );
		 * function my_members_directory_head( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_members_directory_head', $args );

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_members_directory_display
		 * @description Member directory display content
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Member directory shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_members_directory_display', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_members_directory_display', 'my_members_directory_display', 10, 1 );
		 * function my_members_directory_display( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_members_directory_display', $args );

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_members_directory_footer
		 * @description Member directory display footer
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Member directory shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_members_directory_footer', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_members_directory_footer', 'my_members_directory_footer', 10, 1 );
		 * function my_members_directory_footer( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_members_directory_footer', $args ); ?>
	</div>
</div>