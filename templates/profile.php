<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo esc_attr( $form_id ); ?> um-role-<?php echo um_user( 'role' ); ?> ">

	<div class="um-form">
	
		<?php do_action('um_profile_before_header', $args );

		if ( um_is_on_edit_profile() ) { ?>
			<form method="post" action="">
		<?php }

		do_action('um_profile_header_cover_area', $args );
		do_action('um_profile_header', $args );

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

		<div class="um-profile-navbar <?php echo $classes ?>">
			<?php do_action( 'um_profile_navbar', $args ); ?>
			<div class="um-clear"></div>
		</div>

		<?php do_action( 'um_profile_menu', $args );

		$nav = UM()->profile()->active_tab;
		$subnav = ( get_query_var('subnav') ) ? get_query_var('subnav') : 'default';

		print "<div class='um-profile-body $nav $nav-$subnav'>";

			// Custom hook to display tabbed content
			do_action("um_profile_content_{$nav}", $args);
			do_action("um_profile_content_{$nav}_{$subnav}", $args);

		print "</div>";

		if ( um_is_on_edit_profile() ) { ?>
			</form>
		<?php } ?>
	</div>
</div>