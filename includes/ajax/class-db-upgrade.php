<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\ajax\DB_Upgrade' ) ) {


	/**
	 * Class DB_Upgrade
	 *
	 * This class handles all functions that changes data structures and moving files
	 *
	 * @package um\ajax
	 */
	final class DB_Upgrade extends \um\common\DB_Upgrade {

		/**
		 * DB_Upgrade constructor.
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'admin_init', array( $this, 'init_packages_ajax' ), 11 );
		}

		/**
		 *
		 */
		public function init_packages_ajax() {
			if ( ! $this->need_upgrade() ) {
				return;
			}

			// common DB upgrade AJAX actions
			add_action( 'wp_ajax_um_run_package', array( $this, 'ajax_run_package' ) );
			add_action( 'wp_ajax_um_get_packages', array( $this, 'ajax_get_packages' ) );

			foreach ( $this->necessary_packages as $package ) {
				$hooks_file = $this->packages_dir . $package . DIRECTORY_SEPARATOR . 'hooks.php';
				if ( ! file_exists( $hooks_file ) ) {
					continue;
				}

				$handlers_file = $this->packages_dir . $package . DIRECTORY_SEPARATOR . 'functions.php';
				if ( file_exists( $handlers_file ) ) {
					include_once $handlers_file;
				}

				$pack_ajax_hooks = include_once $hooks_file;
				foreach ( $pack_ajax_hooks as $action => $function ) {
					add_action( 'wp_ajax_um_' . $action, "um_upgrade_$function" );
				}
			}
		}

		/**
		 *
		 */
		function ajax_run_package() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_POST['pack'] ) ) {
				exit('');
			} else {
				ob_start();
				include_once $this->packages_dir . sanitize_text_field( $_POST['pack'] ) . DIRECTORY_SEPARATOR . 'init.php';
				ob_get_flush();
				exit;
			}
		}

		/**
		 *
		 */
		function ajax_get_packages() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );
			wp_send_json_success( array( 'packages' => $this->necessary_packages ) );
		}
	}
}
