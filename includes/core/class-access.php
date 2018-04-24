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


		/**
		 * Access constructor.
		 */
		function __construct() {

			$this->singular_page = false;

			$this->redirect_handler = false;
			$this->allow_access = false;

			//there is posts (Posts/Page/CPT) filtration if site is accessible
			//there also will be redirects if they need
			//protect posts types
			add_filter( 'the_posts', array( &$this, 'filter_protected_posts' ), 99, 2 );
			//protect pages for wp_list_pages func
			add_filter( 'get_pages', array( &$this, 'filter_protected_posts' ), 99, 2 );
			//filter menu items
			add_filter( 'wp_nav_menu_objects', array( &$this, 'filter_menu' ), 99, 2 );


			//check the site's accessible more priority have Individual Post/Term Restriction settings
			add_action( 'template_redirect', array( &$this, 'template_redirect' ), 1000 );
			add_action( 'um_access_check_individual_term_settings', array( &$this, 'um_access_check_individual_term_settings' ) );
			add_action( 'um_access_check_global_settings', array( &$this, 'um_access_check_global_settings' ) );
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

			if ( is_tag() ) {
				$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
				if ( empty( $restricted_taxonomies['post_tag'] ) )
					return;

				$tag_id = get_query_var( 'tag_id' );
				if ( ! empty( $tag_id ) ) {
					$restriction = get_term_meta( $tag_id, 'um_content_restriction', true );
				}
			} elseif ( is_category() ) {
				$um_category = get_category( get_query_var( 'cat' ) );

				$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
				if ( empty( $restricted_taxonomies[ $um_category->taxonomy ] ) )
					return;

				if ( ! empty( $um_category->term_id ) ) {
					$restriction = get_term_meta( $um_category->term_id, 'um_content_restriction', true );
				}
			} elseif ( is_tax() ) {
				$tax_name = get_query_var( 'taxonomy' );

				$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
				if ( empty( $restricted_taxonomies[ $tax_name ] ) )
					return;

				$term_name = get_query_var( 'term' );
				$term = get_term_by( 'slug', $term_name, $tax_name );
				if ( ! empty( $term->term_id ) ) {
					$restriction = get_term_meta( $term->term_id, 'um_content_restriction', true );
				}
			}

			if ( ! isset( $restriction ) || empty( $restriction['_um_custom_access_settings'] ) )
				return;

			//post is private
			if ( '0' == $restriction['_um_accessible'] ) {
				$this->allow_access = true;
				return;
			} elseif ( '1' == $restriction['_um_accessible'] ) {
				//if post for not logged in users and user is not logged in
				if ( ! is_user_logged_in() ) {
					$this->allow_access = true;
					return;
				}

			} elseif ( '2' == $restriction['_um_accessible'] ) {
				//if post for logged in users and user is not logged in
				if ( is_user_logged_in() ) {

					$custom_restrict = $this->um_custom_restriction( $restriction );
					if ( empty( $restriction['_um_access_roles'] ) || false === array_search( '1', $restriction['_um_access_roles'] ) ) {
						if ( $custom_restrict ) {
							$this->allow_access = true;
							return;
						} else {
							//restrict terms page by 404 for logged in users with wrong role
							add_filter( 'tag_template', array( &$this, 'taxonomy_message' ), 10, 3 );
							add_filter( 'archive_template', array( &$this, 'taxonomy_message' ), 10, 3 );
							add_filter( 'category_template', array( &$this, 'taxonomy_message' ), 10, 3 );
							add_filter( 'taxonomy_template', array( &$this, 'taxonomy_message' ), 10, 3 );

							/*global $wp_query;
							$wp_query->set_404();
							status_header( 404 );
							nocache_headers();*/
						}
					} else {
						$user_can = $this->user_can( get_current_user_id(), $restriction['_um_access_roles'] );

						if ( isset( $user_can ) && $user_can && $custom_restrict ) {
							$this->allow_access = true;
							return;
						} else {

							add_filter( 'tag_template', array( &$this, 'taxonomy_message' ), 10, 3 );
							add_filter( 'archive_template', array( &$this, 'taxonomy_message' ), 10, 3 );
							add_filter( 'category_template', array( &$this, 'taxonomy_message' ), 10, 3 );
							add_filter( 'taxonomy_template', array( &$this, 'taxonomy_message' ), 10, 3 );

							//restrict terms page by 404 for logged in users with wrong role
							/*global $wp_query;
							$wp_query->set_404();
							status_header( 404 );
							nocache_headers();*/
						}
					}
				}
			}

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

			if ( is_front_page() ) {
				if ( is_user_logged_in() ) {

					$user_default_homepage = um_user( 'default_homepage' );
					if ( ! empty( $user_default_homepage ) )
						return;

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
							if ( ! $redirect )
								$redirect = um_get_core_page( 'login' );

							$this->redirect_handler = $this->set_referer( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), $redirect ) ), 'global' );
						} else {
							$this->allow_access = true;
							return;
						}
					}
				}
			} elseif ( is_category() ) {
				if ( ! is_user_logged_in() ) {

					$access = UM()->options()->get( 'accessible' );

					if ( $access == 2 ) {
						//global settings for accessible home page
						$category_page_accessible = UM()->options()->get( 'category_page_accessible' );
						if ( $category_page_accessible == 0 ) {
							//get redirect URL if not set get login page by default
							$redirect = UM()->options()->get( 'access_redirect' );
							if ( ! $redirect )
								$redirect = um_get_core_page( 'login' );

							$this->redirect_handler = $this->set_referer( esc_url( add_query_arg( 'redirect_to', urlencode_deep( $curr ), $redirect ) ), 'global' );
						} else {
							$this->allow_access = true;
							return;
						}
					}
				}
			}

			$access = UM()->options()->get( 'accessible' );

			if ( $access == 2 && ! is_user_logged_in() ) {

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
			global $post;

			//if we logged by administrator it can access to all content
			if ( current_user_can( 'administrator' ) )
				return;

			//if we use individual restrict content options skip this function
			if ( $this->singular_page )
				return;

			//also skip if we currently at wp-admin or 404 page
			if ( is_admin() || is_404() )
				return;

			//also skip if we currently at UM Register|Login|Reset Password pages
			if ( um_is_core_post( $post, 'register' ) ||
			     um_is_core_post( $post, 'password-reset' ) ||
			     um_is_core_post( $post, 'login' ) )
				return;

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
			if ( $this->check_access() )
				return;

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

			if ( $this->allow_access == true )
				return true;

			if ( $this->redirect_handler ) {

				// login page add protected page automatically
				/*if ( strstr( $this->redirect_handler, um_get_core_page('login') ) ){
					$curr = UM()->permalinks()->get_current_url();
					$this->redirect_handler = esc_url( add_query_arg('redirect_to', urlencode_deep( $curr ), $this->redirect_handler) );
				}*/

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
			$enable_referer = apply_filters( "um_access_enable_referer", false );
			if ( ! $enable_referer )
				return $url;

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
		 * @param $post
		 * @return bool|array
		 */
		function get_post_privacy_settings( $post ) {
			//if logged in administrator all pages are visible
			if ( current_user_can( 'administrator' ) ) {
				return false;
			}

			//exlude from privacy UM default pages (except Members list and User(Profile) page)
			if ( ! empty( $post->post_type ) && $post->post_type == 'page' ) {
				if ( um_is_core_post( $post, 'login' ) || um_is_core_post( $post, 'register' ) ||
				     um_is_core_post( $post, 'account' ) || um_is_core_post( $post, 'logout' ) ||
				     um_is_core_post( $post, 'password-reset' ) )
					return false;
			}

			$restricted_posts = UM()->options()->get( 'restricted_access_post_metabox' );

			if ( ! empty( $post->post_type ) && ! empty( $restricted_posts[ $post->post_type ] ) ) {
				$restriction = get_post_meta( $post->ID, 'um_content_restriction', true );

				if ( ! empty( $restriction['_um_custom_access_settings'] ) ) {
					if ( ! isset( $restriction['_um_accessible'] ) )
						return false;
					else
						return $restriction;
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
					if ( empty( $restricted_taxonomies[$taxonomy] ) )
						continue;

					$terms = array_merge( $terms, wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) ) );
				}
			}

			//get restriction options for first term with privacy settigns
			foreach ( $terms as $term_id ) {
				$restriction = get_term_meta( $term_id, 'um_content_restriction', true );

				if ( ! empty( $restriction['_um_custom_access_settings'] ) ) {
					if ( ! isset( $restriction['_um_accessible'] ) || '0' == $restriction['_um_accessible'] )
						continue;
					else
						return $restriction;
				}
			}


			//post is public
			return false;
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
			if ( empty( $posts ) )
				return $posts;

			$restricted_global_message = UM()->options()->get( 'restricted_access_message' );

			//other filter
			foreach ( $posts as $post ) {

				//Woocommerce AJAX fixes....remove filtration on wc-ajax which goes to Front Page
				if ( ! empty( $_GET['wc-ajax'] ) /*&& $query->is_front_page()*/ ) {
					$filtered_posts[] = $post;
					continue;
				}

				$restriction = $this->get_post_privacy_settings( $post );

				if ( ! $restriction ) {
					$filtered_posts[] = $post;
					continue;
				}

				if ( is_object( $query ) ) {
					$is_singular = $query->is_singular();
				} else {
					$is_singular = ! empty( $query->is_singular ) ? true : false;
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

						if ( current_user_can( 'administrator' ) ) {
							$filtered_posts[] = $post;
							continue;
						}

						if ( empty( $is_singular ) ) {
							//if not single query when exclude if set _um_access_hide_from_queries
							if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {

								if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

									if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
										$post->post_content = stripslashes( $restricted_global_message );
									} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
										$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
									}

								}

								$filtered_posts[] = $post;
								continue;
							}
						} else {
							$this->singular_page = true;

							//if single post query
							if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

								if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = stripslashes( $restricted_global_message );
								} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
								}

								$this->current_single_post = $post;
								add_filter( 'the_content', array( &$this, 'replace_post_content' ), 9999, 1 );

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

						if ( current_user_can( 'administrator' ) ) {
							$filtered_posts[] = $post;
							continue;
						}

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

								if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

									if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
										$post->post_content = stripslashes( $restricted_global_message );
									} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
										$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
									}

								}

								$filtered_posts[] = $post;
								continue;
							}
						} else {
							$this->singular_page = true;

							//if single post query
							if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

								if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = stripslashes( $restricted_global_message );

									$this->current_single_post = $post;
									add_filter( 'the_content', array( &$this, 'replace_post_content' ), 9999, 1 );

									if ( 'attachment' == $post->post_type ) {
										remove_filter( 'the_content', 'prepend_attachment' );
									}
								} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';

									$this->current_single_post = $post;
									add_filter( 'the_content', array( &$this, 'replace_post_content' ), 9999, 1 );

									if ( 'attachment' == $post->post_type ) {
										remove_filter( 'the_content', 'prepend_attachment' );
									}
								}

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

								if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

									if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
										$post->post_content = stripslashes( $restricted_global_message );
									} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
										$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';
									}

								}

								$filtered_posts[] = $post;
								continue;
							}
						} else {
							$this->singular_page = true;

							//if single post query
							if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

								if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = stripslashes( $restricted_global_message );

									$this->current_single_post = $post;
									add_filter( 'the_content', array( &$this, 'replace_post_content' ), 9999, 1 );

									if ( 'attachment' == $post->post_type ) {
										remove_filter( 'the_content', 'prepend_attachment' );
									}
								} elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
									$post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? stripslashes( $restriction['_um_restrict_custom_message'] ) : '';

									$this->current_single_post = $post;
									add_filter( 'the_content', array( &$this, 'replace_post_content' ), 9999, 1 );

									if ( 'attachment' == $post->post_type ) {
										remove_filter( 'the_content', 'prepend_attachment' );
									}
								}

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
		 * @param $content
		 *
		 * @return string
		 */
		function replace_post_content( $content ) {
			$content = $this->current_single_post->post_content;
			return $content;
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
			if ( empty( $menu_items ) )
				return $menu_items;

			$filtered_items = array();

			//other filter
			foreach ( $menu_items as $menu_item ) {

				if ( ! empty( $menu_item->object_id ) && ! empty( $menu_item->object ) ) {

					$restriction = $this->get_post_privacy_settings( get_post( $menu_item->object_id ) );
					if ( ! $restriction ) {
						$filtered_items[] = $menu_item;
						continue;
					}

					//post is private
					if ( '1' == $restriction['_um_accessible'] ) {
						//if post for not logged in users and user is not logged in
						if ( ! is_user_logged_in() ) {
							$filtered_items[] = $menu_item;
							continue;
						} else {

                            if ( current_user_can( 'administrator' ) ) {
                                $filtered_items[] = $menu_item;
                                continue;
                            }

						    //if not single query when exclude if set _um_access_hide_from_queries
							if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {
								$filtered_items[] = $menu_item;
								continue;
							}
						}
					} elseif ( '2' == $restriction['_um_accessible'] ) {
						//if post for logged in users and user is not logged in
						if ( is_user_logged_in() ) {

                            if ( current_user_can( 'administrator' ) ) {
                                $filtered_items[] = $menu_item;
                                continue;
                            }

							$custom_restrict = $this->um_custom_restriction( $restriction );

							if ( empty( $restriction['_um_access_roles'] ) || false === array_search( '1', $restriction['_um_access_roles'] ) ) {
								if ( $custom_restrict ) {
									$filtered_items[] = $menu_item;
									continue;
								}
							} else {
								$user_can = $this->user_can( get_current_user_id(), $restriction['_um_access_roles'] );

								if ( isset( $user_can ) && $user_can && $custom_restrict ) {
									$filtered_items[] = $menu_item;
									continue;
								}
							}

							//if not single query when exclude if set _um_access_hide_from_queries
							if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {
								$filtered_items[] = $menu_item;
								continue;
							}

						} else {
							if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {
								$filtered_items[] = $menu_item;
								continue;
							}
						}
					}

					continue;
				}

				//add all other posts
				$filtered_items[] = $menu_item;
			}

			return $filtered_items;
		}
	}
}