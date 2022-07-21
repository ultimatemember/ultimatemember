<?php
namespace umm\terms_conditions\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'umm\terms_conditions\includes\admin\Site_Health' ) ) {


	/**
	 * Class Site_Health
	 *
	 * @package um\admin
	 */
	class Site_Health {


		/**
		 * Site_Health constructor.
		 */
		public function __construct() {
			add_action( 'um_debug_information_register_form', array( &$this, 'um_debug_information_register_form' ), 21, 2 );
		}


		/**
		 * Extend register form info.
		 *
		 * @since 3.0
		 *
		 * @param array $info
		 * @param int $key
		 *
		 * @return array
		 */
		public function um_debug_information_register_form( $info, $key ) {
			$info['ultimate-member-' . $key ]['fields'] = array_merge(
				$info['ultimate-member-' . $key ]['fields'],
				array(
					'um-register_use_terms_conditions' => array(
						'label' => __( 'T&C Enable on this form', 'ultimate-member' ),
						'value' => get_post_meta( $key, '_um_register_use_terms_conditions', true ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
					),
				)
			);

			if ( 1 == get_post_meta( $key, '_um_register_use_terms_conditions', true ) ) {
				$page_id = get_post_meta( $key, '_um_register_use_terms_conditions_content_id', true );
				$content = '';
				if ( $page_id ) {
					$content = get_the_title( $page_id ) . ' (ID#' . $page_id . ') | ' . get_permalink( $page_id );
				}
				$info['ultimate-member-' . $key ]['fields'] = array_merge(
					$info['ultimate-member-' . $key ]['fields'],
					array(
						'um-register_use_terms_conditions_content_id' => array(
							'label' => __( 'T&C Content', 'ultimate-member' ),
							'value' => $content,
						),
						'um-register_use_terms_conditions_toggle_show' => array(
							'label' => __( 'T&C Toggle Show text', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_register_use_terms_conditions_toggle_show', true ),
						),
						'um-register_use_terms_conditions_toggle_hide' => array(
							'label' => __( 'T&C Toggle Hide text', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_register_use_terms_conditions_toggle_hide', true ),
						),
						'um-register_use_terms_conditions_agreement' => array(
							'label' => __( 'T&C Checkbox agreement description', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_register_use_terms_conditions_agreement', true ),
						),
						'um-register_use_terms_conditions_error_text' => array(
							'label' => __( 'T&C Error Text', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_register_use_terms_conditions_error_text', true ),
						),
					)
				);
			}

			return $info;
		}
	}
}
