<?php
namespace um\admin\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\core\Admin_Metabox' ) ) {


	/**
	 * Class Admin_Metabox
	 *
	 * @package um\admin\core
	 */
	class Admin_Metabox {


		/**
		 * @var bool
		 */
		private $form_nonce_added = false;


		/**
		 * @var bool
		 */
		private $directory_nonce_added = false;


		/**
		 * @var bool
		 */
		private $custom_nonce_added = false;


		/**
		 * Admin_Metabox constructor.
		 */
		function __construct() {
			$this->in_edit = false;
			$this->edit_mode_value = null;

			add_action( 'admin_head', array( &$this, 'admin_head' ), 9);
			add_action( 'admin_footer', array( &$this, 'load_modal_content' ), 9);

			add_action( 'load-post.php', array( &$this, 'add_metabox' ), 9 );
			add_action( 'load-post-new.php', array( &$this, 'add_metabox' ), 9 );

			add_action( 'admin_init', array( &$this, 'add_taxonomy_metabox' ), 9 );

			//roles metaboxes
			add_action( 'um_roles_add_meta_boxes', array( &$this, 'add_metabox_role' ) );

			add_filter( 'um_builtin_validation_types_continue_loop', array( &$this, 'validation_types_continue_loop' ), 1, 4 );
			add_filter( 'um_restrict_content_hide_metabox', array( &$this, 'hide_metabox_restrict_content_shop' ), 10, 1 );

			add_filter( 'um_member_directory_meta_value_before_save', array( UM()->member_directory(), 'before_save_data' ), 10, 3 );
		}


		/**
		 * Hide Woocommerce Shop page restrict content metabox
		 * @param $hide
		 *
		 * @return bool
		 */
		function hide_metabox_restrict_content_shop( $hide ) {
			if ( function_exists( 'wc_get_page_id' ) && ! empty( $_GET['post'] ) &&
			     absint( $_GET['post'] ) == wc_get_page_id( 'shop' ) ) {
				return true;
			}

			return $hide;
		}


		/**
		 * Filter validation types on loop
		 *
		 * @param $break
		 * @param $key
		 * @param $form_id
		 * @param $field_array
		 *
		 * @return bool
		 */
		function validation_types_continue_loop( $break, $key, $form_id, $field_array ) {

			// show unique username validation only for user_login field
			if ( isset( $field_array['metakey'] ) && $field_array['metakey'] == 'user_login' && $key !== 'unique_username' ) {
				return false;
			}

			return $break;
		}


		/**
		 * Gets the role meta
		 *
		 * @param $id
		 *
		 * @return mixed
		 */
		function get_custom_post_meta( $id ) {
			$all_meta = get_post_custom( $id );
			foreach ( $all_meta as $k => $v ) {
				if ( strstr( $k, '_um_' ) ) {
					$um_meta[ $k ] = $v;
				}
			}
			if ( isset( $um_meta ) ) {
				return $um_meta;
			}

			return false;
		}


		/**
		 * Runs on admin head
		 */
		function admin_head(){
			global $post;
			if ( UM()->admin()->is_plugin_post_type() && isset($post->ID) ){
				$this->postmeta = $this->get_custom_post_meta($post->ID);
			}
		}


		/**
		 * Init the metaboxes
		 */
		function add_metabox() {
			global $current_screen;

			if ( $current_screen->id == 'um_form' ) {
				add_action( 'add_meta_boxes', array(&$this, 'add_metabox_form'), 1 );
				add_action( 'save_post', array(&$this, 'save_metabox_form'), 10, 2 );
			}

			if ( $current_screen->id == 'um_directory' ) {
				add_action( 'add_meta_boxes', array(&$this, 'add_metabox_directory'), 1 );
				add_action( 'save_post', array(&$this, 'save_metabox_directory'), 10, 2 );
			}

			//restrict content metabox
			$post_types = UM()->options()->get( 'restricted_access_post_metabox' );
			if ( ! empty( $post_types[ $current_screen->id ] ) ) {

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_restrict_content_hide_metabox
				 * @description Show/Hide Restrict content metabox
				 * @input_vars
				 * [{"var":"$show","type":"bool","desc":"Show Metabox"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_restrict_content_hide_metabox', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_restrict_content_hide_metabox', 'my_restrict_content_hide_metabox', 10, 1 );
				 * function my_restrict_content_hide_metabox( $show ) {
				 *     // your code here
				 *     return $show;
				 * }
				 * ?>
				 */
				$hide_metabox = apply_filters( 'um_restrict_content_hide_metabox', false );

				if ( ! $hide_metabox ) {
					add_action( 'add_meta_boxes', array(&$this, 'add_metabox_restrict_content'), 1 );
					add_action( 'save_post', array( &$this, 'save_metabox_restrict_content' ), 10, 2 );
				}

				if ( $current_screen->id == 'attachment' ) {
					add_action( 'add_attachment', array( &$this, 'save_attachment_metabox_restrict_content' ), 10, 2 );
					add_action( 'edit_attachment', array( &$this, 'save_attachment_metabox_restrict_content' ), 10, 2 );
				}
			}


			add_action( 'save_post', array( &$this, 'save_metabox_custom' ), 10, 2 );
		}


		/**
		 * @param $post_id
		 * @param $post
		 */
		function save_metabox_custom( $post_id, $post ) {
			// validate nonce
			if ( ! isset( $_POST['um_admin_save_metabox_custom_nonce'] ) ||
			     ! wp_verify_nonce( $_POST['um_admin_save_metabox_custom_nonce'], basename( __FILE__ ) ) ) {
				return;
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_custom_restrict_content_metaboxes
			 * @description Save metabox custom with restrict content
			 * @input_vars
			 * [{"var":"$post_id","type":"int","desc":"Post ID"},
			 * {"var":"$post","type":"array","desc":"Post data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_custom_restrict_content_metaboxes', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_custom_restrict_content_metaboxes', 'my_admin_custom_restrict_content', 10, 2 );
			 * function my_admin_custom_restrict_content( $post_id, $post ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_custom_restrict_content_metaboxes', $post_id, $post );
		}


		/**
		 *
		 */
		function add_metabox_restrict_content() {
			global $current_screen;

			add_meta_box(
				'um-admin-restrict-content',
				__( 'UM Content Restriction', 'ultimate-member' ),
				array( &$this, 'restrict_content_cb' ),
				$current_screen->id,
				'normal',
				'default'
			);

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_custom_restrict_content_metaboxes
			 * @description Add restrict content custom metabox
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_custom_restrict_content_metaboxes', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_custom_restrict_content_metaboxes', 'my_admin_custom_restrict_content', 10 );
			 * function my_admin_custom_restrict_content() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_custom_restrict_content_metaboxes' );
		}


		/**
		 * Content restriction metabox
		 *
		 * @param $object
		 * @param $box
		 */
		function restrict_content_cb( $object, $box ) {
			include_once UM()->admin()->templates_path . 'access/restrict_content.php';
			wp_nonce_field( basename( __FILE__ ), 'um_admin_save_metabox_restrict_content_nonce' );
		}


		/**
		 * Init the metaboxes
		 */
		function add_taxonomy_metabox() {
			//restrict content metabox
			$all_taxonomies = get_taxonomies( array( 'public' => true ) );
			$tax_types = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
			$exclude_taxonomies = UM()->excluded_taxonomies();

			foreach ( $all_taxonomies as $key => $taxonomy ) {
				if ( in_array( $key, $exclude_taxonomies ) || empty( $tax_types[$key] ) )
					continue;

				add_action( $taxonomy . '_add_form_fields', array( &$this, 'um_category_access_fields_create' ) );
				add_action( $taxonomy . '_edit_form_fields', array( &$this, 'um_category_access_fields_edit' ) );
				add_action( 'create_' . $taxonomy, array( &$this, 'um_category_access_fields_save' ) );
				add_action( 'edited_' . $taxonomy, array( &$this, 'um_category_access_fields_save' ) );
			}
		}


		/**
		 * @param $post_id
		 * @param $post
		 */
		function save_metabox_restrict_content( $post_id, $post ) {
			// validate nonce
			if ( ! isset( $_POST['um_admin_save_metabox_restrict_content_nonce'] ) ||
			     ! wp_verify_nonce( $_POST['um_admin_save_metabox_restrict_content_nonce'], basename( __FILE__ ) ) ) {
				return;
			}

			// validate user
			$post_type = get_post_type_object( $post->post_type );
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
				return;
			}

			if ( ! empty( $_POST['um_content_restriction'] ) && is_array( $_POST['um_content_restriction'] ) ) {
				update_post_meta( $post_id, 'um_content_restriction', $_POST['um_content_restriction'] );
			} else {
				delete_post_meta( $post_id, 'um_content_restriction' );
			}
		}


		/**
		 * @param $post_id
		 *
		 */
		function save_attachment_metabox_restrict_content( $post_id ) {
			// validate nonce
			if ( ! isset( $_POST['um_admin_save_metabox_restrict_content_nonce'] )
			     || ! wp_verify_nonce( $_POST['um_admin_save_metabox_restrict_content_nonce'], basename( __FILE__ ) ) ) {
				return;
			}

			$post = get_post( $post_id );

			// validate user
			$post_type = get_post_type_object( $post->post_type );
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
				return;
			}

			if ( ! empty( $_POST['um_content_restriction'] ) && is_array( $_POST['um_content_restriction'] ) ) {
				update_post_meta( $post_id, 'um_content_restriction', $_POST['um_content_restriction'] );
			} else {
				delete_post_meta( $post_id, 'um_content_restriction' );
			}
		}


		/**
		 *
		 */
		function um_category_access_fields_create() {
			$data = array();

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_category_access_settings_fields
			 * @description Settings fields for terms
			 * @input_vars
			 * [{"var":"$access_settings_fields","type":"array","desc":"Settings Fields"},
			 * {"var":"$data","type":"array","desc":"Settings Data"},
			 * {"var":"$screen","type":"string","desc":"Category Screen"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_category_access_settings_fields', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_category_access_settings_fields', 'my_admin_category_access_settings_fields', 10, 3 );
			 * function my_admin_category_access_settings_fields( $access_settings_fields, $data, $screen ) {
			 *     // your code here
			 *     $access_settings_fields[] = array(
			 *         'id'          => 'my-field-key',
			 *         'type'        => 'my-field-type',
			 *         'label'       => __( 'My field Label', 'ultimate-member' ),
			 *         'description' => __( 'My Field Description', 'ultimate-member' ),
			 *         'value'       => ! empty( $data['_um_custom_access_settings'] ) ? $data['_um_custom_access_settings'] : 0,
			 *     );
			 *     return $access_settings_fields;
			 * }
			 * ?>
			 */
			$fields = apply_filters( 'um_admin_category_access_settings_fields', array(
				array(
					'id'            => '_um_custom_access_settings',
					'type'          => 'checkbox',
					'label'         => __( 'Restrict access to this content?', 'ultimate-member' ),
					'description'   => __( 'Activate content restriction for this post', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_custom_access_settings'] ) ? $data['_um_custom_access_settings'] : 0,
				),
				array(
					'id'            => '_um_accessible',
					'type'          => 'select',
					'label'         => __( 'Who can access this content?', 'ultimate-member' ),
					'description'   => __( 'Activate content restriction for this post', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_accessible'] ) ? $data['_um_accessible'] : 0,
					'options'       => array(
						'0'         => __( 'Everyone', 'ultimate-member' ),
						'1'         => __( 'Logged out users', 'ultimate-member' ),
						'2'         => __( 'Logged in users', 'ultimate-member' ),
					),
					'conditional'   => array( '_um_custom_access_settings', '=', '1' )
				),
				array(
					'id'            => '_um_access_roles',
					'type'          => 'multi_checkbox',
					'label'         => __( 'Select which roles can access this content', 'ultimate-member' ),
					'description'   => __( 'Activate content restriction for this post', 'ultimate-member' ),
					'options'       => UM()->roles()->get_roles( false, array( 'administrator' ) ),
					'columns'       => 3,
					'conditional'   => array( '_um_accessible', '=', '2' )
				),
				array(
					'id'            => '_um_noaccess_action',
					'type'          => 'select',
					'label'         => __( 'What happens when users without access tries to view the content?', 'ultimate-member' ),
					'description'   => __( 'Action when users without access tries to view the content', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_noaccess_action'] ) ? $data['_um_noaccess_action'] : 0,
					'options'       => array(
						'0'         => __( 'Show access restricted message', 'ultimate-member' ),
						'1'         => __( 'Redirect user', 'ultimate-member' ),
					),
					'conditional'   => array( '_um_accessible', '!=', '0' )
				),
				array(
					'id'            => '_um_restrict_by_custom_message',
					'type'          => 'select',
					'label'         => __( 'Would you like to use the global default message or apply a custom message to this content?', 'ultimate-member' ),
					'description'   => __( 'Action when users without access tries to view the content', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_restrict_by_custom_message'] ) ? $data['_um_restrict_by_custom_message'] : '0',
					'options'       => array(
						'0'         => __( 'Global default message (default)', 'ultimate-member' ),
						'1'         => __( 'Custom message', 'ultimate-member' ),
					),
					'conditional'   => array( '_um_noaccess_action', '=', '0' )
				),
				array(
					'id'            => '_um_restrict_custom_message',
					'type'          => 'wp_editor',
					'label'         => __( 'Custom Restrict Content message', 'ultimate-member' ),
					'description'   => __( 'Changed global restrict message', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_restrict_custom_message'] ) ? $data['_um_restrict_custom_message'] : '',
					'conditional'   => array( '_um_restrict_by_custom_message', '=', '1' )
				),
				array(
					'id'            => '_um_access_redirect',
					'type'          => 'select',
					'label'         => __( 'Where should users be redirected to?', 'ultimate-member' ),
					'description'   => __( 'Select redirect to page when user hasn\'t access to content', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_access_redirect'] ) ? $data['_um_access_redirect'] : '0',
					'conditional'   => array( '_um_noaccess_action', '=', '1' ),
					'options'       => array(
						'0'         => __( 'Login page', 'ultimate-member' ),
						'1'         => __( 'Custom URL', 'ultimate-member' ),
					),
				),
				array(
					'id'            => '_um_access_redirect_url',
					'type'          => 'text',
					'label'         => __( 'Redirect URL', 'ultimate-member' ),
					'description'   => __( 'Changed global restrict message', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_access_redirect_url'] ) ? $data['_um_access_redirect_url'] : '',
					'conditional'   => array( '_um_access_redirect', '=', '1' )
				),
				array(
					'id'            => '_um_access_hide_from_queries',
					'type'          => 'checkbox',
					'label'         => __( 'Hide from queries', 'ultimate-member' ),
					'description'   => __( 'Hide this content from archives, RSS feeds etc for users who do not have permission to view this content', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_access_hide_from_queries'] ) ? $data['_um_access_hide_from_queries'] : '',
					'conditional'   => array( '_um_accessible', '!=', '0' )
				)
			), $data, 'create' );

			UM()->admin_forms( array(
				'class'             => 'um-restrict-content um-third-column',
				'prefix_id'         => 'um_content_restriction',
				'without_wrapper'   => true,
				'div_line'          => true,
				'fields'            => $fields
			) )->render_form();

			wp_nonce_field( basename( __FILE__ ), 'um_admin_save_taxonomy_restrict_content_nonce' );
		}


		/**
		 * @param $term
		 */
		function um_category_access_fields_edit( $term ) {
			$termID = $term->term_id;

			$data = get_term_meta( $termID, 'um_content_restriction', true );

			$_um_access_roles_value = array();
			if ( ! empty( $data['_um_access_roles'] ) ) {
				foreach ( $data['_um_access_roles'] as $key => $value ) {
					if ( $value ) {
						$_um_access_roles_value[] = $key;
					}
				}
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_category_access_settings_fields
			 * @description Settings fields for terms
			 * @input_vars
			 * [{"var":"$access_settings_fields","type":"array","desc":"Settings Fields"},
			 * {"var":"$data","type":"array","desc":"Settings Data"},
			 * {"var":"$screen","type":"string","desc":"Category Screen"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_category_access_settings_fields', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_category_access_settings_fields', 'my_admin_category_access_settings_fields', 10, 3 );
			 * function my_admin_category_access_settings_fields( $access_settings_fields, $data, $screen ) {
			 *     // your code here
			 *     $access_settings_fields[] = array(
			 *         'id'          => 'my-field-key',
			 *         'type'        => 'my-field-type',
			 *         'label'       => __( 'My field Label', 'ultimate-member' ),
			 *         'description' => __( 'My Field Description', 'ultimate-member' ),
			 *         'value'       => ! empty( $data['_um_custom_access_settings'] ) ? $data['_um_custom_access_settings'] : 0,
			 *     );
			 *     return $access_settings_fields;
			 * }
			 * ?>
			 */
			$fields = apply_filters( 'um_admin_category_access_settings_fields', array(
				array(
					'id'            => '_um_custom_access_settings',
					'type'          => 'checkbox',
					'class'         => 'form-field',
					'label'         => __( 'Restrict access to this content?', 'ultimate-member' ),
					'description'   => __( 'Activate content restriction for this post', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_custom_access_settings'] ) ? $data['_um_custom_access_settings'] : 0,
				),
				array(
					'id'            => '_um_accessible',
					'type'          => 'select',
					'class'         => 'form-field',
					'label'         => __( 'Who can access this content?', 'ultimate-member' ),
					'description'   => __( 'Activate content restriction for this post', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_accessible'] ) ? $data['_um_accessible'] : 0,
					'options'       => array(
						'0'         => __( 'Everyone', 'ultimate-member' ),
						'1'         => __( 'Logged out users', 'ultimate-member' ),
						'2'         => __( 'Logged in users', 'ultimate-member' ),
					),
					'conditional'   => array( '_um_custom_access_settings', '=', '1' )
				),
				array(
					'id'            => '_um_access_roles',
					'type'          => 'multi_checkbox',
					'class'         => 'form-field',
					'label'         => __( 'Select which roles can access this content', 'ultimate-member' ),
					'description'   => __( 'Activate content restriction for this post', 'ultimate-member' ),
					'value'         => $_um_access_roles_value,
					'options'       => UM()->roles()->get_roles( false, array( 'administrator' ) ),
					'columns'       => 3,
					'conditional'   => array( '_um_accessible', '=', '2' )
				),
				array(
					'id'            => '_um_noaccess_action',
					'type'          => 'select',
					'class'         => 'form-field',
					'label'         => __( 'What happens when users without access tries to view the content?', 'ultimate-member' ),
					'description'   => __( 'Action when users without access tries to view the content', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_noaccess_action'] ) ? $data['_um_noaccess_action'] : 0,
					'options'       => array(
						'0'         => __( 'Show access restricted message', 'ultimate-member' ),
						'1'         => __( 'Redirect user', 'ultimate-member' ),
					),
					'conditional'   => array( '_um_accessible', '!=', '0' )
				),
				array(
					'id'            => '_um_restrict_by_custom_message',
					'type'          => 'select',
					'class'         => 'form-field',
					'label'         => __( 'Would you like to use the global default message or apply a custom message to this content?', 'ultimate-member' ),
					'description'   => __( 'Action when users without access tries to view the content', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_restrict_by_custom_message'] ) ? $data['_um_restrict_by_custom_message'] : '0',
					'options'       => array(
						'0'         => __( 'Global default message (default)', 'ultimate-member' ),
						'1'         => __( 'Custom message', 'ultimate-member' ),
					),
					'conditional'   => array( '_um_noaccess_action', '=', '0' )
				),
				array(
					'id'            => '_um_restrict_custom_message',
					'type'          => 'wp_editor',
					'class'         => 'form-field',
					'label'         => __( 'Custom Restrict Content message', 'ultimate-member' ),
					'description'   => __( 'Changed global restrict message', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_restrict_custom_message'] ) ? $data['_um_restrict_custom_message'] : '',
					'conditional'   => array( '_um_restrict_by_custom_message', '=', '1' )
				),
				array(
					'id'            => '_um_access_redirect',
					'type'          => 'select',
					'class'         => 'form-field',
					'label'         => __( 'Where should users be redirected to?', 'ultimate-member' ),
					'description'   => __( 'Select redirect to page when user hasn\'t access to content', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_access_redirect'] ) ? $data['_um_access_redirect'] : '0',
					'conditional'   => array( '_um_noaccess_action', '=', '1' ),
					'options'       => array(
						'0'         => __( 'Login page', 'ultimate-member' ),
						'1'         => __( 'Custom URL', 'ultimate-member' ),
					),
				),
				array(
					'id'            => '_um_access_redirect_url',
					'type'          => 'text',
					'class'         => 'form-field',
					'label'         => __( 'Redirect URL', 'ultimate-member' ),
					'description'   => __( 'Changed global restrict message', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_access_redirect_url'] ) ? $data['_um_access_redirect_url'] : '',
					'conditional'   => array( '_um_access_redirect', '=', '1' )
				),
				array(
					'id'            => '_um_access_hide_from_queries',
					'type'          => 'checkbox',
					'class'         => 'form-field',
					'label'         => __( 'Hide from queries', 'ultimate-member' ),
					'description'   => __( 'Hide this content from archives, RSS feeds etc for users who do not have permission to view this content', 'ultimate-member' ),
					'value'         => ! empty( $data['_um_access_hide_from_queries'] ) ? $data['_um_access_hide_from_queries'] : '',
					'conditional'   => array( '_um_accessible', '!=', '0' )
				)
			), $data, 'edit' );

			UM()->admin_forms( array(
				'class'             => 'um-restrict-content um-third-column',
				'prefix_id'         => 'um_content_restriction',
				'without_wrapper'   => true,
				'fields'            => $fields
			) )->render_form();

			wp_nonce_field( basename( __FILE__ ), 'um_admin_save_taxonomy_restrict_content_nonce' );
		}


		/**
		 * @param $termID
		 *
		 * @return mixed
		 */
		function um_category_access_fields_save( $termID ) {

			// validate nonce
			if ( ! isset( $_REQUEST['um_admin_save_taxonomy_restrict_content_nonce'] ) || ! wp_verify_nonce( $_REQUEST['um_admin_save_taxonomy_restrict_content_nonce'], basename( __FILE__ ) ) ) {
				return $termID;
			}

			// validate user
			$term = get_term( $termID );
			$taxonomy = get_taxonomy( $term->taxonomy );

			if ( ! current_user_can( $taxonomy->cap->edit_terms, $termID ) ) {
				return $termID;
			}

			if ( ! empty( $_REQUEST['um_content_restriction'] ) && is_array( $_REQUEST['um_content_restriction'] ) ) {
				update_term_meta( $termID, 'um_content_restriction', $_REQUEST['um_content_restriction'] );
			} else {
				delete_term_meta( $termID, 'um_content_restriction' );
			}

			return $termID;
		}


		/**
		 * Load a directory metabox
		 *
		 * @param $object
		 * @param $box
		 */
		function load_metabox_directory( $object, $box ) {
			$box['id'] = str_replace( 'um-admin-form-', '', $box['id'] );

			preg_match('#\{.*?\}#s', $box['id'], $matches );

			if ( isset( $matches[0] ) ) {
				$path = $matches[0];
				$box['id'] = preg_replace('~(\\{[^}]+\\})~','', $box['id'] );
			} else {
				$path = um_path;
			}

			$path = str_replace('{','', $path );
			$path = str_replace('}','', $path );


			include_once $path . 'includes/admin/templates/directory/'. $box['id'] . '.php';
			if ( ! $this->directory_nonce_added ) {
				$this->directory_nonce_added = true;
				wp_nonce_field( basename( __FILE__ ), 'um_admin_save_metabox_directory_nonce' );
			}
		}


		/**
		 * Load a role metabox
		 *
		 * @param $object
		 * @param $box
		 */
		function load_metabox_role( $object, $box ) {
			global $post;

			$box['id'] = str_replace( 'um-admin-form-', '', $box['id'] );

			if ( $box['id'] == 'builder' ) {
				UM()->builder()->form_id = get_the_ID();
			}

			preg_match('#\{.*?\}#s', $box['id'], $matches);

			if ( isset($matches[0]) ){
				$path = $matches[0];
				$box['id'] = preg_replace('~(\\{[^}]+\\})~','', $box['id'] );
			} else {
				$path = um_path;
			}

			$path = str_replace('{','', $path );
			$path = str_replace('}','', $path );

			include_once $path . 'includes/admin/templates/role/'. $box['id'] . '.php';
			//wp_nonce_field( basename( __FILE__ ), 'um_admin_save_metabox_role_nonce' );
		}


		/**
		 * Load a form metabox
		 *
		 * @param $object
		 * @param $box
		 */
		function load_metabox_form( $object, $box ) {
			global $post;

			$box['id'] = str_replace( 'um-admin-form-','', $box['id'] );

			if ( $box['id'] == 'builder' ) {
				UM()->builder()->form_id = get_the_ID();
			}

			preg_match('#\{.*?\}#s', $box['id'], $matches);

			if ( isset( $matches[0] ) ) {
				$path = $matches[0];
				$box['id'] = preg_replace('~(\\{[^}]+\\})~','', $box['id'] );
			} else {
				$path = um_path;
			}

			$path = str_replace('{','', $path );
			$path = str_replace('}','', $path );

			include_once $path . 'includes/admin/templates/form/'. $box['id'] . '.php';

			if ( ! $this->form_nonce_added ) {
				$this->form_nonce_added = true;
				wp_nonce_field( basename( __FILE__ ), 'um_admin_save_metabox_form_nonce' );
			}
		}


		/**
		 * Load admin custom metabox
		 *
		 * @param $object
		 * @param $box
		 */
		function load_metabox_custom( $object, $box ) {
			global $post;

			$box['id'] = str_replace('um-admin-custom-','', $box['id']);

			preg_match('#\{.*?\}#s', $box['id'], $matches);

			if ( isset($matches[0]) ){
				$path = $matches[0];
				$box['id'] = preg_replace('~(\\{[^}]+\\})~','', $box['id'] );
			} else {
				$path = um_path;
			}

			$path = str_replace('{','', $path );
			$path = str_replace('}','', $path );

			include_once $path . 'includes/admin/templates/'. $box['id'] . '.php';
			if ( ! $this->custom_nonce_added ) {
				$this->custom_nonce_added = true;
				wp_nonce_field( basename( __FILE__ ), 'um_admin_save_metabox_custom_nonce' );
			}
		}


		/**
		 * Add directory metabox
		 */
		function add_metabox_directory() {
			add_meta_box( 'um-admin-form-general', __( 'General Options', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
			add_meta_box( 'um-admin-form-sorting', __( 'Sorting', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
			add_meta_box( 'um-admin-form-profile', __( 'Profile Card', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
			add_meta_box( 'um-admin-form-search', __( 'Search Options', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
			add_meta_box( 'um-admin-form-pagination', __( 'Results &amp; Pagination', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
			add_meta_box( 'um-admin-form-shortcode', __( 'Shortcode', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'side', 'default' );
			add_meta_box( 'um-admin-form-appearance', __( 'Styling: General', 'ultimate-member' ), array( &$this, 'load_metabox_directory'), 'um_directory', 'side', 'default' );
		}


		/**
		 * Add role metabox
		 */
		function add_metabox_role() {
			$callback = array( &$this, 'load_metabox_role' );

			$roles_metaboxes = array(
				array(
					'id'        => 'um-admin-form-admin-permissions',
					'title'     => __( 'Administrative Permissions', 'ultimate-member' ),
					'callback'  => $callback,
					'screen'    => 'um_role_meta',
					'context'   => 'normal',
					'priority'  => 'default'
				),
				array(
					'id'        => 'um-admin-form-general',
					'title'     => __( 'General Permissions', 'ultimate-member' ),
					'callback'  => $callback,
					'screen'    => 'um_role_meta',
					'context'   => 'normal',
					'priority'  => 'default'
				),
				array(
					'id'        => 'um-admin-form-profile',
					'title'     => __( 'Profile Access', 'ultimate-member' ),
					'callback'  => $callback,
					'screen'    => 'um_role_meta',
					'context'   => 'normal',
					'priority'  => 'default'
				)
			);

			if ( ! isset( $_GET['id'] ) || 'administrator' != sanitize_key( $_GET['id'] ) ) {
				$roles_metaboxes[] = array(
					'id'        => 'um-admin-form-home',
					'title'     => __( 'Homepage Options', 'ultimate-member' ),
					'callback'  => $callback,
					'screen'    => 'um_role_meta',
					'context'   => 'normal',
					'priority'  => 'default'
				);
			}

			$roles_metaboxes = array_merge( $roles_metaboxes, array(
				array(
					'id'        => 'um-admin-form-register',
					'title'     => __( 'Registration Options', 'ultimate-member' ),
					'callback'  => $callback,
					'screen'    => 'um_role_meta',
					'context'   => 'normal',
					'priority'  => 'default'
				),
				array(
					'id'        => 'um-admin-form-login',
					'title'     => __( 'Login Options', 'ultimate-member' ),
					'callback'  => $callback,
					'screen'    => 'um_role_meta',
					'context'   => 'normal',
					'priority'  => 'default'
				),
				array(
					'id'        => 'um-admin-form-logout',
					'title'     => __( 'Logout Options', 'ultimate-member' ),
					'callback'  => $callback,
					'screen'    => 'um_role_meta',
					'context'   => 'normal',
					'priority'  => 'default'
				),
				array(
					'id'        => 'um-admin-form-delete',
					'title'     => __( 'Delete Options', 'ultimate-member' ),
					'callback'  => $callback,
					'screen'    => 'um_role_meta',
					'context'   => 'normal',
					'priority'  => 'default'
				),
				array(
					'id'        => 'um-admin-form-publish',
					'title'     => __( 'Publish', 'ultimate-member' ),
					'callback'  => $callback,
					'screen'    => 'um_role_meta',
					'context'   => 'side',
					'priority'  => 'default'
				)
			) );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_role_metaboxes
			 * @description Extend metaboxes at Add/Edit User Role
			 * @input_vars
			 * [{"var":"$roles_metaboxes","type":"array","desc":"Metaboxes at Add/Edit UM Role"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_role_metaboxes', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_role_metaboxes', 'my_admin_role_metaboxes', 10, 1 );
			 * function my_admin_role_metaboxes( $roles_metaboxes ) {
			 *     // your code here
			 *     $roles_metaboxes[] = array(
			 *         'id'        => 'um-admin-form-your-custom',
			 *         'title'     => __( 'My Roles Metabox', 'ultimate-member' ),
			 *         'callback'  => 'my-metabox-callback',
			 *         'screen'    => 'um_role_meta',
			 *         'context'   => 'side',
			 *         'priority'  => 'default'
			 *     );
			 *
			 *     return $roles_metaboxes;
			 * }
			 * ?>
			 */
			$roles_metaboxes = apply_filters( 'um_admin_role_metaboxes', $roles_metaboxes );

			$wp_caps_metabox = false;
			if ( ! empty( $_GET['id'] ) ) {
				$data = get_option( 'um_role_' . sanitize_key( $_GET['id'] ) . '_meta' );
				if ( ! empty( $data['_um_is_custom'] ) ) {
					$wp_caps_metabox = true;
				}
			}
			if ( 'add' == sanitize_key( $_GET['tab'] ) || $wp_caps_metabox ) {
				$roles_metaboxes[] = array(
					'id'        => 'um-admin-form-wp-capabilities',
					'title'     => __( 'WP Capabilities', 'ultimate-member' ),
					'callback'  => $callback,
					'screen'    => 'um_role_meta',
					'context'   => 'normal',
					'priority'  => 'default'
				);
			}


			foreach ( $roles_metaboxes as $metabox ) {
				add_meta_box(
					$metabox['id'],
					$metabox['title'],
					$metabox['callback'],
					$metabox['screen'],
					$metabox['context'],
					$metabox['priority']
				);
			}
		}


		/**
		 * Add form metabox
		 */
		function add_metabox_form() {

			add_meta_box( 'um-admin-form-mode', __( 'Select Form Type', 'ultimate-member' ), array( &$this, 'load_metabox_form' ), 'um_form', 'normal', 'default' );
			add_meta_box( 'um-admin-form-builder', __( 'Form Builder', 'ultimate-member' ), array( &$this, 'load_metabox_form' ), 'um_form', 'normal', 'default' );
			add_meta_box( 'um-admin-form-shortcode', __( 'Shortcode', 'ultimate-member' ), array( &$this, 'load_metabox_form' ), 'um_form', 'side', 'default' );

			add_meta_box( 'um-admin-form-register_customize', __( 'Customize this form', 'ultimate-member' ), array( &$this, 'load_metabox_form' ), 'um_form', 'side', 'default' );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_custom_register_metaboxes
			 * @description Add custom metaboxes for register form
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_custom_register_metaboxes', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_custom_register_metaboxes', 'my_admin_custom_register_metaboxes', 10 );
			 * function my_admin_custom_register_metaboxes() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_custom_register_metaboxes' );

			add_meta_box( 'um-admin-form-profile_customize', __( 'Customize this form', 'ultimate-member' ), array( &$this, 'load_metabox_form' ), 'um_form', 'side', 'default' );
			add_meta_box( 'um-admin-form-profile_settings', __( 'User Meta', 'ultimate-member' ), array( &$this, 'load_metabox_form' ), 'um_form', 'side', 'default' );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_custom_profile_metaboxes
			 * @description Add custom metaboxes for profile form
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_custom_profile_metaboxes', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_custom_profile_metaboxes', 'my_admin_custom_profile_metaboxes', 10 );
			 * function my_admin_custom_profile_metaboxes() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_custom_profile_metaboxes' );

			add_meta_box( 'um-admin-form-login_customize', __( 'Customize this form', 'ultimate-member' ), array( &$this, 'load_metabox_form' ), 'um_form', 'side', 'default' );
			add_meta_box( 'um-admin-form-login_settings', __( 'Options', 'ultimate-member' ), array( &$this, 'load_metabox_form' ), 'um_form', 'side', 'default' );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_custom_login_metaboxes
			 * @description Add custom metaboxes for login form
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_custom_login_metaboxes', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_custom_login_metaboxes', 'my_admin_custom_login_metaboxes', 10 );
			 * function my_admin_custom_login_metaboxes() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_custom_login_metaboxes' );
		}


		/**
		 * Save directory metabox
		 *
		 * @param $post_id
		 * @param $post
		 */
		function save_metabox_directory( $post_id, $post ) {
			global $wpdb;

			// validate nonce
			if ( ! isset( $_POST['um_admin_save_metabox_directory_nonce'] ) ||
			     ! wp_verify_nonce( $_POST['um_admin_save_metabox_directory_nonce'], basename( __FILE__ ) ) ) {
				return;
			}

			// validate post type
			if ( $post->post_type != 'um_directory' ) {
				return;
			}

			// validate user
			$post_type = get_post_type_object( $post->post_type );
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
				return;
			}

			$where = array( 'ID' => $post_id );

			if ( empty( $_POST['post_title'] ) ) {
				$_POST['post_title'] = sprintf( __( 'Directory #%s', 'ultimate-member' ), $post_id );
			}

			$wpdb->update( $wpdb->posts, array( 'post_title' => sanitize_text_field( $_POST['post_title'] ) ), $where );

			do_action( 'um_before_member_directory_save', $post_id );

			// save
			delete_post_meta( $post_id, '_um_roles' );
			delete_post_meta( $post_id, '_um_tagline_fields' );
			delete_post_meta( $post_id, '_um_reveal_fields' );
			delete_post_meta( $post_id, '_um_search_fields' );
			delete_post_meta( $post_id, '_um_roles_can_search' );
			delete_post_meta( $post_id, '_um_roles_can_filter' );
			delete_post_meta( $post_id, '_um_show_these_users' );
			delete_post_meta( $post_id, '_um_exclude_these_users' );

			delete_post_meta( $post_id, '_um_search_filters' );
			delete_post_meta( $post_id, '_um_search_filters_gmt' );

			//save metadata
			foreach ( $_POST['um_metadata'] as $k => $v ) {

				if ( $k == '_um_show_these_users' && trim( $_POST['um_metadata'][ $k ] ) ) {
					$v = preg_split( '/[\r\n]+/', $v, -1, PREG_SPLIT_NO_EMPTY );
				}

				if ( $k == '_um_exclude_these_users' && trim( $_POST['um_metadata'][ $k ] ) ) {
					$v = preg_split( '/[\r\n]+/', $v, -1, PREG_SPLIT_NO_EMPTY );
				}

				if ( strstr( $k, '_um_' ) ) {

					if ( $k === '_um_is_default' ) {

						$mode = UM()->query()->get_attr( 'mode', $post_id );

						if ( ! empty( $mode ) ) {

							$posts = $wpdb->get_col(
								"SELECT post_id
								FROM {$wpdb->postmeta}
								WHERE meta_key = '_um_mode' AND
									  meta_value = 'directory'"
							);

							foreach ( $posts as $p_id ) {
								delete_post_meta( $p_id, '_um_is_default' );
							}

						}

					}

					$v = apply_filters( 'um_member_directory_meta_value_before_save', $v, $k, $post_id );

					update_post_meta( $post_id, $k, $v );

				}
			}

			update_post_meta( $post_id, '_um_search_filters_gmt', intval( $_POST['um-gmt-offset'] ) );
		}


		/**
		 * Save form metabox
		 *
		 * @param $post_id
		 * @param $post
		 */
		function save_metabox_form( $post_id, $post ) {
			global $wpdb;

			// validate nonce
			if ( ! isset( $_POST['um_admin_save_metabox_form_nonce'] ) ||
			     ! wp_verify_nonce( $_POST['um_admin_save_metabox_form_nonce'], basename( __FILE__ ) ) ) {
				return;
			}

			// validate post type
			if ( $post->post_type != 'um_form' ) {
				return;
			}

			// validate user
			$post_type = get_post_type_object( $post->post_type );
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
				return;
			}

			$where = array( 'ID' => $post_id );
			if ( empty( $_POST['post_title'] ) ) {
				$_POST['post_title'] = sprintf( __( 'Form #%s', 'ultimate-member' ), $post_id );
			}
			$wpdb->update( $wpdb->posts, array( 'post_title' => sanitize_text_field( $_POST['post_title'] ) ), $where );

			// save
			delete_post_meta( $post_id, '_um_profile_metafields' );
			foreach ( $_POST['form'] as $k => $v ) {
				if ( strstr( $k, '_um_' ) ) {
					if ( $k === '_um_is_default' ) {
						$mode = UM()->query()->get_attr( 'mode', $post_id );
						if ( ! empty( $mode ) ) {
							$posts = $wpdb->get_col( $wpdb->prepare(
								"SELECT post_id
								FROM {$wpdb->postmeta}
								WHERE meta_key = '_um_mode' AND
									  meta_value = %s",
								$mode
							) );
							foreach ( $posts as $p_id ) {
								delete_post_meta( $p_id, '_um_is_default' );
							}
						}
					}

					update_post_meta( $post_id, $k, $v );
				}
			}

		}


		/**
		 * Load modal content
		 */
		function load_modal_content() {

			$screen = get_current_screen();
			if ( UM()->admin()->is_um_screen() ) {
				foreach ( glob( um_path . 'includes/admin/templates/modal/*.php' ) as $modal_content ) {
					include_once $modal_content;
				}
			}

			// needed on forms only
			if ( ! isset( $this->is_loaded ) && isset( $screen->id ) && strstr( $screen->id, 'um_form' ) ) {
				$settings['textarea_rows'] = 8;

				echo '<div class="um-hidden-editor-edit" style="display:none;">';
				wp_editor( '', 'um_editor_edit', $settings );
				echo '</div>';

				echo '<div class="um-hidden-editor-new" style="display:none;">';
				wp_editor( '', 'um_editor_new', $settings );
				echo '</div>';

				$this->is_loaded = true;
			}
		}


		/**
		 * Show field input for edit at modal field
		 *
		 * @param $attribute
		 * @param null $form_id
		 * @param array $field_args
		 */
		function field_input( $attribute, $form_id = null, $field_args = array() ) {

			if ( $this->in_edit == true ) { // we're editing a field
				$real_attr = substr( $attribute, 1 );
				$this->edit_mode_value = (isset( $this->edit_array[ $real_attr ] ) ) ? $this->edit_array[ $real_attr ] : null;
			}

			switch ( $attribute ) {

				default:

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_admin_field_edit_hook{$attribute}
					 * @description Integration for 3-d party fields at wp-admin
					 * @input_vars
					 * [{"var":"$edit_mode_value","type":"string","desc":"Post ID"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_admin_field_edit_hook{$attribute}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_admin_field_edit_hook{$attribute}', 'my_admin_field_edit', 10, 1 );
					 * function my_admin_field_edit( $edit_mode_value ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( "um_admin_field_edit_hook{$attribute}", $this->edit_mode_value, $form_id, $this->edit_array );

					break;

				case '_visibility':
					?>

					<p><label for="_visibility"><?php _e( 'Visibility', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Select where this field should appear. This option should only be changed on the profile form and allows you to show a field in one mode only (edit or view) or in both modes.','ultimate-member' ) ); ?></label>
						<select name="_visibility" id="_visibility" style="width: 100%">
							<option value="all" <?php selected( 'all', $this->edit_mode_value ); ?>><?php _e( 'View everywhere', 'ultimate-member' ) ?></option>
							<option value="edit" <?php selected( 'edit', $this->edit_mode_value ); ?>><?php _e( 'Edit mode only', 'ultimate-member' ) ?></option>
							<option value="view" <?php selected( 'view', $this->edit_mode_value ); ?>><?php _e( 'View mode only', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_conditional_action':
				case '_conditional_action1':
				case '_conditional_action2':
				case '_conditional_action3':
				case '_conditional_action4':
				?>

				<p>
					<select name="<?php echo esc_attr( $attribute ); ?>" id="<?php echo esc_attr( $attribute ); ?>" style="width: 90px">

						<option></option>

						<?php $actions = array( 'show', 'hide' );
						foreach ( $actions as $action ) { ?>

							<option value="<?php echo esc_attr( $action ); ?>" <?php selected( $action, $this->edit_mode_value ); ?>><?php echo $action; ?></option>

						<?php } ?>

					</select>

					&nbsp;&nbsp;<?php _e( 'If' ); ?>
				</p>

				<?php
				break;

				case '_conditional_field':
				case '_conditional_field1':
				case '_conditional_field2':
				case '_conditional_field3':
				case '_conditional_field4':
				?>

				<p>
					<select name="<?php echo esc_attr( $attribute ); ?>" id="<?php echo esc_attr( $attribute ); ?>" style="width: 150px">

						<option></option>

						<?php $fields = UM()->query()->get_attr( 'custom_fields', $form_id );

						foreach ( $fields as $key => $array ) {
							if ( isset( $array['title'] ) &&
							     ( ! isset( $this->edit_array['metakey'] ) || $key != $this->edit_array['metakey'] ) ) { ?>

								<option value="<?php echo esc_attr( $key ) ?>" <?php selected( $key, $this->edit_mode_value ) ?>><?php echo $array['title'] ?></option>

							<?php }
						} ?>

					</select>
				</p>

				<?php
				break;

				case '_conditional_operator':
				case '_conditional_operator1':
				case '_conditional_operator2':
				case '_conditional_operator3':
				case '_conditional_operator4':
				?>

				<p>
					<select name="<?php echo esc_attr( $attribute ); ?>" id="<?php echo esc_attr( $attribute ); ?>" style="width: 150px">

						<option></option>

						<?php $operators = array(
							'empty',
							'not empty',
							'equals to',
							'not equals',
							'greater than',
							'less than',
							'contains'
						);

						foreach ( $operators as $operator ) { ?>

							<option value="<?php echo esc_attr( $operator ); ?>" <?php selected( $operator, $this->edit_mode_value ); ?>><?php echo $operator; ?></option>

						<?php } ?>

					</select>
				</p>

				<?php
				break;

				case '_conditional_value':
				case '_conditional_value1':
				case '_conditional_value2':
				case '_conditional_value3':
				case '_conditional_value4':
				?>

				<p>
					<input type="text" name="<?php echo esc_attr( $attribute ); ?>" id="<?php echo esc_attr( $attribute ); ?>" value="<?php echo isset( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" placeholder="<?php esc_attr_e( 'Value', 'ultimate-member' ); ?>" style="width: 150px!important;position: relative;top: -1px;" />
				</p>

				<?php
				break;

				case '_validate':
					?>

					<p><label for="_validate"><?php _e( 'Validate', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Does this field require a special validation', 'ultimate-member' ) ); ?></label>
						<select name="_validate" id="_validate" data-placeholder="<?php esc_attr_e( 'Select a validation type...', 'ultimate-member' ) ?>" class="um-adm-conditional" data-cond1="custom" data-cond1-show="_custom_validate" style="width: 100%">

							<option value="" <?php selected( '', $this->edit_mode_value ); ?>></option>

							<?php foreach( UM()->builtin()->validation_types() as $key => $name ) { ?>
								<?php
								/**
								 * UM hook
								 *
								 * @type filter
								 * @title um_builtin_validation_types_continue_loop
								 * @description Builtin Validation Types
								 * @input_vars
								 * [{"var":"$continue","type":"bool","desc":"Validate?"},
								 * {"var":"$key","type":"string","desc":"Field Key"},
								 * {"var":"$form_id","type":"int","desc":"Form ID"},
								 * {"var":"$field_args","type":"array","desc":"Field Settings"}]
								 * @change_log
								 * ["Since: 2.0"]
								 * @usage add_filter( 'um_builtin_validation_types_continue_loop', 'function_name', 10, 4 );
								 * @example
								 * <?php
								 * add_filter( 'um_builtin_validation_types_continue_loop', 'my_builtin_validation_types', 10, 4 );
								 * function my_builtin_validation_types( $continue, $key, $form_id, $field_args ) {
								 *     // your code here
								 *     return $continue;
								 * }
								 * ?>
								 */
								$continue = apply_filters( "um_builtin_validation_types_continue_loop", true, $key, $form_id, $field_args );
								if ( $continue ) { ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $this->edit_mode_value ); ?>><?php echo $name; ?></option>
								<?php } ?>
							<?php } ?>

						</select>
					</p>

					<?php
					break;

				case '_custom_validate':
					?>

					<p class="_custom_validate"><label for="_custom_validate"><?php _e( 'Custom Action', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'If you want to apply your custom validation, you can use action hooks to add custom validation. Please refer to documentation for further details.', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_custom_validate" id="_custom_validate" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_icon':

					if ( $this->set_field_type == 'row' ) {
						$back = 'UM_edit_row';

						?>

						<p class="_heading_text"><label for="_icon"><?php _e( 'Icon', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Select an icon to appear in the field. Leave blank if you do not want an icon to show in the field.', 'ultimate-member' ) ); ?></label>

							<a href="javascript:void(0);" class="button" data-modal="UM_fonticons" data-modal-size="normal" data-dynamic-content="um_admin_fonticon_selector" data-arg1="" data-arg2="" data-back="<?php echo esc_attr( $back ); ?>"><?php _e( 'Choose Icon', 'ultimate-member' ) ?></a>

							<span class="um-admin-icon-value"><?php if ( $this->edit_mode_value ) { ?><i class="<?php echo $this->edit_mode_value; ?>"></i><?php } else { ?><?php _e( 'No Icon', 'ultimate-member' ) ?><?php } ?></span>

							<input type="hidden" name="_icon" id="_icon" value="<?php echo (isset( $this->edit_mode_value ) ) ? $this->edit_mode_value : ''; ?>" />

							<?php if ( $this->edit_mode_value ) { ?>
								<span class="um-admin-icon-clear show"><i class="um-icon-android-cancel"></i></span>
							<?php } else { ?>
								<span class="um-admin-icon-clear"><i class="um-icon-android-cancel"></i></span>
							<?php } ?>

						</p>

					<?php } else {

						if ( $this->in_edit ) {
							$back = 'UM_edit_field';
						} else {
							$back = 'UM_add_field';
						}

						?>

						<div class="um-admin-tri">

							<p><label for="_icon"><?php _e( 'Icon', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Select an icon to appear in the field. Leave blank if you do not want an icon to show in the field.', 'ultimate-member' ) ); ?></label>

								<a href="javascript:void(0);" class="button" data-modal="UM_fonticons" data-modal-size="normal" data-dynamic-content="um_admin_fonticon_selector" data-arg1="" data-arg2="" data-back="<?php echo esc_attr( $back ); ?>"><?php _e( 'Choose Icon', 'ultimate-member' ) ?></a>

								<span class="um-admin-icon-value"><?php if ( $this->edit_mode_value ) { ?><i class="<?php echo $this->edit_mode_value; ?>"></i><?php } else { ?><?php _e( 'No Icon', 'ultimate-member' ) ?><?php } ?></span>

								<input type="hidden" name="_icon" id="_icon" value="<?php echo (isset( $this->edit_mode_value ) ) ? $this->edit_mode_value : ''; ?>" />

								<?php if ( $this->edit_mode_value ) { ?>
									<span class="um-admin-icon-clear show"><i class="um-icon-android-cancel"></i></span>
								<?php } else { ?>
									<span class="um-admin-icon-clear"><i class="um-icon-android-cancel"></i></span>
								<?php } ?>

							</p>

						</div>

						<?php

					}

					break;

				case '_css_class':
					?>

					<p><label for="_css_class"><?php _e( 'CSS Class', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Specify a custom CSS class to be applied to this element', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_css_class" id="_css_class" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_width':
					?>

					<p><label for="_width"><?php _e( 'Thickness (in pixels)', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This is the width in pixels, e.g. 4 or 2, etc', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_width" id="_width" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : 4; ?>" />
					</p>

					<?php
					break;

				case '_divider_text':
					?>

					<p><label for="_divider_text"><?php _e( 'Optional Text', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Optional text to include with the divider', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_divider_text" id="_divider_text" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_padding':
					?>

					<p><label for="_padding"><?php _e( 'Padding', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Set padding for this section', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_padding" id="_padding" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : '0px 0px 0px 0px'; ?>" />
					</p>

					<?php
					break;

				case '_margin':
					?>

					<p><label for="_margin"><?php _e( 'Margin', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Set margin for this section', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_margin" id="_margin" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : '0px 0px 30px 0px'; ?>" />
					</p>

					<?php
					break;

				case '_border':
					?>

					<p><label for="_border"><?php _e( 'Border', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Set border for this section', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_border" id="_border" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : '0px 0px 0px 0px'; ?>" />
					</p>

					<?php
					break;

				case '_borderstyle':
					?>

					<p><label for="_borderstyle"><?php _e( 'Style', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Choose the border style', 'ultimate-member' ) ); ?></label>
						<select name="_borderstyle" id="_borderstyle" style="width: 100%">
							<option value="solid"  <?php selected( 'solid', $this->edit_mode_value ); ?>><?php _e( 'Solid', 'ultimate-member' ) ?></option>
							<option value="dotted" <?php selected( 'dotted', $this->edit_mode_value ); ?>><?php _e( 'Dotted', 'ultimate-member' ) ?></option>
							<option value="dashed" <?php selected( 'dashed', $this->edit_mode_value ); ?>><?php _e( 'Dashed', 'ultimate-member' ) ?></option>
							<option value="double" <?php selected( 'double', $this->edit_mode_value ); ?>><?php _e( 'Double', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_borderradius':
					?>

					<p><label for="_borderradius"><?php _e( 'Border Radius', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Rounded corners can be applied by setting a pixels value here. e.g. 5px', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_borderradius" id="_borderradius" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : '0px'; ?>" />
					</p>

					<?php
					break;

				case '_bordercolor':
					?>

					<p><label for="_bordercolor"><?php _e( 'Border Color', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Give a color to this border', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_bordercolor" id="_bordercolor" class="um-admin-colorpicker" data-default-color="" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_heading':
					?>

					<p><label for="_heading"><?php _e( 'Enable Row Heading', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Whether to enable a heading for this row', 'ultimate-member' ) ); ?></label>
						<input type="checkbox" name="_heading" id="_heading" value="1" <?php checked( isset( $this->edit_mode_value ) ? $this->edit_mode_value : 0 ) ?> class="um-adm-conditional" data-cond1="1" data-cond1-show="_heading_text" data-cond1-hide="xxx" />
					</p>

					<?php
					break;

				case '_heading_text':
					?>

					<p class="_heading_text"><label for="_heading_text"><?php _e( 'Heading Text', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Enter the row heading text here', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_heading_text" id="_heading_text" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_background':
					?>

					<p><label for="_background"><?php _e( 'Background Color', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This will be the background of entire section', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_background" id="_background" class="um-admin-colorpicker" data-default-color="" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_heading_background_color':
					?>

					<p class="_heading_text"><label for="_heading_background_color"><?php _e( 'Heading Background Color', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This will be the background of the heading section', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_heading_background_color" id="_heading_background_color" class="um-admin-colorpicker" data-default-color="" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_heading_text_color':
					?>

					<p class="_heading_text"><label for="_heading_text_color"><?php _e( 'Heading Text Color', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This will be the text color of heading part only', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_heading_text_color" id="_heading_text_color" class="um-admin-colorpicker" data-default-color="" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_text_color':
					?>

					<p><label for="_text_color"><?php _e( 'Text Color', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This will be the text color of entire section', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_text_color" id="_text_color" class="um-admin-colorpicker" data-default-color="" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_icon_color':
					?>

					<p class="_heading_text"><label for="_icon_color"><?php _e( 'Icon Color', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This will be the color of selected icon. By default It will be the same color as heading text color', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_icon_color" id="_icon_color" class="um-admin-colorpicker" data-default-color="" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_color':
					?>

					<p><label for="_color"><?php _e( 'Color', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Select a color for this divider', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_color" id="_color" class="um-admin-colorpicker" data-default-color="#eeeeee" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : '#eeeeee'; ?>" />
					</p>

					<?php
					break;

				case '_url_text':
					?>

					<p><label for="_url_text"><?php _e( 'URL Alt Text', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Entering custom text here will replace the url with a text link', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_url_text" id="_url_text" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : ''; ?>" />
					</p>

					<?php
					break;

				case '_url_target':
					?>

					<p><label for="_url_target"><?php _e( 'Link Target', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Choose whether to open this link in same window or in a new window', 'ultimate-member' ) ); ?></label>
						<select name="_url_target" id="_url_target" style="width: 100%">
							<option value="_blank" <?php selected( '_blank', $this->edit_mode_value ); ?>><?php _e( 'Open in new window', 'ultimate-member' ) ?></option>
							<option value="_self"  <?php selected( '_self', $this->edit_mode_value ); ?>><?php _e( 'Same window', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_url_rel':
					?>

					<p><label for="_url_rel"><?php _e( 'SEO Follow', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Whether to follow or nofollow this link by search engines', 'ultimate-member' ) ); ?></label>
						<select name="_url_rel" id="_url_rel" style="width: 100%">
							<option value="follow"  <?php selected( 'follow', $this->edit_mode_value ); ?>><?php _e( 'Follow', 'ultimate-member' ) ?></option>
							<option value="nofollow" <?php selected( 'nofollow', $this->edit_mode_value ); ?>><?php _e( 'No-Follow', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_force_good_pass':
					?>

					<p><label for="_force_good_pass"><?php _e( 'Force strong password?', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Turn on to force users to create a strong password (A combination of one lowercase letter, one uppercase letter, and one number). If turned on this option is only applied to register forms and not to login forms.', 'ultimate-member' ) ); ?></label>
						<input type="checkbox" name="_force_good_pass" id="_force_good_pass" value="1" <?php checked( isset( $this->edit_mode_value ) ? $this->edit_mode_value : 0 ) ?> />
					</p>

					<?php
					break;

				case '_force_confirm_pass':
					?>

					<p><label for="_force_confirm_pass"><?php _e( 'Automatically add a confirm password field?', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Turn on to add a confirm password field. If turned on the confirm password field will only show on register forms and not on login forms.', 'ultimate-member' ) ); ?></label>
						<input type="checkbox" name="_force_confirm_pass" id="_force_confirm_pass" value="1" <?php checked( isset( $this->edit_mode_value ) ? $this->edit_mode_value : 0 ) ?> />
					</p>

					<?php
					break;

				case '_style':
					?>

					<p><label for="_style"><?php _e( 'Style', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This is the line-style of divider', 'ultimate-member' ) ); ?></label>
						<select name="_style" id="_style" style="width: 100%">
							<option value="solid"  <?php selected( 'solid', $this->edit_mode_value ); ?>><?php _e( 'Solid', 'ultimate-member' ) ?></option>
							<option value="dotted" <?php selected( 'dotted', $this->edit_mode_value ); ?>><?php _e( 'Dotted', 'ultimate-member' ) ?></option>
							<option value="dashed" <?php selected( 'dashed', $this->edit_mode_value ); ?>><?php _e( 'Dashed', 'ultimate-member' ) ?></option>
							<option value="double" <?php selected( 'double', $this->edit_mode_value ); ?>><?php _e( 'Double', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_intervals':

					?>

					<p><label for="_intervals"><?php _e( 'Time Intervals (in minutes)', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Choose the minutes interval between each time in the time picker.', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_intervals" id="_intervals" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : 60; ?>" placeholder="<?php esc_attr_e( 'e.g. 30, 60, 120', 'ultimate-member' ) ?>" />
					</p>

					<?php
					break;


				case '_format':

					if ( $this->set_field_type == 'date' ) {
						?>

						<p><label for="_format"><?php _e( 'Date User-Friendly Format', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'The display format of the date which is visible to user.', 'ultimate-member' ) ); ?></label>
							<select name="_format" id="_format" style="width: 100%">
								<option value="j M Y" <?php selected( 'j M Y', $this->edit_mode_value ); ?>><?php echo UM()->datetime()->get_time('j M Y'); ?></option>
								<option value="M j Y" <?php selected( 'M j Y', $this->edit_mode_value ); ?>><?php echo UM()->datetime()->get_time('M j Y'); ?></option>
								<option value="j F Y" <?php selected( 'j F Y', $this->edit_mode_value ); ?>><?php echo UM()->datetime()->get_time('j F Y'); ?></option>
								<option value="F j Y" <?php selected( 'F j Y', $this->edit_mode_value ); ?>><?php echo UM()->datetime()->get_time('F j Y'); ?></option>
							</select>
						</p>

					<?php } else { ?>

						<p><label for="_format"><?php _e( 'Time Format', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Choose the displayed time-format for this field', 'ultimate-member' ) ); ?></label>
							<select name="_format" id="_format" style="width: 100%">
								<option value="g:i a" <?php selected( 'g:i a', $this->edit_mode_value ); ?>><?php echo UM()->datetime()->get_time('g:i a'); ?><?php _e( '( 12-hr format )', 'ultimate-member' ) ?></option>
								<option value="g:i A" <?php selected( 'g:i A', $this->edit_mode_value ); ?>><?php echo UM()->datetime()->get_time('g:i A'); ?><?php _e( '( 12-hr format )', 'ultimate-member' ) ?></option>
								<option value="H:i"  <?php selected( 'H:i', $this->edit_mode_value ); ?>><?php echo UM()->datetime()->get_time('H:i'); ?><?php _e( '( 24-hr format )', 'ultimate-member' ) ?></option>
							</select>
						</p>

						<?php
					}
					break;

				case '_format_custom':
					?>

					<p><label for="_format_custom"><?php _e( 'Use custom Date format', 'ultimate-member' ); ?> <?php UM()->tooltip( __( 'This option overrides "Date User-Friendly Format" option. See https://www.php.net/manual/en/function.date.php', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_format_custom" id="_format_custom" value="<?php echo htmlspecialchars( $this->edit_mode_value, ENT_QUOTES ); ?>" placeholder="j M Y" />
					</p>

					<?php
					break;

				case '_pretty_format':
					?>

					<p><label for="_pretty_format"><?php _e( 'Displayed Date Format', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Whether you wish to show the date in full or only show the years e.g. 25 Years', 'ultimate-member' ) ); ?></label>
						<select name="_pretty_format" id="_pretty_format" style="width: 100%">
							<option value="0" <?php selected( 0, $this->edit_mode_value ); ?>><?php _e( 'Show full date', 'ultimate-member' ) ?></option>
							<option value="1" <?php selected( 1, $this->edit_mode_value ); ?>><?php _e( 'Show years only', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_disabled_weekdays':

					if ( isset( $this->edit_mode_value ) && is_array( $this->edit_mode_value ) ) {
						$values = $this->edit_mode_value;
					} else {
						$values = array('');
					}
					?>

					<p><label for="_disabled_weekdays"><?php _e( 'Disable specific weekdays', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Disable specific week days from being available for selection in this date picker', 'ultimate-member' ) ); ?></label>
						<select name="_disabled_weekdays[]" id="_disabled_weekdays" multiple="multiple" style="width: 100%">
							<option value="1" <?php if ( in_array( 1, $values ) ) { echo 'selected'; } ?>><?php _e( 'Sunday', 'ultimate-member' ) ?></option>
							<option value="2" <?php if ( in_array( 2, $values ) ) { echo 'selected'; } ?>><?php _e( 'Monday', 'ultimate-member' ) ?></option>
							<option value="3" <?php if ( in_array( 3, $values ) ) { echo 'selected'; } ?>><?php _e( 'Tuesday', 'ultimate-member' ) ?></option>
							<option value="4" <?php if ( in_array( 4, $values ) ) { echo 'selected'; } ?>><?php _e( 'Wednesday', 'ultimate-member' ) ?></option>
							<option value="5" <?php if ( in_array( 5, $values ) ) { echo 'selected'; } ?>><?php _e( 'Thursday', 'ultimate-member' ) ?></option>
							<option value="6" <?php if ( in_array( 6, $values ) ) { echo 'selected'; } ?>><?php _e( 'Friday', 'ultimate-member' ) ?></option>
							<option value="7" <?php if ( in_array( 7, $values ) ) { echo 'selected'; } ?>><?php _e( 'Saturday', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_years':
					?>

					<p class="_years"><label for="_years"><?php _e( 'Number of Years to pick from', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Number of years available for the date selection. Default to last 50 years', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_years" id="_years" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : 50; ?>" />
					</p>

					<?php
					break;

				case '_years_x':
					?>

					<p class="_years"><label for="_years_x"><?php _e( 'Years Selection', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This decides which years should be shown relative to today date', 'ultimate-member' ) ); ?></label>
						<select name="_years_x" id="_years_x" style="width: 100%">
							<option value="equal"  <?php selected( 'equal', $this->edit_mode_value ); ?>><?php _e( 'Equal years before / after today', 'ultimate-member' ) ?></option>
							<option value="past" <?php selected( 'past', $this->edit_mode_value ); ?>><?php _e( 'Past years only', 'ultimate-member' ) ?></option>
							<option value="future" <?php selected( 'future', $this->edit_mode_value ); ?>><?php _e( 'Future years only', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_range_start':
					?>

					<p class="_date_range"><label for="_range_start"><?php _e( 'Date Range Start', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Set the minimum date/day in range in the format YYYY/MM/DD', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_range_start" id="_range_start" value="<?php echo $this->edit_mode_value; ?>" placeholder="<?php esc_attr_e( 'YYYY/MM/DD', 'ultimate-member' ) ?>" />
					</p>

					<?php
					break;

				case '_range_end':
					?>

					<p class="_date_range"><label for="_range_end"><?php _e( 'Date Range End', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Set the maximum date/day in range in the format YYYY/MM/DD', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_range_end" id="_range_end" value="<?php echo $this->edit_mode_value; ?>" placeholder="<?php esc_attr_e( 'YYYY/MM/DD', 'ultimate-member' ) ?>" />
					</p>

					<?php
					break;

				case '_range':
					?>

					<p><label for="_range"><?php _e( 'Set Date Range', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Whether to show a specific number of years or specify a date range to be available for the date picker.', 'ultimate-member' ) ); ?></label>
						<select name="_range" id="_range" class="um-adm-conditional" data-cond1='years' data-cond1-show='_years' data-cond2="date_range" data-cond2-show="_date_range" style="width: 100%">
							<option value="years" <?php selected( 'years', $this->edit_mode_value ); ?>><?php _e( 'Fixed Number of Years', 'ultimate-member' ) ?></option>
							<option value="date_range" <?php selected( 'date_range', $this->edit_mode_value ); ?>><?php _e( 'Specific Date Range', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_content':

					if ( $this->set_field_type == 'shortcode' ) {

						?>

						<p><label for="_content"><?php _e( 'Enter Shortcode', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Enter the shortcode in the following textarea and it will be displayed on the fields', 'ultimate-member' ) ); ?></label>
							<textarea name="_content" id="_content" placeholder="<?php esc_attr_e( 'e.g. [my_custom_shortcode]', 'ultimate-member' ) ?>"><?php echo $this->edit_mode_value; ?></textarea>
						</p>

						<?php

					} else {

						?>

						<div class="um-admin-editor-h"><label><?php _e( 'Content Editor', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Edit the content of this field here', 'ultimate-member' ) ); ?></label></div>

						<div class="um-admin-editor"><!-- editor dynamically loaded here --></div>

						<?php

					}

					break;

				case '_crop':
					?>

					<p><label for="_crop"><?php _e( 'Crop Feature', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Enable/disable crop feature for this image upload and define ratio', 'ultimate-member' ) ); ?></label>
						<select name="_crop" id="_crop" style="width: 100%">
							<option value="0" <?php selected( '0', $this->edit_mode_value ); ?>><?php _e( 'Turn Off (Default)', 'ultimate-member' ) ?></option>
							<option value="1" <?php selected( '1', $this->edit_mode_value ); ?>><?php _e( 'Crop and force 1:1 ratio', 'ultimate-member' ) ?></option>
							<option value="3" <?php selected( '3', $this->edit_mode_value ); ?>><?php _e( 'Crop and force user-defined ratio', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_allowed_types':

					if ( $this->set_field_type == 'image' ) {

						if ( isset( $this->edit_mode_value ) && is_array( $this->edit_mode_value ) ) {
							$values = $this->edit_mode_value;
						} else {
							$values = array( 'png','jpeg','jpg','gif' );
						} ?>

						<p><label for="_allowed_types"><?php _e( 'Allowed Image Types', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Select the image types that you want to allow to be uploaded via this field.', 'ultimate-member' ) ); ?></label>
							<select name="_allowed_types[]" id="_allowed_types" multiple="multiple" style="width: 100%">
								<?php foreach( UM()->files()->allowed_image_types() as $e => $n ) { ?>
									<option value="<?php echo $e; ?>" <?php if ( in_array( $e, $values ) ) { echo 'selected'; } ?>><?php echo $n; ?></option>
								<?php } ?>
							</select>
						</p>

						<?php

					} else {

						if ( isset( $this->edit_mode_value ) && is_array( $this->edit_mode_value ) ) {
							$values = $this->edit_mode_value;
						} else {
							$values = array( 'pdf', 'txt' );
						} ?>

						<p><label for="_allowed_types"><?php _e( 'Allowed File Types', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Select the image types that you want to allow to be uploaded via this field.', 'ultimate-member' ) ); ?></label>
							<select name="_allowed_types[]" id="_allowed_types" multiple="multiple" style="width: 100%">
								<?php foreach( UM()->files()->allowed_file_types() as $e => $n ) { ?>
									<option value="<?php echo $e; ?>" <?php if ( in_array( $e, $values ) ) { echo 'selected'; } ?>><?php echo $n; ?></option>
								<?php } ?>
							</select>
						</p>

						<?php

					}

					break;

				case '_upload_text':

					if ( $this->set_field_type == 'image' ) {
						$value = __( 'Drag &amp; Drop Photo', 'ultimate-member' );
					}
					if ( $this->set_field_type == 'file' ) {
						$value = __( 'Drag &amp; Drop File', 'ultimate-member' );
					}

					?>

					<p><label for="_upload_text"><?php _e( 'Upload Box Text', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This is the headline that appears in the upload box for this field', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_upload_text" id="_upload_text" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : $value; ?>" />
					</p>

					<?php
					break;

				case '_upload_help_text':
					?>

					<p><label for="_upload_help_text"><?php _e( 'Additional Instructions Text', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'If you need to add information or secondary line below the headline of upload box, enter it here', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_upload_help_text" id="_upload_help_text" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php
					break;

				case '_button_text':
					?>

					<p><label for="_button_text"><?php _e( 'Upload Box Text', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'The text that appears on the button. e.g. Upload', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_button_text" id="_button_text" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : __( 'Upload', 'ultimate-member' ); ?>" />
					</p>

					<?php
					break;

				case '_max_size':
					?>

					<p><label for="_max_size"><?php _e( 'Maximum Size in bytes', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'The maximum size for image that can be uploaded through this field. Leave empty for unlimited size.', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_max_size" id="_max_size" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php
					break;

				case '_height':
					?>

					<p><label for="_height"><?php _e( 'Textarea Height', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'The height of textarea in pixels. Default is 100 pixels', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_height" id="_height" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : '100px'; ?>" />
					</p>

					<?php
					break;

				case '_spacing':
					?>

					<p><label for="_spacing"><?php _e( 'Spacing', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This is the required spacing in pixels. e.g. 20px', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_spacing" id="_spacing" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : '20px'; ?>" />
					</p>

					<?php
					break;

				case '_is_multi':
					?>

					<p><label for="_is_multi"><?php _e( 'Allow multiple selections', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Enable/disable multiple selections for this field', 'ultimate-member' ) ); ?></label>
						<input type="checkbox" name="_is_multi" id="_is_multi" value="1" <?php checked( isset( $this->edit_mode_value ) ? $this->edit_mode_value : 0 ) ?> class="um-adm-conditional" data-cond1="1" data-cond1-show="_max_selections" data-cond1-hide="xxx" />
					</p>

					<?php
					break;

				case '_max_selections':
					?>

					<p class="_max_selections"><label for="_max_selections"><?php _e( 'Maximum number of selections', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Enter a number here to force a maximum number of selections by user for this field', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_max_selections" id="_max_selections" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php
					break;

				case '_min_selections':
					?>

					<p class="_min_selections"><label for="_min_selections"><?php _e( 'Minimum number of selections', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Enter a number here to force a minimum number of selections by user for this field', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_min_selections" id="_min_selections" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php
					break;

				case '_max_entries':
					?>

					<p class="_max_entries"><label for="_max_selections"><?php _e( 'Maximum number of entries', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This is the max number of entries the user can add via field group.', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_max_entries" id="_max_entries" value="<?php echo ( $this->edit_mode_value ) ? $this->edit_mode_value : 10; ?>" />
					</p>

					<?php
					break;

				case '_max_words':
					?>

					<p><label for="_max_words"><?php _e( 'Maximum allowed words', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'If you want to enable a maximum number of words to be input in this textarea. Leave empty to disable this setting', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_max_words" id="_max_words" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php
					break;

				case '_min':
					?>

					<p><label for="_min"><?php _e( 'Minimum Number', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Minimum number that can be entered in this field', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_min" id="_min" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php
					break;

				case '_max':
					?>

					<p><label for="_max"><?php _e( 'Maximum Number', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Maximum number that can be entered in this field', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_max" id="_max" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php
					break;

				case '_min_chars':
					?>

					<p><label for="_min_chars"><?php _e( 'Minimum length', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'If you want to enable a minimum number of characters to be input in this field. Leave empty to disable this setting', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_min_chars" id="_min_chars" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php
					break;

				case '_max_chars':
					?>

					<p><label for="_max_chars"><?php _e( 'Maximum length', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'If you want to enable a maximum number of characters to be input in this field. Leave empty to disable this setting', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_max_chars" id="_max_chars" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php
					break;

				case '_html':
					?>

					<p><label for="_html"><?php _e( 'Does this textarea accept HTML?', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Turn on/off HTML tags for this textarea', 'ultimate-member' ) ); ?></label>
						<input type="checkbox" name="_html" id="_html" value="1" <?php checked( isset( $this->edit_mode_value ) ? $this->edit_mode_value : 0 ) ?> />
					</p>

					<?php
					break;

				case '_options':

					if ( isset( $this->edit_mode_value ) && is_array( $this->edit_mode_value ) ) {
						$values = implode("\n", $this->edit_mode_value);
					} else if ( $this->edit_mode_value ) {
						$values = $this->edit_mode_value;
					} else {
						$values = '';
					} ?>

					<p><label for="_options"><?php _e( 'Edit Choices', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Enter one choice per line. This will represent the available choices or selections available for user.', 'ultimate-member' ) ); ?></label>
						<textarea name="_options" id="_options"><?php echo $values; ?></textarea>
					</p>

					<?php
					break;

				case '_title':
					?>

					<p><label for="_title"><?php _e( 'Title', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This is the title of the field for your reference in the backend. The title will not appear on the front-end of your website.', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_title" id="_title" value="<?php echo htmlspecialchars( $this->edit_mode_value, ENT_QUOTES ); ?>" />
					</p>

					<?php
					break;

				case '_id':

					?>

					<p style="display:none"><label for="_id"><?php _e( 'Unique ID', 'ultimate-member' ) ?></label>
						<input type="text" name="_id" id="_id" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php

					break;

				case '_metakey':

					if ( $this->in_edit ) {

						?>

						<p><label for="_metakey"><?php _e( 'Meta Key', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'The meta key cannot be changed for duplicated fields or when editing an existing field. If you require a different meta key please create a new field.', 'ultimate-member' ) ); ?></label>
							<input type="text" name="_metakey_locked" id="_metakey_locked" value="<?php echo $this->edit_mode_value; ?>" disabled />
						</p>

					<?php } else { ?>

						<p><label for="_metakey"><?php _e( 'Meta Key', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'A meta key is required to store the entered info in this field in the database. The meta key should be unique to this field and be written in lowercase with an underscore ( _ ) separating words e.g country_list or job_title', 'ultimate-member' ) ); ?></label>
							<input type="text" name="_metakey" id="_metakey" value="" />
						</p>

						<?php

					}

					break;

				case '_help':
					?>

					<p><label for="_help"><?php _e( 'Help Text', 'ultimate-member' ) ?> <?php UM()->tooltip( __('This is the text that appears in a tooltip when a user hovers over the info icon. Help text is useful for providing users with more information about what they should enter in the field. Leave blank if no help text is needed for field.', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_help" id="_help" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

					<?php
					break;

				case '_default':
					?>

					<?php if ( $this->set_field_type == 'textarea' ) { ?>

					<p><label for="_default"><?php _e( 'Default Text', 'ultimate-member' ); ?> <?php UM()->tooltip( __( 'Text to display by default in this field', 'ultimate-member' ) ); ?></label>
						<textarea name="_default" id="_default"><?php echo $this->edit_mode_value; ?></textarea>
					</p>

				<?php } elseif ( $this->set_field_type == 'date' ) { ?>

					<p class="um"><label for="_default"><?php _e( 'Default Date', 'ultimate-member' ); ?> <?php UM()->tooltip( __( 'You may use all PHP compatible date formats such as: 2020-02-02, 02/02/2020, yesterday, today, tomorrow, next monday, first day of next month, +3 day', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_default" id="_default" value="<?php echo $this->edit_mode_value; ?>" class="um-datepicker" data-format="yyyy/mm/dd" />
					</p>

				<?php } elseif ( $this->set_field_type == 'time' ) { ?>

					<p class="um"><label for="_default"><?php _e( 'Default Time', 'ultimate-member' ); ?> <?php UM()->tooltip( __( 'You may use all PHP compatible date formats such as: 2020-02-02, 02/02/2020, yesterday, today, tomorrow, next monday, first day of next month, +3 day', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_default" id="_default" value="<?php echo $this->edit_mode_value; ?>" class="um-timepicker" data-format="HH:i" />
					</p>

				<?php } elseif ( $this->set_field_type == 'rating' ) { ?>

					<p><label for="_default"><?php _e( 'Default Rating', 'ultimate-member' ); ?> <?php UM()->tooltip( __( 'If you wish the rating field to be prefilled with a number of stars, enter it here.', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_default" id="_default" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

				<?php } else { ?>

					<p><label for="_default"><?php _e( 'Default Value', 'ultimate-member' ); ?> <?php UM()->tooltip( __( 'This option allows you to pre-fill the field with a default value prior to the user entering a value in the field. Leave blank to have no default value', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_default" id="_default" value="<?php echo $this->edit_mode_value; ?>" />
					</p>

				<?php } ?>

					<?php
					break;

				case '_label':
					?>

					<p><label for="_label"><?php _e( 'Label', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'The field label is the text that appears above the field on your front-end form. Leave blank to not show a label above field.', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_label" id="_label" value="<?php echo htmlspecialchars( $this->edit_mode_value, ENT_QUOTES ); ?>" />
					</p>

					<?php
					break;

				case '_placeholder':
					?>

					<p><label for="_placeholder"><?php _e( 'Placeholder', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This is the text that appears within the field e.g please enter your email address. Leave blank to not show any placeholder text.', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_placeholder" id="_placeholder" value="<?php echo htmlspecialchars( $this->edit_mode_value, ENT_QUOTES ); ?>" />
					</p>

					<?php
					break;

				case '_public':
					$privacy_options = array(
						'1'     => __( 'Everyone', 'ultimate-member' ),
						'2'     => __( 'Members', 'ultimate-member' ),
						'-1'    => __( 'Only visible to profile owner and admins', 'ultimate-member' ),
						'-3'    => __( 'Only visible to profile owner and specific roles', 'ultimate-member' ),
						'-2'    => __( 'Only specific member roles', 'ultimate-member' ),
					);

					$privacy_options = apply_filters( 'um_field_privacy_options', $privacy_options ); ?>

					<p>
						<label for="_public"><?php _e( 'Privacy', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Field privacy allows you to select who can view this field on the front-end. The site admin can view all fields regardless of the option set here.', 'ultimate-member' ) ); ?></label>
						<select name="_public" id="_public" class="um-adm-conditional" data-cond1="-2" data-cond1-show="_roles" data-cond2="-3" data-cond2-show="_roles"  style="width: 100%">
							<?php foreach ( $privacy_options as $value => $title ) { ?>
								<option value="<?php echo esc_attr( $value ) ?>" <?php selected( $value, $this->edit_mode_value ); ?>>
									<?php echo $title ?>
								</option>
							<?php } ?>
						</select>
					</p>

					<?php
					break;

				case '_roles':

					if ( isset( $this->edit_mode_value ) && is_array( $this->edit_mode_value ) ) {
						$values = $this->edit_mode_value;
					} else {
						$values = array('');
					}

					?>

					<p class="_roles"><label for="_roles"><?php _e( 'Select member roles', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Select the member roles that can view this field on the front-end.', 'ultimate-member' ) ); ?></label>
						<select name="_roles[]" id="_roles" style="width: 100%" multiple="multiple">

							<?php foreach ( UM()->roles()->get_roles() as $key => $value) { ?>

								<option value="<?php echo $key; ?>" <?php if ( in_array( $key, $values ) ) { echo 'selected'; } ?>><?php echo $value; ?></option>

							<?php } ?>

						</select>
					</p>

					<?php
					break;

				case '_required':

					if ( $this->set_field_type == 'password' )
						$def_required = 1;
					else
						$def_required = 0;

					?>

					<div class="um-admin-tri">

						<p><label for="_required"><?php _e( 'Is this field required?', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This option allows you to set whether the field must be filled in before the form can be processed.', 'ultimate-member' ) ); ?></label>
							<input type="checkbox" name="_required" id="_required" value="1" <?php checked( isset( $this->edit_mode_value ) ? $this->edit_mode_value : $def_required ) ?> />
						</p>

					</div>

					<?php
					break;

				case '_editable':
					?>

					<div class="um-admin-tri">

						<p><label for="_editable"><?php _e( 'Can user edit this field?', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'This option allows you to set whether or not the user can edit the information in this field.', 'ultimate-member' ) ); ?></label>
							<input type="hidden" name="_editable" id="_editable_hidden" value="0" />
							<input type="checkbox" name="_editable" id="_editable" value="1" <?php checked( null === $this->edit_mode_value || $this->edit_mode_value ) ?> />
						</p>

					</div>

					<?php
					break;

				case '_number':
					?>

					<p><label for="_number"><?php _e( 'Rating System', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Choose whether you want a 5-stars or 10-stars ratings based here.', 'ultimate-member' ) ); ?></label>
						<select name="_number" id="_number" style="width: 100%">
							<option value="5" <?php selected( 5, $this->edit_mode_value ); ?>><?php _e( '5  stars rating system', 'ultimate-member' ) ?></option>
							<option value="10" <?php selected( 10, $this->edit_mode_value ); ?>><?php _e( '10 stars rating system', 'ultimate-member' ) ?></option>
						</select>
					</p>

					<?php
					break;

				case '_custom_dropdown_options_source':
					?>

					<p><label for="_custom_dropdown_options_source"><?php _e( 'Choices Callback', 'ultimate-member' ) ?> <?php UM()->tooltip( __( 'Add a callback source to retrieve choices.', 'ultimate-member' ) ); ?></label>
						<input type="text" name="_custom_dropdown_options_source" id="_custom_dropdown_options_source" value="<?php echo htmlspecialchars($this->edit_mode_value, ENT_QUOTES); ?>" />
					</p>

					<?php
					break;


				case '_parent_dropdown_relationship':
					?>

					<p><label for="_parent_dropdown_relationship"><?php _e( 'Parent Option', 'ultimate-member' ) ?><?php UM()->tooltip( __( 'Dynamically populates the option based from selected parent option.', 'ultimate-member' ) ); ?></label>
						<select name="_parent_dropdown_relationship" id="_parent_dropdown_relationship" style="width: 100%">
							<option value=""><?php _e( 'No Selected', 'ultimate-member' ) ?></option>

							<?php if ( UM()->builtin()->custom_fields ) {
								foreach ( UM()->builtin()->custom_fields as $field_key => $array ) {
									if ( in_array( $array['type'], array( 'select' ) ) && ( ! isset( $field_args['metakey'] ) || $field_args['metakey'] != $array['metakey'] ) && isset( $array['title'] ) ) { ?>
										<option value="<?php echo esc_attr( $array['metakey'] ) ?>" <?php selected( $array['metakey'], $this->edit_mode_value ) ?>><?php echo $array['title'] ?></option>
									<?php }
								}
							} ?>
						</select>
					</p>

					<?php
					break;


			}

		}

	}
}