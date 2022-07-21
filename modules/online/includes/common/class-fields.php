<?php
namespace umm\online\includes\common;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Fields
 *
 * @package umm\online\includes\common
 */
class Fields {


	/**
	 * Fields constructor.
	 */
	function __construct() {
		add_filter( 'um_predefined_fields_hook', array( &$this, 'add_fields' ), 100, 1 );
		add_filter( 'um_profile_field_filter_hook__online_status', array( &$this, 'online_status_field' ), 99, 1 );
	}


	/**
	 * Extends core fields
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	function add_fields( $fields ) {
		$fields['_hide_online_status'] = array(
			'title'        => __( 'Show my online status?', 'ultimate-member' ),
			'metakey'      => '_hide_online_status',
			'type'         => 'radio',
			'label'        => __( 'Show my online status?', 'ultimate-member' ),
			'help'         => __( 'Do you want other people to see that you are online?', 'ultimate-member' ),
			'required'     => 0,
			'public'       => 1,
			'editable'     => 1,
			'default'      => 'yes',
			'options'      => array(
				'yes' => __( 'Yes', 'ultimate-member' ),
				'no'  => __( 'No', 'ultimate-member' ),
			),
			'account_only' => true,
		);

		UM()->account()->add_displayed_field( '_hide_online_status', 'privacy' );

		$fields['online_status'] = array(
			'title'          => __( 'Online Status', 'ultimate-member' ),
			'metakey'        => 'online_status',
			'type'           => 'text',
			'label'          => __( 'Online Status', 'ultimate-member' ),
			'edit_forbidden' => 1,
			'show_anyway'    => true,
			'custom'         => true,
		);

		return $fields;
	}


	/**
	 * Shows the online status
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	function online_status_field( $value ) {
		$user_id = UM()->frontend()->user()->get_id();

		if ( UM()->module( 'online' )->common()->user()->is_hidden_status( $user_id ) ) {
			return $value;
		}

		$args['is_online'] = UM()->module( 'online' )->common()->user()->is_online( $user_id );

		return um_get_template_html( 'online-text.php', $args, 'online' );
	}
}
