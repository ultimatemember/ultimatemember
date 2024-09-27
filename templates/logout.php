<?php
/**
 * Template for the logout
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/templates/logout.php
 *
 * Page: "Logout"
 *
 * @version 2.6.1
 *
 * @var string $mode
 * @var int    $form_id
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="um <?php echo esc_attr( $this->get_class( $mode, $args ) ); ?> um-<?php echo esc_attr( $form_id ); ?>">

	<div class="um-form">

		<div class="um-misc-with-img">

			<div class="um-misc-img">
				<a href="<?php echo esc_url( um_get_core_page( 'user' ) ); ?>">
					<?php echo get_avatar( um_user( 'ID' ), 80 ) ?>
				</a>
			</div>

			<div><strong><?php echo esc_html( um_user( 'display_name' ) ); ?></strong></div>

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_logout_after_user_welcome
			 * @description Some actions on logout form
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Logout form shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_logout_after_user_welcome', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_logout_after_user_welcome', 'my_logout_after_user_welcome', 10, 1 );
			 * function my_logout_after_user_welcome( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_logout_after_user_welcome', $args ); ?>

		</div>

		<ul class="um-misc-ul">

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_logout_user_links
			 * @description Logout user links
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Logout form shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_logout_user_links', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_logout_user_links', 'my_logout_user_links', 10, 1 );
			 * function my_logout_user_links( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_logout_user_links', $args ); ?>

		</ul>
	</div>
</div>
