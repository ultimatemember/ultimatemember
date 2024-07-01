<?php
/**
 * Template for the profile page
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/profile.php
 *
 * Page: "Profile"
 *
 * @version 2.6.9
 *
 * @var array  $roles
 * @var int    $form_id
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wrapper_classes = array(
	'um',
	'um-profile',
	"um-{$form_id}",
);

$roles_classes = $roles;
foreach ( $roles_classes as &$value ) {
	$value = 'um-user-role-' . $value;
}
unset( $value );

$wrapper_classes = array_merge( $wrapper_classes, $roles_classes );
$wrapper_classes = implode( ' ', $wrapper_classes );

$avatar           = '';
$header_row_class = 'um-profile-header';
if ( get_option( 'show_avatars' ) ) {
	$avatar            = get_avatar( um_user( 'ID' ), 50, 'mystery' );
	$header_row_class .= ' has-avatar';
}
?>

<div class="<?php echo esc_attr( $wrapper_classes ); ?>">
	<div class="um-cover-photo-wrapper"></div>
	<div class="<?php echo esc_attr( $header_row_class ); ?>">
		<?php if ( ! empty( $avatar ) ) { ?>
			<div class="um-profile-photo">
				<?php echo $avatar; ?>
			</div>
		<?php } ?>
		<div class="um-profile-header-content">
			<div class="um-profile-header-content">
				<div class="um-profile-title"><?php echo um_user( 'display_name' ); ?></div>
				<div class="um-profile-description"><?php echo um_user( 'description' ); ?></div>
			</div>
			<div class="um-profile-header-content">
				<a href="#" class="um-button">Action</a>
				<a href="#" class="um-button">Edit</a>
			</div>
		</div>
	</div>

	<?php
	$menu_enabled = UM()->options()->get( 'profile_menu' );
	if ( $menu_enabled ) {
		$tabs = UM()->profile()->tabs_active();

		if ( ! empty( $tabs ) ) {
			$nav    = UM()->profile()->active_tab();
			$subnav = UM()->profile()->active_subnav();
			$subnav = ! empty( $subnav ) ? $subnav : 'default';
			?>
			<div class="um-profile-nav-menu">
				<ul class="um-profile-main-menu">
					<?php
					foreach ( $tabs as $tab_data ) {
						if ( empty( $tab_data['subnav'] ) ) {
							?>
							<li class="um-profile-menu-item">
								<a href="javascript:void(0);"><?php echo esc_html( $tab_data['name'] ); ?></a>
							</li>
							<?php
						} else {
							?>
							<li class="um-profile-menu-item">
								<a href="javascript:void(0);"><?php echo esc_html( $tab_data['name'] ); ?></a>
								<button><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" focusable="false" style="stroke: currentColor"><path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path></svg></button>
								<ul class="um-profile-sub-menu">
									<?php foreach ( $tab_data['subnav'] as $submenu_key => $submenu_title ) { ?>
										<li class="um-profile-menu-item">
											<a href="javascript:void(0);"><?php echo esc_html( $submenu_title ); ?></a>
										</li>
									<?php } ?>
								</ul>
							</li>
							<?php
						}
					}
					?>
				</ul>
				<div class="um-profile-nav-menu-item"></div>
			</div>
			<?php
		}
	}
	?>

	<div class="um-form" data-mode="profile">

		<?php
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_profile_before_header
		 * @description Some actions before profile form header
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_profile_before_header', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_profile_before_header', 'my_profile_before_header', 10, 1 );
		 * function my_profile_before_header( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_profile_before_header', $args );

		if ( um_is_on_edit_profile() ) { ?>
			<form method="post" action="" data-description_key="<?php echo esc_attr( $description_key ); ?>">
		<?php }

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_profile_header_cover_area
		 * @description Profile header cover area
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_profile_header_cover_area', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_profile_header_cover_area', 'my_profile_header_cover_area', 10, 1 );
		 * function my_profile_header_cover_area( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_profile_header_cover_area', $args );

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_profile_header
		 * @description Profile header area
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_profile_header', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_profile_header', 'my_profile_header', 10, 1 );
		 * function my_profile_header( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_profile_header', $args );

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_profile_navbar_classes
		 * @description Additional classes for profile navbar
		 * @input_vars
		 * [{"var":"$classes","type":"string","desc":"UM Posts Tab query"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_profile_navbar_classes', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_profile_navbar_classes', 'my_profile_navbar_classes', 10, 1 );
		 * function my_profile_navbar_classes( $classes ) {
		 *     // your code here
		 *     return $classes;
		 * }
		 * ?>
		 */
		$classes = apply_filters( 'um_profile_navbar_classes', '' ); ?>

		<div class="um-profile-navbar <?php echo esc_attr( $classes ); ?>">
			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_profile_navbar
			 * @description Profile navigation bar
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_profile_navbar', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_profile_navbar', 'my_profile_navbar', 10, 1 );
			 * function my_profile_navbar( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_profile_navbar', $args ); ?>
			<div class="um-clear"></div>
		</div>

		<?php
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_profile_menu
		 * @description Profile menu
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_profile_menu', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_profile_menu', 'my_profile_navbar', 10, 1 );
		 * function my_profile_navbar( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_profile_menu', $args );

		if ( um_is_on_edit_profile() || UM()->user()->preview ) {

			$nav = 'main';
			$subnav = UM()->profile()->active_subnav();
			$subnav = ! empty( $subnav ) ? $subnav : 'default'; ?>

			<div class="um-profile-body <?php echo esc_attr( $nav . ' ' . $nav . '-' . $subnav ); ?>">

				<?php
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_profile_content_{$nav}
				 * @description Custom hook to display tabbed content
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_profile_content_{$nav}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_profile_content_{$nav}', 'my_profile_content', 10, 1 );
				 * function my_profile_content( $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action("um_profile_content_{$nav}", $args);

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_profile_content_{$nav}_{$subnav}
				 * @description Custom hook to display tabbed content
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_profile_content_{$nav}_{$subnav}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_profile_content_{$nav}_{$subnav}', 'my_profile_content', 10, 1 );
				 * function my_profile_content( $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_profile_content_{$nav}_{$subnav}", $args ); ?>

				<div class="clear"></div>
			</div>

			<?php if ( ! UM()->user()->preview ) { ?>

			</form>

			<?php }
		} else {
			$menu_enabled = UM()->options()->get( 'profile_menu' );
			$tabs = UM()->profile()->tabs_active();

			$nav = UM()->profile()->active_tab();
			$subnav = UM()->profile()->active_subnav();
			$subnav = ! empty( $subnav ) ? $subnav : 'default';

			if ( $menu_enabled || ! empty( $tabs[ $nav ]['hidden'] ) ) { ?>

				<div class="um-profile-body <?php echo esc_attr( $nav . ' ' . $nav . '-' . $subnav ); ?>">

					<?php
					// Custom hook to display tabbed content
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_profile_content_{$nav}
					 * @description Custom hook to display tabbed content
					 * @input_vars
					 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_profile_content_{$nav}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_profile_content_{$nav}', 'my_profile_content', 10, 1 );
					 * function my_profile_content( $args ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action("um_profile_content_{$nav}", $args);

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_profile_content_{$nav}_{$subnav}
					 * @description Custom hook to display tabbed content
					 * @input_vars
					 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_profile_content_{$nav}_{$subnav}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_profile_content_{$nav}_{$subnav}', 'my_profile_content', 10, 1 );
					 * function my_profile_content( $args ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( "um_profile_content_{$nav}_{$subnav}", $args ); ?>

					<div class="clear"></div>
				</div>

			<?php }
		}

		do_action( 'um_profile_footer', $args ); ?>
	</div>
</div>
