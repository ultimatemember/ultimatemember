<?php
namespace um_ext\um_online\core;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Online_Common
 * @package um_ext\um_online\core
 */
class Online_Common {

	/**
	 * Online_Frontend constructor.
	 */
	function __construct() {
		add_action( 'init', array( &$this, 'log' ), 1 );

		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ), 9999 );

		add_action( 'um_after_profile_name_inline', array( &$this, 'um_online_show_user_status' ) );

		add_filter( 'um_predefined_fields_hook', array( &$this, 'um_online_add_fields' ), 100, 1 );
		add_filter( 'um_account_tab_privacy_fields', array( &$this, 'um_activity_account_online_fields' ), 10, 2 );
		add_filter( 'um_profile_field_filter_hook__online_status', array( &$this, 'um_online_show_status' ), 99, 2 );

		add_action( 'um_messaging_conversation_list_name', array( &$this, 'messaging_show_online_dot' ) );
		add_action( 'um_messaging_conversation_list_name_js', array( &$this, 'messaging_show_online_dot_js' ) );
		add_filter( 'um_messaging_conversation_json_data', array( &$this, 'messaging_online_status' ), 10, 1 );

		add_action( 'um_delete_user',  array( $this, 'clear_online_user' ), 10, 1 );

		add_action( 'clear_auth_cookie', array( $this, 'clear_auth_cookie_clear_online_user' ), 10 );

		add_filter( 'um_rest_api_get_stats', array( &$this, 'rest_api_get_stats' ), 10, 1 );

		// Friends
		add_filter( 'um_friends_online_users', array( $this, 'get_online_users' ) );

		add_filter( 'um_settings_structure', array( $this, 'admin_settings' ), 10, 1 );
	}


	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	function admin_settings( $settings ) {

		$key = ! empty( $settings['extensions']['sections'] ) ? 'online' : '';
		$settings['extensions']['sections'][ $key ] = array(
			'title'     => __( 'Online', 'ultimate-member' ),
			'fields'    => array(
				array(
					'id'    => 'online_show_stats',
					'type'  => 'checkbox',
					'label' => __( 'Show online stats in member directory', 'ultimate-member' ),
				),
			)
		);

		return $settings;
	}


	/**
	 * Logs online user
	 */
	function log() {
		// Guest or not on frontend
		if ( is_admin() || ! is_user_logged_in() ) {
			return;
		}

		// User privacy do not allow that
		if ( $this->is_hidden_status( get_current_user_id() ) ) {
			return;
		}

		// We have a logged in user
		// Store the user as online with a timestamp of last seen
		UM()->Online()->users[ get_current_user_id() ] = current_time( 'timestamp' );

		// Save the new online users
		update_option('um_online_users', UM()->Online()->users );
	}


	/**
	 * Register custom scripts
	 */
	function wp_enqueue_scripts() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || defined( 'UM_SCRIPT_DEBUG' ) ) ? '' : '.min';

		wp_register_script( 'um-online', um_online_url . 'assets/js/um-online' . $suffix . '.js', array( 'jquery' ), um_online_version, true );
		wp_register_style( 'um-online', um_online_url . 'assets/css/um-online' . $suffix . '.css', array( 'um_styles' ), um_online_version );
	}


	/**
	 * Show user online status beside name
	 *
	 * @param $args
	 */
	function um_online_show_user_status( $args ) {
		if ( $this->is_hidden_status( um_profile_id() ) ) {
			return;
		}

		UM()->Online()->enqueue_scripts();

		$args['is_online'] = UM()->Online()->is_online( um_profile_id() );

		ob_start();

		UM()->get_template( 'online-marker.php', um_online_plugin, $args, true );

		ob_end_flush();
	}


	/**
	 * Extends core fields
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	function um_online_add_fields( $fields ) {

		$fields['_hide_online_status'] = array(
			'title'         => __( 'Show my online status?', 'ultimate-member' ),
			'metakey'       => '_hide_online_status',
			'type'          => 'radio',
			'label'         => __( 'Show my online status?', 'ultimate-member' ),
			'help'          => __( 'Do you want other people to see that you are online?', 'ultimate-member' ),
			'required'      => 0,
			'public'        => 1,
			'editable'      => 1,
			'default'       => 'yes',
			'options'       => array( 'yes' => __( 'Yes', 'ultimate-member' ), 'no' => __( 'No', 'ultimate-member' ) ),
			'account_only'  => true,
		);

		UM()->account()->add_displayed_field( '_hide_online_status', 'privacy' );

		$fields['online_status'] = array(
			'title'             => __( 'Online Status', 'ultimate-member' ),
			'metakey'           => 'online_status',
			'type'              => 'text',
			'label'             => __( 'Online Status', 'ultimate-member' ),
			'edit_forbidden'    => 1,
			'show_anyway'       => true,
			'custom'            => true,
		);

		return $fields;
	}


	/**
	 * Shows the online field in account page
	 *
	 * @param string $args
	 * @param array $shortcode_args
	 *
	 * @return string
	 */
	function um_activity_account_online_fields( $args, $shortcode_args ) {
		return $args . ',_hide_online_status';
	}


	/**
	 * Shows the online status
	 *
	 * @param $value
	 * @param $data
	 *
	 * @return string
	 */
	function um_online_show_status( $value, $data ) {
		if ( $this->is_hidden_status( um_user('ID') ) ) {
			return $value;
		}

		UM()->Online()->enqueue_scripts();

		$args['is_online'] = UM()->Online()->is_online( um_user('ID') );

		ob_start();

		UM()->get_template( 'online-text.php', um_online_plugin, $args, true );

		$output = ob_get_clean();
		return $output;
	}


	/**
	 * Show online dot in messaging extension
	 */
	function messaging_show_online_dot() {
		if ( $this->is_hidden_status( um_user('ID') ) ) {
			return;
		}

		UM()->Online()->enqueue_scripts();

		$args['is_online'] = UM()->Online()->is_online( um_user('ID') );

		ob_start();

		UM()->get_template( 'online-marker.php', um_online_plugin, $args, true );

		ob_end_flush();
	}


	/**
	 * Private Messages online status integration
	 * JS template for conversations list
	 *
	 */
	function messaging_show_online_dot_js() {
		ob_start(); ?>

		<span class="um-online-status <# if ( conversation.online ) { #>online<# } else { #>offline<# } #>"><i class="um-faicon-circle"></i></span>

		<?php ob_end_flush();
	}


	/**
	 * Private Messages online status integration
	 *
	 * @param array $conversation
	 *
	 * @return array $conversation
	 */
	function messaging_online_status( $conversation ) {
		$conversation['online'] = UM()->Online()->is_online( um_user('ID') );
		return $conversation;
	}


	/**
	 * Make the user offline
	 *
	 * @param $user_id
	 */
	function clear_online_user( $user_id ) {
		$online_users = UM()->Online()->get_users();

		if ( ! empty( $online_users[ $user_id ] ) ) {
			unset( $online_users[ $user_id ] );
			update_option( 'um_online_users', $online_users );

			update_option( 'um_online_users_last_updated', time() );
		}
	}


	/**
	 * Remove online user on logout process
	 */
	function clear_auth_cookie_clear_online_user() {
		$userinfo = wp_get_current_user();

		if ( ! empty( $userinfo->ID ) ) {
			$this->clear_online_user( $userinfo->ID );
		}
	}


	/**
	 * Get online users count via REST API
	 *
	 * @param $response
	 *
	 * @return mixed
	 */
	function rest_api_get_stats( $response ) {
		$users = UM()->Online()->get_users();
		$response['stats']['total_online'] = $users ? count( $users ) : 0;
		return $response;
	}


	/**
	 * If user set hidden online status
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	function is_hidden_status( $user_id ) {
		$_hide_online_status = get_user_meta( $user_id, '_hide_online_status', true );
		if ( $_hide_online_status == 1 || ( isset( $_hide_online_status[0] ) && $_hide_online_status[0] == 'no' ) ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Return an array of online users ID
	 *
	 * @param array $online_user_ids
	 *
	 * @return array
	 */
	public function get_online_users( $online_user_ids = array() ) {
		$online = UM()->Online()->get_users();
		if ( is_array( $online ) ) {
			$online_user_ids = array_keys( $online );
		}

		return $online_user_ids;
	}
}