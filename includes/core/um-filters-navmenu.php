<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if ( ! is_admin() ) {
	/**
	 * Add dynamic profile headers
	 *
	 * @param array $sorted_menu_items
	 * @param \stdClass $args
	 *
	 * @return array
	 */
	function um_add_custom_message_to_menu( $sorted_menu_items, $args ) {
		if ( empty( $sorted_menu_items ) ) {
			return $sorted_menu_items;
		}

		if ( is_user_logged_in() ) {
			um_fetch_user( get_current_user_id() );
		}

		foreach ( $sorted_menu_items as &$menu_item ) {
			if ( $menu_item->title ) {
				$menu_item->title = UM()->shortcodes()->convert_user_tags( $menu_item->title );
			}
			if ( $menu_item->attr_title ) {
				$menu_item->attr_title = UM()->shortcodes()->convert_user_tags( $menu_item->attr_title );
			}
			if ( $menu_item->description ) {
				$menu_item->description = UM()->shortcodes()->convert_user_tags( $menu_item->description );
			}
		}

		if ( is_user_logged_in() ) {
			um_reset_user();
		}

		return $sorted_menu_items;
	}
	add_filter( 'wp_nav_menu_objects', 'um_add_custom_message_to_menu', 9999, 2 );


	/**
	 * Conditional menu items
	 *
	 * @param $menu_items
	 * @param $args
	 *
	 * @return mixed
	 */
	function um_conditional_nav_menu( $menu_items, $args ) {
		//if empty
		if ( empty( $menu_items ) ) {
			return $menu_items;
		}

		um_fetch_user( get_current_user_id() );

		$filtered_items = array();
		$hide_children_of = array();

		//other filter
		foreach ( $menu_items as $item ) {

			$mode = get_post_meta( $item->ID, 'menu-item-um_nav_public', true );
			$roles = get_post_meta( $item->ID, 'menu-item-um_nav_roles', true );

			$visible = true;

			// hide any item that is the child of a hidden item
			if ( in_array( $item->menu_item_parent, $hide_children_of ) ) {
				$visible = false;
				$hide_children_of[] = $item->ID; // for nested menus
			}

			if ( isset( $mode ) && $visible ) {

				switch( $mode ) {

					case 2:
						if ( is_user_logged_in() && ! empty( $roles ) ) {
                            if ( current_user_can( 'administrator' ) ) {
                                $visible = true;
                            } else {
                                $current_user_roles = um_user( 'roles' );
                                if ( empty( $current_user_roles ) ) {
                                    $visible = false;
                                } else {
                                    $visible = ( count( array_intersect( $current_user_roles, (array)$roles ) ) > 0 ) ? true : false;
                                }
                            }
						} else {
							$visible = is_user_logged_in() ? true : false;
						}
						break;

					case 1:
						$visible = ! is_user_logged_in() ? true : false;
						break;

				}

			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_nav_menu_roles_item_visibility
			 * @description Add filter to work with plugins that don't use traditional roles
			 * @input_vars
			 * [{"var":"$visible","type":"bool","desc":"Visible?"},
			 * {"var":"$item","type":"object","desc":"Menu Item"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_nav_menu_roles_item_visibility', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_nav_menu_roles_item_visibility', 'my_nav_menu_roles_item_visibility', 10, 2 );
			 * function my_nav_menu_roles_item_visibility( $visible, $item ) {
			 *     // your code here
			 *     return $visible;
			 * }
			 * ?>
			 */
			$visible = apply_filters( 'um_nav_menu_roles_item_visibility', $visible, $item );

			// unset non-visible item
			if ( ! $visible ) {
				$hide_children_of[] = $item->ID; // store ID of item
			} else {
				$filtered_items[] = $item;
				continue;
			}

		}

		um_reset_user();

		return $filtered_items;
	}
	add_filter( 'wp_nav_menu_objects', 'um_conditional_nav_menu', 9999, 2 );


	/**
	 * Conditional menu items
	 *
	 * @param $items
	 * @param $menu
	 * @param $args
	 *
	 * @return mixed
	 */
	function um_get_nav_menu_items( $items, $menu, $args ) {
		return um_conditional_nav_menu( $items, $args );
	}
	add_filter( 'wp_get_nav_menu_items', 'um_get_nav_menu_items', 9999, 3 );
}
