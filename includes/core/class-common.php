<?php
namespace um\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Common' ) ) {

    class Common {
        /**
         * Common constructor.
         */
        function __construct() {
            add_action( 'init',  array( &$this, 'create_post_types' ), 1 );

            add_filter( 'posts_request', array( &$this, 'um_query_pages' ) );
        }


        /**
         * Create taxonomies for use for UM
         */
        function create_post_types() {

            register_post_type( 'um_form', array(
                'labels' => array(
                    'name' => __( 'Forms' ),
                    'singular_name' => __( 'Form' ),
                    'add_new' => __( 'Add New' ),
                    'add_new_item' => __('Add New Form' ),
                    'edit_item' => __('Edit Form'),
                    'not_found' => __('You did not create any forms yet'),
                    'not_found_in_trash' => __('Nothing found in Trash'),
                    'search_items' => __('Search Forms')
                ),
                'show_ui' => true,
                'show_in_menu' => false,
                'public' => false,
                'supports' => array('title')
            ) );

            if ( um_get_option( 'members_page' ) || ! get_option( 'um_options' ) ) {

                register_post_type( 'um_directory', array(
                    'labels' => array(
                        'name' => __( 'Member Directories' ),
                        'singular_name' => __( 'Member Directory' ),
                        'add_new' => __( 'Add New' ),
                        'add_new_item' => __('Add New Member Directory' ),
                        'edit_item' => __('Edit Member Directory'),
                        'not_found' => __('You did not create any member directories yet'),
                        'not_found_in_trash' => __('Nothing found in Trash'),
                        'search_items' => __('Search Member Directories')
                    ),
                    'show_ui' => true,
                    'show_in_menu' => false,
                    'public' => false,
                    'supports' => array('title')
                ) );

            }

        }


        /**
         * Check query string on 'posts_request' for our pages
         *
         * @param string $q
         *
         * @return string
         */
        public function um_query_pages( $q ) {
            global $wp_query;

            //We need main query
            if ( $q == $wp_query->request ) {

                if ( ! empty( $wp_query->query_vars['um_page'] ) ) {

                    if ( 'api' == $wp_query->query_vars['um_page'] ) {
                        $router = new Router();
                        $router->frontend_requests();
                    }
                }

            }

            return $q;
        }
    }

}