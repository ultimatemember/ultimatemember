<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Access' ) ) {
    class Access {

        function __construct() {

            $this->redirect_handler = false;
            $this->allow_access = false;

            add_action( 'template_redirect', array( &$this, 'template_redirect' ), 1000 );

            //protect posts types
            add_filter( 'the_posts', array( &$this, 'filter_protected_posts' ), 99, 2 );

            //protect pages for wp_list_pages func
            add_filter( 'get_pages', array( &$this, 'filter_protected_posts' ), 99, 2 );

            //filter menu items
            add_filter( 'wp_nav_menu_objects', array( &$this, 'filter_menu' ), 99, 2 );
        }


        /**
         * Set custom access actions and redirection
         *
         * Old global restrict content logic
         */
        function template_redirect() {
            global $post;

            do_action('um_access_global_settings');

            do_action('um_access_user_custom_homepage');

            do_action('um_access_frontpage_per_role');

            do_action('um_access_homepage_per_role');

            if ( $this->redirect_handler && $this->allow_access == false &&
                ( ! um_is_core_page('login') || um_is_core_page( 'login' ) && is_user_logged_in() ) ) {

                // login page add protected page automatically

                if ( strstr( $this->redirect_handler, um_get_core_page('login') ) ){
                    $curr = UM()->permalinks()->get_current_url();
                    $this->redirect_handler = esc_url( add_query_arg('redirect_to', urlencode_deep( $curr ), $this->redirect_handler) );
                }

                wp_redirect( $this->redirect_handler );

            }

        }


        /**
         * Get custom access settings meta
         * @param  integer $post_id
         * @return array
         */
        function get_meta( $post_id ) {
            global $post;
            $meta = get_post_custom( $post_id );
            if ( isset( $meta ) && is_array( $meta ) ) {
                foreach ($meta as $k => $v){
                    if ( strstr($k, '_um_') ) {
                        $k = str_replace('_um_', '', $k);
                        $array[$k] = $v[0];
                    }
                }
            }
            if ( isset( $array ) )
                return (array)$array;
            else
                return array('');
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

            $enable_referer = apply_filters( "um_access_enable_referer", false );
            if( ! $enable_referer ) return $url;

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
            if ( current_user_can( 'administrator' ) )
                return false;

            //exlude from privacy UM default pages (except Members list and User(Profile) page)
            if ( um_is_core_post( $post, 'login' ) || um_is_core_post( $post, 'register' ) ||
                 um_is_core_post( $post, 'account' ) || um_is_core_post( $post, 'logout' ) ||
                 um_is_core_post( $post, 'password-reset' ) )
                return false;

            $restricted_posts = um_get_option( 'restricted_access_post_metabox' );

            if ( ! empty( $restricted_posts[$post->post_type] ) ) {
                $restriction = get_post_meta( $post->ID, 'um_content_restriction', true );

                if ( ! empty( $restriction['_um_custom_access_settings'] ) ) {
                    if ( ! isset( $restriction['_um_accessible'] ) || '0' == $restriction['_um_accessible'] )
                        return false;
                    else
                        return $restriction;
                }
            }

            //post hasn't privacy settings....check all terms of this post
            $restricted_taxonomies = um_get_option( 'restricted_access_taxonomy_metabox' );

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
         * @param $query
         * @return array
         */
        function filter_protected_posts( $posts, $query ) {
            $filtered_posts = array();

            //if empty
            if ( empty( $posts ) )
                return $posts;

            $restricted_global_message = um_get_option( 'restricted_access_message' );

            //other filter
            foreach ( $posts as $post ) {

                $restriction = $this->get_post_privacy_settings( $post );
                if ( ! $restriction ) {
                    $filtered_posts[] = $post;
                    continue;
                }

                //post is private
                if ( '1' == $restriction['_um_accessible'] ) {
                    //if post for not logged in users and user is not logged in
                    if ( ! is_user_logged_in() ) {
                        $filtered_posts[] = $post;
                        continue;
                    } else {

                        if ( current_user_can( 'administrator' ) ) {
                            $filtered_posts[] = $post;
                            continue;
                        }

                        if ( ! $query->is_singular ) {
                            //if not single query when exclude if set _um_access_hide_from_queries
                            if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {

                                if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

                                    if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
                                        $post->post_content = $restricted_global_message;
                                    } elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
                                        $post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? $restriction['_um_restrict_custom_message'] : '';
                                    }

                                }

                                $filtered_posts[] = $post;
                                continue;
                            }
                        } else {
                            //if single post query
                            if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

                                if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
                                    $post->post_content = $restricted_global_message;
                                } elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
                                    $post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? $restriction['_um_restrict_custom_message'] : '';
                                }

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

                        $user_can = $this->user_can( get_current_user_id(), $restriction['_um_access_roles'] );

                        if ( $user_can ) {
                            $filtered_posts[] = $post;
                            continue;
                        }

                        if ( ! $query->is_singular ) {
                            //if not single query when exclude if set _um_access_hide_from_queries
                            if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {

                                if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

                                    if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
                                        $post->post_content = $restricted_global_message;
                                    } elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
                                        $post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? $restriction['_um_restrict_custom_message'] : '';
                                    }

                                }

                                $filtered_posts[] = $post;
                                continue;
                            }
                        } else {
                            //if single post query
                            if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

                                if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
                                    $post->post_content = $restricted_global_message;
                                } elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
                                    $post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? $restriction['_um_restrict_custom_message'] : '';
                                }

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
                        if ( ! $query->is_singular ) {
                            if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {

                                if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

                                    if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
                                        $post->post_content = $restricted_global_message;
                                    } elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
                                        $post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? $restriction['_um_restrict_custom_message'] : '';
                                    }

                                }

                                $filtered_posts[] = $post;
                                continue;
                            }
                        } else {
                            //if single post query
                            if ( ! isset( $restriction['_um_noaccess_action'] ) || '0' == $restriction['_um_noaccess_action'] ) {

                                if ( ! isset( $restriction['_um_restrict_by_custom_message'] ) || '0' == $restriction['_um_restrict_by_custom_message'] ) {
                                    $post->post_content = $restricted_global_message;
                                } elseif ( '1' == $restriction['_um_restrict_by_custom_message'] ) {
                                    $post->post_content = ! empty( $restriction['_um_restrict_custom_message'] ) ? $restriction['_um_restrict_custom_message'] : '';
                                }

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
                            //if not single query when exclude if set _um_access_hide_from_queries
                            if ( empty( $restriction['_um_access_hide_from_queries'] ) ) {
                                $filtered_items[] = $menu_item;
                                continue;
                            }
                        }
                    } elseif ( '2' == $restriction['_um_accessible'] ) {
                        //if post for logged in users and user is not logged in
                        if ( is_user_logged_in() ) {

                            $user_can = $this->user_can( get_current_user_id(), $restriction['_um_access_roles'] );

                            if ( $user_can ) {
                                $filtered_items[] = $menu_item;
                                continue;
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