<?php
namespace umm\online\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Private_Messages
 *
 * @package umm\online\includes\cross_modules
 */
class Private_Messages {


	/**
	 * Private_Messages constructor.
	 */
	public function __construct() {
		add_action( 'um_messaging_conversation_list_name', array( &$this, 'messaging_show_online_dot' ) );
		add_action( 'um_messaging_conversation_list_name_js', array( &$this, 'messaging_show_online_dot_js' ) );
		add_filter( 'um_messaging_conversation_json_data', array( &$this, 'messaging_online_status' ), 10, 1 );
	}


	/**
	 * Show online dot in messaging extension
	 */
	public function messaging_show_online_dot() {
		if ( UM()->module( 'online' )->common()->user()->is_hidden_status( um_user('ID') ) ) {
			return;
		}

		$args['is_online'] = UM()->module( 'online' )->common()->user()->is_online( um_user('ID') );

		um_get_template( 'online-marker.php', $args, 'online' );
	}


	/**
	 * Private Messages online status integration
	 * JS template for conversations list
	 *
	 */
	public function messaging_show_online_dot_js() {
		ob_start();
		?>

		<span class="um-online-status <# if ( conversation.online ) { #>online<# } else { #>offline<# } #>"><i class="fas fa-circle"></i></span>

		<?php
		ob_end_flush();
	}


	/**
	 * Private Messages online status integration
	 *
	 * @param array $conversation
	 *
	 * @return array $conversation
	 */
	public function messaging_online_status( $conversation ) {
		$conversation['online'] = UM()->module( 'online' )->common()->user()->is_online( um_user('ID') );
		return $conversation;
	}
}
