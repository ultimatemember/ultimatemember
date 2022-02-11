<?php
namespace umm\jobboardwp\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class User_Bookmarks
 *
 * @package uumm\jobboardwp\includes\cross_modules
 */
class User_Bookmarks {


	/**
	 * User_Bookmarks constructor.
	 */
	function __construct() {
		add_filter( 'jb_jobs_job_data_response', array( &$this, 'add_bookmarks_action' ), 10, 2 );
		add_filter( 'um_bookmarks_add_button_args', array( $this, 'remove_text_ajax' ), 10, 1 );
		add_filter( 'um_bookmarks_remove_button_args', array( $this, 'remove_text_ajax' ), 10, 1 );
	}

	/**
	 * @param array $job_data
	 * @param \WP_Post $job_post
	 *
	 * @return mixed
	 */
	function add_bookmarks_action( $job_data, $job_post ) {
		if ( ! is_user_logged_in() ) {
			return $job_data;
		}

		add_filter( 'um_bookmarks_add_button_args', array( $this, 'remove_text' ), 10, 1 );
		add_filter( 'um_bookmarks_remove_button_args', array( $this, 'remove_text' ), 10, 1 );

		$button = UM()->User_Bookmarks()->common()->get_bookmarks_button( $job_post->ID, false );

		if ( ! empty( $button ) ) {
			$job_data['actions'][] = array(
				'html' => $button,
			);
		}

		remove_filter( 'um_bookmarks_add_button_args', array( $this, 'remove_text' ) );
		remove_filter( 'um_bookmarks_remove_button_args', array( $this, 'remove_text' ) );

		return $job_data;
	}


	/**
	 * @param array $button_args
	 *
	 * @return array
	 */
	function remove_text_ajax( $button_args ) {
		if ( ! UM()->is_request( 'ajax' ) ) {
			return $button_args;
		}

		if ( ! empty( $button_args['post_id'] ) && ! empty( $_REQUEST['job_list'] ) ) {
			$post = get_post( $button_args['post_id'] );
			if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
				if ( 'jb-job' === $post->post_type ) {
					$button_args['text'] = '';
				}
			}
		}

		return $button_args;
	}


	/**
	 * @param array $button_args
	 *
	 * @return array
	 */
	function remove_text( $button_args ) {
		if ( ! empty( $button_args['post_id'] ) ) {
			$post = get_post( $button_args['post_id'] );
			if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
				if ( 'jb-job' === $post->post_type ) {
					$button_args['text'] = '';
				}
			}
		}

		return $button_args;
	}
}
