<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\AJAX_Common' ) ) {

	/**
	 * Class AJAX_Common
	 * @package um\core
	 */
	class AJAX_Common {

		/**
		 * AJAX_Common constructor.
		 */
		public function __construct() {
			// UM_EVENT => nopriv
			$ajax_actions = array();

			foreach ( $ajax_actions as $action => $nopriv ) {

				add_action( 'wp_ajax_um_' . $action, array( $this, $action ) );

				if ( $nopriv ) {
					add_action( 'wp_ajax_nopriv_um_' . $action, array( $this, $action ) );
				}
			}

			add_action( 'wp_ajax_um_delete_cover_photo', array( UM()->profile(), 'ajax_delete_cover_photo' ) );

			add_action( 'wp_ajax_um_ajax_paginate', array( UM()->query(), 'ajax_paginate' ) );

			add_action( 'wp_ajax_um_muted_action', array( UM()->form(), 'ajax_muted_action' ) );

			if ( ! UM()->is_new_ui() ) {
				add_action( 'wp_ajax_um_delete_profile_photo', array( UM()->profile(), 'ajax_delete_profile_photo' ) );
				add_action( 'wp_ajax_um_select_options', array( UM()->form(), 'ajax_select_options' ) );
				add_action( 'wp_ajax_nopriv_um_select_options', array( UM()->form(), 'ajax_select_options' ) );
			}
		}
	}
}
