<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'Roles_Capabilities' ) ) {
    class Roles_Capabilities {

        function __construct() {

            add_action( 'wp_roles_init', array( &$this, 'um_roles_init' ), 99999 );

        }


        /**
         * Loop through dynamic roles and add them to the $wp_roles array
         *
         * @param null|object $wp_roles
         * @return null
         */
        function um_roles_init( $wp_roles = null ) {

            //Add UM role data to WP Roles
            foreach ( $wp_roles->roles as $roleID => $role_data ) {
                $role_meta = get_option( "um_role_{$roleID}_meta" );

                if ( ! empty( $role_meta ) )
                    $wp_roles->roles[$roleID] = array_merge( $role_data, $role_meta );
            }


            //Add custom UM roles
            $roles = array();

            $role_keys = get_option( 'um_roles' );

            if ( $role_keys ) {

                foreach ( $role_keys as $role_key ) {
                    $role_meta = get_option( "um_role_{$role_key}_meta" );
                    if ( $role_meta ) {
                        $role_meta['name'] = 'UM ' . $role_meta['name'];
                        $roles['um_' . $role_key] = $role_meta;
                    }
                }

                foreach ( $roles as $role_id => $details ) {
                    $capabilities = ! empty( $details['wp_capabilities'] ) ? array_keys( $details['wp_capabilities'] ) : array();
                    $details['capabilities'] = array_fill_keys( array_values( $capabilities ), true );
                    unset( $details['wp_capabilities'] );
                    $wp_roles->roles[$role_id]        = $details;
                    $wp_roles->role_objects[$role_id] = new \WP_Role( $role_id, $capabilities );
                    $wp_roles->role_names[$role_id]   = $details['name'];
                }

            }

            // Return the modified $wp_roles array
            return $wp_roles;
        }


        /**
         * Return a user's main role
         *
         * @param int $user_id
         * @param string $new_role
         * @uses get_userdata() To get the user data
         * @uses apply_filters() Calls 'um_set_user_role' with the role and user id
         * @return string
         */
        function set_um_user_role( $user_id = 0, $new_role = '' ) {
            // Validate user id
            $user = get_userdata( $user_id );

            // User exists
            if ( ! empty( $user ) ) {

                // Get users old UM role
                $role = $this->um_get_user_role( $user_id );

                // User already has this role so no new role is set
                if ( $new_role === $role ) {
                    $new_role = false;
                } else {
                    // Users role is different than the new role

                    // Remove the old UM role
                    if ( ! empty( $role ) )
                        $user->remove_role( $role );

                    // Add the new role
                    if ( ! empty( $new_role ) ) {
                        $user->add_role( $new_role );
                    }

                    do_action( 'um_when_role_is_set', um_user('ID') );

                    do_action('um_before_user_role_is_changed');

                    UM()->user()->profile['role'] = $new_role;

                    do_action('um_member_role_upgrade', $role,  UM()->user()->profile['role'] );

                    UM()->user()->update_usermeta_info('role');

                    do_action('um_after_user_role_is_changed');

                    do_action('um_after_user_role_is_updated', um_user('ID'), $role );
                }
            } else {
                // User does don exist so return false
                $new_role = false;
            }

            return apply_filters( 'um_set_user_role', $new_role, $user_id, $user );
        }


        /**
         * Get user one of UM roles if it has it
         *
         * @param int $user_id
         * @return bool|mixed
         */
        function um_get_user_role( $user_id = 0 ) {
            $user    = get_userdata( $user_id );
            $role    = false;

            // User has roles so look for a UM Role one
            if ( ! empty( $user->roles ) ) {
                $role_keys = get_option( 'um_roles' );

                if ( empty( $role_keys ) )
                    return array_shift( $user->roles );

                $role_keys = array_map( function( $item ) {
                    return 'um_' . $item;
                }, $role_keys );

                $roles = array_intersect( array_values( $user->roles ), $role_keys );
                if ( ! empty( $roles ) ) {
                    $role = array_shift( $roles );
                } else {
                    return array_shift( $user->roles );
                }
            }

            return $role;
        }


        /**
         * Get role name by roleID
         *
         * @param $slug
         * @return bool|string
         */
        function get_role_name( $slug ) {
            $roledata = $this->role_data( $slug );

            if ( empty( $roledata['name'] ) ) {
                global $wp_roles;

                if ( empty( $wp_roles->roles[$slug] ) )
                    return false;
                else
                    return $wp_roles->roles[$slug]['name'];
            }


            return $roledata['name'];
        }


        /**
         * Get role data
         *
         * @param int $roleID Role ID
         * @return mixed|void
         */
        function role_data( $roleID ) {
            if ( strpos( $roleID, 'um_' ) === 0 )
                $roleID = substr( $roleID, 3 );

            $role_data = get_option( "um_role_{$roleID}_meta" );

            if ( ! $role_data )
                return array();

            $temp = array();
            foreach ( $role_data as $key=>$value ) {
                if ( strpos( $key, '_um_' ) === 0 )
                    $key = str_replace( '_um_', '', $key );
                $temp[$key] = $value;
            }
            return $temp;
        }


        /***
         ***	@Query for UM roles
         ***/
        function get_roles( $add_default = false, $exclude = null ){
            global $wp_roles;

            if ( empty( $wp_roles ) ) {
                return array();
            }

            $roles = $wp_roles->role_names;

            if ( $add_default ) {
                $roles[0] = $add_default;
            }

            if ( $exclude ) {
                foreach( $exclude as $role ) {
                    unset( $roles[$role] );
                }
            }

            return $roles;
        }


        /***
         ***	@Current user can
         ***/
        function um_current_user_can( $cap, $user_id ) {
            if ( ! is_user_logged_in() )
                return false;

            $return = 1;

            um_fetch_user( get_current_user_id() );

            switch( $cap ) {
                case 'edit':
                    if ( get_current_user_id() == $user_id && um_user( 'can_edit_profile' ) )
                        $return = 1;
                    elseif ( ! um_user( 'can_edit_everyone' ) )
                        $return = 0;
                    elseif ( get_current_user_id() == $user_id && ! um_user( 'can_edit_profile') )
                        $return = 0;
                    elseif ( um_user( 'can_edit_roles' ) && ! in_array( UM()->roles()->um_get_user_role( $user_id ), um_user( 'can_edit_roles' ) ) )
                        $return = 0;
                    break;

                case 'delete':
                    if ( ! um_user( 'can_delete_everyone' ) ) $return = 0;
                    elseif ( um_user( 'can_delete_roles' ) && ! in_array( UM()->roles()->um_get_user_role( $user_id ), um_user( 'can_delete_roles' ) ) ) $return = 0;
                    break;

            }

            um_fetch_user( $user_id );

            return $return;
        }


        /***
         ***	@User can (role settings )
         ***/
        function um_user_can( $permission ) {
            if ( ! is_user_logged_in() )
                return false;

            $user_id = get_current_user_id();
            $role = get_user_meta( $user_id, 'role', true );
            $permissions = $this->role_data( $role );

            $permissions = apply_filters( 'um_user_permissions_filter', $permissions, $user_id );
            if ( isset( $permissions[ $permission ] ) && is_serialized( $permissions[ $permission ] ) )
                return unserialize( $permissions[ $permission ] );
            if ( isset( $permissions[ $permission ] ) && $permissions[ $permission ] == 1 )
                return true;
            return false;
        }

/*
        /**
         * @param $cap
         * @param bool $value
         * @param string $struct
         * @return bool
         *
        function current_user_can( $cap, $value = true, $struct = 'string' ) {
            if ( ! is_user_logged_in() )
                return false;

            $user_id = get_current_user_id();
            return $this->user_can( $user_id, $cap, $value, $struct );
        }


        /**
         * @param $user_id
         * @param $cap
         * @param bool $value
         * @param string $struct
         * @return bool
         *
        function user_can( $user_id, $cap, $value = true, $struct = 'string' ) {


            $role = $this->um_get_user_role( $user_id );
            $role_meta = $this->role_data( $role );
            $role_meta = apply_filters( 'um_user_permissions_filter', $role_meta, $user_id );

            /*            $um_roles = get_option( 'um_roles' );
                        $um_roles = array_map( function( $item ) {
                            return 'um_' . $item;
                        }, $um_roles );

                        $user_meta = get_userdata( $user_id );
                        $user_roles = $user_meta->roles;

                        $user_um_role = array_intersect( $um_roles, $user_roles );
                        if ( ! count( $user_um_role ) )
                            return false;

                        $user_um_role = $user_um_role[0];
                        $role_meta = get_option( "um_role_" . substr( $user_um_role, 3 ) . "_meta" );*

            if ( empty( $role_meta[$cap] ) )
                return false;

            if ( $struct == 'array' ) {
                if ( is_array( $role_meta[$cap] ) && ! in_array( $value, $role_meta[$cap] ) )
                    return false;
            } else {
                if ( $role_meta[$cap] != $value )
                    return false;
            }

            return true;
        }*/
    }
}