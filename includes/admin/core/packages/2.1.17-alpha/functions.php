<?php if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * @param $tab
 *
 * @return mixed
 */
function um_upgrade_get_slug2117( $tab ) {
	$slug = get_post_meta( $tab->ID, 'um_tab_slug', true );
	if ( UM()->external_integrations()->is_wpml_active() ) {
		global $sitepress;

		$tab_id = $sitepress->get_object_id( $tab->ID, 'um_profile_tabs', true, $sitepress->get_default_language() );
		if ( $tab_id && $tab_id != $tab->ID ) {
			$slug = get_post_meta( $tab_id, 'um_tab_slug', true );
		}
	}

	return $slug;
}


function um_upgrade_profile_tabs2117() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	$labels = [
		'name'              => _x( 'Profile Tabs', 'Post Type General Name', 'ultimate-member' ),
		'singular_name'     => _x( 'Profile tab', 'Post Type Singular Name', 'ultimate-member' ),
		'menu_name'         => __( 'Profile Tabs', 'ultimate-member' ),
		'name_admin_bar'    => __( 'Profile Tabs', 'ultimate-member' ),
		'archives'          => __( 'Item Archives', 'ultimate-member' ),
		'attributes'        => __( 'Item Attributes', 'ultimate-member' ),
		'parent_item_colon' => __( 'Parent Item:', 'ultimate-member' ),
		'all_items'         => __( 'All Items', 'ultimate-member' ),
		'add_new_item'      => __( 'Add New Item', 'ultimate-member' ),
		'add_new'           => __( 'Add New', 'ultimate-member' ),
		'new_item'          => __( 'New Item', 'ultimate-member' ),
		'edit_item'         => __( 'Edit Item', 'ultimate-member' ),
		'update_item'       => __( 'Update Item', 'ultimate-member' ),
		'view_item'         => __( 'View Item', 'ultimate-member' ),
		'view_items'        => __( 'View Items', 'ultimate-member' ),
		'search_items'      => __( 'Search Item', 'ultimate-member' ),
		'not_found'         => __( 'Not found', 'ultimate-member' ),
	];

	$args = [
		'label'                 => __( 'Profile Tabs', 'ultimate-member' ),
		'description'           => __( '', 'ultimate-member' ),
		'labels'                => $labels,
		'supports'              => ['title', 'editor' ],
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => false,
		'menu_position'         => 5,
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	];

	register_post_type( 'um_profile_tabs', $args );

	$profile_tabs = get_posts( [
		'post_type'         => 'um_profile_tabs',
		'orderby'           => 'menu_order',
		'posts_per_page'    => -1,
	] );

	if ( ! empty( $profile_tabs ) ) {
		$tabs_slugs = [];

		foreach ( $profile_tabs as $tab ) {
			$slug = um_upgrade_get_slug2117( $tab );
			if ( ! empty( $slug ) && in_array( $slug, $tabs_slugs ) ) {
				continue;
			}

			if ( preg_match( "/[a-z0-9]+$/i", urldecode( $tab->post_name ) ) ) {
				$tab_slug = sanitize_title( $tab->post_name );
			} else {
				// otherwise use autoincrement and slug generator
				$auto_increment = UM()->options()->get( 'custom_profiletab_increment' );
				$auto_increment = ! empty( $auto_increment ) ? $auto_increment : 1;
				$tab_slug = "custom_profiletab_{$auto_increment}";
			}

			if ( UM()->external_integrations()->is_wpml_active() ) {
				global $sitepress;

				$tab_id = $sitepress->get_object_id( $tab->ID, 'um_profile_tabs', true, $sitepress->get_default_language() );
				if ( $tab_id && $tab_id == $tab->ID ) {
					update_post_meta( $tab->ID, 'um_tab_slug', $tab_slug );

					$tabs_slugs[] = $tab_slug;

					if ( isset( $auto_increment ) ) {
						$auto_increment++;
						UM()->options()->update( 'custom_profiletab_increment', $auto_increment );
					}

					// show new profile tab by default - update UM Appearances > Profile Tabs settings
					if ( UM()->options()->get( 'profile_tab_' . $tab_slug ) === '' ) {
						UM()->options()->update( 'profile_tab_' . $tab_slug, '1' );
						UM()->options()->update( 'profile_tab_' . $tab_slug . '_privacy', '0' );
					}
				}
			} else {
				update_post_meta( $tab->ID, 'um_tab_slug', $tab_slug );

				$tabs_slugs[] = $tab_slug;

				if ( isset( $auto_increment ) ) {
					$auto_increment++;
					UM()->options()->update( 'custom_profiletab_increment', $auto_increment );
				}

				// show new profile tab by default - update UM Appearances > Profile Tabs settings
				if ( UM()->options()->get( 'profile_tab_' . $tab_slug ) === '' ) {
					UM()->options()->update( 'profile_tab_' . $tab_slug, '1' );
					UM()->options()->update( 'profile_tab_' . $tab_slug . '_privacy', '0' );
				}
			}
		}
	}

	update_option( 'um_last_version_upgrade', '2.1.17-alpha' );

	if ( ! empty( $profile_tabs ) ) {
		wp_send_json_success( array( 'message' => __( 'Profile tabs have been updated successfully', 'ultimate-member' ) ) );
	} else {
		wp_send_json_success( array( 'message' => __( 'Database has been updated successfully', 'ultimate-member' ) ) );
	}
}