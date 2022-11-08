<?php
namespace umm\member_directory\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Columns
 *
 * @package umm\member_directory\includes\admin
 */
class Columns {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_filter( 'manage_edit-um_directory_columns', array( &$this, 'manage_edit_um_directory_columns' ) );
		add_action( 'manage_um_directory_posts_custom_column', array( &$this, 'manage_um_directory_posts_custom_column' ), 10, 3 );
	}


	/**
	 * Custom columns for Directory
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	function manage_edit_um_directory_columns( $columns ) {
		$new_columns['cb']         = '<input type="checkbox" />';
		$new_columns['title']      = __( 'Title', 'ultimate-member' );
		$new_columns['id']         = __( 'ID', 'ultimate-member' );
		$new_columns['is_default'] = __( 'Default', 'ulitmate-member' );
		$new_columns['shortcode']  = __( 'Shortcode', 'ultimate-member' );
		$new_columns['date']       = __( 'Date', 'ultimate-member' );

		return $new_columns;
	}


	/**
	 * Display custom columns for Directory
	 *
	 * @param string $column_name
	 * @param int $id
	 */
	function manage_um_directory_posts_custom_column( $column_name, $id ) {
		switch ( $column_name ) {
			case 'id':
				echo '<span class="um-admin-number">' . $id . '</span>';
				break;
			case 'shortcode':
				echo '[ultimatemember_directory id="' . esc_attr( $id ) . '"]';
				break;
			case 'is_default':
				$is_default = UM()->query()->get_attr( 'is_default', $id );
				echo empty( $is_default ) ? __( 'No', 'ultimate-member' ) : __( 'Yes', 'ultimate-member' );
				break;
		}
	}
}
