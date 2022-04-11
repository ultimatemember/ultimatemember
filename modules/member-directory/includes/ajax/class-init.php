<?php
namespace umm\member_directory\includes\ajax;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\member_directory\includes\ajax
 */
class Init {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}


	function includes() {
		$this->directory();
		$this->settings();
		$this->fields();
	}


	/**
	 * @return Fields()
	 */
	function fields() {
		if ( empty( UM()->classes['umm\member_directory\includes\ajax\fields'] ) ) {
			UM()->classes['umm\member_directory\includes\ajax\fields'] = new Fields();
		}
		return UM()->classes['umm\member_directory\includes\ajax\fields'];
	}


	/**
	 * @return Settings()
	 */
	function settings() {
		if ( empty( UM()->classes['umm\member_directory\includes\ajax\settings'] ) ) {
			UM()->classes['umm\member_directory\includes\ajax\settings'] = new Settings();
		}
		return UM()->classes['umm\member_directory\includes\ajax\settings'];
	}


	/**
	 * @return Directory()
	 */
	function directory() {
		if ( empty( UM()->classes['umm\member_directory\includes\ajax\directory'] ) ) {
			$search_in_table = UM()->options()->get( 'member_directory_own_table' );

			if ( ! empty( $search_in_table ) ) {
				UM()->classes['umm\member_directory\includes\ajax\directory'] = new Directory_Meta();
			} else {
				UM()->classes['umm\member_directory\includes\ajax\directory'] = new Directory();
			}
		}
		return UM()->classes['umm\member_directory\includes\ajax\directory'];
	}
}
