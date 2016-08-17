<?php

class UM_Menu_Item_Custom_Fields_Walker extends Walker_Nav_Menu_Edit {

	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$item_output = '';
		parent::start_el( $item_output, $item, $depth, $args, $id );
		$output .= preg_replace(
			// NOTE: Check this regex from time to time!
			'/(?=<p[^>]+class="[^"]*field-move)/',
			$this->get_fields( $item, $depth, $args ),
			$item_output
		);
	}

	protected function get_fields( $item, $depth, $args = array(), $id = 0 ) {
		ob_start();

		$id = esc_attr( $item->ID );

		do_action( 'wp_nav_menu_item_custom_fields', $id, $item, $depth, $args );

		return ob_get_clean();
	}
}