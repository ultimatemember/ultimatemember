<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Modal
 *
 * @package um\frontend
 */
class Account {

	/**
	 * Modal constructor.
	 */
	public function __construct() {
		add_action( 'um_layouts_before_tabs_list', array( &$this, 'back_button' ) );
	}

	/**
	 * Load modal content.
	 */
	public function back_button( $args ) {
		if ( array_key_exists( 'id', $args ) && 0 === strpos( $args['id'], 'um-account-navigation-' ) ) {
			$back_button = UM()->frontend()::layouts()::link(
				__( 'Back to Profile', 'ultimate-member' ),
				array(
					'title'   => __( 'Back to Profile', 'ultimate-member' ),
					'type'    => 'button',
					'url'     => um_user_profile_url( get_current_user_id() ),
					'design'  => 'secondary-gray',
					'size'    => 's',
					'classes' => array(
						'um-account-back-to-profile',
					),
				)
			);

			echo wp_kses( $back_button, UM()->get_allowed_html( 'templates' ) );
		}
	}
}
