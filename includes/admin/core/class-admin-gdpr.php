<?php
namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\core\Admin_GDPR' ) ) {


	/**
	 * Class Admin_GDPR
	 * @package um\admin\core
	 */
	class Admin_GDPR {

		/**
		 * @var array
		 */
		var $meta_associations = array();


		/**
		 * Admin_GDPR constructor.
		 */
		function __construct() {
			add_action( 'init', array( &$this, 'init_fields' ), 10 );
			add_action( 'admin_init', array( &$this, 'plugin_add_suggested_privacy_content' ), 20 );
			add_filter( 'wp_privacy_personal_data_exporters', array( &$this, 'plugin_register_exporters' ) );
			add_filter( 'wp_privacy_personal_data_erasers', array( &$this, 'plugin_register_erasers' ) );

			add_action( 'um_admin_custom_register_metaboxes', array( &$this, 'add_metabox_register' ) );
		}


		/**
		 *
		 */
		function add_metabox_register() {
			add_meta_box(
				"um-admin-form-register_gdpr",
				__( 'Privacy Policy', 'ultimate-member' ),
				array( UM()->metabox(), 'load_metabox_form' ),
				'um_form',
				'side',
				'default'
			);
		}


		/**
		 *
		 */
		function init_fields() {
			$this->meta_associations = array(

				'account_status'                        => __( 'Account Status', 'ultimate-member' ),
				'submitted'                             => __( 'Submitted data on Registration', 'ultimate-member' ),
				'form_id'                               => __( 'Registration Form ID', 'ultimate-member' ),
				'timestamp'                             => __( 'Registration Timestamp', 'ultimate-member' ),
				'request'                               => __( 'Registration Request', 'ultimate-member' ),
				'_wpnonce'                              => __( 'Registration Nonce', 'ultimate-member' ),
				'_wp_http_referer'                      => __( 'Registration HTTP referer', 'ultimate-member' ),
				'role'                                  => __( 'Community Role', 'ultimate-member' ),
				'um_user_profile_url_slug_user_login'   => __( 'Profile Slug "Username"', 'ultimate-member' ),
				'um_user_profile_url_slug_name'         => __( 'Profile Slug "First and Last Name with \'.\'"', 'ultimate-member' ),
				'um_user_profile_url_slug_name_dash'    => __( 'Profile Slug "First and Last Name with \'-\'"', 'ultimate-member' ),
				'um_user_profile_url_slug_name_plus'    => __( 'Profile Slug "First and Last Name with \'+\'"', 'ultimate-member' ),
				'um_user_profile_url_slug_user_id'      => __( 'Profile Slug "User ID"', 'ultimate-member' ),
				'_um_last_login'                        => __( 'Last Login Timestamp', 'ultimate-member' ),

				//Private content extension
				'_um_private_content_post_id'           => __( 'Private Content Post ID', 'ultimate-member' ),

				//Verified Users extension
				'_um_verified'                          => __( 'Verified Account', 'ultimate-member' ),

				//Terms & Conditions extension
				'use_terms_conditions_agreement'        => __( 'Terms&Conditions Agreement', 'ultimate-member' ),

				//GDPR extension
				'use_gdpr_agreement'                    => __( 'Privacy Policy Agreement', 'ultimate-member' ),


			);

			$all_fields = UM()->builtin()->all_user_fields( null, true );
			unset( $all_fields[0] );

			$all_fields = array_map( function( $value ) {
				return $value['title'];
			}, $all_fields );

			$this->meta_associations = array_merge( $this->meta_associations, $all_fields );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_gdpr_meta_associations
			 * @description Exclude taxonomies for UM
			 * @input_vars
			 * [{"var":"$meta_associations","type":"array","desc":"Meta Keys Titles"}]
			 * @change_log
			 * ["Since: 2.0.14"]
			 * @usage
			 * <?php add_filter( 'um_gdpr_meta_associations', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_gdpr_meta_associations', 'my_gdpr_meta_associations', 10, 1 );
			 * function my_gdpr_meta_associations( $meta_associations ) {
			 *     // your code here
			 *     return $meta_associations;
			 * }
			 * ?>
			 */
			$this->meta_associations = apply_filters( 'um_gdpr_meta_associations', $this->meta_associations );
		}


		/**
		 * Return the default suggested privacy policy content.
		 *
		 * @return string The default policy content.
		 */
		function plugin_get_default_privacy_content() {
			ob_start();

			include UM()->admin()->templates_path . 'gdpr.php';

			return ob_get_clean();
		}


		/**
		 * Add the suggested privacy policy text to the policy postbox.
		 */
		function plugin_add_suggested_privacy_content() {
			$content = $this->plugin_get_default_privacy_content();
			wp_add_privacy_policy_content( UM_PLUGIN_NAME, $content );
		}


		/**
		 * Register exporter for Plugin user data.
		 *
		 * @see https://github.com/allendav/wp-privacy-requests/blob/master/EXPORT.md
		 *
		 * @param $exporters
		 *
		 * @return array
		 */
		function plugin_register_exporters( $exporters ) {
			$exporters[] = array(
				'exporter_friendly_name' => UM_PLUGIN_NAME,
				'callback'               => array( &$this, 'data_exporter' )
			);
			return $exporters;
		}


		/**
		 * Get user metadata in key => value array
		 *
		 *
		 * @param $user_id
		 *
		 * @return array
		 */
		function get_metadata( $user_id ) {
			global $wpdb;

			$metadata = $wpdb->get_results( $wpdb->prepare(
				"SELECT meta_key, meta_value
				FROM {$wpdb->usermeta}
				WHERE user_id = %d",
				$user_id
			), ARRAY_A );

			$filtered = array();
			foreach ( $metadata as $data ) {
				if ( in_array( $data['meta_key'], array_keys( $this->meta_associations ) ) ) {
					$filtered[] = array(
						'key'  => $data['meta_key'],
						'name'  => $this->meta_associations[ $data['meta_key'] ],
						//'value' => maybe_unserialize( $data['meta_value'] ),
						'value' => $data['meta_value'],
					);
				}
			}

			return $filtered;
		}


		/**
		 * Exporter for Plugin user data.
		 *
		 * @see https://github.com/allendav/wp-privacy-requests/blob/master/EXPORT.md
		 *
		 * @param     $email_address
		 * @param int $page
		 *
		 * @return array
		 */
		function data_exporter( $email_address, $page = 1 ) {
			$export_items = array();
			$user = get_user_by( 'email', $email_address );

			if ( $user && $user->ID ) {
				// Most item IDs should look like postType-postID
				// If you don't have a post, comment or other ID to work with,
				// use a unique value to avoid having this item's export
				// combined in the final report with other items of the same id
				$item_id = "ultimate-member-{$user->ID}";

				// Core group IDs include 'comments', 'posts', etc.
				// But you can add your own group IDs as needed
				$group_id = 'ultimate-member';

				// Optional group label. Core provides these for core groups.
				// If you define your own group, the first exporter to
				// include a label will be used as the group label in the
				// final exported report
				$group_label = UM_PLUGIN_NAME;

				// Plugins can add as many items in the item data array as they want
				//$data = array();

				$data = $this->get_metadata( $user->ID );

				if ( ! empty( $data ) ) {
					// Add this group of items to the exporters data array.
					$export_items[] = array(
						'group_id'    => $group_id,
						'group_label' => $group_label,
						'item_id'     => $item_id,
						'data'        => $data,
					);
				}
			}
			// Returns an array of exported items for this pass, but also a boolean whether this exporter is finished.
			//If not it will be called again with $page increased by 1.
			return array(
				'data' => $export_items,
				'done' => true,
			);
		}


		/**
		 * Register eraser for Plugin user data.
		 *
		 * @param array $erasers
		 *
		 * @return array
		 */
		function plugin_register_erasers( $erasers = array() ) {
			$erasers[] = array(
				'eraser_friendly_name'  => UM_PLUGIN_NAME,
				'callback'              => array( &$this, 'data_eraser' )
			);
			return $erasers;
		}


		/**
		 * Eraser for Plugin user data.
		 *
		 * @param     $email_address
		 * @param int $page
		 *
		 * @return array
		 */
		function data_eraser( $email_address, $page = 1 ) {
			if ( empty( $email_address ) ) {
				return array(
					'items_removed'  => false,
					'items_retained' => false,
					'messages'       => array(),
					'done'           => true,
				);
			}

			$user = get_user_by( 'email', $email_address );
			$messages = array();
			$items_removed  = false;
			$items_retained = false;

			if ( $user && $user->ID ) {
				$data = $this->get_metadata( $user->ID );

				foreach ( $data as $metadata ) {
					$deleted = delete_user_meta( $user->ID, $metadata['key'] );
					if ( $deleted ) {
						$items_removed = true;
					} else {
						// translators: %s: metadata name.
						$messages[]     = sprintf( __( 'Your %s was unable to be removed at this time.', 'ultimate-member' ), $metadata['name'] );
						$items_retained = true;
					}
				}
			}

			// Returns an array of exported items for this pass, but also a boolean whether this exporter is finished.
			//If not it will be called again with $page increased by 1.
			return array(
				'items_removed'  => $items_removed,
				'items_retained' => $items_retained,
				'messages'       => $messages,
				'done'           => true,
			);
		}

	}

}
