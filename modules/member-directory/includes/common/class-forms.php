<?php
namespace umm\member_directory\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Forms
 *
 * @package umm\member_directory\includes\common
 */
class Forms {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_filter( 'um_form_meta_list', array( &$this, 'add_form_meta' ), 10, 1 );
		add_filter( 'um_get_post_data_maybe_skip_unset_meta', array( &$this, 'maybe_skip_unset_meta' ), 10, 2 );
	}


	/**
	 * @param bool $skip
	 * @param string $mode
	 *
	 * @return bool
	 */
	public function maybe_skip_unset_meta( $skip, $mode ) {
		if ( 'directory' === $mode ) {
			$skip = true;
		}
		return $skip;
	}


	/**
	 * @param array $meta
	 *
	 * @return array
	 */
	function add_form_meta( $meta ) {
		$meta = array_merge(
			$meta,
			array(
				'_um_directory_template'      => 'members',
				'_um_directory_header'        => __( '{total_users} Members', 'ultimate-member' ),
				'_um_directory_header_single' => __( '{total_users} Member', 'ultimate-member' ),
			)
		);

		return $meta;
	}
}
