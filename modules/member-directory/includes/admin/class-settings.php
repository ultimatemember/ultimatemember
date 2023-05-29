<?php
namespace umm\member_directory\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 *
 * @package umm\member_directory\includes\admin
 */
class Settings {

	private $gravatar_changed = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_filter( 'um_settings_map', array( &$this, 'add_settings_sanitize' ), 10, 1 );

		add_filter( 'um_settings_structure', array( $this, 'add_account_settings' ) );

		add_filter( 'um_settings_structure', array( $this, 'admin_settings' ), 10, 1 );
		add_filter( 'um_pages_settings_description', array( $this, 'admin_pages_settings' ), 10, 3 );

		add_action( 'um_settings_before_save', array( $this, 'check_use_gravatars_setting_change' ) );
		add_action( 'um_settings_save', array( $this, 'maybe_update_member_directory_data' ) );
	}

	/**
	 * @param string $page_setting_description
	 * @param string $content
	 * @param string $slug
	 *
	 * @return string
	 */
	public function admin_pages_settings( $page_setting_description, $content, $slug ) {
		if ( 'members' === $slug && ! has_shortcode( $content, 'ultimatemember' ) ) {
			$page_setting_description = __( '<strong>Warning:</strong> Members page must contain a member directory shortcode. You can get existing shortcode or create a new one <a href="edit.php?post_type=um_directory" target="_blank">here</a>.', 'ultimate-member' );
		}
		return $page_setting_description;
	}

	/**
	 * @param array $settings_map
	 *
	 * @return array
	 */
	public function add_settings_sanitize( $settings_map ) {
		$settings_map = array_merge(
			$settings_map,
			array(
				'account_hide_in_directory'         => array(
					'sanitize' => 'bool',
				),
				'account_hide_in_directory_default' => array(
					'sanitize' => 'bool',
				),
			)
		);

		return $settings_map;
	}

	/**
	 * Add settings related to the General > Account subtab.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function add_account_settings( $settings ) {
		$key_after = false;
		foreach ( $settings['']['sections']['account']['fields'] as $k => $field_data ) {
			if ( array_key_exists( 'id', $field_data ) && 'account_tab_privacy' === $field_data['id'] ) {
				$key_after = $k;
			}
		}

		if ( false !== $key_after ) {
			$md_account_settings = array(
				array(
					'id'          => 'account_hide_in_directory',
					'type'        => 'checkbox',
					'label'       => __( 'Allow users to hide their profiles from directory', 'ultimate-member' ),
					'description' => __( 'Whether to allow users changing their profile visibility from member directory in account page. Only visible if the "Privacy" account tab is visible.', 'ultimate-member' ),
					'conditional' => array( 'account_tab_privacy', '=', '1' ),
				),
				array(
					'id'          => 'account_hide_in_directory_default',
					'type'        => 'checkbox',
					'label'       => __( 'Hide profiles from directory by default', 'ultimate-member' ),
					'description' => __( 'Set default value for the "Hide my profile from directory" option', 'ultimate-member' ),
					'conditional' => array( 'account_hide_in_directory', '=', '1' ),
				),
			);

			$settings['']['sections']['account']['fields'] = UM()->array_insert_after( $settings['']['sections']['account']['fields'], 9, $md_account_settings );
		}

		return $settings;
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public function admin_settings( $settings ) {
		$latest_update   = get_option( 'um_member_directory_update_meta', false );
		$latest_truncate = get_option( 'um_member_directory_truncated', false );

		$redirect_link    = add_query_arg(
			array(
				'page'    => 'ultimatemember',
				'tab'     => 'modules',
				'section' => 'member-directory',
				'update'  => 'settings_updated',
			),
			admin_url( 'admin.php' )
		);
		$same_page_update = array(
			'id'                    => 'member_directory_own_table',
			'type'                  => 'same_page_update',
			'label'                 => __( 'Enable custom table for usermeta', 'ultimate-member' ),
			'description'           => __( 'Check this box if you would like to enable the use of a custom table for user metadata. Improved performance for member directory searches.', 'ultimate-member' ),
			'successfully_redirect' => $redirect_link,
		);

		if ( empty( $latest_update ) || ( ! empty( $latest_truncate ) && $latest_truncate > $latest_update ) ) {
			$same_page_update['upgrade_cb']          = 'sync_metatable';
			$same_page_update['upgrade_description'] = '<p>' . __( 'We recommend creating a backup of your site before running the update process. Do not exit the page before the update process has complete.', 'ultimate-member' ) . '</p>
<p>' . __( 'After clicking the <strong>"Run"</strong> button, the update process will start. All information will be displayed in the field below.', 'ultimate-member' ) . '</p>
<p>' . __( 'If the update was successful, you will see a corresponding message. Otherwise, contact technical support if the update failed.', 'ultimate-member' ) . '</p>';
		}

		$settings['modules']['sections']['member-directory'] = array(
			'title'  => __( 'Member Directory', 'ultimate-member' ),
			'fields' => array(
				$same_page_update,
			),
		);

		return $settings;
	}

	/**
	 *
	 */
	public function check_use_gravatars_setting_change() {
		// set variable if gravatar settings were changed
		// update for um_member_directory_data metakey
		if ( isset( $_POST['um_options']['use_gravatars'] ) ) {
			$use_gravatars_new = $_POST['um_options']['use_gravatars'];
			$use_gravatars_old = UM()->options()->get( 'use_gravatars' );
			if ( ( empty( $use_gravatars_old ) && ! empty( $use_gravatars_new ) ) || ( ! empty( $use_gravatars_old ) && empty( $use_gravatars_new ) ) ) {
				$this->gravatar_changed = true;
			}
		}
	}


	/**
	 *
	 */
	public function maybe_update_member_directory_data() {
		if ( ! empty( $_POST['um_options'] ) ) {

			// update for um_member_directory_data metakey
			if ( isset( $_POST['um_options']['use_gravatars'] ) ) {
				if ( $this->gravatar_changed ) {
					global $wpdb;

					if ( ! empty( $_POST['um_options']['use_gravatars'] ) ) {

						$results = $wpdb->get_col(
							"SELECT u.ID FROM {$wpdb->users} AS u
								LEFT JOIN {$wpdb->usermeta} AS um ON ( um.user_id = u.ID AND um.meta_key = 'synced_gravatar_hashed_id' )
								LEFT JOIN {$wpdb->usermeta} AS um2 ON ( um2.user_id = u.ID AND um2.meta_key = 'um_member_directory_data' )
								WHERE um.meta_value != '' AND um.meta_value IS NOT NULL AND
									um2.meta_value LIKE '%s:13:\"profile_photo\";b:0;%'"
						);

					} else {

						$results = $wpdb->get_col(
							"SELECT u.ID FROM {$wpdb->users} AS u
								LEFT JOIN {$wpdb->usermeta} AS um ON ( um.user_id = u.ID AND ( um.meta_key = 'synced_profile_photo' || um.meta_key = 'profile_photo' ) )
								LEFT JOIN {$wpdb->usermeta} AS um2 ON ( um2.user_id = u.ID AND um2.meta_key = 'um_member_directory_data' )
								WHERE ( um.meta_value IS NULL OR um.meta_value = '' ) AND
									um2.meta_value LIKE '%s:13:\"profile_photo\";b:1;%'"
						);

					}

					if ( ! empty( $results ) ) {
						foreach ( $results as $user_id ) {
							$md_data = get_user_meta( $user_id, 'um_member_directory_data', true );
							if ( ! empty( $md_data ) ) {
								$md_data['profile_photo'] = ! empty( $_POST['um_options']['use_gravatars'] );
								update_user_meta( $user_id, 'um_member_directory_data', $md_data );
							}
						}
					}
				}
			} elseif ( isset( $_POST['um_options']['member_directory_own_table'] ) ) {
				if ( empty( $_POST['um_options']['member_directory_own_table'] ) ) {
					global $wpdb;

					$results = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}um_metadata LIMIT 1", ARRAY_A );

					if ( ! empty( $results ) ) {
						$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}um_metadata" );
					}

					update_option( 'um_member_directory_truncated', time() );
				}
			} elseif ( isset( $_POST['um_options']['account_hide_in_directory_default'] ) ) {
				global $wpdb;

				if ( $_POST['um_options']['account_hide_in_directory_default'] === 'No' ) {

					$results = $wpdb->get_col(
						"SELECT u.ID FROM {$wpdb->users} AS u
							LEFT JOIN {$wpdb->usermeta} AS um ON ( um.user_id = u.ID AND um.meta_key = 'hide_in_members' )
							LEFT JOIN {$wpdb->usermeta} AS um2 ON ( um2.user_id = u.ID AND um2.meta_key = 'um_member_directory_data' )
							WHERE um.meta_value IS NULL AND
								um2.meta_value LIKE '%s:15:\"hide_in_members\";b:1;%'"
					);

				} else {

					$results = $wpdb->get_col(
						"SELECT u.ID FROM {$wpdb->users} AS u
							LEFT JOIN {$wpdb->usermeta} AS um ON ( um.user_id = u.ID AND um.meta_key = 'hide_in_members' )
							LEFT JOIN {$wpdb->usermeta} AS um2 ON ( um2.user_id = u.ID AND um2.meta_key = 'um_member_directory_data' )
							WHERE um.meta_value IS NULL AND
								um2.meta_value LIKE '%s:15:\"hide_in_members\";b:0;%'"
					);

				}

				if ( ! empty( $results ) ) {
					foreach ( $results as $user_id ) {
						$md_data = get_user_meta( $user_id, 'um_member_directory_data', true );
						if ( ! empty( $md_data ) ) {
							$md_data['hide_in_members'] = ( $_POST['um_options']['account_hide_in_directory_default'] === 'No' ) ? false : true;
							update_user_meta( $user_id, 'um_member_directory_data', $md_data );
						}
					}
				}
			}
		}
	}
}
