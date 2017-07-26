<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Profile' ) ) {
    class Profile {

        public $arr_user_slugs = array();
        public $arr_user_roles = array();

        var $active_tab;

        function __construct() {

            add_action('template_redirect', array(&$this, 'active_tab'), 10002);
            add_action('template_redirect', array(&$this, 'active_subnav'), 10002);

        }


        function ajax_delete_profile_photo() {
            extract( $_REQUEST );

            if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) )
                die( __( 'You can not edit this user' ) );

            UM()->files()->delete_core_user_photo( $user_id, 'profile_photo' );
        }


        function ajax_delete_cover_photo() {
            extract($_REQUEST);

            if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) )
                die( __( 'You can not edit this user' ) );

            UM()->files()->delete_core_user_photo( $user_id, 'cover_photo' );
        }


        /***
         ***	@all tab data
         ***/
        function tabs() {

            $tabs = apply_filters( 'um_profile_tabs', array(
                'main' => array(
                    'name' => __( 'About', 'ultimate-member' ),
                    'icon' => 'um-faicon-user'
                ),
                'posts' => array(
                    'name' => __( 'Posts', 'ultimate-member' ),
                    'icon' => 'um-faicon-pencil'
                ),
                'comments' => array(
                    'name' => __( 'Comments', 'ultimate-member' ),
                    'icon' => 'um-faicon-comment'
                )
            ) );

            // disable private tabs
            if ( ! is_admin() ) {
                foreach ( $tabs as $id => $tab ) {
                    if ( ! $this->can_view_tab( $id ) ) {
                        unset( $tabs[$id] );
                    }
                }
            }

            return $tabs;
        }

        /***
         ***	@tabs that are active
         ***/
        function tabs_active(){
            $tabs = $this->tabs();
            foreach( $tabs as $id => $info ) {
                if ( !um_get_option('profile_tab_'.$id) && !isset( $info['_builtin'] ) && !isset( $info['custom'] ) )
                    unset( $tabs[$id] );
            }
            return $tabs;
        }

        /***
         ***	@primary tabs only
         ***/
        function tabs_primary(){
            $tabs = $this->tabs();
            $primary = array();
            foreach ( $tabs as $id => $info ) {
                if ( isset( $info['name'] ) ) {
                    $primary[$id] = $info['name'];
                }
            }
            return $primary;
        }

        /***
         ***	@Activated tabs in backend
         ***/
        function tabs_enabled(){
            $tabs = $this->tabs();
            foreach( $tabs as $id => $info ){
                if ( isset( $info['name'] ) ) {
                    if ( um_get_option('profile_tab_'.$id) || isset( $info['_builtin'] ) ) {
                        $primary[$id] = $info['name'];
                    }
                }
            }
            return ( isset( $primary ) ) ? $primary : '';
        }

        /***
         ***	@Privacy options
         ***/
        function tabs_privacy() {
            $privacy = array(
                0 => 'Anyone',
                1 => 'Guests only',
                2 => 'Members only',
                3 => 'Only the owner',
                4 => 'Specific roles'
            );

            return $privacy;
        }

        /***
         ***	@Check if the user can view the current tab
         ***/
        function can_view_tab( $tab ) {
            $privacy  = intval( um_get_option( 'profile_tab_' . $tab . '_privacy' ) );
            $can_view = false;

            switch( $privacy ) {
                case 1:
                    $can_view = is_user_logged_in() ? false : true;
                    break;

                case 2:
                    $can_view = is_user_logged_in() ? true : false;
                    break;

                case 3:
                    $can_view = get_current_user_id() == um_user( 'ID' ) ? true : false;
                    break;

                case 4:
                    $can_view = false;
                    if( is_user_logged_in() ) {
                        $roles = um_get_option( 'profile_tab_' . $tab . '_roles' );
                        if( is_array( $roles )
                            && in_array( UM()->user()->get_role(), $roles ) ) {
                            $can_view = true;
                        }
                    }
                    break;

                default:
                    $can_view = true;
                    break;
            }

            return $can_view;
        }

        /***
         ***	@Get active_tab
         ***/
        function active_tab() {

            $this->active_tab = um_get_option('profile_menu_default_tab');

            if ( get_query_var('profiletab') ) {
                $this->active_tab = get_query_var('profiletab');
            }

            $this->active_tab = apply_filters( 'um_profile_active_tab', $this->active_tab );

            return $this->active_tab;
        }

        /***
         ***	@Get active active_subnav
         ***/
        function active_subnav() {

            $this->active_subnav = null;

            if ( get_query_var('subnav') ) {
                $this->active_subnav = get_query_var('subnav');
            }

            return $this->active_subnav;
        }

        /***
         ***	@Show meta in profile
         ***/
        function show_meta( $array ) {
            $output = '';

            if( isset( $array ) ){
                foreach( $array as $key ) {
                    $data = '';
                    if ( $key && um_filtered_value( $key ) ) {

                        if ( isset( UM()->builtin()->all_user_fields[ $key ] ) ){
                            $data = UM()->builtin()->all_user_fields[ $key ];
                        }

                        if ( isset( $data['icon'] ) ) {
                            $icon = $data['icon'];
                        } else {
                            $icon = '';
                        }

                        $data['in_profile_meta'] = true;

                        $icon = ( isset( $icon ) && !empty( $icon ) ) ? '<i class="'.$icon.'"></i>' : '';

                        if ( !um_get_option('profile_show_metaicon') ){
                            $icon = '';
                        }

                        $value = um_filtered_value( $key, $data );

                        $items[] = '<span>' . $icon . $value . '</span>';
                        $items[] = '<span class="b">&bull;</span>';

                    }
                }
            }
            if ( isset( $items ) ) {
                array_pop($items);
                foreach( $items as $item ) {
                    $output .= $item;
                }
            }

            return $output;
        }

    }
}