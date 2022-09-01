<?php
/**
 * Provides class for getting operated with Ultimate Member Predefined pages.
 *
 * @package um\common
 */
namespace um\common;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\common\Pages' ) ) {


	/**
	 * Class Pages.
	 *
	 * @example UM()->common()->pages;
	 * @package um\common
	 */
	class Pages {

		/**
		 * Predefined pages slugs that must be deleted
		 *
		 * @var array
		 */
		private $removing_slugs = array();

		/**
		 * Pages constructor.
		 */
		public function __construct() {
			add_action( 'delete_post', array( &$this, 'delete_predefined_page' ), 10, 2 );
			add_action( 'after_delete_post', array( &$this, 'maybe_delete_predefined_page_option' ), 10 );
		}

		/**
		 * @param int      $postid
		 * @param \WP_Post $post
		 *
		 * @uses um_is_predefined_page()
		 */
		public function delete_predefined_page( $postid, $post ) {
			if ( ! isset( $post->post_type ) || 'page' !== $post->post_type ) {
				return;
			}

			$predefined_pages = UM()->config()->get( 'predefined_pages' );
			if ( empty( $predefined_pages ) ) {
				return;
			}

			foreach ( array_keys( $predefined_pages ) as $slug ) {
				if ( um_is_predefined_page( $slug, $post ) ) {
					$this->removing_slugs[] = $slug;
				}
			}
		}

		/**
		 * Remove predefined page ID from UM Options when this page is deleted
		 */
		public function maybe_delete_predefined_page_option() {
			if ( empty( $this->removing_slugs ) ) {
				return;
			}

			foreach ( $this->removing_slugs as $slug ) {
				$option = UM()->common()->options()->get_predefined_page_option_key( $slug );
				UM()->options()->remove( $option );
			}
		}
	}
}
