<?php if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class UM_Online
 */
class UM_Online {


	/**
	 * @var array
	 */
	var $users;


	/**
	 * @var
	 */
	private static $instance;


	/**
	 * @return UM_Online
	 */
	static public function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * UM_Online constructor.
	 */
	function __construct() {
		// Global for backwards compatibility.
		add_filter( 'um_call_object_Online', array( &$this, 'get_this' ) );

		$this->init();

		$this->common();
		if ( UM()->is_request( 'frontend' ) ) {
			$this->shortcode();
		}

		$this->member_directory();

		require_once um_online_path . 'includes/core/um-online-widget.php';
		add_action( 'widgets_init', array( &$this, 'widgets_init' ) );
	}


	/**
	 * For using UM()->Online() function in plugin
	 *
	 * @return $this
	 */
	function get_this() {
		return $this;
	}


	/**
	 * Init variables
	 */
	function init() {
		$this->users = get_option( 'um_online_users' );
		$this->schedule_update();
	}


	/**
	 * @return um_ext\um_online\core\Online_Common()
	 */
	function common() {
		if ( empty( UM()->classes['um_online_common'] ) ) {
			UM()->classes['um_online_common'] = new um_ext\um_online\core\Online_Common();
		}
		return UM()->classes['um_online_common'];
	}


	/**
	 * @return um_ext\um_online\core\Online_Shortcode()
	 */
	function shortcode() {
		if ( empty( UM()->classes['um_online_shortcode'] ) ) {
			UM()->classes['um_online_shortcode'] = new um_ext\um_online\core\Online_Shortcode();
		}
		return UM()->classes['um_online_shortcode'];
	}


	/**
	 * @return um_ext\um_online\core\Online_Member_Directory()
	 */
	function member_directory() {
		if ( empty( UM()->classes['um_online_member_directory'] ) ) {
			UM()->classes['um_online_member_directory'] = new um_ext\um_online\core\Online_Member_Directory();
		}
		return UM()->classes['um_online_member_directory'];
	}


	/**
	 * Init Online users widget
	 */
	function widgets_init() {
		register_widget( 'um_online_users' );
	}


	/**
	 * Gets users online
	 *
	 * @return bool|array
	 */
	function get_users() {
		if ( ! empty( $this->users ) && is_array( $this->users ) ) {
			arsort( $this->users ); // this will get us the last active user first
			return $this->users;
		}
		return false;
	}


	/**
	 * Checks if user is online
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	function is_online( $user_id ) {
		return isset( $this->users[ $user_id ] );
	}


	/**
	 * Update the online users
	 */
	private function schedule_update() {
		// Send a maximum of once per period
		$minute_interval = apply_filters( 'um_online_interval', 15 ); // minutes

		$last_send = get_option( 'um_online_users_last_updated' );
		if ( $last_send && $last_send > strtotime( "-{$minute_interval} minutes" ) ) {
			return;
		}

		// We have to check if each user was last seen in the previous x
		if ( is_array( $this->users ) ) {
			foreach( $this->users as $user_id => $last_seen ) {
				if ( ( current_time('timestamp') - $last_seen ) > ( 60 * $minute_interval ) ) {
					// Time now is more than x since he was last seen
					// Remove user from online list
					unset( $this->users[ $user_id ] );
				}
			}
			update_option('um_online_users', $this->users );
		}

		update_option( 'um_online_users_last_updated', time() );
	}


	/**
	 * Enqueue necessary scripts
	 */
	function enqueue_scripts() {
		wp_enqueue_script( 'um-online' );
		wp_enqueue_style( 'um-online' );
	}
}

//create class var
add_action( 'plugins_loaded', 'um_init_online', -10, 1 );
function um_init_online() {
	if ( function_exists( 'UM' ) ) {
		UM()->set_class( 'Online', true );
	}
}