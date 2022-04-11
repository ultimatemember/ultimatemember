<?php
namespace umm\member_directory\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Metabox
 *
 * @package umm\member_directory\includes\admin
 */
class Metabox {


	private $nonce_added = false;


	var $member_directory_meta = array();


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_filter( 'enter_title_here', array( &$this, 'enter_title_here' ), 10, 2 );
		add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );
		add_action( 'admin_init', array( &$this, 'remove_meta_box' ), 0 );

		add_action( 'load-post.php', array( &$this, 'add_metabox' ), 9 );
		add_action( 'load-post-new.php', array( &$this, 'add_metabox' ), 9 );

		add_filter( 'um_member_directory_meta_value_before_save', array( $this, 'before_save_data' ), 10, 3 );

		add_action( 'admin_init', array( &$this, 'init_variables' ), 0 );
	}


	function init_variables() {
		$this->member_directory_meta = apply_filters(
			'um_member_directory_meta_map',
			array(
				'_um_directory_template'       => array(
					'sanitize' => 'text',
				),
				'_um_mode'                     => array(
					'sanitize' => 'key',
				),
				'_um_view_types'               => array(
					'sanitize' => array( $this, 'sanitize_md_view_types' ),
				),
				'_um_default_view'             => array(
					'sanitize' => 'key',
				),
				'_um_roles'                    => array(
					'sanitize' => array( UM()->admin(), 'sanitize_restriction_existed_role' ),
				),
				'_um_has_profile_photo'        => array(
					'sanitize' => 'bool',
				),
				'_um_show_these_users'         => array(
					'sanitize' => 'textarea',
				),
				'_um_exclude_these_users'      => array(
					'sanitize' => 'textarea',
				),
				'_um_must_search'              => array(
					'sanitize' => 'bool',
				),
				'_um_max_users'                => array(
					'sanitize' => 'absint',
				),
				'_um_profiles_per_page'        => array(
					'sanitize' => 'absint',
				),
				'_um_profiles_per_page_mobile' => array(
					'sanitize' => 'absint',
				),
				'_um_directory_header'         => array(
					'sanitize' => 'text',
				),
				'_um_directory_header_single'  => array(
					'sanitize' => 'text',
				),
				'_um_directory_no_users'       => array(
					'sanitize' => 'text',
				),
				'_um_profile_photo'            => array(
					'sanitize' => 'bool',
				),
				'_um_cover_photos'             => array(
					'sanitize' => 'bool',
				),
				'_um_show_name'                => array(
					'sanitize' => 'bool',
				),
				'_um_show_tagline'             => array(
					'sanitize' => 'bool',
				),
				'_um_tagline_fields'           => array(
					'sanitize' => array( UM()->admin(), 'sanitize_user_field' ),
				),
				'_um_show_userinfo'            => array(
					'sanitize' => 'bool',
				),
				'_um_reveal_fields'            => array(
					'sanitize' => array( UM()->admin(), 'sanitize_user_field' ),
				),
				'_um_show_social'              => array(
					'sanitize' => 'bool',
				),
				'_um_userinfo_animate'         => array(
					'sanitize' => 'bool',
				),
				'_um_search'                   => array(
					'sanitize' => 'bool',
				),
				'_um_roles_can_search'         => array(
					'sanitize' => array( UM()->admin(), 'sanitize_restriction_existed_role' ),
				),
				'_um_filters'                  => array(
					'sanitize' => 'bool',
				),
				'_um_roles_can_filter'         => array(
					'sanitize' => array( UM()->admin(), 'sanitize_restriction_existed_role' ),
				),
				'_um_search_fields'            => array(
					'sanitize' => array( $this, 'sanitize_filter_fields' ),
				),
				'_um_filters_expanded'         => array(
					'sanitize' => 'bool',
				),
				'_um_filters_is_collapsible'   => array(
					'sanitize' => 'bool',
				),
				'_um_search_filters'           => array(
					'sanitize' => array( $this, 'sanitize_filter_fields' ),
				),
				'_um_sortby'                   => array(
					'sanitize' => 'text',
				),
				'_um_sortby_custom'            => array(
					'sanitize' => 'text',
				),
				'_um_sortby_custom_label'      => array(
					'sanitize' => 'text',
				),
				'_um_enable_sorting'           => array(
					'sanitize' => 'bool',
				),
				'_um_sorting_fields'           => array(
					'sanitize' => array( $this, 'sanitize_md_sorting_fields' ),
				),
			)
		);
	}


	/**
	 * @param array|string $value
	 *
	 * @return array|string
	 */
	public function sanitize_filter_fields( $value ) {
		$filter_fields = array_keys( UM()->module( 'member-directory' )->config()->get( 'filter_fields' ) );

		if ( '' !== $value ) {
			$value = array_filter(
				$value,
				function( $v, $k ) use ( $filter_fields ) {
					return in_array( sanitize_text_field( $v ), $filter_fields, true );
				},
				ARRAY_FILTER_USE_BOTH
			);

			$value = array_map( 'sanitize_text_field', $value );
		}

		return $value;
	}


	/**
	 * @param array|string $value
	 *
	 * @return array|string
	 */
	public function sanitize_md_sorting_fields( $value ) {
		$sort_fields = array_merge( UM()->module( 'member-directory' )->config()->get( 'sort_fields' ), array( 'other' => __( 'Other (Custom Field)', 'ultimate-member' ) ) );
		$sort_fields = array_keys( $sort_fields );

		if ( '' !== $value ) {
			$value = array_filter(
				$value,
				function( $v, $k ) use ( $sort_fields ) {
					if ( 'other_data' === $k ) {
						return true;
					} else {
						return in_array( sanitize_text_field( $v ), $sort_fields, true );
					}
				},
				ARRAY_FILTER_USE_BOTH
			);

			$value = array_map(
				function( $item ) {
					if ( is_array( $item ) ) {
						if ( isset( $item['meta_key'] ) ) {
							$item['meta_key'] = sanitize_text_field( $item['meta_key'] );
						}
						if ( isset( $item['label'] ) ) {
							$item['label'] = sanitize_text_field( $item['label'] );
						}

						return $item;
					} else {
						return sanitize_text_field( $item );
					}
				},
				$value
			);
		}

		return $value;
	}


	/**
	 * @param array|string $value
	 *
	 * @return array|string
	 */
	public function sanitize_md_view_types( $value ) {
		$view_types = array_map(
			function ( $item ) {
				return $item['title'];
			},
			UM()->module( 'member-directory' )->config()->get( 'view_types' )
		);
		$view_types = array_keys( $view_types );

		if ( '' !== $value ) {
			$value = array_filter(
				$value,
				function( $v, $k ) use ( $view_types ) {
					return in_array( sanitize_key( $k ), $view_types, true ) && 1 === (int) $v;
				},
				ARRAY_FILTER_USE_BOTH
			);

			$value = array_map( 'sanitize_key', $value );
		}

		return $value;
	}


	/**
	 * Sanitize member directory meta when wp-admin form has been submitted
	 *
	 * @todo checking all sanitize types
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function sanitize_member_directory_meta( $data ) {
		$sanitized = array();
		foreach ( $data as $k => $v ) {
			if ( ! array_key_exists( $k, $this->member_directory_meta ) ) {
				// @todo remove since 2.2.x and leave only continue
				$sanitized[ $k ] = $v;
				continue;
			}

			if ( ! array_key_exists( 'sanitize', $this->member_directory_meta[ $k ] ) ) {
				// @todo remove since 2.2.x and leave only continue
				$sanitized[ $k ] = $v;
				continue;
			}

			if ( is_callable( $this->member_directory_meta[ $k ]['sanitize'], true, $callable_name ) ) {
				add_filter( 'um_member_directory_meta_sanitize_' . $k, $this->member_directory_meta[ $k ]['sanitize'], 10, 1 );
			}

			switch ( $this->member_directory_meta[ $k ]['sanitize'] ) {
				default:
					$sanitized[ $k ] = apply_filters( 'um_member_directory_meta_sanitize_' . $k, $data[ $k ] );
					break;
				case 'int':
					$sanitized[ $k ] = (int) $v;
					break;
				case 'bool':
					$sanitized[ $k ] = (bool) $v;
					break;
				case 'url':
					if ( is_array( $v ) ) {
						$sanitized[ $k ] = array_map( 'esc_url_raw', $v );
					} else {
						$sanitized[ $k ] = esc_url_raw( $v );
					}
					break;
				case 'text':
					$sanitized[ $k ] = sanitize_text_field( $v );
					break;
				case 'textarea':
					$sanitized[ $k ] = sanitize_textarea_field( $v );
					break;
				case 'key':
					if ( is_array( $v ) ) {
						$sanitized[ $k ] = array_map( 'sanitize_key', $v );
					} else {
						$sanitized[ $k ] = sanitize_key( $v );
					}
					break;
				case 'absint':
					if ( is_array( $v ) ) {
						$sanitized[ $k ] = array_map( 'absint', $v );
					} else {
						$sanitized[ $k ] = absint( $v );
					}
					break;
			}
		}

		$data = $sanitized;

		$data = apply_filters( 'um_save_member_directory_meta_sanitize', $data );

		return $data;
	}


	/**
	 * Enter title placeholder
	 *
	 * @param string $title
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	function enter_title_here( $title, $post ) {
		if ( ! isset( $post->post_type ) ) {
			return $title;
		}

		if ( 'um_directory' === $post->post_type ) {
			$title = __( 'e.g. Member Directory', 'ultimate-member' );
		}

		return $title;
	}


	/**
	 * Updated post messages
	 *
	 * @param array $messages
	 *
	 * @return array
	 */
	function post_updated_messages( $messages ) {
		$messages['um_directory'] = array(
			0   => '',
			1   => __( 'Directory updated.', 'ultimate-member' ),
			2   => __( 'Custom field updated.', 'ultimate-member' ),
			3   => __( 'Custom field deleted.', 'ultimate-member' ),
			4   => __( 'Directory updated.', 'ultimate-member' ),
			5   => isset( $_GET['revision'] ) ? __( 'Directory restored to revision.', 'ultimate-member' ) : false,
			6   => __( 'Directory created.', 'ultimate-member' ),
			7   => __( 'Directory saved.', 'ultimate-member' ),
			8   => __( 'Directory submitted.', 'ultimate-member' ),
			9   => __( 'Directory scheduled.', 'ultimate-member' ),
			10  => __( 'Directory draft updated.', 'ultimate-member' ),
		);

		return $messages;
	}


	function remove_meta_box() {
		remove_meta_box( 'submitdiv', 'um_directory', 'core' );
		remove_meta_box( 'slugdiv', 'um_directory', 'core' );
	}


	/**
	 * Init the metaboxes
	 */
	function add_metabox() {
		global $current_screen;

		if ( $current_screen->id == 'um_directory' ) {
			add_action( 'add_meta_boxes', array(&$this, 'add_metabox_directory'), 1 );
			add_action( 'save_post', array(&$this, 'save_metabox_directory'), 10, 2 );
		}
	}


	/**
	 * Add directory metabox
	 */
	function add_metabox_directory() {
		add_meta_box('submitdiv', __( 'Publish', 'ultimate-member' ), array( UM()->admin()->metabox(), 'custom_submitdiv' ), 'um_directory', 'side', 'high' );

		add_meta_box( 'um-admin-form-general', __( 'General Options', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
		add_meta_box( 'um-admin-form-sorting', __( 'Sorting', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
		add_meta_box( 'um-admin-form-profile', __( 'Profile Card', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
		add_meta_box( 'um-admin-form-search', __( 'Search Options', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
		add_meta_box( 'um-admin-form-pagination', __( 'Results &amp; Pagination', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'normal', 'default' );
		add_meta_box( 'um-admin-form-shortcode', __( 'Shortcode', 'ultimate-member' ), array( &$this, 'load_metabox_directory' ), 'um_directory', 'side', 'default' );
		add_meta_box( 'um-admin-form-appearance', __( 'Styling: General', 'ultimate-member' ), array( &$this, 'load_metabox_directory'), 'um_directory', 'side', 'default' );
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
		if ( ! $this->nonce_added ) {
			$this->nonce_added = true;
			wp_nonce_field( basename( __FILE__ ), 'um_admin_save_metabox_directory_nonce' );
		}
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

		delete_post_meta( $post_id, '_um_sorting_fields' );

		//save metadata
		$metadata = $this->sanitize_member_directory_meta( $_POST['um_metadata'] );
		foreach ( $metadata as $k => $v ) {

			if ( $k == '_um_show_these_users' && trim( $v ) ) {
				$v = preg_split( '/[\r\n]+/', $v, -1, PREG_SPLIT_NO_EMPTY );
			}

			if ( $k == '_um_exclude_these_users' && trim( $v ) ) {
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

		update_post_meta( $post_id, '_um_search_filters_gmt', (int) $_POST['um-gmt-offset'] );
	}


	/**
	 * @param $value
	 * @param $key
	 * @param $post_id
	 *
	 * @return array
	 */
	public function before_save_data( $value, $key, $post_id ) {
		$post = get_post( $post_id );

		if ( 'um_directory' === $post->post_type ) {

			if ( ! empty( $value ) && in_array( $key, array( '_um_view_types', '_um_roles', '_um_roles_can_search', '_um_roles_can_filter' ), true ) ) {
				$value = array_keys( $value );
			} elseif ( '_um_search_filters' === $key ) {

				$temp_value = array();

				if ( ! empty( $value ) ) {
					$filter_types = UM()->module( 'member-directory' )->config()->get( 'filter_types' );
					foreach ( $value as $k ) {
						$filter_type = $filter_types[ $k ];
						if ( ! empty( $filter_type ) ) {
							if ( 'slider' === $filter_type ) {
								if ( ! empty( $_POST[ $k ] ) ) {
									if ( count( $_POST[ $k ] ) > 1 ) {
										$temp_value[ $k ] = array_map( 'intval', $_POST[ $k ] );
									} else {
										$temp_value[ $k ] = (int) $_POST[ $k ];
									}
								}
							} elseif ( 'timepicker' === $filter_type || 'datepicker' === $filter_type ) {
								if ( ! empty( $_POST[ $k . '_from' ] ) && ! empty( $_POST[ $k . '_to' ] ) ) {
									$temp_value[ $k ] = array(
										sanitize_text_field( $_POST[ $k . '_from' ] ),
										sanitize_text_field( $_POST[ $k . '_to' ] ),
									);
								}
							} elseif ( 'select' === $filter_type ) {
								if ( ! empty( $_POST[ $k ] ) ) {
									if ( is_array( $_POST[ $k ] ) ) {
										$temp_value[ $k ] = array_map( 'trim', $_POST[ $k ] );
									} else {
										$temp_value[ $k ] = array( trim( $_POST[ $k ] ) );
									}

									$temp_value[ $k ] = array_map( 'sanitize_text_field', $temp_value[ $k ] );
								}
							} else {
								if ( ! empty( $_POST[ $k ] ) ) {
									$temp_value[ $k ] = trim( sanitize_text_field( $_POST[ $k ] ) );
								}
							}
						}
					}
				}

				$value = $temp_value;
			} elseif ( '_um_sorting_fields' === $key ) {
				if ( ! empty( $value['other_data'] ) ) {
					$other_data = $value['other_data'];
					unset( $value['other_data'] );

					foreach ( $value as $k => &$row ) {
						if ( ! empty( $other_data[ $k ]['meta_key'] ) ) {
							$metakey = sanitize_text_field( $other_data[ $k ]['meta_key'] );
							if ( ! empty( $metakey ) ) {
								if ( ! empty( $other_data[ $k ]['label'] ) ) {
									$metalabel = wp_strip_all_tags( $other_data[ $k ]['label'] );
								}
								$row = array(
									$metakey => ! empty( $metalabel ) ? $metalabel : $metakey,
								);
							}
						}
					}
				}
			} elseif ( '_um_sortby_custom' === $key ) {
				$value = sanitize_text_field( $value );
			} elseif ( '_um_sortby_custom_label' === $key ) {
				$value = wp_strip_all_tags( $value );
			}
		}

		return $value;
	}
}
