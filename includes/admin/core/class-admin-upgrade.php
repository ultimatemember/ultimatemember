<?php
namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\admin\core\Admin_Upgrade' ) ) {


	/**
	 * Class Admin_Upgrade
	 *
	 * This class handles all functions that changes data structures and moving files
	 *
	 * @package um\admin\core
	 */
	class Admin_Upgrade {


		/**
		 * @var
		 */
		var $update_versions;
		var $update_packages;
		var $necessary_packages;


		/**
		 * @var string
		 */
		var $packages_dir;


		/**
		 * Admin_Upgrade constructor.
		 */
		function __construct() {
			$this->packages_dir = plugin_dir_path( __FILE__ ) . 'packages' . DIRECTORY_SEPARATOR;
			$this->necessary_packages = $this->need_run_upgrades();

			if ( ! empty( $this->necessary_packages ) ) {
				$this->init_packages_ajax();
				add_action( 'admin_menu', array( $this, 'admin_menu' ), 0 );

				add_action( 'wp_ajax_um_run_package', array( $this, 'ajax_run_package' ) );
				add_action( 'wp_ajax_um_get_packages', array( $this, 'ajax_get_packages' ) );
			}

			//add_action( 'in_plugin_update_message-' . um_plugin, array( $this, 'in_plugin_update_message' ) );
		}

		/**
		 * Function for major updates
		 *
		 */
		/*function in_plugin_update_message( $args ) {

			$lastversion = get_option( '%UNIQUE_ID%_last_version', false );
			if ( $lastversion && version_compare( $lastversion, %UNIQUE_ID%_current_version, '>' ) )  {
				$upgrade_notice = get_option( '%UNIQUE_ID%_major_update' . $lastversion );

				echo '<style type="text/css">
	            .%UNIQUE_ID%_plugin_upgrade_notice {
	                font-weight: 400;
	                color: #fff;
	                background: #d53221;
	                padding: 1em;
	                margin: 9px 0;
	                display: block;
	                box-sizing: border-box;
	                -webkit-box-sizing: border-box;
	                -moz-box-sizing: border-box;
	            }
	            .%UNIQUE_ID%_plugin_upgrade_notice:before {
	                content: "\f348";
	                display: inline-block;
	                font: 400 18px/1 dashicons;
	                speak: none;
	                margin: 0 8px 0 -2px;
	                -webkit-font-smoothing: antialiased;
	                -moz-osx-font-smoothing: grayscale;
	                vertical-align: top;
	            }
	        </style>' . wp_kses_post( $upgrade_notice );
			}
		}*/


		/**
		 * Get array of necessary upgrade packages
		 *
		 * @return array
		 */
		function need_run_upgrades() {
			$um_last_version_upgrade = get_option( 'um_last_version_upgrade' );
			//first install
			if ( ! $um_last_version_upgrade ) {
				$um_last_version_upgrade = '1.3.88';
			}

			$diff_packages = array();

			$all_packages = $this->get_packages();
			foreach ( $all_packages as $package ) {
				if ( version_compare( $um_last_version_upgrade, $package, '<' ) ) {
					$diff_packages[] = $package;
				}
			}

			return $diff_packages;
		}


		/**
		 * Get all upgrade packages
		 *
		 * @return array
		 */
		function get_packages() {
			$update_versions = array();
			$handle = opendir( $this->packages_dir );
			if ( $handle ) {
				while ( false !== ( $filename = readdir( $handle ) ) ) {
					if ( $filename != '.' && $filename != '..' ) {
						if ( is_dir( $this->packages_dir . DIRECTORY_SEPARATOR . $filename ) ) {
							$update_versions[] = $filename;
						}
					}
				}
				closedir( $handle );

				usort( $update_versions, array( &$this, 'version_compare_sort' ) );
			}

			return $update_versions;
		}


		/**
		 *
		 */
		function init_packages_ajax() {
			foreach ( $this->necessary_packages as $package ) {
				$hooks_file = $this->packages_dir . DIRECTORY_SEPARATOR . $package . DIRECTORY_SEPARATOR . 'hooks.php';
				if ( file_exists( $hooks_file ) ) {
					$pack_ajax_hooks = include $hooks_file;

					foreach ( $pack_ajax_hooks as $action => $function ) {
						add_action( 'wp_ajax_um_' . $action, "um_upgrade_$function" );
					}
				}
			}
		}


		/**
		 *
		 */
		function init_packages_ajax_handlers() {
			foreach ( $this->necessary_packages as $package ) {
				$handlers_file = $this->packages_dir . DIRECTORY_SEPARATOR . $package . DIRECTORY_SEPARATOR . 'functions.php';
				if ( file_exists( $handlers_file ) ) {
					include $handlers_file;
				}
			}
		}


		/**
		 * Add Upgrades admin menu
		 */
		function admin_menu() {
			add_submenu_page( 'ultimatemember', __( 'Upgrade', 'ultimate-member' ), '<span style="color:#ca4a1f;">' . __( 'Upgrade', 'ultimate-member' ) . '</span>', 'manage_options', 'um_upgrade', array( &$this, 'upgrade_page' ) );
		}


		/**
		 * Upgrade Menu Callback Page
		 */
		function upgrade_page() {
			$um_last_version_upgrade = get_option( 'um_last_version_upgrade', __( 'empty', 'ultimate-member' ) ); ?>

			<div class="wrap">
				<h2><?php printf( __( '%s - Upgrade Process', 'ultimate-member' ), ultimatemember_plugin_name ) ?></h2>
				<p><?php printf( __( 'You have installed %s version. Your latest DB version is %s. Before the click to "Run" button make sure that did the following:', 'ultimate-member' ), ultimatemember_version, $um_last_version_upgrade ) ?></p>
				<ul style="list-style: inside;">
					<li><?php _e( 'Create full site\'s backup.', 'ultimate-member' ) ?></li>
					<li><?php _e( 'Set maintenance mode (if you need)', 'ultimate-member' ) ?></li>
					<li><?php _e( 'You have nice Internet connection', 'ultimate-member' ) ?></li>
				</ul>
				<p><?php _e( 'After the click to "Run" button, the update process will be started. All information will be displayed in "Upgrade Log" field.', 'ultimate-member' ); ?></p>
				<p><?php _e( 'If the update was successful, you will see a corresponding message. Otherwise, contact technical support if the update failed.', 'ultimate-member' ); ?></p>
				<h4><?php printf( __( 'Upgrade Log' ), ultimatemember_plugin_name ) ?></h4>
				<div id="upgrade_log" style="width: 100%;height:300px; overflow: auto;border: 1px solid #a1a1a1;margin: 0 0 10px 0;"></div>
				<div>
					<input type="button" id="run_upgrade" class="button button-primary" value="<?php esc_attr_e( 'Run', 'ultimate-member' ) ?>"/>
				</div>
			</div>

			<script type="text/javascript">
				var um_packages;

				jQuery( document ).ready( function() {
					jQuery( '#run_upgrade' ).click( function() {
						jQuery(this).prop( 'disabled', true );

						um_add_upgrade_log( 'Upgrade Process Started...' );
						um_add_upgrade_log( 'Get Upgrades Packages...' );

						jQuery.ajax({
							url: '<?php echo admin_url( 'admin-ajax.php' ) ?>',
							type: 'POST',
							dataType: 'json',
							data: {
								action: 'um_get_packages'
							},
							success: function( response ) {
								um_packages = response.data.packages;

								um_add_upgrade_log( 'Upgrades Packages are ready, start unpacking...' );

								//run first package....the running of the next packages will be at each init.php file
								um_run_upgrade();
							}
						});
					});
				});


				/**
				 *
				 * @returns {boolean}
				 */
				function um_run_upgrade() {
					if ( um_packages.length ) {
						var pack = um_packages.shift();
						um_add_upgrade_log( '<br />=================================================================' );
						um_add_upgrade_log( '<h4 style="font-weight: bold;">Prepare package "' + pack + '" version...</h4>' );
						jQuery.ajax({
							url: '<?php echo admin_url( 'admin-ajax.php' ) ?>',
							type: 'POST',
							dataType: 'html',
							data: {
								action: 'um_run_package',
								pack: pack
							},
							success: function( html ) {
								um_add_upgrade_log( 'Package "' + pack + '" is ready. Start the execution...' );
								jQuery( '#run_upgrade' ).after( html );
							}
						});
					} else {
						window.location = '<?php echo add_query_arg( array( 'page' => 'ultimatemember', 'msg' => 'updated' ), admin_url( 'admin.php' ) ) ?>'
					}

					return false;
				}


				/**
				 *
				 * @param line
				 */
				function um_add_upgrade_log( line ) {
					var log_field = jQuery( '#upgrade_log' );
					var previous_html = log_field.html();
					log_field.html( previous_html + line + "<br />" );
				}


				function um_wrong_ajax() {
					um_add_upgrade_log( 'Wrong AJAX response...' );
					um_add_upgrade_log( 'Your upgrade was crashed, please contact with support' );
				}


				function um_something_wrong() {
					um_add_upgrade_log( 'Something went wrong with AJAX request...' );
					um_add_upgrade_log( 'Your upgrade was crashed, please contact with support' );
				}
			</script>

			<?php

		}


		function ajax_run_package() {
			if ( empty( $_POST['pack'] ) ) {
				exit('');
			} else {
				ob_start();
				include $this->packages_dir . DIRECTORY_SEPARATOR . $_POST['pack'] . DIRECTORY_SEPARATOR . 'init.php';
				ob_get_flush();
				exit;
			}
		}


		function ajax_get_packages() {
			$update_versions = $this->need_run_upgrades();
			wp_send_json_success( array( 'packages' => $update_versions ) );
		}

		/**
		 * Load packages
		 */
		/*public function packages() {
			if ( ! ini_get( 'safe_mode' ) ) {
				@set_time_limit(0);
			}

			$this->set_update_versions();

			$um_last_version_upgrade = get_option( 'um_last_version_upgrade' );
			$um_last_version_upgrade = ! $um_last_version_upgrade ? '0.0.0' : $um_last_version_upgrade;

			foreach ( $this->update_versions as $update_version ) {

				if ( version_compare( $update_version, $um_last_version_upgrade, '<=' ) )
					continue;

				if ( version_compare( $update_version, ultimatemember_version, '>' ) )
					continue;

				$file_path = $this->packages_dir . $update_version . '.php';

				if ( file_exists( $file_path ) ) {
					include_once( $file_path );
					update_option( 'um_last_version_upgrade', $update_version );
				}
			}
		}*/


		/**
		 * Parse packages dir for packages files
		 */
		function set_update_versions() {
			$update_versions = array();
			$handle = opendir( $this->packages_dir );
			if ( $handle ) {
				while ( false !== ( $filename = readdir( $handle ) ) ) {
					if ( $filename != '.' && $filename != '..' )
						$update_versions[] = preg_replace( '/(.*?)\.php/i', '$1', $filename );
				}
				closedir( $handle );

				usort( $update_versions, array( &$this, 'version_compare_sort' ) );

				$this->update_versions = $update_versions;
			}
		}



		/**
		 * Parse packages dir for packages files
		 */
		/*function set_update_versions_() {
			$update_versions = array();
			$handle = opendir( $this->packages_dir );
			if ( $handle ) {
				while ( false !== ( $filename = readdir( $handle ) ) ) {
					if ( $filename != '.' && $filename != '..' ) {
						var_dump( $filename );
						if ( is_dir( $this->packages_dir . DIRECTORY_SEPARATOR . $filename ) ) {
							$update_versions[] = $filename;
						}
					}
				}
				closedir( $handle );

				usort( $update_versions, array( &$this, 'version_compare_sort' ) );

				$this->update_packages = $update_versions;
			}
		}*/


		/**
		 * Sort versions by version compare function
		 * @param $a
		 * @param $b
		 * @return mixed
		 */
		function version_compare_sort( $a, $b ) {
			return version_compare( $a, $b );
		}

	}
}