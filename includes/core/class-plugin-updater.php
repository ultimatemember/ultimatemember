<?php
namespace um\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Plugin_Updater' ) ) {


	/**
	 * Class Plugin_Updater
	 * @package um\core
	 */
	class Plugin_Updater {


		/**
		 * Plugin_Updater constructor.
		 */
		function __construct() {
			//create cron event
			if ( ! wp_next_scheduled( 'um_check_extensions_licenses' ) ) {
				wp_schedule_event( time() + ( 24*60*60 ), 'daily', 'um_check_extensions_licenses' );
			}

			register_deactivation_hook( um_plugin, array( &$this, 'um_plugin_updater_deactivation_hook' ) );

			//cron request to ultimatemember.com
			add_action( 'um_check_extensions_licenses', array( &$this, 'um_checklicenses' ) );

			//update plugin info
			add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'um_check_update' ) );

			//plugin information info
			add_filter( 'plugins_api', array( &$this, 'um_plugins_api_filter' ), 9999, 3 );
		}


		/**
		 * Get all paid UM extensions
		 *
		 * @return array
		 */
		function um_get_active_plugins() {
			$paid_extensions = array(
				'um-bbpress/um-bbpress.php'                             => array(
					'key'   => 'bbpress',
					'title' => 'bbPress',
				),
				'um-followers/um-followers.php'                         => array(
					'key'   => 'followers',
					'title' => 'Followers',
				),
				'um-friends/um-friends.php'                             => array(
					'key'   => 'friends',
					'title' => 'Friends',
				),
				'um-groups/um-groups.php'                               => array(
					'key'   => 'groups',
					'title' => 'Groups',
				),
				'um-instagram/um-instagram.php'                         => array(
					'key'   => 'instagram',
					'title' => 'Instagram',
				),
				'um-invitations/um-invitations.php'                     => array(
					'key'   => 'invitations',
					'title' => 'Invitations',
				),
				'um-mailchimp/um-mailchimp.php'                         => array(
					'key'   => 'mailchimp',
					'title' => 'MailChimp',
				),
				'um-messaging/um-messaging.php'                         => array(
					'key'   => 'messaging',
					'title' => 'Messaging',
				),
				'um-mycred/um-mycred.php'                               => array(
					'key'   => 'mycred',
					'title' => 'myCRED',
				),
				'um-notices/um-notices.php'                             => array(
					'key'   => 'notices',
					'title' => 'Notices',
				),
				'um-notifications/um-notifications.php'                 => array(
					'key'   => 'notifications',
					'title' => 'Notifications',
				),
				'um-profile-completeness/um-profile-completeness.php'   => array(
					'key'   => 'profile_completeness',
					'title' => 'Profile Completeness',
				),
				'um-reviews/um-reviews.php'                             => array(
					'key'   => 'reviews',
					'title' => 'Reviews',
				),
				'um-social-activity/um-social-activity.php'             => array(
					'key'   => 'activity',
					'title' => 'Social Activity',
				),
				'um-social-login/um-social-login.php'                   => array(
					'key'   => 'social_login',
					'title' => 'Social Login',
				),
				'um-user-tags/um-user-tags.php'                         => array(
					'key'   => 'user_tags',
					'title' => 'User Tags',
				),
				'um-verified-users/um-verified-users.php'               => array(
					'key'   => 'verified_users',
					'title' => 'Verified Users',
				),
				'um-woocommerce/um-woocommerce.php'                     => array(
					'key'   => 'woocommerce',
					'title' => 'Woocommerce',
				),
			);

			$the_plugs = get_option( 'active_plugins' );
			$active_um_plugins = array();
			foreach ( $the_plugs as $key => $value ) {

				if ( in_array( $value, array_keys( $paid_extensions ) ) ) {
					$license = UM()->options()->get( "um_{$paid_extensions[ $value ]['key']}_license_key" );

					if ( empty( $license ) )
						continue;

					$active_um_plugins[ $value ] = $paid_extensions[ $value ];
					$active_um_plugins[ $value ]['license'] = $license;
				}
			}

			return $active_um_plugins;
		}


		/**
		 * Remove CRON events on deactivation hook
		 */
		function um_plugin_updater_deactivation_hook() {
			wp_clear_scheduled_hook( 'um_check_extensions_licenses' );
		}


		/**
		 * Check license function
		 */
		function um_checklicenses() {
			$exts = $this->um_get_active_plugins();

			if ( 0 == count( $exts ) )
				return;

			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			$api_params = array(
				'edd_action' => 'check_licenses',
				'author'     => 'Ultimate Member',
				'url'        => home_url(),
			);

			$api_params['active_extensions'] = array();
			foreach ( $exts as $slug => $data ) {
				$plugin_data = get_plugin_data( ABSPATH . "wp-content/plugins/{$slug}" );

				$api_params['active_extensions'][$slug] = array(
					'slug'      => $slug,
					'license'   => $data['license'],
					'item_name' => str_replace( 'Ultimate Member - ', '', $plugin_data['Name'] ),
					'version'   => $plugin_data['Version']
				);
			}

			$request = wp_remote_post(
				'https://ultimatemember.com/',
				array(
					'timeout'   => 15,
					'sslverify' => false,
					'body'      => $api_params
				)
			);

			if ( ! is_wp_error( $request ) )
				$request = json_decode( wp_remote_retrieve_body( $request ) );

			$request = ( $request ) ? maybe_unserialize( $request ) : false;

			if ( $request ) {
				foreach ( $exts as $slug => $data ) {
					if ( ! empty( $request->$slug->license_check ) )
						update_option( "{$data['key']}_edd_answer", $request->$slug->license_check );

					if ( ! empty( $request->$slug->get_version_check ) ) {

						$request->$slug->get_version_check = json_decode( $request->$slug->get_version_check );

						if ( ! empty( $request->$slug->get_version_check->package ) )
							$request->$slug->get_version_check->package = $this->extend_download_url( $request->$slug->get_version_check->package, $slug, $data );

						if ( ! empty( $request->$slug->get_version_check->download_link ) )
							$request->$slug->get_version_check->download_link = $this->extend_download_url( $request->$slug->get_version_check->download_link, $slug, $data );

						if ( isset( $request->$slug->get_version_check->sections ) ) {
							$request->$slug->get_version_check->sections = maybe_unserialize( $request->$slug->get_version_check->sections );
							$request->$slug->get_version_check = json_encode( $request->$slug->get_version_check );
						} else {
							$request->$slug->get_version_check = new \WP_Error( 'plugins_api_failed',
								sprintf(
								/* translators: %s: support forums URL */
									__( 'An unexpected error occurred. Something may be wrong with https://ultimatemember.com/ or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
									__( 'https://wordpress.org/support/' )
								),
								wp_remote_retrieve_body( $request->$slug->get_version_check )
							);
						}

						update_option( "{$data['key']}_version_check_edd_answer", $request->$slug->get_version_check );
					}
				}
			}

			return;
		}


		/**
		 * Check for Updates by request to the marketplace
		 * and modify the update array.
		 *
		 * @param array $_transient_data plugin update array build by WordPress.
		 * @return \stdClass modified plugin update array.
		 */
		function um_check_update( $_transient_data ) {
			global $pagenow;

			if ( ! is_object( $_transient_data ) )
				$_transient_data = new \stdClass;

			if ( 'plugins.php' == $pagenow && is_multisite() )
				return $_transient_data;

			$exts = $this->um_get_active_plugins();
			foreach ( $exts as $slug => $data ) {

				$plugin_data = get_plugin_data( ABSPATH . "wp-content/plugins/{$slug}" );

				//if response for current product isn't empty check for override
				if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $slug ] ) )
					continue;

				$version_info = get_option( "{$data['key']}_version_check_edd_answer" );
				$version_info = json_decode( $version_info );

				if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {
					//show update version block if new version > then current
					if ( version_compare( $plugin_data['Version'], $version_info->new_version, '<' ) )
						$_transient_data->response[ $slug ] = $version_info;

					$_transient_data->last_checked      = time();
					$_transient_data->checked[ $slug ]  = $plugin_data['Version'];

				}
			}

			return $_transient_data;
		}


		/**
		 * Updates information on the "View version x.x details" popup with custom data.
		 *
		 * @param mixed   $_data
		 * @param string  $_action
		 * @param object  $_args
		 * @return object $_data
		 */
		function um_plugins_api_filter( $_data, $_action = '', $_args = null ) {
			//by default $data = false (from Wordpress)

			if ( $_action != 'plugin_information' )
				return $_data;

			$exts = $this->um_get_active_plugins();

			foreach ( $exts as $slug => $data ) {
				if ( isset( $_args->slug ) && $_args->slug == $slug )
					$api_request_transient = get_option( "{$data['key']}_version_check_edd_answer" );
			}

			//If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
			if ( ! empty( $api_request_transient ) ) {
				$_data = json_decode( $api_request_transient );
			}

			if ( isset( $_data->sections ) )
				$_data->sections = (array)$_data->sections;

			return $_data;
		}


		/**
		 * Disable SSL verification in order to prevent download update failures
		 *
		 * @param array   $args
		 * @param string  $url
		 * @return array $array
		 */
		function http_request_args( $args, $url ) {
			// If it is an https request and we are performing a package download, disable ssl verification
			if ( strpos( $url, 'https://' ) !== false && strpos( $url, 'action=package_download' ) ) {
				$args['sslverify'] = false;
			}
			return $args;
		}


		/**
		 * Download extension URL
		 *
		 * @param $download_url
		 * @param $slug
		 * @param $data
		 *
		 * @return string
		 */
		function extend_download_url( $download_url, $slug, $data ) {

			$url = get_site_url( get_current_blog_id() );
			$domain  = strtolower( urlencode( rtrim( $url, '/' ) ) );

			$plugin_data = get_plugin_data( ABSPATH . "wp-content/plugins/{$slug}" );

			$api_params = array(
				'action'        => 'get_last_version',
				'license'       => ! empty( $data['license'] ) ? $data['license'] : '',
				'item_name'     => str_replace( 'Ultimate Member - ', '', $plugin_data['Name'] ),
				'blog_id'       => get_current_blog_id(),
				'site_url'      => urlencode( $url ),
				'domain'        => urlencode( $domain ),
				'slug'          => urlencode( $slug ),
			);

			$download_url = add_query_arg( $api_params, $download_url );

			return $download_url;
		}
	}

}