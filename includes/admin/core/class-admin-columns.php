<?php
namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Admin_Columns' ) ) {
    class Admin_Columns {

        function __construct() {

            $this->slug = 'ultimatemember';

            add_filter('manage_edit-um_form_columns', array(&$this, 'manage_edit_um_form_columns') );
            add_action('manage_um_form_posts_custom_column', array(&$this, 'manage_um_form_posts_custom_column'), 10, 3);

            add_filter('manage_edit-um_directory_columns', array(&$this, 'manage_edit_um_directory_columns') );
            add_action('manage_um_directory_posts_custom_column', array(&$this, 'manage_um_directory_posts_custom_column'), 10, 3);

            add_filter('post_row_actions', array(&$this, 'post_row_actions'), 99, 2);

        }

        /***
         ***	@custom row actions
         ***/
        function post_row_actions( $actions, $post ) {
            //check for your post type
            if ( $post->post_type == "um_form" ) {
                $actions['um_duplicate'] = '<a href="' . $this->duplicate_uri( $post->ID ) . '">' . __('Duplicate','ultimate-member') . '</a>';
            }
            return $actions;
        }

        /***
         ***	@duplicate a form
         ***/
        function duplicate_uri( $id ) {
            $url = add_query_arg('um_adm_action', 'duplicate_form', admin_url('edit.php?post_type=um_form') );
            $url = add_query_arg('post_id', $id, $url);
            return $url;
        }

        /***
         ***	@Custom columns for Form
         ***/
        function manage_edit_um_form_columns( $columns ) {

            $new_columns['cb'] = '<input type="checkbox" />';
            $new_columns['title'] = __( 'Title', 'ulitmatemember' );
            $new_columns['id'] = __('ID', 'ulitmatemember' );
            $new_columns['mode'] = __( 'Type', 'ulitmatemember' );
            $new_columns['shortcode'] = __( 'Shortcode', 'ulitmatemember' );
            $new_columns['date'] = __( 'Date', 'ulitmatemember' );

            return $new_columns;

        }

        /***
         ***	@Custom columns for Directory
         ***/
        function manage_edit_um_directory_columns( $columns ) {

            $new_columns['cb'] = '<input type="checkbox" />';

            $new_columns['title'] = __( 'Title', 'ultimate-member' );
            $new_columns['id'] = __( 'ID', 'ultimate-member' );
            $new_columns['shortcode'] = __( 'Shortcode', 'ultimate-member' );
            $new_columns['date'] = __( 'Date', 'ultimate-member' );

            return $new_columns;

        }

        /***
         ***	@Display cusom columns for Form
         ***/
        function manage_um_form_posts_custom_column( $column_name, $id ) {

            switch ( $column_name ) {

                case 'id':
                    echo '<span class="um-admin-number">'.$id.'</span>';
                    break;

                case 'shortcode':
                    echo UM()->shortcodes()->get_shortcode( $id );
                    break;

                case 'mode':
                    $mode = UM()->query()->get_attr( 'mode', $id );
                    echo UM()->form()->display_form_type( $mode, $id );
                    break;

            }

        }

        /***
         ***	@Display cusom columns for Directory
         ***/
        function manage_um_directory_posts_custom_column($column_name, $id) {
            global $wpdb;

            switch ($column_name) {

                case 'id':
                    echo '<span class="um-admin-number">'.$id.'</span>';
                    break;

                case 'shortcode':
                    echo UM()->shortcodes()->get_shortcode( $id );
                    break;

            }

        }

    }
}