<?php
namespace umm\jobboardwp\includes\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Account
 *
 * @package umm\jobboardwp\includes\frontend
 */
class Account {


	/**
	 * Account constructor.
	 */
	public function __construct() {
		add_filter( 'um_account_page_default_tabs_hook', array( &$this, 'add_account_tab' ), 10, 1 );
		add_filter( 'um_account_content_hook_jobboardwp', array( &$this, 'account_tab' ), 60, 2 );
	}


	/**
	 * @param array $tabs
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	public function add_account_tab( $tabs ) {
		if ( empty( $tabs[700]['jobboardwp'] ) ) {
			$tabs[700]['jobboardwp'] = array(
				'icon'        => 'far fa-list-alt',
				'title'       => __( 'Jobs Dashboard', 'ultimate-member' ),
				'show_button' => false,
			);
		}

		return $tabs;
	}


	/**
	 * @param string $output
	 *
	 * @return string
	 *
	 * @since 1.0
	 */
	public function account_tab( $output, $args ) {
		$output .= '<div class="um-clear"></div><br />' . apply_shortcodes( '[jb_jobs_dashboard /]' );

		return $output;
	}
}
