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


		private $ignore_exclude = false;


		/**
		 * Access constructor.
		 */
		function __construct() {
			$this->singular_page = false;

			$this->redirect_handler = false;
			$this->allow_access = false;

			// NEW HOOKS
			// Navigation line below the post content, change query to exclude restricted
			add_filter( 'get_next_post_where', array( &$this, 'exclude_navigation_posts' ), 99, 5 );
			add_filter( 'get_previous_post_where', array( &$this, 'exclude_navigation_posts' ), 99, 5 );

			// change the title of the post
			add_filter( 'the_title', array( &$this, 'filter_restricted_post_title' ), 10, 2 );
			// change the content of the restricted post
			add_filter( 'the_content', array( &$this, 'filter_restricted_post_content' ), 999999, 1 );
			// change the excerpt of the restricted post
			add_filter( 'get_the_excerpt', array( &$this, 'filter_restricted_post_excerpt' ), 999999, 2 );

			// filter attachment
			add_filter( 'wp_get_attachment_url', array( &$this, 'filter_attachment' ), 99, 2 );
			add_filter( 'has_post_thumbnail', array( &$this, 'filter_post_thumbnail' ), 99, 3 );

			// comments queries
			add_action( 'pre_get_comments', array( &$this, 'exclude_posts_comments' ), 99, 1 );
			add_filter( 'wp_count_comments', array( &$this, 'custom_comments_count_handler' ), 99, 2 );
			// comments RSS
			add_filter( 'comment_feed_where', array( &$this, 'exclude_posts_comments_feed' ), 99, 2 );
			// Disable comments if user has not permission to access current post
			add_filter( 'comments_open', array( $this, 'disable_comments_open' ), 99, 2 );
			add_filter( 'get_comments_number', array( $this, 'disable_comments_open_number' ), 99, 2 );

			// filter menu items
			add_filter( 'wp_nav_menu_objects', array( &$this, 'filter_menu' ), 99, 2 );

			// Gutenberg blocks restrictions
			add_filter( 'render_block', array( $this, 'restrict_blocks' ), 10, 2 );

			// check the site's accessible more priority have Individual Post/Term Restriction settings
			add_action( 'template_redirect', array( &$this, 'template_redirect' ), 1000 );
			add_action( 'um_access_check_individual_term_settings', array( &$this, 'um_access_check_individual_term_settings' ) );
			add_action( 'um_access_check_global_settings', array( &$this, 'um_access_check_global_settings' ) );

			add_action( 'plugins_loaded', array( &$this, 'disable_restriction_pre_queries' ), 1 );
		}


		/**
		 * Rollback function for old business logic to avoid security enhancements with 404 errors
		 */
		function disable_restriction_pre_queries() {
			// Using inside plugins_loaded hook because of there can be earlier direct queries without hooks.
			// Avoid using to not getting fatal error for not exists WordPress native functions.

			// Change recent posts widget query.
			add_filter( 'widget_posts_args', array( &$this, 'exclude_restricted_posts_widget' ), 99, 1 );
			// Exclude pages displayed by wp_list_pages function.
			add_filter( 'wp_list_pages_excludes', array( &$this, 'exclude_restricted_pages' ), 10, 1 );
			// Archives list change where based on restricted posts.
			add_filter( 'getarchives_where', array( &$this, 'exclude_restricted_posts_archives_widget' ), 99, 2 );

			// Callbacks for changing posts query.
			add_action( 'pre_get_posts', array( &$this, 'exclude_posts' ), 99, 1 );
			add_filter( 'posts_where', array( &$this, 'exclude_posts_where' ), 10, 2 );
			add_filter( 'wp_count_posts', array( &$this, 'custom_count_posts_handler' ), 99, 3 );

			// Callbacks for changing terms query.
			add_action( 'pre_get_terms', array( &$this, 'exclude_hidden_terms_query' ), 99, 1 );

			// there is posts (Posts/Page/CPT) filtration if site is accessible
			// there also will be redirects if they need
			// protect posts types
			add_filter( 'the_posts', array( &$this, 'filter_protected_posts' ), 99, 2 );
			// protect pages for wp_list_pages func
			add_filter( 'get_pages', array( &$this, 'filter_protected_posts' ), 99, 2 );

			if ( ! UM()->options()->get( 'disable_restriction_pre_queries' ) ) {
				return;
			}

			remove_action( 'pre_get_terms', array( &$this, 'exclude_hidden_terms_query' ), 99 );
			remove_filter( 'widget_posts_args', array( &$this, 'exclude_restricted_posts_widget' ), 99 );
			remove_filter( 'wp_list_pages_excludes', array( &$this, 'exclude_restricted_pages' ), 10 );
			remove_filter( 'getarchives_where', array( &$this, 'exclude_restricted_posts_archives_widget' ), 99 );
			remove_filter( 'get_next_post_where', array( &$this, 'exclude_navigation_posts' ), 99 );
			remove_filter( 'get_previous_post_where', array( &$this, 'exclude_navigation_posts' ), 99 );
			remove_action( 'pre_get_posts', array( &$this, 'exclude_posts' ), 99 );
			remove_filter( 'posts_where', array( &$this, 'exclude_posts_where' ), 10 );
			remove_filter( 'wp_count_posts', array( &$this, 'custom_count_posts_handler' ), 99 );
		}


		/**
		 * Get array with restricted posts
		 *
		 * @param bool $force
		 * @param bool|array|string $post_types
		 *
		 * @return array
		 */
		function exclude_posts_array( $force = false, $post_types = false ) {
			if ( $this->ignore_exclude ) {
				return array();
			}

			static $cache = array();

			$cache_key = $force ? 'force' : 'default';

			// `force` cache contains all restricted post IDs we can get them all from cache instead new queries
			$force_cache_key = '';
			if ( 'default' === $cache_key ) {
				$force_cache_key = 'force';
			}

			// make $post_types as array if string
			if ( ! empty( $post_types ) ) {
				$post_types = is_array( $post_types ) ? $post_types : array( $post_types );
				$cache_key .= md5( serialize( $post_types ) );
				if ( ! empty( $force_cache_key ) ) {
					$force_cache_key .= md5( serialize( $post_types ) );
				}
			}

			if ( array_key_exists( $cache_key, $cache ) ) {
				return $cache[ $cache_key ];
			}

			$exclude_posts = array();
			if ( current_user_can( 'administrator' ) ) {
				$cache[ $cache_key ] = $exclude_posts;
				return $exclude_posts;
			}

			// @todo using Object Cache `wp_cache_get()` `wp_cache_set()` functions

			// `force` cache contains all restricted post IDs we can get them all from cache instead new queries
			if ( ! empty( $force_cache_key ) && array_key_exists( $force_cache_key, $cache ) ) {
				$post_ids = $cache[ $force_cache_key ];

				if ( ! empty( $post_ids ) ) {
					foreach ( $post_ids as $post_id ) {
						$content_restriction = $this->get_post_privacy_settings( $post_id );
						if ( ! empty( $content_restriction['_um_access_hide_from_queries'] ) ) {
							array_push( $exclude_posts, $post_id );
						}
					}
				}
			} else {
				$restricted_posts = UM()->options()->get( 'restricted_access_post_metabox' );
				if ( ! empty( $restricted_posts ) ) {
					$restricted_posts = array_keys( $restricted_posts );
					if ( ! empty( $post_types ) ) {
						$restricted_posts = array_intersect( $post_types, $restricted_posts );
					}
				}

				if ( ! empty( $restricted_posts ) ) {
					$this->ignore_exclude = true;
					// exclude all posts assigned to current term without individual restriction settings
					$post_ids = get_posts(
						array(
							'fields'      => 'ids',
							'post_status' => 'any',
							'post_type'   => $restricted_posts,
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
					$restricted_taxonomies = array_keys( $restricted_taxonomies );
					foreach ( $restricted_taxonomies as $k => $taxonomy ) {
						if ( ! taxonomy_exists( $taxonomy ) ) {
							unset( $restricted_taxonomies[ $k ] );
						}
					}
					$restricted_taxonomies = array_values( $restricted_taxonomies );

					if ( ! empty( $post_types ) ) {
						$taxonomies = array();
						foreach ( $post_types as $p_t ) {
							$taxonomies = array_merge( $taxonomies, get_object_taxonomies( $p_t ) );
						}
						$restricted_taxonomies = array_intersect( $taxonomies, $restricted_taxonomies );
					}
				}

				if ( ! empty( $restricted_taxonomies ) ) {
					global $wpdb;

					$terms = $wpdb->get_results(
						"SELECT tm.term_id AS term_id, 
					        tt.taxonomy AS taxonomy 
					FROM {$wpdb->termmeta} tm 
					LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id 
					WHERE tm.meta_key = 'um_content_restriction' AND 
					      tt.taxonomy IN('" . implode( "','", $restricted_taxonomies ) . "')",
						ARRAY_A
					);

					if ( ! empty( $terms ) ) {
						foreach ( $terms as $term ) {
							if ( ! $this->is_restricted_term( $term['term_id'] ) ) {
								continue;
							}

							$taxonomy_data = get_taxonomy( $term['taxonomy'] );

							$this->ignore_exclude = true;
							// exclude all posts assigned to current term without individual restriction settings
							$posts = get_posts(
								array(
									'fields'      => 'ids',
									'post_status' => 'any',
									'post_type'   => $taxonomy_data->object_type,
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
			}

			$exclude_posts = apply_filters( 'um_exclude_restricted_posts_ids', $exclude_posts, $force );

			$cache[ $cache_key ] = $exclude_posts;
			return $exclude_posts;
		}



		/**
		 * Get array with restricted terms
		 *
		 * @param \WP_Term_Query $query
		 *
		 * @return array
		 */
		function exclude_terms_array( $query ) {
			$exclude = array();

			$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
			if ( ! empty( $restricted_taxonomies ) ) {
				$restricted_taxonomies = array_keys( $restricted_taxonomies );
				foreach ( $restricted_taxonomies as $k => $taxonomy ) {
					if ( ! taxonomy_exists( $taxonomy ) ) {
						unset( $restricted_taxonomies[ $k ] );
					}
				}
				$restricted_taxonomies = array_values( $restricted_taxonomies );

				if ( ! empty( $restricted_taxonomies ) ) {
					if ( isset( $query->query_vars['taxonomy'] ) && is_array( $query->query_vars['taxonomy'] ) ) {
						$restricted_taxonomies = array_intersect( $query->query_vars['taxonomy'], $restricted_taxonomies );
					} elseif ( ! empty( $query->query_vars['term_taxonomy_id'] ) ) {
						$term_taxonomy_ids = is_array( $query->query_vars['term_taxonomy_id'] ) ? $query->query_vars['term_taxonomy_id'] : array( $query->query_vars['term_taxonomy_id'] );

						global $wpdb;
						$tax_in_query = $wpdb->get_col( "SELECT DISTINCT taxonomy FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id IN('" . implode( "','", $term_taxonomy_ids ) . "')" );
						if ( ! empty( $tax_in_query ) ) {
							$restricted_taxonomies = array_intersect( $tax_in_query, $restricted_taxonomies );
						} else {
							$restricted_taxonomies = array();
						}
					}
				}
			}

			if ( empty( $restricted_taxonomies ) ) {
				return $exclude;
			}

			$cache_key = md5( serialize( $restricted_taxonomies ) );

			static $cache = array();

			if ( array_key_exists( $cache_key, $cache ) ) {
				return $cache[ $cache_key ];
			}

			$term_ids = get_terms(
				array(
					'taxonomy'          => $restricted_taxonomies,
					'hide_empty'        => false,
					'fields'            => 'ids',
					'meta_query'        => array(
						'key'     => 'um_content_restriction',
						'compare' => 'EXISTS',
					),
					'um_ignore_exclude' => true,
				)
			);

			if ( empty( $term_ids ) || is_wp_error( $term_ids ) ) {
				$cache[ $cache_key ] = $exclude;
				return $exclude;
			}

			foreach ( $term_ids as $term_id ) {
				if ( $this->is_restricted_term( $term_id ) ) {
					$exclude[] = $term_id;
				}
			}

			$exclude = apply_filters( 'um_exclude_restricted_terms_ids', $exclude );
			$cache[ $cache_key ] = $exclude;
			return $exclude;
		}


		/**
		 * @param \WP_Term_Query $query
		 */
		function exclude_hidden_terms_query( $query ) {
			if ( current_user_can( 'administrator' ) || ! empty( $query->query_vars['um_ignore_exclude'] ) ) {
				return;
			}

			$exclude = $this->exclude_terms_array( $query );
			if ( ! empty( $exclude ) ) {
				$query->query_vars['exclude'] = ! empty( $query->query_vars['exclude'] ) ? wp_parse_id_list( $query->query_vars['exclude'] ) : $exclude;
			}
		}


		/**
		 * @param \WP_Query $query
		 */
		function exclude_posts( $query ) {
			if ( current_user_can( 'administrator' ) ) {
				return;
			}

			// use these functions is_search() || is_admin() for getting force hide all posts
			// don't handle `hide from WP_Query` and show 404 option for searching and wp-admin query
			if ( $query->is_main_query() || ! empty( $query->query_vars['um_main_query'] ) ) {
				$force = is_feed() || is_search() || is_admin();

				if ( is_object( $query ) ) {
					$is_singular = $query->is_singular();
				} else {
					$is_singular = ! empty( $query->is_singular ) ? true : false;
				}

				if ( ! $is_singular ) {
					// need to know what post type is here
					$q_values = ! empty( $query->query_vars['post_type'] ) ? $query->query_vars['post_type'] : array();
					if ( ! is_array( $q_values ) ) {
						$q_values = explode( ',', $query->query_vars['post_type'] );
					}

					// 'any' will cause the query var to be ignored.
					if ( in_array( 'any', $q_values, true ) || empty( $q_values ) ) {
						$exclude_posts = $this->exclude_posts_array( $force );
					} else {
						$exclude_posts = $this->exclude_posts_array( $force, $q_values );
					}

					if ( ! empty( $exclude_posts ) ) {
						$post__not_in = $query->get( 'post__not_in', array() );
						$query->set( 'post__not_in', array_merge( wp_parse_id_list( $post__not_in ), $exclude_posts ) );
					}
				}
			}
		}


		/**
		 * Exclude restricted post from query if there is a single query that exclude post_not_in by default in \WP_Query
		 *
		 * @param string $where
		 * @param \WP_Query $query
		 *
		 * @return mixed
		 */
		function exclude_posts_where( $where, $query ) {
			if ( current_user_can( 'administrator' ) ) {
				return $where;
			}

			if ( ! $query->is_main_query() ) {
				return $where;
			}

			if ( ! empty( $query->query_vars['p'] ) && $this->is_restricted( $query->query_vars['p'] ) ) {
				$restriction_settings = $this->get_post_privacy_settings( $query->query_vars['p'] );
				if ( ! empty( $restriction_settings['_um_access_hide_from_queries'] ) && ! empty( $query->query_vars['post__not_in'] ) ) {
					global $wpdb;
					$post__not_in = implode( ',', array_map( 'absint', $query->query_vars['post__not_in'] ) );
					$where       .= " AND {$wpdb->posts}.ID NOT IN ($post__not_in)";
				}
			}

			return $where;
		}


		/**
		 * Change the posts count based on restriction settings
		 *
		 * @param object $counts Post counts
		 * @param string $type Post type
		 * @param string $perm The permission to determine if the posts are 'readable'
		 *                     by the current user.
		 *
		 * @return object
		 */
		function custom_count_posts_handler( $counts, $type = 'post', $perm = '' ) {
			if ( current_user_can( 'administrator' ) ) {
				return $counts;
			}

			global $wpdb;

			static $cache = array();

			$cache_key  = _count_posts_cache_key( $type, $perm );
			$force      = is_feed() || is_search() || is_admin();
			$cache_key .= $force ? 'force' : '';

			if ( array_key_exists( $cache_key, $cache ) ) {
				return $cache[ $cache_key ];
			}

			$exclude_posts = $this->exclude_posts_array( $force, array( $type ) );
			if ( empty( $exclude_posts ) ) {
				$cache[ $cache_key ] = $counts;
				return $counts;
			}

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

			$cache[ $cache_key ] = $counts;
			return $counts;
		}


		/**
		 * Exclude restricted posts in Recent Posts widget
		 *
		 * @param array $array Query args
		 *
		 * @return array
		 */
		function exclude_restricted_posts_widget( $array ) {
			if ( current_user_can( 'administrator' ) ) {
				return $array;
			}

			$exclude_posts = $this->exclude_posts_array( false, 'post' );
			if ( ! empty( $exclude_posts ) ) {
				$post__not_in = ! empty( $array['post__not_in'] ) ? $array['post__not_in'] : array();
				$array['post__not_in'] = array_merge( wp_parse_id_list( $post__not_in ), $exclude_posts );
			}

			return $array;
		}


		/**
		 * Exclude restricted posts in Recent Posts widget
		 *
		 * @param array $array Query args
		 *
		 * @return array
		 */
		function exclude_restricted_pages( $array ) {
			if ( current_user_can( 'administrator' ) ) {
				return $array;
			}

			$exclude_posts = $this->exclude_posts_array( false, 'page' );
			if ( ! empty( $exclude_posts ) ) {
				$array = array_merge( $array, $exclude_posts );
			}

			return $array;
		}


		/**
		 * Exclude restricted posts in widgets
		 *
		 * @param string $sql_where
		 * @param array $parsed_args
		 *
		 * @return string
		 */
		function exclude_restricted_posts_archives_widget( $sql_where, $parsed_args = array() ) {
			if ( current_user_can( 'administrator' ) ) {
				return $sql_where;
			}

			$post_type = ! empty( $parsed_args['post_type'] ) ? $parsed_args['post_type'] : false;

			$exclude_posts = $this->exclude_posts_array( false, $post_type );
			if ( ! empty( $exclude_posts ) ) {
				$exclude_string = implode( ',', $exclude_posts );
				$sql_where .= ' AND ID NOT IN ( ' . $exclude_string . ' )';
			}

			return $sql_where;
		}


		/**
		 * Exclude posts from next, previous navigation
		 *
		 * @param string $where
		 * @param bool $in_same_term
		 * @param string|array $excluded_terms
		 * @param string $taxonomy
		 * @param null|\WP_Post $post
		 *
		 * @return string
		 */
		function exclude_navigation_posts( $where, $in_same_term = false, $excluded_terms = '', $taxonomy = 'category', $post = null ) {
			if ( current_user_can( 'administrator' ) ) {
				return $where;
			}

			if ( empty( $post ) ) {
				return $where;
			}

			$exclude_posts = $this->exclude_posts_array( false, $post->post_type );
			if ( ! empty( $exclude_posts ) ) {
				$exclude_string = implode( ',', $exclude_posts );
				$where .= ' AND ID NOT IN ( ' . $exclude_string . ' )';
			}

			return $where;
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
			if ( ! UM()->options()->get( 'restricted_post_title_replace' ) ) {
				return $title;
			}

			if ( current_user_can( 'administrator' ) ) {
				return $title;
			}

			if ( ! isset( $id ) ) {
				return $title;
			}

			if ( ! is_numeric( $id ) ) {
				$id = absint( $id );
			}

			$ignore = apply_filters( 'um_ignore_restricted_title', false, $id );
			if ( $ignore ) {
				return $title;
			}

			if ( $this->is_restricted( $id ) ) {
				$restricted_global_title = UM()->options()->get( 'restricted_access_post_title' );
				$title = stripslashes( $restricted_global_title );
			}

			return $title;
		}


		/**
		 * Replace content of restricted posts
		 *
		 * @param string $content
		 *
		 * @return string
		 */
		function filter_restricted_post_content( $content ) {
			if ( current_user_can( 'administrator' ) ) {
				return $content;
			}

			$id = get_the_ID();
			if ( ! $id || is_admin() ) {
				return $content;
			}

			$ignore = apply_filters( 'um_ignore_restricted_content', false, $id );
			if ( $ignore ) {
				return $content;
			}

			if ( $this->is_restricted( $id ) ) {
				$restriction = $this->get_post_privacy_settings( $id );

				if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
					$content = stripslashes( UM()->options()->get( 'restricted_access_message' ) );
				} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
					$content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
				}

				// translators: %s: Restricted post message.
				$content = sprintf( __( '%s', 'ultimate-member' ), $content );
			}

			return $content;
		}


		/**
		 * Replace excerpt of restricted posts
		 *
		 * @param string $post_excerpt
		 * @param \WP_Post $post
		 *
		 * @return string
		 */
		function filter_restricted_post_excerpt( $post_excerpt = '', $post = null ) {
			if ( empty( $post ) ) {
				return $post_excerpt;
			}

			if ( current_user_can( 'administrator' ) || is_admin() ) {
				return $post_excerpt;
			}

			$ignore = apply_filters( 'um_ignore_restricted_excerpt', false, $post->ID );
			if ( $ignore ) {
				return $post_excerpt;
			}

			if ( $this->is_restricted( $post->ID ) ) {
				$post_excerpt = '';
			}

			return $post_excerpt;
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
			if ( current_user_can( 'administrator' ) ) {
				return $url;
			}

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
		function filter_post_thumbnail( $has_thumbnail, $post = null, $thumbnail_id = false ) {
			if ( empty( $thumbnail_id ) ) {
				return $has_thumbnail;
			}

			if ( current_user_can( 'administrator' ) ) {
				return $has_thumbnail;
			}

			if ( $this->is_restricted( $thumbnail_id ) ) {
				$has_thumbnail = false;
			} elseif ( ! empty( $post ) && ! empty( $post->ID ) ) {
				if ( $this->is_restricted( $post->ID ) ) {
					$has_thumbnail = false;
				}
			} else {
				$post_id = get_the_ID();
				if ( false !== $post_id && $this->is_restricted( $post_id ) ) {
					$has_thumbnail = false;
				}
			}

			$has_thumbnail = apply_filters( 'um_restrict_post_thumbnail', $has_thumbnail, $post, $thumbnail_id );

			return $has_thumbnail;
		}



		/**
		 * Exclude comments from restricted posts in widgets
		 *
		 * @param \WP_Comment_Query $query
		 */
		function exclude_posts_comments( $query ) {
			if ( current_user_can( 'administrator' ) ) {
				return;
			}

			if ( ! empty( $query->query_vars['post_id'] ) ) {
				$exclude_posts = array();
				if ( $this->is_restricted( $query->query_vars['post_id'] ) ) {
					$exclude_posts[] = $query->query_vars['post_id'];
				}
			} else {
				$q_values = ! empty( $query->query_vars['post_type'] ) ? $query->query_vars['post_type'] : array();
				if ( ! is_array( $q_values ) ) {
					$q_values = explode( ',', $query->query_vars['post_type'] );
				}

				// 'any' will cause the query var to be ignored.
				if ( in_array( 'any', $q_values, true ) || empty( $q_values ) ) {
					$exclude_posts = $this->exclude_posts_array( true, $this->get_available_comments_post_types() );
				} else {
					$exclude_posts = $this->exclude_posts_array( true, $q_values );
				}
			}

			if ( ! empty( $exclude_posts ) ) {
				$post__not_in = ! empty( $query->query_vars['post__not_in'] ) ? $query->query_vars['post__not_in'] : array();
				$query->query_vars['post__not_in'] = array_merge( wp_parse_id_list( $post__not_in ), $exclude_posts );
			}
		}


		/**
		 * @return array
		 */
		function get_available_comments_post_types() {
			global $wp_taxonomies, $wpdb;

			$restricted_posts = UM()->options()->get( 'restricted_access_post_metabox' );
			if ( empty( $restricted_posts ) ) {
				$restricted_posts = array();
			}
			$restricted_posts = array_keys( $restricted_posts );

			$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
			if ( ! empty( $restricted_taxonomies ) ) {
				$restricted_taxonomies = array_keys( $restricted_taxonomies );
				foreach ( $restricted_taxonomies as $k => $taxonomy ) {
					if ( taxonomy_exists( $taxonomy ) ) {
						$restricted_posts = array_merge( $restricted_posts, $wp_taxonomies[ $taxonomy ]->object_type );
					}
				}
			}

			$restricted_posts = array_unique( $restricted_posts );
			foreach ( $restricted_posts as $k => $post_type ) {
				if ( 'closed' === get_default_comment_status( $post_type ) ) {
					$open_comments = $wpdb->get_var( $wpdb->prepare(
						"SELECT ID 
						FROM {$wpdb->posts} 
						WHERE post_type = %s AND 
						      comment_status != 'closed'",
						$post_type
					) );

					if ( empty( $open_comments ) ) {
						unset( $restricted_posts[ $k ] );
					}
				}
			}

			$restricted_posts = array_values( $restricted_posts );

			return $restricted_posts;
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
			if ( current_user_can( 'administrator' ) ) {
				return $where;
			}

			$exclude_posts = $this->exclude_posts_array( true, $this->get_available_comments_post_types() );
			if ( ! empty( $exclude_posts ) ) {
				$exclude_string = implode( ',', $exclude_posts );
				$where .= ' AND comment_post_ID NOT IN ( ' . $exclude_string . ' )';
			}

			return $where;
		}


		/**
		 * @param array|object $stats
		 * @param int $post_id Post ID. Can be 0 for the whole website
		 *
		 * @return object
		 */
		function custom_comments_count_handler( $stats = array(), $post_id = 0 ) {
			if ( ! empty( $stats ) || current_user_can( 'administrator' ) ) {
				return $stats;
			}

			if ( $post_id === 0 ) {
				$exclude_posts = $this->exclude_posts_array( true, $this->get_available_comments_post_types() );
				if ( empty( $exclude_posts ) ) {
					return $stats;
				}
			} else {
				$exclude_posts = array();
				if ( $this->is_restricted( $post_id ) ) {
					$exclude_posts[] = $post_id;
				}
			}

			$stats              = $this->get_comment_count( $post_id, $exclude_posts );
			$stats['moderated'] = $stats['awaiting_moderation'];
			unset( $stats['awaiting_moderation'] );

			$stats_object = (object) $stats;

			return $stats_object;
		}


		/**
		 * @param int $post_id
		 * @param array $exclude_posts
		 *
		 * @return array
		 */
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
		 * Disable comments if user has not permission to access this post
		 *
		 * @param mixed $open
		 * @param int $post_id
		 * @return boolean
		 */
		function disable_comments_open( $open, $post_id ) {
			if ( current_user_can( 'administrator' ) ) {
				return $open;
			}

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
		function disable_comments_open_number( $count, $post_id = 0 ) {
			if ( current_user_can( 'administrator' ) ) {
				return $count;
			}

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
		 * Protect Post Types in menu query
		 * Restrict content new logic
		 * @param array $menu_items
		 * @param array $args
		 * @return array
		 */
		function filter_menu( $menu_items, $args = array() ) {
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
					if ( isset( $menu_item->type ) && 'taxonomy' === $menu_item->type ) {
						if ( ! $this->is_restricted_term( $menu_item->object_id ) ) {
							$filtered_items[] = $menu_item;
							continue;
						}
					} elseif ( isset( $menu_item->type ) && 'post_type' === $menu_item->type ) {
						if ( ! $this->is_restricted( $menu_item->object_id ) ) {
							$filtered_items[] = $menu_item;
							continue;
						} else {
							$restriction_settings = $this->get_post_privacy_settings( $menu_item->object_id );
							if ( empty( $restriction_settings['_um_access_hide_from_queries'] ) || UM()->options()->get( 'disable_restriction_pre_queries' ) ) {
								$filtered_items[] = $this->maybe_replace_nav_menu_title( $menu_item );
								continue;
							}
						}
					} elseif ( isset( $menu_item->type ) && 'custom' === $menu_item->type ) {
						$filtered_items[] = $menu_item;
						continue;
					} else {
						$filtered_items[] = $menu_item;
						continue;
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


		/**
		 * @param \WP_Post $post
		 *
		 * @return \WP_Post
		 */
		function maybe_replace_title( $post ) {
			if ( ! UM()->options()->get( 'restricted_post_title_replace' ) ) {
				return $post;
			}

			if ( current_user_can( 'administrator' ) ) {
				return $post;
			}

			if ( ! is_a( $post, '\WP_Post' ) ) {
				return $post;
			}

			$ignore = apply_filters( 'um_ignore_restricted_title', false, $post->ID );
			if ( $ignore ) {
				return $post;
			}

			$restricted_global_title = UM()->options()->get( 'restricted_access_post_title' );
			$post->post_title = stripslashes( $restricted_global_title );

			return $post;
		}


		/**
		 * @param \WP_Post $nav_item
		 *
		 * @return \WP_Post
		 */
		function maybe_replace_nav_menu_title( $nav_item ) {
			if ( ! UM()->options()->get( 'restricted_post_title_replace' ) ) {
				return $nav_item;
			}

			if ( current_user_can( 'administrator' ) ) {
				return $nav_item;
			}

			if ( ! is_a( $nav_item, '\WP_Post' ) ) {
				return $nav_item;
			}

			$ignore = apply_filters( 'um_ignore_restricted_title', false, $nav_item->ID );
			if ( $ignore ) {
				return $nav_item;
			}

			$restricted_global_title = UM()->options()->get( 'restricted_access_post_title' );
			$nav_item->title = stripslashes( $restricted_global_title );

			return $nav_item;
		}


		/**
		 * Protect Post Types in query
		 * Restrict content new logic
		 *
		 * @param array $posts
		 * @param array|\WP_Query $query
		 * @return array
		 */
		function filter_protected_posts( $posts, $query ) {
			if ( current_user_can( 'administrator' ) ) {
				return $posts;
			}

			//Woocommerce AJAX fixes....remove filtration on wc-ajax which goes to Front Page
			if ( ! empty( $_GET['wc-ajax'] ) && defined( 'WC_DOING_AJAX' ) && WC_DOING_AJAX ) {
				return $posts;
			}

			//if empty
			if ( empty( $posts ) || is_admin() ) {
				return $posts;
			}

			if ( is_object( $query ) ) {
				$is_singular = $query->is_singular();
			} else {
				$is_singular = ! empty( $query->is_singular ) ? true : false;
			}

			if ( is_object( $query ) && is_a( $query, '\WP_Query' ) &&
			     ( $query->is_main_query() || ! empty( $query->query_vars['um_main_query'] ) ) ) {
				if ( $is_singular ) {
					if ( ! UM()->options()->get( 'disable_restriction_pre_queries' ) && $this->is_restricted( $posts[0]->ID ) ) {
						$content_restriction = $this->get_post_privacy_settings( $posts[0]->ID );
						if ( ! empty( $content_restriction['_um_access_hide_from_queries'] ) ) {
							unset( $posts[0] );
							return $posts;
						}
					}
				}
			}

			$filtered_posts = array();

			//other filter
			foreach ( $posts as $post ) {
				if ( is_user_logged_in() && isset( $post->post_author ) && $post->post_author == get_current_user_id() ) {
					$filtered_posts[] = $post;
					continue;
				}

				$restriction = $this->get_post_privacy_settings( $post );
				if ( ! $restriction ) {
					$filtered_posts[] = $post;
					continue;
				}

				if ( $is_singular ) {
					$this->singular_page = true;
				}

				if ( ! $this->is_restricted( $post->ID ) ) {
					$filtered_posts[] = $post;
					continue;
				} else {
					if ( $is_singular ) {
						if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {
							if ( UM()->options()->get( 'disable_restriction_pre_queries' ) || empty( $restriction['_um_access_hide_from_queries'] ) ) {
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

								$filtered_posts[] = $this->maybe_replace_title( $post );
								continue;
							}
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
					} else {
						if ( empty( $restriction['_um_access_hide_from_queries'] ) || UM()->options()->get( 'disable_restriction_pre_queries' ) ) {
							$filtered_posts[] = $this->maybe_replace_title( $post );
							continue;
						}
					}
				}
			}

			return $filtered_posts;
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

			if ( $this->is_restricted_term( $term_id, true ) ) {
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
				} else {
					add_filter( 'tag_template', array( &$this, 'taxonomy_message' ), 10, 3 );
					add_filter( 'archive_template', array( &$this, 'taxonomy_message' ), 10, 3 );
					add_filter( 'category_template', array( &$this, 'taxonomy_message' ), 10, 3 );
					add_filter( 'taxonomy_template', array( &$this, 'taxonomy_message' ), 10, 3 );
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
			$ms_empty_role_access = is_multisite() && is_user_logged_in() && ! UM()->roles()->get_priority_user_role( um_user( 'ID' ) );

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
					$this->redirect_handler = $this->set_referer( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), $redirect_to ) ), 'custom_homepage' );

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
		 * Check access
		 *
		 * @return bool
		 */
		function check_access() {
			if ( $this->allow_access === true ) {
				return true;
			}

			if ( $this->redirect_handler ) {
				wp_redirect( $this->redirect_handler );
				exit;
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
		 * Get privacy settings for post
		 * return false if post is not private
		 * Restrict content new logic
		 *
		 * @param \WP_Post|int $post Post ID or object
		 * @return bool|array
		 */
		function get_post_privacy_settings( $post ) {
			// break for incorrect post
			if ( empty( $post ) ) {
				return false;
			}

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

								$terms = array_merge( $terms, wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids', 'um_ignore_exclude' => true, ) ) );
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

					$terms = array_merge( $terms, wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids', 'um_ignore_exclude' => true, ) ) );
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
		 * Helper for checking if the user can some of the roles array
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
		 * Helper for 3rd-party integrations with content restriction settings
		 *
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
		 * Is post restricted?
		 *
		 * @param int $post_id
		 * @return bool
		 */
		function is_restricted( $post_id ) {
			// break for incorrect post
			if ( empty( $post_id ) ) {
				return false;
			}

			static $cache = array();

			if ( isset( $cache[ $post_id ] ) ) {
				return $cache[ $post_id ];
			}

			if ( current_user_can( 'administrator' ) ) {
				$cache[ $post_id ] = false;
				return false;
			}

			$post = get_post( $post_id );
			if ( is_user_logged_in() && isset( $post->post_author ) && $post->post_author == get_current_user_id() ) {
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

			$restricted = apply_filters( 'um_is_restricted_post', $restricted, $post_id );

			$cache[ $post_id ] = $restricted;

			return $restricted;
		}


		/**
		 * Is term restricted?
		 *
		 * @param int $term_id
		 * @param bool $on_term_page
		 * @return bool
		 */
		function is_restricted_term( $term_id, $on_term_page = false ) {
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
						if ( $on_term_page ) {
							$this->allow_access = true;
						}
					} elseif ( '1' == $restriction['_um_accessible'] ) {
						//if term for not logged in users and user is not logged in
						if ( ! is_user_logged_in() ) {
							$restricted = false;
							if ( $on_term_page ) {
								$this->allow_access = true;
							}
						}
					} elseif ( '2' == $restriction['_um_accessible'] ) {
						//if term for logged in users and user is not logged in
						if ( is_user_logged_in() ) {
							$custom_restrict = $this->um_custom_restriction( $restriction );

							if ( empty( $restriction['_um_access_roles'] ) || false === array_search( '1', $restriction['_um_access_roles'] ) ) {
								if ( $custom_restrict ) {
									$restricted = false;
									if ( $on_term_page ) {
										$this->allow_access = true;
									}
								}
							} else {
								$user_can = $this->user_can( get_current_user_id(), $restriction['_um_access_roles'] );

								if ( $user_can && $custom_restrict ) {
									$restricted = false;
									if ( $on_term_page ) {
										$this->allow_access = true;
									}
								}
							}
						}
					}
				}
			}

			$restricted = apply_filters( 'um_is_restricted_term', $restricted, $term_id, $on_term_page );

			$cache[ $term_id ] = $restricted;
			return $restricted;
		}
	}
}
