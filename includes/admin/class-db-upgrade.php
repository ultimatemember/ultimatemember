<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\admin\DB_Upgrade' ) ) {


	/**
	 * Class DB_Upgrade
	 *
	 * This class handles all functions that changes data structures and moving files
	 *
	 * @package um\admin
	 */
	final class DB_Upgrade extends \um\common\DB_Upgrade {

		/**
		 * DB_Upgrade constructor.
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'um_extend_admin_menu', array( $this, 'admin_menu' ), 9999999 );
			add_action( 'in_plugin_update_message-' . UM_PLUGIN, array( $this, 'in_plugin_update_message' ) );
		}

		/**
		 * Function for major updates
		 *
		 */
		public function in_plugin_update_message( $args ) {
			$show_additional_notice = false;
			if ( isset( $args['new_version'] ) ) {
				$old_version_array = explode( '.', UM_VERSION );
				$new_version_array = explode( '.', $args['new_version'] );

				if ( $old_version_array[0] < $new_version_array[0] ) {
					$show_additional_notice = true;
				} else {
					if ( $old_version_array[1] < $new_version_array[1] ) {
						$show_additional_notice = true;
					}
				}

			}

			if ( $show_additional_notice ) {
				ob_start(); ?>

				<style type="text/css">
					.um_plugin_upgrade_notice {
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

					.um_plugin_upgrade_notice:before {
						content: "\f348";
						display: inline-block;
						font: 400 18px/1 dashicons;
						speak: none;
						margin: 0 8px 0 -2px;
						vertical-align: top;
					}
				</style>

				<span class="um_plugin_upgrade_notice">
					<?php printf( __( '%s is a major update, and we highly recommend creating a full backup of your site before updating.', 'ultimate-member' ), $args['new_version'] ); ?>
				</span>

				<?php ob_get_flush();
			}
		}

		/**
		 * Add Upgrades admin menu
		 */
		public function admin_menu() {
			if ( $this->need_upgrade() ) {
				add_submenu_page( UM()->admin()->menu()->slug, __( 'Upgrade', 'ultimate-member' ), '<span style="color:#ca4a1f;">' . __( 'Upgrade', 'ultimate-member' ) . '</span>', 'manage_options', 'um_upgrade', array( &$this, 'upgrade_page' ), 1 );
			}
		}

		/**
		 * Upgrade Menu Callback Page
		 */
		public function upgrade_page() {
			$um_last_version_upgrade = get_option( 'um_last_version_upgrade', __( 'empty', 'ultimate-member' ) );
			?>

			<div class="wrap">
				<h2><?php printf( __( '%s - Upgrade Process', 'ultimate-member' ), UM_PLUGIN_NAME ); ?></h2>
				<p><?php printf( __( 'You have installed <strong>%s</strong> version. Your latest DB version is <strong>%s</strong>. We recommend creating a backup of your site before running the update process. Do not exit the page before the update process has complete.', 'ultimate-member' ), UM_VERSION, $um_last_version_upgrade ); ?></p>
				<p><?php _e( 'After clicking the <strong>"Run"</strong> button, the update process will start. All information will be displayed in the <strong>"Upgrade Log"</strong> field.', 'ultimate-member' ); ?></p>
				<p><?php esc_html_e( 'If the update was successful, you will see a corresponding message. Otherwise, contact technical support if the update failed.', 'ultimate-member' ); ?></p>
				<h4><?php esc_html_e( 'Upgrade Log', 'ultimate-member' ); ?></h4>
				<div id="upgrade_log" style="width: 100%;height:300px; overflow: auto;border: 1px solid #a1a1a1;margin: 0 0 10px 0;"></div>
				<div>
					<input type="button" id="run_upgrade" class="button button-primary" value="<?php esc_attr_e( 'Run', 'ultimate-member' ) ?>"/>
				</div>
			</div>

			<script type="text/javascript">
				var um_request_throttle = 15000;
				var um_packages;

				jQuery( document ).ready( function() {
					jQuery( '#run_upgrade' ).click( function() {
						jQuery(this).prop( 'disabled', true );

						um_add_upgrade_log( 'Upgrade Process Started...' );
						um_add_upgrade_log( 'Get Upgrades Packages...' );

						jQuery.ajax({
							url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ) ?>',
							type: 'POST',
							dataType: 'json',
							data: {
								action: 'um_get_packages',
								nonce: um_admin_scripts.nonce
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
						// 30s between upgrades
						setTimeout( function () {
							var pack = um_packages.shift();
							um_add_upgrade_log( '<br />=================================================================' );
							um_add_upgrade_log( '<h4 style="font-weight: bold;">Prepare package "' + pack + '" version...</h4>' );
							jQuery.ajax({
								url: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ) ?>',
								type: 'POST',
								dataType: 'html',
								data: {
									action: 'um_run_package',
									pack: pack,
									nonce: um_admin_scripts.nonce
								},
								success: function( html ) {
									um_add_upgrade_log( 'Package "' + pack + '" is ready. Start the execution...' );
									jQuery( '#run_upgrade' ).after( html );
								}
							});
						}, um_request_throttle );
					} else {
						window.location = '<?php echo add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'version_upgraded' ), admin_url( 'admin.php' ) ) ?>'
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
	}
}
