<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'UM_Menu_Item_Custom_Fields' ) ) :

class UM_Menu_Item_Custom_Fields {
	/**
	 *
	 */
	public static function load() {
		add_filter( 'wp_edit_nav_menu_walker', array( __CLASS__, '_filter_walker' ), 999 );
	}


	/**
	 * @param $walker
	 * @return string
	 */
	public static function _filter_walker( $walker ) {
		$um_walker = 'UM_Menu_Item_Custom_Fields_Walker';

		if ( UM()->options()->get( 'menu_item_workaround' ) ) {
			//hard rewrite workaround with conflicted themes/plugins
			$walker = 'Walker_Nav_Menu_Edit';
		}

		$walker_filename = dirname( __FILE__ ) . '/um-navmenu-walker.php';
		$walker_template = file_get_contents( dirname( __FILE__ ) . '/um-navmenu-walker-template.php' );

		$current_walker_content = file_get_contents( $walker_filename );
		if ( strpos( $current_walker_content, $um_walker ) === false ||
			 strpos( $current_walker_content, $walker ) === false ) {

			$walker_template = str_replace(
				array(
					'{{{%um_navmenu_walker%}}}',
					'{{{%parent_walker%}}}'
				),
				array(
					$um_walker,
					$walker
				),
				$walker_template
			);

			$fp = fopen( $walker_filename, 'w+' );
			fwrite( $fp, $walker_template );
			fclose( $fp );
		}
		require_once $walker_filename;
		return $um_walker;
	}
}
add_action( 'wp_loaded', array( 'UM_Menu_Item_Custom_Fields', 'load' ), 9 );

endif;

require_once dirname( __FILE__ ) . '/um-navmenu-walker-edit.php';