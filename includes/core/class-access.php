<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Access' ) ) {


	/**
	 * Class Access
	 * @package um\core
	 */
	class Access {


		/**
		 * If true then we use individual restrict content options
		 * for post
		 *
		 * @var bool
		 */
		private $singular_page;


		/**
		 * @var bool
		 */
		private $redirect_handler;


		/**
		 * @var bool
		 */
		private $allow_access;


		/**
		 * @var \WP_Post
		 */
		private $current_single_post;


		private $ignore_terms_exclude = false;

		private $ignore_exclude = false;


		/**
		 * Access constructor.
		 */
		function __construct() {
			$this->singular_page = false;

			$this->redirect_handler = false;
			$this->allow_access = false;

			// callbacks for changing posts query
			add_action( 'pre_get_posts', array( &$this, 'exclude_posts' ), 99, 1 );
			add_filter( 'get_next_post_where', array( &$this, 'exclude_navigation_posts' ), 99, 5 );
			add_filter( 'get_previous_post_where', array( &$this, 'exclude_navigation_posts' ), 99, 5 );
			add_filter( 'widget_posts_args', array( &$this, 'exclude_restricted_posts_widget' ), 99, 1 );

			add_filter( 'wp_count_posts', array( &$this, 'custom_count_posts_handler' ), 99, 3 );
			add_filter( 'getarchives_where', array( &$this, 'exclude_restricted_posts_archives_widget' ), 99, 2 );

			// callbacks for changing terms query
			add_action( 'pre_get_terms', array( &$this, 'exclude_hidden_terms_query' ), 99, 1 );

			// callbacks for changing comments query
			add_action( 'pre_get_comments', array( &$this, 'exclude_posts_comments' ), 99, 1 );
			add_filter( 'comment_feed_where', array( &$this, 'exclude_posts_comments_feed' ), 99, 2 );
			add_filter( 'wp_count_comments', array( &$this, 'custom_comments_count_handler' ), 99, 2 );
			/* Disable comments if user has not permission to access current post */
			add_filter( 'comments_open', array( $this, 'disable_comments_open' ), 99, 2 );
			add_filter( 'get_comments_number', array( $this, 'disable_comments_open_number' ), 99, 2 );

			//there is posts (Posts/Page/CPT) filtration if site is accessible
			//there also will be redirects if they need
			//protect posts types
			add_filter( 'the_posts', array( &$this, 'filter_protected_posts' ), 99, 2 );
			//protect pages for wp_list_pages func
			add_filter( 'get_pages', array( &$this, 'filter_protected_posts' ), 99, 2 );

			add_filter( 'the_title', array( &$this, 'filter_restricted_post_title' ), 10, 2 );

			//filter menu items
			add_filter( 'wp_nav_menu_objects', array( &$this, 'filter_menu' ), 99, 2 );
			// blocks restrictions
			add_filter( 'render_block', array( $this, 'restrict_blocks' ), 10, 2 );

			//filter attachment
			add_filter( 'wp_get_attachment_url', array( &$this, 'filter_attachment' ), 99, 2 );
			add_filter( 'has_post_thumbnail', array( &$this, 'filter_post_thumbnail' ), 99, 3 );

			// turn on/off content replacement on the filter 'the_content'
			add_action( 'get_header', array( &$this, 'replace_post_content_on' ), 12 );
			add_action( 'get_footer', array( &$this, 'replace_post_content_off' ), 8 );
			// turn on/off content replacement on the filter 'the_content' with the theme "Avada"
			add_action( 'avada_before_body_content', array( &$this, 'replace_post_content_off' ), 8 );
			add_action( 'avada_before_main_container', array( &$this, 'replace_post_content_on' ), 12 );
			add_action( 'avada_after_main_content', array( &$this, 'replace_post_content_off' ), 8 );

			//check the site's accessible more priority have Individual Post/Term Restriction settings
			add_action( 'template_redirect', array( &$this, 'template_redirect' ), 1000 );
			add_action( 'um_access_check_individual_term_settings', array( &$this, 'um_access_check_individual_term_settings' ) );
			add_action( 'um_access_check_global_settings', array( &$this, 'um_access_check_global_settings' ) );
		}

		/**
		 * Get array with restricted terms
		 *
		 * @return array
		 */
		function exclude_terms_array() {
			static $cache = false;

			if ( false !== $cache ) {
				return $cache;
			}

			$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
			if ( empty( $restricted_taxonomies ) ) {
				$cache = array();
				return array();
			}

			global $wpdb;
			$term_ids = $wpdb->get_col(
				"SELECT tm.term_id
				FROM {$wpdb->termmeta} tm 
				LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id 
				WHERE tm.meta_key = 'um_content_restriction' AND 
				      tt.taxonomy IN('" . implode( "','", array_keys( $restricted_taxonomies ) ) . "')"
			);

			if ( empty( $term_ids ) ) {
				$cache = array();
				return array();
			}

			$exclude = array();
			foreach ( $term_ids as $term_id ) {
				if ( $this->is_restricted_term( $term_id ) ) {
					$exclude[] = $term_id;
				}
			}

			$cache = $exclude;
			return $exclude;
		}


		function exclude_hidden_terms_query( $query ) {
			if ( current_user_can( 'administrator' ) || $this->ignore_terms_exclude ) {
				return;
			}

			$exclude = $this->exclude_terms_array();
			if ( ! empty( $exclude ) ) {
				$query->query_vars['exclude'] = ! empty( $query->query_vars['exclude'] ) ? wp_parse_id_list( $query->query_vars['exclude'] ) : $exclude;
			}
		}


		function custom_count_posts_handler( $counts, $type, $perm ) {
			global $wpdb;

			$exclude_posts = $this->exclude_posts_array( is_admin() );
			if ( empty( $exclude_posts ) ) {
				return $counts;
			}

			$cache_key = _count_posts_cache_key( $type, $perm );

			$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s";

			if ( 'readable' === $perm && is_user_logged_in() ) {
				$post_type_object = get_post_type_object( $type );
				if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
					$query .= $wpdb->prepare(
						" AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
						get_current_user_id()
					);
				}
			}

			$query .= " AND ID NOT IN('" . implode( "','", $exclude_posts ) . "')";

			$query .= ' GROUP BY post_status';

			$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );
			$counts  = array_fill_keys( get_post_stati(), 0 );

			foreach ( $results as $row ) {
				$counts[ $row['post_status'] ] = $row['num_posts'];
			}

			$counts = (object) $counts;
			wp_cache_set( $cache_key, $counts, 'counts' );

			return $counts;
		}


		function custom_comments_count_handler( $stats, $post_id ) {
			if ( ! empty( $stats ) ) {
				return $stats;
			}

			$exclude_posts = $this->exclude_posts_array( true );

			if ( empty( $exclude_posts ) ) {
				return $stats;
			}

			$stats              = $this->get_comment_count( $post_id, $exclude_posts );
			$stats['moderated'] = $stats['awaiting_moderation'];
			unset( $stats['awaiting_moderation'] );

			$stats_object = (object) $stats;

			return $stats_object;
		}


		function get_comment_count( $post_id = 0, $exclude_posts = array() ) {
			static $cache = array();

			if ( isset( $cache[ $post_id ] ) ) {
				return $cache[ $post_id ];
			}

			global $wpdb;

			$post_id = (int) $post_id;

			$where = 'WHERE 1=1';
			if ( $post_id > 0 ) {
				$where .= $wpdb->prepare( ' AND comment_post_ID = %d', $post_id );
			}

			if ( ! empty( $exclude_posts ) ) {
				$exclude_string = implode( ',', $exclude_posts );
				$where .= ' AND comment_post_ID NOT IN ( ' . $exclude_string . ' )';
			}

			$totals = (array) $wpdb->get_results(
				"
		SELECT comment_approved, COUNT( * ) AS total
		FROM {$wpdb->comments}
		{$where}
		GROUP BY comment_approved
	",
				ARRAY_A
			);

			$comment_count = array(
				'approved'            => 0,
				'awaiting_moderation' => 0,
				'spam'                => 0,
				'trash'               => 0,
				'post-trashed'        => 0,
				'total_comments'      => 0,
				'all'                 => 0,
			);

			foreach ( $totals as $row ) {
				switch ( $row['comment_approved'] ) {
					case 'trash':
						$comment_count['trash'] = $row['total'];
						break;
					case 'post-trashed':
						$comment_count['post-trashed'] = $row['total'];
						break;
					case 'spam':
						$comment_count['spam']            = $row['total'];
						$comment_count['total_comments'] += $row['total'];
						break;
					case '1':
						$comment_count['approved']        = $row['total'];
						$comment_count['total_comments'] += $row['total'];
						$comment_count['all']            += $row['total'];
						break;
					case '0':
						$comment_count['awaiting_moderation'] = $row['total'];
						$comment_count['total_comments']     += $row['total'];
						$comment_count['all']                += $row['total'];
						break;
					default:
						break;
				}
			}

			$comment_count     = array_map( 'intval', $comment_count );
			$cache[ $post_id ] = $comment_count;

			return $comment_count;
		}


		/**
		 * @param array $restriction
		 *
		 * @return bool
		 */
		function um_custom_restriction( $restriction ) {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_custom_restriction
			 * @description Extend Sort Types for Member Directory
			 * @input_vars
			 * [{"var":"$custom_restriction","type":"bool","desc":"Custom Restriction"},
			 * {"var":"$restriction","type":"array","desc":"Restriction settings"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_custom_restriction', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_filter( 'um_custom_restriction', 'my_custom_restriction', 10, 2 );
			 * function my_directory_sort_users_select( $custom_restriction, $restriction ) {
			 *     // your code here
			 *     return $custom_restriction;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_custom_restriction', true, $restriction );
		}


		/**
		 * Check individual term Content Restriction settings
		 */
		function um_access_check_individual_term_settings() {
			//check only tax|tags|categories - skip archive, author, and date lists
			if ( ! ( is_tax() || is_tag() || is_category() ) ) {
				return;
			}

			$term_id = null;
			if ( is_tag() ) {
				$term_id = get_query_var( 'tag_id' );
			} elseif ( is_category() ) {
				$term_id = get_query_var( 'cat' );
			} elseif ( is_tax() ) {
				$tax_name = get_query_var( 'taxonomy' );

				$term_name = get_query_var( 'term' );
				$term = get_term_by( 'slug', $term_name, $tax_name );

				$term_id = ! empty( $term->term_id ) ? $term->term_id : $term_id;
			}

			if ( ! isset( $term_id ) ) {
				return;
			}

			if ( $this->is_restricted_term( $term_id ) ) {
				add_filter( 'tag_template', array( &$this, 'taxonomy_message' ), 10, 3 );
				add_filter( 'archive_template', array( &$this, 'taxonomy_message' ), 10, 3 );
				add_filter( 'category_template', array( &$this, 'taxonomy_message' ), 10, 3 );
				add_filter( 'taxonomy_template', array( &$this, 'taxonomy_message' ), 10, 3 );

				$restriction = get_term_meta( $term_id, 'um_content_restriction', true );
				if ( '1' == $restriction['_um_noaccess_action'] ) {
					$curr = UM()->permalinks()->get_current_url();

					if ( ! isset( $restriction['_um_access_redirect'] ) || '0' == $restriction['_um_access_redirect'] ) {

						$this->redirect_handler = $this->set_referer( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), um_get_core_page( 'login' ) ) ), 'individual_term' );

					} elseif ( '1' == $restriction['_um_access_redirect'] ) {

						if ( ! empty( $restriction['_um_access_redirect_url'] ) ) {
							$redirect = $restriction['_um_access_redirect_url'];
						} else {
							$redirect = esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), um_get_core_page( 'login' ) ) );
						}

						$this->redirect_handler = $this->set_referer( $redirect, 'individual_term' );

					}
				}
			}
		}


		/**
		 * @param $template
		 * @param $type
		 * @param $templates
		 *
		 * @return string
		 */
		function taxonomy_message( $template, $type, $templates ) {
			return UM()->locate_template( 'restricted-taxonomy.php' );
		}


		/**
		 * Check global accessible settings
		 */
		function um_access_check_global_settings() {
			global $post;

			$curr = UM()->permalinks()->get_current_url();
			$ms_empty_role_access = is_multisite() && is_user_logged_in() && !UM()->roles()->get_priority_user_role( um_user('ID') );

			if ( is_front_page() ) {
				if ( is_user_logged_in() && ! $ms_empty_role_access ) {

					$user_default_homepage = um_user( 'default_homepage' );
					if ( ! empty( $user_default_homepage ) ) {
						return;
					}

					$redirect_homepage = um_user( 'redirect_homepage' );
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_custom_homepage_redirect_url
					 * @description Change custom homepage redirect
					 * @input_vars
					 * [{"var":"$url","type":"string","desc":"Redirect URL"},
					 * {"var":"$id","type":"int","desc":"User ID"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_custom_homepage_redirect_url', 'function_name', 10, 2 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_custom_homepage_redirect_url', 'my_custom_homepage_redirect_url', 10, 2 );
					 * function my_custom_homepage_redirect_url( $url, $id ) {
					 *     // your code here
					 *     return $url;
					 * }
					 * ?>
					 */
					$redirect_homepage = apply_filters( 'um_custom_homepage_redirect_url', $redirect_homepage, um_user( 'ID' ) );
					$redirect_to = ! empty( $redirect_homepage ) ? $redirect_homepage : um_get_core_page( 'user' );
					$this->redirect_handler = $this->set_referer( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), $redirect_to ) ), "custom_homepage" );

				} else {
					$access = UM()->options()->get( 'accessible' );

					if ( $access == 2 ) {
						//global settings for accessible home page
						$home_page_accessible = UM()->options()->get( 'home_page_accessible' );

						if ( $home_page_accessible == 0 ) {
							//get redirect URL if not set get login page by default
							$redirect = UM()->options()->get( 'access_redirect' );
							if ( ! $redirect ) {
								$redirect = um_get_core_page( 'login' );
							}

							$this->redirect_handler = $this->set_referer( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), $redirect ) ), 'global' );
						} else {
							$this->allow_access = true;
							return;
						}
					}
				}
			} elseif ( is_category() ) {
				if ( ! is_user_logged_in() || $ms_empty_role_access ) {

					$access = UM()->options()->get( 'accessible' );

					if ( $access == 2 ) {
						//global settings for accessible home page
						$category_page_accessible = UM()->options()->get( 'category_page_accessible' );
						if ( $category_page_accessible == 0 ) {
							//get redirect URL if not set get login page by default
							$redirect = UM()->options()->get( 'access_redirect' );
							if ( ! $redirect ) {
								$redirect = um_get_core_page( 'login' );
							}

							$this->redirect_handler = $this->set_referer( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), $redirect ) ), 'global' );
						} else {
							$this->allow_access = true;
							return;
						}
					}
				}
			}

			$access = UM()->options()->get( 'accessible' );

			if ( $access == 2 && ( ! is_user_logged_in() || $ms_empty_role_access ) ) {

				//build exclude URLs pages
				$redirects = array();
				$redirects[] = trim( untrailingslashit( UM()->options()->get( 'access_redirect' ) ) );

				$exclude_uris = UM()->options()->get( 'access_exclude_uris' );
				if ( ! empty( $exclude_uris ) ) {
					$exclude_uris = array_map( 'trim', $exclude_uris );
					$redirects = array_merge( $redirects, $exclude_uris );
				}

				$redirects = array_unique( $redirects );

				$current_url = UM()->permalinks()->get_current_url( get_option( 'permalink_structure' ) );
				$current_url = untrailingslashit( $current_url );
				$current_url_slash = trailingslashit( $current_url );

				if ( ! ( isset( $post->ID ) && ( in_array( $current_url, $redirects ) || in_array( $current_url_slash, $redirects ) ) ) ) {
					//if current page not in exclude URLs
					//get redirect URL if not set get login page by default
					$redirect = UM()->options()->get( 'access_redirect' );
					if ( ! $redirect ) {
						$redirect = um_get_core_page( 'login' );
					}

					$this->redirect_handler = $this->set_referer( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), $redirect ) ), 'global' );
				} else {
					$this->redirect_handler = false;
					$this->allow_access = true;
				}
			}
		}


		/**
		 * Set custom access actions and redirection
		 *
		 * Old global restrict content logic
		 */
		function template_redirect() {
			global $post, $wp_query;

			//if we logged by administrator it can access to all content
			if ( current_user_can( 'administrator' ) ) {
				return;
			}

			if ( is_object( $wp_query ) ) {
				$is_singular = $wp_query->is_singular();
			} else {
				$is_singular = ! empty( $wp_query->is_singular ) ? true : false;
			}

			//if we use individual restrict content options skip this function
			if ( $is_singular && $this->singular_page ) {
				return;
			}

			//also skip if we currently at wp-admin or 404 page
			if ( is_admin() || is_404() ) {
				return;
			}

			//also skip if we currently at UM Register|Login|Reset Password pages
			if ( um_is_core_post( $post, 'register' ) ||
				 um_is_core_post( $post, 'password-reset' ) ||
				 um_is_core_post( $post, 'login' ) ) {
				return;
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_roles_add_meta_boxes_um_role_meta
			 * @description Check terms individual restrict options
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_access_check_individual_term_settings', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_access_check_individual_term_settings', 'my_access_check_individual_term_settings', 10 );
			 * function my_access_check_individual_term_settings() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_access_check_individual_term_settings' );
			//exit from function if term page is accessible
			if ( $this->check_access() ) {
				return;
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_access_check_global_settings
			 * @description Check global restrict content options
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_access_check_global_settings', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_access_check_global_settings', 'my_access_check_global_settings', 10 );
			 * function my_access_check_global_settings() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_access_check_global_settings' );

			$this->check_access();
		}


		/**
		 * Check access
		 *
		 * @return bool
		 */
		function check_access() {
			if ( $this->allow_access === true ) {
				return true;
			}

			if ( $this->redirect_handler ) {
				wp_redirect( $this->redirect_handler ); exit;
			}

			return false;
		}


		/**
		 * Sets a custom access referer in a redirect URL
		 *
		 * @param string $url
		 * @param string $referer
		 *
		 * @return string
		 */
		function set_referer( $url, $referer ) {

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_access_enable_referer
			 * @description Access Referrer Enable/Disable
			 * @input_vars
			 * [{"var":"$referrer","type":"bool","desc":"Access referrer"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_access_enable_referer', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_access_enable_referer', 'my_access_enable_referer', 10, 1 );
			 * function my_access_enable_referer( $referrer ) {
			 *     // your code here
			 *     return $referrer;
			 * }
			 * ?>
			 */
			$enable_referer = apply_filters( 'um_access_enable_referer', false );
			if ( ! $enable_referer ) {
				return $url;
			}

			$url = add_query_arg( 'um_ref', $referer, $url );
			return $url;
		}


		/**
		 * User can some of the roles array
		 * Restrict content new logic
		 *
		 * @param $user_id
		 * @param $roles
		 * @return bool
		 */
		function user_can( $user_id, $roles ) {
			$user_can = false;

			if ( ! empty( $roles ) ) {
				foreach ( $roles as $key => $value ) {
					if ( ! empty( $value ) && user_can( $user_id, $key ) ) {
						$user_can = true;
						break;
					}
				}
			}

			return $user_can;
		}


		/**
		 * Get privacy settings for post
		 * return false if post is not private
		 * Restrict content new logic
		 *
		 * @param \WP_Post|int $post Post ID or object
		 * @return bool|array
		 */
		function get_post_privacy_settings( $post ) {
			static $cache = array();

			$cache_key = is_numeric( $post ) ? $post : $post->ID;

			if ( isset( $cache[ $cache_key ] ) ) {
				return $cache[ $cache_key ];
			}

			if ( is_numeric( $post ) ) {
				$post = get_post( $post );
			}

			//if logged in administrator all pages are visible
			if ( current_user_can( 'administrator' ) ) {
				$cache[ $cache_key ] = false;
				return false;
			}

			$exclude = false;
			//exclude from privacy UM default pages (except Members list and User(Profile) page)
			if ( ! empty( $post->post_type ) && $post->post_type === 'page' ) {

				if ( um_is_core_post( $post, 'login' ) || um_is_core_post( $post, 'register' ) ||
					 um_is_core_post( $post, 'account' ) || um_is_core_post( $post, 'logout' ) ||
					 um_is_core_post( $post, 'password-reset' ) || ( is_user_logged_in() && um_is_core_post( $post, 'user' ) ) )
					$exclude = true;
			}

			$exclude = apply_filters( 'um_exclude_posts_from_privacy', $exclude, $post );
			if ( $exclude ) {
				$cache[ $cache_key ] = false;
				return false;
			}

			$restricted_posts = UM()->options()->get( 'restricted_access_post_metabox' );

			if ( ! empty( $post->post_type ) && ! empty( $restricted_posts[ $post->post_type ] ) ) {
				$restriction = get_post_meta( $post->ID, 'um_content_restriction', true );

				if ( ! empty( $restriction['_um_custom_access_settings'] ) ) {
					if ( ! isset( $restriction['_um_accessible'] ) ) {
						$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );

						//get all taxonomies for current post type
						$taxonomies = get_object_taxonomies( $post );

						//get all post terms
						$terms = array();
						if ( ! empty( $taxonomies ) ) {
							foreach ( $taxonomies as $taxonomy ) {
								if ( empty( $restricted_taxonomies[ $taxonomy ] ) ) {
									continue;
								}

								$this->ignore_terms_exclude = true;
								$terms = array_merge( $terms, wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) ) );
								$this->ignore_terms_exclude = false;
							}
						}

						//get restriction options for first term with privacy settigns
						foreach ( $terms as $term_id ) {
							$restriction = get_term_meta( $term_id, 'um_content_restriction', true );

							if ( ! empty( $restriction['_um_custom_access_settings'] ) ) {
								if ( ! isset( $restriction['_um_accessible'] ) ) {
									continue;
								} else {
									$cache[ $cache_key ] = $restriction;
									return $restriction;
								}
							}
						}

						$cache[ $cache_key ] = false;
						return false;
					} else {

						// set default redirect if Profile page is restricted for not-logged in users and showing message instead of redirect
						// this snippet was added to make the same action for {site_url}/user and {site_url}/user/{user_slug} URLs
						// by default {site_url}/user is redirected to Homepage in rewrite rules because hasn't found username in query when user is not logged in
						if ( ! is_user_logged_in() && um_is_core_post( $post, 'user' ) && $restriction['_um_accessible'] == '2' && $restriction['_um_noaccess_action'] == '0' ) {
							if ( isset( $restriction['_um_access_roles'] ) ) {
								$restriction = array(
									'_um_accessible'            => '2',
									'_um_access_roles'          => $restriction['_um_access_roles'],
									'_um_noaccess_action'       => '1',
									'_um_access_redirect'       => '1',
									'_um_access_redirect_url'   => get_home_url( get_current_blog_id() )
								);
							} else {
								$restriction = array(
									'_um_accessible'            => '2',
									'_um_noaccess_action'       => '1',
									'_um_access_redirect'       => '1',
									'_um_access_redirect_url'   => get_home_url( get_current_blog_id() )
								);
							}
						}

						$restriction = apply_filters( 'um_post_content_restriction_settings', $restriction, $post );

						$cache[ $cache_key ] = $restriction;
						return $restriction;
					}
				}
			}

			//post hasn't privacy settings....check all terms of this post
			$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );

			//get all taxonomies for current post type
			$taxonomies = get_object_taxonomies( $post );

			//get all post terms
			$terms = array();
			if ( ! empty( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					if ( empty( $restricted_taxonomies[ $taxonomy ] ) ) {
						continue;
					}

					$this->ignore_terms_exclude = true;
					$terms = array_merge( $terms, wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) ) );
					$this->ignore_terms_exclude = false;
				}
			}

			//get restriction options for first term with privacy settings
			foreach ( $terms as $term_id ) {
				$restriction = get_term_meta( $term_id, 'um_content_restriction', true );

				if ( ! empty( $restriction['_um_custom_access_settings'] ) ) {
					if ( ! isset( $restriction['_um_accessible'] ) ) {
						continue;
					} else {
						$cache[ $cache_key ] = $restriction;
						return $restriction;
					}
				}
			}

			$cache[ $cache_key ] = false;
			//post is public
			return false;
		}


		/**
		 * Replace titles of restricted posts
		 *
		 * @param string $title
		 * @param int|null $id
		 *
		 * @return string
		 */
		function filter_restricted_post_title( $title, $id = null ) {
			if ( ! isset( $id ) ) {
				return $title;
			}

			if ( ! is_numeric( $id ) ) {
				$id = absint( $id );
			}

			if ( $this->is_restricted( $id ) ) {
				$restricted_global_title = UM()->options()->get( 'restricted_access_post_title' );
				$title = stripslashes( $restricted_global_title );
			}

			return $title;
		}


		/**
		 * Protect Post Types in query
		 * Restrict content new logic
		 *
		 * @param $posts
		 * @param \WP_Query $query
		 * @return array
		 */
		function filter_protected_posts( $posts, $query ) {
			$filtered_posts = array();

			//if empty
			if ( empty( $posts ) || is_admin() ) {
				return $posts;
			}

			if ( current_user_can( 'administrator' ) ) {
				return $posts;
			}

			$restricted_global_message = UM()->options()->get( 'restricted_access_message' );
			$restricted_global_title = UM()->options()->get( 'restricted_access_post_title' );

			if ( is_object( $query ) ) {
				$is_singular = $query->is_singular();
			} else {
				$is_singular = ! empty( $query->is_singular ) ? true : false;
			}

			//other filter
			foreach ( $posts as $post ) {

				$original_post = $post;

				//Woocommerce AJAX fixes....remove filtration on wc-ajax which goes to Front Page
				if ( ! empty( $_GET['wc-ajax'] ) && defined( 'WC_DOING_AJAX' ) && WC_DOING_AJAX ) {
					$filtered_posts[] = $post;
					continue;
				}

				$restriction = $this->get_post_privacy_settings( $post );

				if ( ! $restriction ) {
					$filtered_posts[] = $post;
					continue;
				}

				//post is private
				if ( '0' == $restriction['_um_accessible'] ) {
					$this->singular_page = true;
					$filtered_posts[] = $post;
					continue;
				} elseif ( '1' == $restriction['_um_accessible'] ) {
					//if post for not logged in users and user is not logged in
					if ( ! is_user_logged_in() ) {
						$this->singular_page = true;

						$filtered_posts[] = $post;
						continue;
					} else {

						if ( empty( $is_singular ) ) {
							//if not single query when exclude if set _um_access_hide_from_queries
							if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {

								if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = stripslashes( $restricted_global_message );
									$post->post_title = stripslashes( $restricted_global_title );
									$post->post_excerpt = '';
								} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
									$post->post_title = stripslashes( $restricted_global_title );
									$post->post_excerpt = '';
								}

								$post = apply_filters( 'um_restricted_archive_post', $post, $restriction, $original_post );
								$filtered_posts[] = $post;
								continue;
							}
						} else {
							$this->singular_page = true;

							//if single post query
							if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

								if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = stripslashes( $restricted_global_message );
									$post->post_title = stripslashes( $restricted_global_title );
								} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
									$post->post_title = stripslashes( $restricted_global_title );
								}

								$post = apply_filters( 'um_restricted_singular_post', $post, $restriction, $original_post );

								$this->current_single_post = $post;

								/**
								 * UM hook
								 *
								 * @type action
								 * @title um_access_fix_external_post_content
								 * @description Hook for 3-d party content filtration
								 * @change_log
								 * ["Since: 2.0"]
								 * @usage add_action( 'um_access_fix_external_post_content', 'function_name', 10 );
								 * @example
								 * <?php
								 * add_action( 'um_access_fix_external_post_content', 'my_access_fix_external_post_content', 10 );
								 * function my_access_fix_external_post_content() {
								 *     // your code here
								 * }
								 * ?>
								 */
								do_action( 'um_access_fix_external_post_content' );

								add_filter( 'single_template', array( &$this, 'woocommerce_template' ), 9999999, 1 );

								$filtered_posts[] = $post;
								continue;
							} elseif ( '1' == $restriction['_um_noaccess_action'] ) {
								$curr = UM()->permalinks()->get_current_url();

								if ( ! isset( $restriction['_um_access_redirect'] ) || '0' == $restriction['_um_access_redirect'] ) {

									exit( wp_redirect( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), um_get_core_page( 'login' ) ) ) ) );

								} elseif ( '1' == $restriction['_um_access_redirect'] ) {

									if ( ! empty( $restriction['_um_access_redirect_url'] ) ) {
										$redirect = $restriction['_um_access_redirect_url'];
									} else {
										$redirect = esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), um_get_core_page( 'login' ) ) );
									}

									exit( wp_redirect( $redirect ) );
								}

							}
						}
					}
				} elseif ( '2' == $restriction['_um_accessible'] ) {
					//if post for logged in users and user is not logged in
					if ( is_user_logged_in() ) {

						$custom_restrict = $this->um_custom_restriction( $restriction );

						if ( empty( $restriction['_um_access_roles'] ) || false === array_search( '1', $restriction['_um_access_roles'] ) ) {
							if ( $custom_restrict ) {
								$this->singular_page = true;

								$filtered_posts[] = $post;
								continue;
							}
						} else {
							$user_can = $this->user_can( get_current_user_id(), $restriction['_um_access_roles'] );

							if ( isset( $user_can ) && $user_can && $custom_restrict ) {
								$this->singular_page = true;

								$filtered_posts[] = $post;
								continue;
							}
						}

						if ( empty( $is_singular ) ) {
							//if not single query when exclude if set _um_access_hide_from_queries
							if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {

								if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = stripslashes( $restricted_global_message );
									$post->post_title = stripslashes( $restricted_global_title );
									$post->post_excerpt = '';
								} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
									$post->post_title = stripslashes( $restricted_global_title );
									$post->post_excerpt = '';
								}

								$post = apply_filters( 'um_restricted_archive_post', $post, $restriction, $original_post );

								$filtered_posts[] = $post;
								continue;
							}
						} else {
							$this->singular_page = true;

							//if single post query
							if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

								if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = stripslashes( $restricted_global_message );
									$post->post_title = stripslashes( $restricted_global_title );

									$this->current_single_post = $post;

									if ( 'attachment' == $post->post_type ) {
										remove_filter( 'the_content', 'prepend_attachment' );
									}
								} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
									$post->post_title = stripslashes( $restricted_global_title );

									$this->current_single_post = $post;

									if ( 'attachment' == $post->post_type ) {
										remove_filter( 'the_content', 'prepend_attachment' );
									}
								}

								$post = apply_filters( 'um_restricted_singular_post', $post, $restriction, $original_post );

								/**
								 * UM hook
								 *
								 * @type action
								 * @title um_access_fix_external_post_content
								 * @description Hook for 3-d party content filtration
								 * @change_log
								 * ["Since: 2.0"]
								 * @usage add_action( 'um_access_fix_external_post_content', 'function_name', 10 );
								 * @example
								 * <?php
								 * add_action( 'um_access_fix_external_post_content', 'my_access_fix_external_post_content', 10 );
								 * function my_access_fix_external_post_content() {
								 *     // your code here
								 * }
								 * ?>
								 */
								do_action( 'um_access_fix_external_post_content' );

								add_filter( 'single_template', array( &$this, 'woocommerce_template' ), 9999999, 1 );

								$filtered_posts[] = $post;
								continue;
							} elseif ( '1' == $restriction['_um_noaccess_action'] ) {

								$curr = UM()->permalinks()->get_current_url();

								if ( ! isset( $restriction['_um_access_redirect'] ) || '0' == $restriction['_um_access_redirect'] ) {

									exit( wp_redirect( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), um_get_core_page( 'login' ) ) ) ) );

								} elseif ( '1' == $restriction['_um_access_redirect'] ) {

									if ( ! empty( $restriction['_um_access_redirect_url'] ) ) {
										$redirect = $restriction['_um_access_redirect_url'];
									} else {
										$redirect = esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), um_get_core_page( 'login' ) ) );
									}

									exit( wp_redirect( $redirect ) );
								}

							}
						}

					} else {

						if ( empty( $is_singular ) ) {
							if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {

								if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = stripslashes( $restricted_global_message );
									$post->post_title = stripslashes( $restricted_global_title );
									$post->post_excerpt = '';
								} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
									$post->post_title = stripslashes( $restricted_global_title );
									$post->post_excerpt = '';
								}

								$post = apply_filters( 'um_restricted_archive_post', $post, $restriction, $original_post );

								$filtered_posts[] = $post;
								continue;
							}
						} else {
							$this->singular_page = true;

							//if single post query
							if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

								if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = stripslashes( $restricted_global_message );
									$post->post_title = stripslashes( $restricted_global_title );

									$this->current_single_post = $post;

									if ( 'attachment' == $post->post_type ) {
										remove_filter( 'the_content', 'prepend_attachment' );
									}
								} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
									$post->post_title = stripslashes( $restricted_global_title );

									$this->current_single_post = $post;

									if ( 'attachment' == $post->post_type ) {
										remove_filter( 'the_content', 'prepend_attachment' );
									}
								}

								$post = apply_filters( 'um_restricted_singular_post', $post, $restriction, $original_post );

								/**
								 * UM hook
								 *
								 * @type action
								 * @title um_access_fix_external_post_content
								 * @description Hook for 3-d party content filtration
								 * @change_log
								 * ["Since: 2.0"]
								 * @usage add_action( 'um_access_fix_external_post_content', 'function_name', 10 );
								 * @example
								 * <?php
								 * add_action( 'um_access_fix_external_post_content', 'my_access_fix_external_post_content', 10 );
								 * function my_access_fix_external_post_content() {
								 *     // your code here
								 * }
								 * ?>
								 */
								do_action( 'um_access_fix_external_post_content' );

								add_filter( 'single_template', array( &$this, 'woocommerce_template' ), 9999999, 1 );

								$filtered_posts[] = $post;
								continue;
							} elseif ( '1' == $restriction['_um_noaccess_action'] ) {

								$curr = UM()->permalinks()->get_current_url();

								if ( ! isset( $restriction['_um_access_redirect'] ) || '0' == $restriction['_um_access_redirect'] ) {

									exit( wp_redirect( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), um_get_core_page( 'login' ) ) ) ) );

								} elseif ( '1' == $restriction['_um_access_redirect'] ) {

									if ( ! empty( $restriction['_um_access_redirect_url'] ) ) {
										$redirect = $restriction['_um_access_redirect_url'];
									} else {
										$redirect = esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), um_get_core_page( 'login' ) ) );
									}

									exit( wp_redirect( $redirect ) );
								}
							}
						}
					}
				}
			}

			return $filtered_posts;
		}


		/**
		 * Get array with restricted posts
		 *
		 * @param bool $force
		 *
		 * @return array
		 */
		function exclude_posts_array( $force = false ) {
			static $cache = array(
				'force'   => false,
				'default' => false,
			);

			$cache_key = $force ? 'force' : 'default';

			if ( false !== $cache[ $cache_key ] ) {
				return $cache[ $cache_key ];
			}

			$exclude_posts = array();
			if ( current_user_can( 'administrator' ) || $this->ignore_exclude ) {
				$cache[ $cache_key ] = $exclude_posts;
				return $exclude_posts;
			}

			// @todo using Object Cache `wp_cache_get()` `wp_cache_set()` functions

			global $wpdb;

			$restricted_posts = UM()->options()->get( 'restricted_access_post_metabox' );
			if ( ! empty( $restricted_posts ) ) {
				$this->ignore_exclude = true;
				// exclude all posts assigned to current term without individual restriction settings
				$post_ids = get_posts(
					array(
						'fields'      => 'ids',
						'post_status' => 'any',
						'post_type'   => array_keys( $restricted_posts ),
						'numberposts' => -1,
						'meta_query'  => array(
							array(
								'key'     => 'um_content_restriction',
								'compare' => 'EXISTS',
							),
						),
					)
				);

				$this->ignore_exclude = false;
			}

			$post_ids = empty( $post_ids ) ? array() : $post_ids;

			$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );

			if ( ! empty( $restricted_taxonomies ) ) {
				$terms = $wpdb->get_results(
					"SELECT tm.term_id AS term_id, 
					        tt.taxonomy AS taxonomy 
					FROM {$wpdb->termmeta} tm 
					LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id 
					WHERE tm.meta_key = 'um_content_restriction' AND 
					      tt.taxonomy IN('" . implode( "','", array_keys( $restricted_taxonomies ) ) . "')",
					ARRAY_A
				);

				if ( ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						if ( ! $this->is_restricted_term( $term['term_id'] ) ) {
							continue;
						}

						$this->ignore_exclude = true;
						// exclude all posts assigned to current term without individual restriction settings
						$posts = get_posts(
							array(
								'fields'      => 'ids',
								'post_status' => 'any',
								'numberposts' => -1,
								'tax_query'   => array(
									array(
										'taxonomy' => $term['taxonomy'],
										'field'    => 'id',
										'terms'    => $term['term_id'],
									),
								),
								'meta_query'  => array(
									'relation' => 'OR',
									array(
										'relation' => 'AND',
										array(
											'key'     => 'um_content_restriction',
											'value'   => 's:26:"_um_custom_access_settings";s:1:"1"',
											'compare' => 'NOT LIKE',
										),
										array(
											'key'     => 'um_content_restriction',
											'value'   => 's:26:"_um_custom_access_settings";b:1',
											'compare' => 'NOT LIKE',
										),
									),
									array(
										'key'     => 'um_content_restriction',
										'compare' => 'NOT EXISTS',
									),
								),
							)
						);
						$this->ignore_exclude = false;

						if ( empty( $posts ) ) {
							continue;
						}

						$post_ids = array_merge( $post_ids, $posts );
					}
				}
			}

			if ( ! empty( $post_ids ) ) {
				$post_ids = array_unique( $post_ids );

				foreach ( $post_ids as $post_id ) {
					// handle every post privacy setting based on post type maybe it's inactive for now
					// if individual restriction is enabled then get post terms restriction settings
					if ( $this->is_restricted( $post_id ) ) {
						if ( true === $force ) {
							array_push( $exclude_posts, $post_id );
						} else {
							$content_restriction = $this->get_post_privacy_settings( $post_id );
							if ( ! empty( $content_restriction['_um_access_hide_from_queries'] ) ) {
								array_push( $exclude_posts, $post_id );
							}
						}
					}
				}
			}

			$cache[ $cache_key ] = $exclude_posts;
			return $exclude_posts;
		}


		/**
		 * Exclude posts from query
		 *
		 * @param \WP_Query $query
		 */
		function exclude_posts( $query ) {
			if ( $query->is_main_query() ) {
				$exclude_posts = $this->exclude_posts_array( is_admin() );
				if ( ! empty( $exclude_posts ) ) {
					$post__not_in = $query->get( 'post__not_in', array() );
					$query->set( 'post__not_in', array_merge( wp_parse_id_list( $post__not_in ), $exclude_posts ) );
				}
			}
		}


		/**
		 * Exclude comments from restricted posts in widgets
		 *
		 * @param \WP_Comment_Query $query
		 */
		function exclude_posts_comments( $query ) {
			$exclude_posts = $this->exclude_posts_array( true );
			if ( ! empty( $exclude_posts ) ) {
				$post__not_in = ! empty( $query->query_vars['post__not_in'] ) ? $query->query_vars['post__not_in'] : array();
				$query->query_vars['post__not_in'] = array_merge( wp_parse_id_list( $post__not_in ), $exclude_posts );
			}
		}


		/**
		 * Exclude comments from comments feed
		 *
		 * @param string $where
		 * @param \WP_Query $query
		 *
		 * @return string
		 */
		function exclude_posts_comments_feed( $where, $query ) {
			$exclude_posts = $this->exclude_posts_array( true );
			if ( ! empty( $exclude_posts ) ) {
				$exclude_string = implode( ',', $exclude_posts );
				$where .= ' AND comment_post_ID NOT IN ( ' . $exclude_string . ' )';
			}

			return $where;
		}


		/**
		 * Exclude posts from next, previous navigation
		 *
		 * @param string  $where
		 * @param bool    $in_same_term
		 * @param array   $excluded_terms
		 * @param string  $taxonomy.
		 * @param \WP_Post $post
		 *
		 * @return string
		 */
		function exclude_navigation_posts( $where, $in_same_term, $excluded_terms, $taxonomy, $post ) {
			$exclude_posts = $this->exclude_posts_array();
			if ( ! empty( $exclude_posts ) ) {
				$exclude_string = implode( ',', $exclude_posts );
				$where .= ' AND ID NOT IN ( ' . $exclude_string . ' )';
			}

			return $where;
		}


		/**
		 * Exclude restricted posts in widgets
		 *
		 * @param array  $array
		 *
		 * @return array
		 */
		function exclude_restricted_posts_widget( $array ) {
			$exclude_posts = $this->exclude_posts_array();
			if ( ! empty( $exclude_posts ) ) {
				$post__not_in = ! empty( $array['post__not_in'] ) ? $array['post__not_in'] : array();
				$array['post__not_in'] = array_merge( wp_parse_id_list( $post__not_in ), $exclude_posts );
			}

			return $array;
		}


		/**
		 * Exclude restricted posts in widgets
		 *
		 * @param string  $sql_where
		 * @param array $parsed_args
		 *
		 * @return string
		 */
		function exclude_restricted_posts_archives_widget( $sql_where, $parsed_args ) {
			$exclude_posts = $this->exclude_posts_array();
			if ( ! empty( $exclude_posts ) ) {
				$exclude_string = implode( ',', $exclude_posts );
				$sql_where .= ' AND ID NOT IN ( ' . $exclude_string . ' )';
			}

			return $sql_where;
		}


		/**
		 * @param string $single_template
		 *
		 * @return string
		 */
		function woocommerce_template( $single_template ) {
			if ( ! UM()->dependencies()->woocommerce_active_check() ) {
				return $single_template;
			}

			if ( is_product() ) {
				remove_filter( 'template_include', array( 'WC_Template_Loader', 'template_loader' ) );
			}

			return $single_template;
		}


		/**
		 * Replace the content on the filter 'the_content'
		 *
		 * @param $content
		 * @return string
		 */
		function replace_post_content( $content ) {
			if ( ! empty( $this->current_single_post ) ) {
				$content = $this->current_single_post->post_content;
			}
			return $content;
		}


		/**
		 * Turn on the content replacement on the filter 'the_content'
		 * 
		 * @hooked  get_header 12
		 * @since   2.1.17
		 */
		public function replace_post_content_on() {
			add_filter( 'the_content', array( $this, 'replace_post_content' ), 9999, 1 );
		}


		/**
		 * Turn off the content replacement on the filter 'the_content'
		 *
		 * @hooked  get_footer
		 * @since   2.1.17
		 */
		public function replace_post_content_off() {
			remove_filter( 'the_content', array( $this, 'replace_post_content' ), 9999 );
		}


		/**
		 * Disable comments if user has not permission to access this post
		 *
		 * @param mixed $open
		 * @param int $post_id
		 * @return boolean
		 */
		function disable_comments_open( $open, $post_id ) {
			static $cache = array();

			if ( isset( $cache[ $post_id ] ) ) {
				return $cache[ $post_id ] ? $open : false;
			}

			if ( ! $this->is_restricted( $post_id ) ) {
				$cache[ $post_id ] = $open;
				return $open;
			}

			$open = false;

			$cache[ $post_id ] = $open;
			return $open;
		}


		/**
		 * Disable comments if user has not permission to access this post
		 *
		 * @param int $count
		 * @param int $post_id
		 * @return boolean
		 */
		function disable_comments_open_number( $count, $post_id ) {
			static $cache_number = array();

			if ( isset( $cache_number[ $post_id ] ) ) {
				return $cache_number[ $post_id ];
			}

			if ( ! $this->is_restricted( $post_id ) ) {
				$cache_number[ $post_id ] = $count;
				return $count;
			}

			$count = 0;

			$cache_number[ $post_id ] = $count;
			return $count;
		}


		/**
		 * Is post restricted?
		 *
		 * @param int $post_id
		 * @return bool
		 */
		function is_restricted( $post_id ) {
			static $cache = array();

			if ( isset( $cache[ $post_id ] ) ) {
				return $cache[ $post_id ];
			}

			if ( current_user_can( 'administrator' ) ) {
				$cache[ $post_id ] = false;
				return false;
			}

			$restricted = true;

			$restriction = $this->get_post_privacy_settings( $post_id );
			if ( ! $restriction ) {
				$restricted = false;
			} else {
				if ( '0' == $restriction['_um_accessible'] ) {
					//post is private
					$restricted = false;
				} elseif ( '1' == $restriction['_um_accessible'] ) {
					//if post for not logged in users and user is not logged in
					if ( ! is_user_logged_in() ) {
						$restricted = false;
					}
				} elseif ( '2' == $restriction['_um_accessible'] ) {
					//if post for logged in users and user is not logged in
					if ( is_user_logged_in() ) {
						$custom_restrict = $this->um_custom_restriction( $restriction );

						if ( empty( $restriction['_um_access_roles'] ) || false === array_search( '1', $restriction['_um_access_roles'] ) ) {
							if ( $custom_restrict ) {
								$restricted = false;
							}
						} else {
							$user_can = $this->user_can( get_current_user_id(), $restriction['_um_access_roles'] );

							if ( $user_can && $custom_restrict ) {
								$restricted = false;
							}
						}
					}
				}
			}

			$cache[ $post_id ] = $restricted;

			return $restricted;
		}


		/**
		 * Is term restricted?
		 *
		 * @param int $term_id
		 * @return bool
		 */
		function is_restricted_term( $term_id ) {
			static $cache = array();

			if ( isset( $cache[ $term_id ] ) ) {
				return $cache[ $term_id ];
			}

			if ( current_user_can( 'administrator' ) ) {
				$cache[ $term_id ] = false;
				return false;
			}

			$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
			if ( empty( $restricted_taxonomies ) ) {
				$cache[ $term_id ] = false;
				return false;
			}

			$term = get_term( $term_id );
			if ( empty( $term->taxonomy ) || empty( $restricted_taxonomies[ $term->taxonomy ] ) ) {
				$cache[ $term_id ] = false;
				return false;
			}

			$restricted = true;

			// $this->allow_access = true only in case if the

			$restriction = get_term_meta( $term_id, 'um_content_restriction', true );
			if ( empty( $restriction ) ) {
				$restricted = false;
			} else {
				if ( empty( $restriction['_um_custom_access_settings'] ) ) {
					$restricted = false;
				} else {
					if ( '0' == $restriction['_um_accessible'] ) {
						//term is private
						$restricted = false;
						$this->allow_access = true;
					} elseif ( '1' == $restriction['_um_accessible'] ) {
						//if term for not logged in users and user is not logged in
						if ( ! is_user_logged_in() ) {
							$restricted = false;
							$this->allow_access = true;
						}
					} elseif ( '2' == $restriction['_um_accessible'] ) {
						//if term for logged in users and user is not logged in
						if ( is_user_logged_in() ) {
							$custom_restrict = $this->um_custom_restriction( $restriction );

							if ( empty( $restriction['_um_access_roles'] ) || false === array_search( '1', $restriction['_um_access_roles'] ) ) {
								if ( $custom_restrict ) {
									$restricted = false;
									$this->allow_access = true;
								}
							} else {
								$user_can = $this->user_can( get_current_user_id(), $restriction['_um_access_roles'] );

								if ( $user_can && $custom_restrict ) {
									$restricted = false;
									$this->allow_access = true;
								}
							}
						}
					}
				}
			}

			$cache[ $term_id ] = $restricted;
			return $restricted;
		}


		/**
		 * Hide attachment if the post is restricted
		 *
		 * @param string $url
		 * @param int $attachment_id
		 *
		 * @return boolean|string
		 */
		function filter_attachment( $url, $attachment_id ) {
			return ( $attachment_id && $this->is_restricted( $attachment_id ) ) ? false : $url;
		}


		/**
		 * Hide attachment if the post is restricted
		 *
		 * @param $has_thumbnail
		 * @param $post
		 * @param $thumbnail_id
		 *
		 * @return bool
		 */
		function filter_post_thumbnail( $has_thumbnail, $post, $thumbnail_id ) {
			if ( $this->is_restricted( $thumbnail_id ) ) {
				$has_thumbnail = false;
			} elseif ( ! empty( $post ) && ! empty( $post->ID ) ) {
				if ( $this->is_restricted( $post->ID ) ) {
					$has_thumbnail = false;
				}
			} else {
				$post_id = get_the_ID();
				if ( $this->is_restricted( $post_id ) ) {
					$has_thumbnail = false;
				}
			}

			$has_thumbnail = apply_filters( 'um_restrict_post_thumbnail', $has_thumbnail, $post, $thumbnail_id );

			return $has_thumbnail;
		}


		/**
		 * Protect Post Types in menu query
		 * Restrict content new logic
		 * @param $menu_items
		 * @param $args
		 * @return array
		 */
		function filter_menu( $menu_items, $args ) {
			//if empty
			if ( empty( $menu_items ) ) {
				return $menu_items;
			}

			if ( current_user_can( 'administrator' ) ) {
				return $menu_items;
			}

			$filtered_items = array();

			//other filter
			foreach ( $menu_items as $menu_item ) {
				if ( ! empty( $menu_item->object_id ) && ! empty( $menu_item->object ) ) {
					if ( ! $this->is_restricted( $menu_item->object_id ) ) {
						$filtered_items[] = $menu_item;
						continue;
					} else {
						$restriction_settings = $this->get_post_privacy_settings( $menu_item->object_id );
						if ( empty( $restriction_settings['_um_access_hide_from_queries'] ) ) {
							$filtered_items[] = $menu_item;
							continue;
						}
					}
				} else {
					//add all other posts
					$filtered_items[] = $menu_item;
				}
			}

			return $filtered_items;
		}


		/**
		 * @param $block_content
		 * @param $block
		 *
		 * @return string
		 */
		function restrict_blocks( $block_content, $block ) {
			if ( is_admin() ) {
				return $block_content;
			}

			$restricted_blocks = UM()->options()->get( 'restricted_blocks' );
			if ( empty( $restricted_blocks ) ) {
				return $block_content;
			}

			if ( is_user_logged_in() && current_user_can( 'administrator' ) ) {
				return $block_content;
			}

			if ( ! isset( $block['attrs']['um_is_restrict'] ) || $block['attrs']['um_is_restrict'] !== true ) {
				return $block_content;
			}

			if ( empty( $block['attrs']['um_who_access'] ) ) {
				return $block_content;
			}

			$default_message = UM()->options()->get( 'restricted_block_message' );
			switch ( $block['attrs']['um_who_access'] ) {
				case '1': {
					if ( ! is_user_logged_in() ) {
						$block_content = '';
						if ( isset( $block['attrs']['um_message_type'] ) ) {
							if ( $block['attrs']['um_message_type'] == '1' ) {
								$block_content = $default_message;
							} elseif ( $block['attrs']['um_message_type'] == '2' ) {
								$block_content = $block['attrs']['um_message_content'];
							}
						}
					} else {
						$display = true;

						// What roles can access this content?
						if ( ! empty( $block['attrs']['um_roles_access'] ) ) {
							$display = false;
							foreach ( $block['attrs']['um_roles_access'] as $role ) {
								if ( current_user_can( $role ) ) {
									$display = true;
								}
							}
						}

						$display = apply_filters( 'um_loggedin_block_restriction', $display, $block );

						if ( ! $display ) {
							$block_content = '';
							if ( isset( $block['attrs']['um_message_type'] ) ) {
								if ( $block['attrs']['um_message_type'] == '1' ) {
									$block_content = $default_message;
								} elseif ( $block['attrs']['um_message_type'] == '2' ) {
									$block_content = $block['attrs']['um_message_content'];
								}
							}
						}
					}
					break;
				}
				case '2': {
					if ( is_user_logged_in() ) {
						$block_content = '';
						if ( isset( $block['attrs']['um_message_type'] ) ) {
							if ( $block['attrs']['um_message_type'] == '1' ) {
								$block_content = $default_message;
							} elseif ( $block['attrs']['um_message_type'] == '2' ) {
								$block_content = $block['attrs']['um_message_content'];
							}
						}
					}
					break;
				}
			}

			return $block_content;
		}

	}
}
