<?php
namespace umm\online\includes;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Profile
 *
 * @package umm\online\includes
 */
class Profile {


	/**
	 * Profile constructor.
	 */
	function __construct() {
		add_action( 'um_after_profile_name_inline', array( &$this, 'show_user_status' ) );
	}


	/**
	 * Show user online status beside name
	 *
	 * @param $args
	 */
	function show_user_status( $args ) {
		if ( UM()->module( 'online' )->user()->is_hidden_status( um_profile_id() ) ) {
			return;
		}

		$args['is_online'] = UM()->module( 'online' )->user()->is_online( um_profile_id() );

		um_get_template( 'online-marker.php', $args, 'online' );
	}
}
