<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\ajax\Init' ) ) {


	/**
	 * Class Init
	 *
	 * @package um\ajax
	 */
	class Init {


		/**
		 * Init constructor.
		 */
		function __construct() {

		}


		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @used-by \UM::includes()
		 */
		function includes() {
			$this->db_upgrade();
			$this->field_group();
			$this->notices();
			$this->user();
			$this->builder();
			UM()->admin()->notices();
			$this->settings();
			$this->pages();
		}


		/**
		 * Check nonce
		 *
		 * @param bool|string $action
		 *
		 * @since 3.0
		 */
		function check_nonce( $action = false ) {
			$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( $_REQUEST['nonce'] ) : '';
			$action = empty( $action ) ? 'um-common-nonce' : $action;

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_send_json_error( __( 'Wrong AJAX Nonce', 'ultimate-member' ) );
			}
		}


		/**
		 * @since 3.0
		 *
		 * @return DB_Upgrade()
		 */
		function db_upgrade() {
			if ( empty( UM()->classes['um\ajax\db_upgrade'] ) ) {
				UM()->classes['um\ajax\db_upgrade'] = new DB_Upgrade();
			}
			return UM()->classes['um\ajax\db_upgrade'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Field_Group()
		 */
		function field_group() {
			if ( empty( UM()->classes['um\ajax\field_group'] ) ) {
				UM()->classes['um\ajax\field_group'] = new Field_Group();
			}
			return UM()->classes['um\ajax\field_group'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Notices()
		 */
		function notices() {
			if ( empty( UM()->classes['um\ajax\notices'] ) ) {
				UM()->classes['um\ajax\notices'] = new Notices();
			}
			return UM()->classes['um\ajax\notices'];
		}


		/**
		 * @since 3.0
		 *
		 * @return User()
		 */
		function user() {
			if ( empty( UM()->classes['um\ajax\user'] ) ) {
				UM()->classes['um\ajax\user'] = new User();
			}
			return UM()->classes['um\ajax\user'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Settings()
		 */
		function settings() {
			if ( empty( UM()->classes['um\ajax\settings'] ) ) {
				UM()->classes['um\ajax\settings'] = new Settings();
			}
			return UM()->classes['um\ajax\settings'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Pages()
		 */
		function pages() {
			if ( empty( UM()->classes['um\ajax\pages'] ) ) {
				UM()->classes['um\ajax\pages'] = new Pages();
			}
			return UM()->classes['um\ajax\pages'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Builder()
		 */
		function builder() {
			if ( empty( UM()->classes['um\ajax\builder'] ) ) {
				UM()->classes['um\ajax\builder'] = new Builder();
			}
			return UM()->classes['um\ajax\builder'];
		}
	}
}
