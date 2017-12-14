<?php
/**
 * This is template for NavMenu Walker Class which extends from latest parents
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( '{{{%um_navmenu_walker%}}}' ) ) :

class {{{%um_navmenu_walker%}}} extends {{{%parent_walker%}}} {

	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$item_output = '';
		parent::start_el( $item_output, $item, $depth, $args, $id );

		if( $new_fields =  $this->get_fields( $item, $depth, $args, $id ) ){
			//$item_output = preg_replace('/(?=<div[^>]+class="[^"]*submitbox)/', $new_fields, $item_output);
			$item_output = preg_replace('/(?=<(fieldset|p)[^>]+class="[^"]*field-move)/', $new_fields, $item_output);
		}

		$output .= $item_output;

	}

	protected function get_fields( $item, $depth, $args = array(), $id = 0 ) {
		ob_start();

		if( isset(  $item->ID ) ){
			$id = esc_attr( $item->ID );
		}

		do_action( 'wp_nav_menu_item_custom_fields', $id, $item, $depth, $args );

		return ob_get_clean();
	}
}

endif;