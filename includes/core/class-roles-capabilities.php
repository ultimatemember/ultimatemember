<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Roles_Capabilities' ) ) {


	/**
	 * Class Roles_Capabilities
	 * @package um\core
	 */
	class Roles_Capabilities {


		/**
		 * Roles_Capabilities constructor.
		 */
		function __construct() {
			add_action( 'wp_roles_init', array( &$this, 'um_roles_init' ), 99999 );
			add_action( 'update_option', array( &$this, 'um_on_roles_update' ), 10, 3 );
		}


		/**
		 * @param string $option
		 * @param mixed $old_value
		 * @param mixed $value
		 */
		function um_on_roles_update( $option, $old_value, $value ) {
			global $wp_roles;

			if ( isset( $wp_roles->role_key ) && $option == $wp_roles->role_key ) {
				foreach ( $value as $role_key => $role_data ) {
					$role_keys = get_option( 'um_roles', array() );
					$role_keys = array_map( function( $item ) {
						return 'um_' . $item;
					}, $role_keys );

					if ( ! empty( $role_keys ) && in_array( $role_key, $role_keys ) ) {
						$role_meta = get_option( 'um_role_' . substr( $role_key, 3 ) . '_meta' );

						if ( ! isset( $role_meta['wp_capabilities'] ) ) {
							$role_meta['wp_capabilities'] = array();
						}

						if ( ! empty( $role_data['capabilities'] ) && is_array( $role_data['capabilities'] ) ) {
							$old_role_caps = ! empty( $old_value[ $role_key ]['capabilities'] ) ? array_keys( $old_value[ $role_key ]['capabilities'] ) : array();

							if ( ! empty( $old_role_caps ) ) {
								$unset_caps = array_diff( $old_role_caps, array_keys( $role_data['capabilities'] ) );

								if ( ! empty( $unset_caps ) ) {
									foreach ( $unset_caps as $cap ) {
										if ( ! empty( $role_meta['wp_capabilities'][ $cap ] ) ) {
											unset( $role_meta['wp_capabilities'][ $cap ] );
										}
									}
								}
							}

							foreach ( $role_data['capabilities'] as $cap => $grant ) {
								if ( $grant ) {
									$role_meta['wp_capabilities'][ $cap ] = true;
								}
							}
						}

						update_option( 'um_role_' . substr( $role_key, 3 ) . '_meta', $role_meta );
					}
				}
			}
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

				if ( ! empty( $role_meta ) ) {
					$wp_roles->roles[ $roleID ] = array_merge( $role_data, $role_meta );
				}
			}


			//Add custom UM roles
			$roles = array();

			$role_keys = get_option( 'um_roles', array() );
			foreach ( $role_keys as $role_key ) {
				$role_meta = get_option( "um_role_{$role_key}_meta" );
				if ( $role_meta ) {
					$roles[ 'um_' . $role_key ] = $role_meta;
				}
			}

			foreach ( $roles as $role_id => $details ) {
				$capabilities = ! empty( $details['wp_capabilities'] ) ? array_keys( $details['wp_capabilities'] ) : array();
				$details['capabilities'] = array_fill_keys( array_values( $capabilities ), true );
				unset( $details['wp_capabilities'] );
				$wp_roles->roles[ $role_id ]        = $details;
				$wp_roles->role_objects[ $role_id ] = new \WP_Role( $role_id, $details['capabilities'] );
				$wp_roles->role_names[ $role_id ]   = $details['name'];
			}

			// Return the modified $wp_roles array
			return $wp_roles;
		}


		/**
		 * Check if role is custom
		 *
		 * @param $role
		 * @return bool
		 */
		function is_role_custom( $role ) {
			// User has roles so look for a UM Role one
			$role_keys = get_option( 'um_roles' );

			if ( empty( $role_keys ) )
				return false;

			$role_keys = array_map( function( $item ) {
				return 'um_' . $item;
			}, $role_keys );

			return in_array( $role, $role_keys );
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
		function set_role( $user_id, $new_role = '' ) {
			// Validate user id
			$user = get_userdata( $user_id );

			// User exists
			if ( ! empty( $user ) ) {
				// Get users old UM role
				$role = UM()->roles()->get_um_user_role( $user_id );

				// User already has this role so no new role is set
				if ( $new_role === $role || ( ! $this->is_role_custom( $new_role ) && user_can( $user, $new_role ) ) ) {
					$new_role = false;
				} else {
					// Users role is different than the new role

					// Remove the old UM role
					if ( ! empty( $role ) && $this->is_role_custom( $role ) ) {
						$user->remove_role( $role );
					}

					// Add the new role
					if ( ! empty( $new_role ) ) {
						$user->add_role( $new_role );
					}

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_when_role_is_set
					 * @description Action before user role changed
					 * @input_vars
					 * [{"var":"$user_id","type":"int","desc":"User ID"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_when_role_is_set', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_when_role_is_set', 'my_when_role_is_set', 10, 1 );
					 * function my_when_role_is_set( $user_id ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_when_role_is_set', $user_id );
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_before_user_role_is_changed
					 * @description Action before user role changed
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_before_user_role_is_changed', 'function_name', 10 );
					 * @example
					 * <?php
					 * add_action( 'um_before_user_role_is_changed', 'my_before_user_role_is_changed', 10 );
					 * function my_before_user_role_is_changed() {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_before_user_role_is_changed' );

					UM()->user()->profile['role'] = $new_role;

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_member_role_upgrade
					 * @description Action on user role changed
					 * @input_vars
					 * [{"var":"$user_id","type":"int","desc":"User ID"},
					 * {"var":"$role","type":"string","desc":"User role"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_member_role_upgrade', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_action( 'um_member_role_upgrade', 'my_member_role_upgrade', 10, 2 );
					 * function my_member_role_upgrade( $old_role, $new_role ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_member_role_upgrade', $role, UM()->user()->profile['role'] );

					UM()->user()->update_usermeta_info( 'role' );
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_after_user_role_is_changed
					 * @description Action after user role changed
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_after_user_role_is_changed', 'function_name', 10 );
					 * @example
					 * <?php
					 * add_action( 'um_after_user_role_is_changed', 'my_after_user_role_is_changed', 10 );
					 * function my_after_user_role_is_changed() {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_after_user_role_is_changed' );
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_after_user_role_is_updated
					 * @description Action after user role changed
					 * @input_vars
					 * [{"var":"$user_id","type":"int","desc":"User ID"},
					 * {"var":"$role","type":"string","desc":"User role"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_after_user_role_is_updated', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_action( 'um_after_user_role_is_updated', 'my_after_user_role_is_updated', 10, 2 );
					 * function my_after_user_role_is_updated( $user_id, $role ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_after_user_role_is_updated', $user_id, $role );
				}
			} else {
				// User does don exist so return false
				$new_role = false;
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_set_user_role
			 * @description User role was changed
			 * @input_vars
			 * [{"var":"$new_role","type":"string","desc":"New role"},
			 * {"var":"$user_id","type":"int","desc":"User ID"},
			 * {"var":"$user","type":"array","desc":"Userdata"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_set_user_role', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_set_user_role', 'my_set_user_role', 10, 1 );
			 * function my_set_user_role( $new_role ) {
			 *     // your code here
			 *     return $new_role;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_set_user_role', $new_role, $user_id, $user );
		}


		/**
		 * Remove user role
		 *
		 * @param $user_id
		 * @param $role
		 */
		function remove_role( $user_id, $role ) {
			// Validate user id
			$user = get_userdata( $user_id );

			// User exists
			if ( ! empty( $user ) ) {
				// Remove role
				$user->remove_role( $role );
			}
		}


		/**
		 * Remove user role
		 *
		 * @param $user_id
		 * @param $role
		 */
		function set_role_wp( $user_id, $role ) {
			// Validate user id
			$user = get_userdata( $user_id );

			// User exists
			if ( ! empty( $user ) ) {
				// Remove role
				$user->add_role( $role );
			}
		}


		/**
		 * Set roles to user (remove all previous roles)
		 * make user only with $roles roles
		 *
		 * @param int $user_id
		 * @param string|array $roles
		 */
		function set_roles( $user_id, $roles ) {

		}


		/**
		 * Get user one of UM roles if it has it
		 *
		 * @deprecated since 2.0
		 * @param int $user_id
		 * @return bool|mixed
		 */
		function um_get_user_role( $user_id ) {
			return $this->get_um_user_role( $user_id );
		}


		/**
		 * @param $user_id
		 *
		 * @return array|bool
		 */
		function get_all_user_roles( $user_id ) {
			$user = get_userdata( $user_id );

			if ( empty( $user->roles ) ) {
				return false;
			}

			return array_values( $user->roles );
		}


		/**
		 * @param $user_id
		 *
		 * @return bool|mixed
		 */
		function get_priority_user_role( $user_id ) {
			$user = get_userdata( $user_id );

			if ( empty( $user->roles ) )
				return false;

			// User has roles so look for a UM Role one
			$um_roles_keys = get_option( 'um_roles' );

			if ( ! empty( $um_roles_keys ) ) {
				$um_roles_keys = array_map( function( $item ) {
					return 'um_' . $item;
				}, $um_roles_keys );
			}

			$orders = array();
			foreach ( array_values( $user->roles ) as $userrole ) {
				if ( ! empty( $um_roles_keys ) && in_array( $userrole, $um_roles_keys ) ) {
					$userrole_metakey = substr( $userrole, 3 );
				} else {
					$userrole_metakey = $userrole;
				}

				$rolemeta = get_option( "um_role_{$userrole_metakey}_meta", false );

				if ( ! $rolemeta ) {
					$orders[ $userrole ] = 0;
					continue;
				}

				$orders[ $userrole ] = ! empty( $rolemeta['_um_priority'] ) ? $rolemeta['_um_priority'] : 0;
			}

			arsort( $orders );
			$roles_in_priority = array_keys( $orders );

			return array_shift( $roles_in_priority );
		}


		/**
		 * Get editable UM user roles
		 *
		 * @return array
		 */
		function get_editable_user_roles() {
			$default_roles = array( 'subscriber' );

			// User has roles so look for a UM Role one
			$um_roles_keys = get_option( 'um_roles', array() );

			if ( ! empty( $um_roles_keys ) && is_array( $um_roles_keys ) ) {
				$um_roles_keys = array_map( function( $item ) {
					return 'um_' . $item;
				}, $um_roles_keys );

				return array_merge( $um_roles_keys, $default_roles );
			}

			return $default_roles;
		}


		/**
		 * @param $user_id
		 *
		 * @return bool|mixed
		 */
		function get_editable_priority_user_role( $user_id ) {
			$user = get_userdata( $user_id );

			if ( empty( $user->roles ) )
				return false;

			// User has roles so look for a UM Role one
			$um_roles_keys = get_option( 'um_roles' );

			if ( ! empty( $um_roles_keys ) ) {
				$um_roles_keys = array_map( function( $item ) {
					return 'um_' . $item;
				}, $um_roles_keys );

			}

			$orders = array();
			foreach ( array_values( $user->roles ) as $userrole ) {
				if ( ! empty( $um_roles_keys ) && in_array( $userrole, $um_roles_keys ) ) {
					$userrole_metakey = substr( $userrole, 3 );
				} else {
					$userrole_metakey = $userrole;
				}

				$rolemeta = get_option( "um_role_{$userrole_metakey}_meta", false );

				if ( ! $rolemeta ) {
					$orders[ $userrole ] = 0;
					continue;
				}

				$orders[ $userrole ] = ! empty( $rolemeta['_um_priority'] ) ? $rolemeta['_um_priority'] : 0;
			}

			arsort( $orders );
			$roles_in_priority = array_keys( $orders );
			$roles_in_priority = array_intersect( $roles_in_priority, $this->get_editable_user_roles() );

			return array_shift( $roles_in_priority );
		}


		/**
		 * @param $user_id
		 *
		 * @return bool|mixed
		 */
		function get_um_user_role( $user_id ) {
			// User has roles so look for a UM Role one
			$um_roles_keys = get_option( 'um_roles' );

			if ( empty( $um_roles_keys ) )
				return false;

			$user = get_userdata( $user_id );

			if ( empty( $user->roles ) )
				return false;

			$um_roles_keys = array_map( function( $item ) {
				return 'um_' . $item;
			}, $um_roles_keys );

			$user_um_roles_array = array_intersect( $um_roles_keys, array_values( $user->roles ) );

			if ( empty( $user_um_roles_array ) )
				return false;

			return array_shift( $user_um_roles_array );
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
		 * @return array
		 */
		function role_data( $roleID ) {
			if ( strpos( $roleID, 'um_' ) === 0 ) {
				$roleID = substr( $roleID, 3 );
				$role_data = get_option( "um_role_{$roleID}_meta", array() );
			}

			if ( empty( $role_data ) ) {
				$role_data = get_option( "um_role_{$roleID}_meta", array() );
			}

			if ( ! $role_data ) {
				return array();
			}

			$temp = array();
			foreach ( $role_data as $key=>$value ) {
				if ( strpos( $key, '_um_' ) === 0 ) {
					$key = preg_replace('/_um_/', '', $key, 1);
				}

				//$key = str_replace( '_um_', '', $key, $count );
				$temp[ $key ] = $value;
			}

			$temp = apply_filters( 'um_change_role_data', $temp, $roleID );

			return $temp;
		}


		/**
		 * Query for UM roles
		 *
		 * @param bool $add_default
		 * @param null $exclude
		 *
		 * @return array
		 */
		function get_roles( $add_default = false, $exclude = null ) {
			global $wp_roles;

			if ( empty( $wp_roles ) ) {
				return array();
			}

			$roles = $wp_roles->role_names;

			if ( $add_default ) {
				$roles[0] = $add_default;
			}

			if ( $exclude ) {
				foreach ( $exclude as $role ) {
					unset ( $roles[ $role ] );
				}
			}

			$roles = array_map( 'stripslashes', $roles );

			return $roles;
		}


		/**
		 * Current user can
		 *
		 * @param $cap
		 * @param $user_id
		 *
		 * @return bool|int
		 */
		function um_current_user_can( $cap, $user_id ) {
			if ( ! is_user_logged_in() ) {
				return false;
			}

			$return = 1;

			um_fetch_user( get_current_user_id() );

			$current_user_roles = $this->get_all_user_roles( $user_id );

			switch( $cap ) {
				case 'edit':

					if ( get_current_user_id() == $user_id ) {
						if ( um_user( 'can_edit_profile' ) ) {
							$return = 1;
						} else {
							$return = 0;
						}
					} else {
						if ( ! um_user( 'can_edit_everyone' ) ) {
							$return = 0;
						} else {
							if ( um_user( 'can_edit_roles' ) && ( empty( $current_user_roles ) || count( array_intersect( $current_user_roles, um_user( 'can_edit_roles' ) ) ) <= 0 ) ) {
								$return = 0;
							} else {
								$return = 1;
							}
						}
					}

					break;

				case 'delete':
					if ( ! um_user( 'can_delete_everyone' ) )
						$return = 0;
					elseif ( um_user( 'can_delete_roles' ) && ( empty( $current_user_roles ) || count( array_intersect( $current_user_roles, um_user( 'can_delete_roles' ) ) ) <= 0 ) )
						$return = 0;
					break;

			}

			um_fetch_user( $user_id );

			return $return;
		}


		/**
		 * User can ( role settings )
		 *
		 * @param $permission
		 * @return bool|mixed
		 */
		function um_user_can( $permission ) {
			if ( ! is_user_logged_in() )
				return false;

			$user_id = get_current_user_id();
			$role = UM()->roles()->get_priority_user_role( $user_id );

			$permissions = $this->role_data( $role );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_user_permissions_filter
			 * @description Change User Permissions
			 * @input_vars
			 * [{"var":"$permissions","type":"array","desc":"User Permissions"},
			 * {"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_user_permissions_filter', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_user_permissions_filter', 'my_user_permissions', 10, 2 );
			 * function my_user_permissions( $permissions, $user_id ) {
			 *     // your code here
			 *     return $permissions;
			 * }
			 * ?>
			 */
			$permissions = apply_filters( 'um_user_permissions_filter', $permissions, $user_id );

			if ( isset( $permissions[ $permission ] ) && is_serialized( $permissions[ $permission ] ) )
				return unserialize( $permissions[ $permission ] );

			if ( isset( $permissions[ $permission ] ) && is_array( $permissions[ $permission ] ) )
				return $permissions[ $permission ];

			if ( isset( $permissions[ $permission ] ) && $permissions[ $permission ] == 1 )
				return true;

			return false;
		}
	}
}