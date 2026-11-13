<?php
namespace um\common\actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\actions\Files' ) ) {

	/**
	 * Class Files
	 *
	 * @package um\common\actions
	 */
	class Files {

		/**
		 * Scheduled action hook name.
		 *
		 * @var string
		 */
		const FLUSH_ACTION = 'um_flush_temp_folder';

		/**
		 * Guests constructor.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'maybe_add_scheduled_action' ) );
			add_action( self::FLUSH_ACTION, array( $this, 'flush_temp_folder' ) );
		}

		/**
		 * Maybe add recurring cleanup action.
		 *
		 * @return void
		 */
		public function maybe_add_scheduled_action() {
			if ( UM()->maybe_action_scheduler()->next_scheduled_action( self::FLUSH_ACTION ) ) {
				return;
			}

			$flush_interval = apply_filters( 'um_flush_temp_folder_scheduled_action_interval', DAY_IN_SECONDS );

			UM()->maybe_action_scheduler()->schedule_recurring_action(
				strtotime( 'midnight tonight' ),
				$flush_interval,
				self::FLUSH_ACTION
			);
		}

		/**
		 * Flush the `temp` directory from expired directories and files.
		 *
		 * @return void
		 */
		public function flush_temp_folder() {
			$this->remove_old_files( UM()->common()->filesystem()->get_tempdir() );
		}

		/**
		 * Remove old files.
		 *
		 * @param string     $dir       Path to directoty.
		 * @param int|string $timestamp Unix timestamp or PHP relative time. All older files will be removed.
		 */
		private function remove_old_files( $dir, $timestamp = null ) {
			$removed_files = array();

			if ( empty( $timestamp ) ) {
				$timestamp = strtotime( '-1 day' );
			} elseif ( is_string( $timestamp ) && ! is_numeric( $timestamp ) ) {
				$timestamp = strtotime( $timestamp );
			}

			if ( $timestamp && is_dir( $dir ) ) {
				$files = glob( $dir . '/*' );

				foreach ( (array) $files as $file ) {
					if ( in_array( wp_basename( $file ), array( '.', '..' ), true ) ) {
						continue;
					}

					if ( is_dir( $file ) ) {
						$this->remove_old_files( $file, $timestamp );
					} elseif ( is_file( $file ) ) {
						$fileatime = fileatime( $file );
						if ( $fileatime && $fileatime < (int) $timestamp ) {
							$result = wp_delete_file( $file );
							if ( $result ) {
								$removed_files[] = $file;
							}
						}
					}
				}
			}

			return $removed_files;
		}
	}
}
