<?php namespace um\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\common\User' ) ) {


	/**
	 * Class User
	 *
	 * @package um\common
	 */
	class User {

		/**
		 * @var null|string|\WP_Error
		 */
		private $password_reset_key = null;

		/**
		 * Store User ID while delete
		 *
		 * @var null|int
		 */
		private $deleted_user_id = null;

		/**
		 * User constructor.
		 *
		 * @since 3.0
		 */
		function __construct() {
		}

		/**
		 *
		 */
		public function hooks() {
			add_filter( 'user_has_cap', array( &$this, 'map_caps_by_role' ), 10, 3 );
			add_action( 'lostpassword_post', array( &$this, 'reset_password_attempts' ), 10, 2 );

			if ( is_multisite() ) {
				add_action( 'wpmu_delete_user', array( &$this, 'delete_user_handler' ), 10, 1 );
			} else {
				add_action( 'delete_user', array( &$this, 'delete_user_handler' ), 10, 1 );
			}

			add_filter( 'get_avatar', array( &$this, 'get_avatar_cb' ), 99999, 5 );
			add_filter( 'avatar_defaults', array( $this, 'avatar_defaults' ) );
		}

		/**
		 * Get user UM avatars
		 *
		 * @since 3.0.0
		 *
		 * @param string $avatar
		 * @param string $id_or_email
		 * @param int    $size
		 * @param string $default
		 * @param string $alt
		 * @param array  $args {
		 *     Optional. Extra arguments to retrieve the avatar.
		 *
		 *     @type int          $height        Display height of the avatar in pixels. Defaults to $size.
		 *     @type int          $width         Display width of the avatar in pixels. Defaults to $size.
		 *     @type bool         $force_default Whether to always show the default image, never the Gravatar. Default false.
		 *     @type string       $rating        What rating to display avatars up to. Accepts 'G', 'PG', 'R', 'X', and are
		 *                                       judged in that order. Default is the value of the 'avatar_rating' option.
		 *     @type string       $scheme        URL scheme to use. See set_url_scheme() for accepted values.
		 *                                       Default null.
		 *     @type array|string $class         Array or string of additional classes to add to the img element.
		 *                                       Default null.
		 *     @type bool         $force_display Whether to always show the avatar - ignores the show_avatars option.
		 *                                       Default false.
		 *     @type string       $loading       Value for the `loading` attribute.
		 *                                       Default null.
		 *     @type string       $extra_attr    HTML attributes to insert in the IMG element. Is not sanitized. Default empty.
		 * }
		 *
		 * @return string Avatar in image html elements
		 */
		public function get_avatar_cb( $avatar, $id_or_email = '', $size = 96, $default = '', $alt = '', $args = null ) {
			$defaults = array(
				// get_avatar_data() args.
				'size'          => 96,
				'height'        => null,
				'width'         => null,
				'default'       => get_option( 'avatar_default', 'mystery' ),
				'force_default' => false,
				'rating'        => get_option( 'avatar_rating' ),
				'scheme'        => null,
				'alt'           => '',
				'class'         => null,
				'force_display' => false,
				'loading'       => null,
				'extra_attr'    => '',
				'decoding'      => 'async',
			);

			if ( wp_lazy_loading_enabled( 'img', 'get_avatar' ) ) {
				$defaults['loading'] = wp_get_loading_attr_default( 'get_avatar' );
			}

			if ( empty( $args ) ) {
				$args = array();
			}

			$args['size']    = (int) $size;
			$args['default'] = $default;
			$args['alt']     = $alt;

			$args = wp_parse_args( $args, $defaults );

			if ( empty( $args['height'] ) ) {
				$args['height'] = $args['size'];
			}
			if ( empty( $args['width'] ) ) {
				$args['width'] = $args['size'];
			}

			$args = get_avatar_data( $id_or_email, $args );

			$class = array( 'avatar', 'avatar-' . (int) $args['size'], 'photo' );

			if ( ! $args['found_avatar'] || $args['force_default'] ) {
				$class[] = 'avatar-default';
			}

			if ( $args['class'] ) {
				if ( is_array( $args['class'] ) ) {
					$class = array_merge( $class, $args['class'] );
				} else {
					$class[] = $args['class'];
				}
			}

			// Add `loading` attribute.
			$extra_attr = $args['extra_attr'];
			$loading    = $args['loading'];

			if ( in_array( $loading, array( 'lazy', 'eager' ), true ) && ! preg_match( '/\bloading\s*=/', $extra_attr ) ) {
				if ( ! empty( $extra_attr ) ) {
					$extra_attr .= ' ';
				}

				$extra_attr .= "loading='{$loading}'";
			}

			if ( in_array( $args['decoding'], array( 'async', 'sync', 'auto' ) ) && ! preg_match( '/\bdecoding\s*=/', $extra_attr ) ) {
				if ( ! empty( $extra_attr ) ) {
					$extra_attr .= ' ';
				}
				$extra_attr .= "decoding='{$args['decoding']}'";
			}

			$um_avatar_url = $this->get_avatar_url( $id_or_email );

			if ( UM()->options()->get( 'use_um_gravatar_default_image' ) && empty( $um_avatar_url ) ) {
				$avatar = sprintf( '<img src="%s" class="%s" alt="%s" height="%d" width="%d" %s />',
					esc_attr( um_get_default_avatar_url() ),
					esc_attr( implode( ' ', $class ) ),
					esc_attr( $args['alt'] ),
					(int) $args['height'],
					(int) $args['width'],
					$extra_attr
				);

				return $avatar;
			}

			if ( ! empty( $um_avatar_url ) ) {
				$avatar = sprintf( '<img src="%s" class="%s" alt="%s" height="%d" width="%d" %s />',
					esc_attr( $um_avatar_url ),
					esc_attr( implode( ' ', $class ) ),
					esc_attr( $args['alt'] ),
					(int) $args['height'],
					(int) $args['width'],
					$extra_attr
				);
			}

			return $avatar;
		}

		/**
		 * Remove the custom get_avatar hook for the default avatar list output on
		 * the Discussion Settings page.
		 *
		 * @since 3.0.0
		 * @param array $avatar_defaults
		 * @return array
		 */
		public function avatar_defaults( $avatar_defaults ) {
			remove_action( 'get_avatar', array( $this, 'get_avatar_cb' ), 99999 );
			return $avatar_defaults;
		}

		/**
		 * @param $user_id
		 * @param $size
		 *
		 * @return mixed
		 */
		public function get_avatar( $user_id, $size ) {
			return get_avatar( $user_id, um_get_avatar_size( $size ) );
		}

		/**
		 * @param int $user_id
		 *
		 * @return bool
		 */
		public function id_exists( $user_id ) {
			$user = get_userdata( $user_id );
			return false === $user ? false : true;
		}

		/**
		 * Get avatar URL
		 *
		 * @param int $user_id
		 * @param $image
		 * @param null|array $attrs
		 *
		 * @since 3.0.0
		 *
		 * @return bool|string
		 */
		public function get_avatar_url( $user_id, $image = null, $attrs = null ) {
			if ( ! file_exists( wp_normalize_path( UM()->uploader()->get_upload_base_dir() . $user_id . '/profile_photo.jpg' ) ) ) {
				return false;
			}

			return set_url_scheme( UM()->uploader()->get_upload_base_url() . $user_id . '/profile_photo.jpg' );

			$uri = false;
			$uri_common = false;
			$find = false;
			$ext = '.' . pathinfo( $image, PATHINFO_EXTENSION );

			if ( is_multisite() ) {
				//multisite fix for old customers
				$multisite_fix_dir = UM()->uploader()->get_upload_base_dir();
				$multisite_fix_url = UM()->uploader()->get_upload_base_url();
				$multisite_fix_dir = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $multisite_fix_dir );
				$multisite_fix_url = str_replace( '/sites/' . get_current_blog_id() . '/', '/', $multisite_fix_url );

				if ( $attrs == 'original' && file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo{$ext}" ) ) {
					$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo{$ext}";
				} elseif ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$attrs}x{$attrs}{$ext}" ) ) {
					$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo-{$attrs}x{$attrs}{$ext}";
				} elseif ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$attrs}{$ext}" ) ) {
					$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo-{$attrs}{$ext}";
				} else {
					$sizes = UM()->options()->get( 'photo_thumb_sizes' );
					if ( is_array( $sizes ) ) {
						$find = um_closest_num( $sizes, $attrs );
					}

					if ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$find}x{$find}{$ext}" ) ) {
						$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo-{$find}x{$find}{$ext}";
					} elseif ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$find}{$ext}" ) ) {
						$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo-{$find}{$ext}";
					} elseif ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo{$ext}" ) ) {
						$uri_common = $multisite_fix_url . um_user( 'ID' ) . "/profile_photo{$ext}";
					}
				}
			}

			if ( $attrs == 'original' && file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo{$ext}" ) ) {
				$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo{$ext}";
			} elseif ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$attrs}x{$attrs}{$ext}" ) ) {
				$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo-{$attrs}x{$attrs}{$ext}";
			} elseif ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$attrs}{$ext}" ) ) {
				$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo-{$attrs}{$ext}";
			} else {
				$sizes = UM()->options()->get( 'photo_thumb_sizes' );
				if ( is_array( $sizes ) ) {
					$find = um_closest_num( $sizes, $attrs );
				}

				if ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$find}x{$find}{$ext}" ) ) {
					$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo-{$find}x{$find}{$ext}";
				} elseif ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo-{$find}{$ext}" ) ) {
					$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo-{$find}{$ext}";
				} elseif ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . "profile_photo{$ext}" ) ) {
					$uri = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/profile_photo{$ext}";
				}
			}

			if ( ! empty( $uri_common ) && empty( $uri ) ) {
				$uri = $uri_common;
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_filter_avatar_cache_time
			 * @description Change Profile field value if it's empty
			 * @input_vars
			 * [{"var":"$timestamp","type":"timestamp","desc":"Avatar cache time"},
			 * {"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_filter_avatar_cache_time', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_filter( 'um_filter_avatar_cache_time', 'my_avatar_cache_time', 10, 2 );
			 * function my_avatar_cache_time( $timestamp, $user_id ) {
			 *     // your code here
			 *     return $timestamp;
			 * }
			 * ?>
			 */
			$cache_time = apply_filters( 'um_filter_avatar_cache_time', current_time( 'timestamp' ), um_user( 'ID' ) );
			if ( ! empty( $cache_time ) ) {
				$uri .= "?{$cache_time}";
			}

			return $uri;
		}

		/**
		 * @param \WP_User $userdata
		 *
		 * @return string|\WP_Error
		 */
		public function maybe_generate_password_reset_key( $userdata ) {
			if ( empty( $this->password_reset_key ) ) {
				$this->password_reset_key = get_password_reset_key( $userdata );
			}

			return $this->password_reset_key;
		}

		/**
		 * Get Reset URL
		 *
		 * @param $userdata \WP_User
		 *
		 * @return string
		 */
		public function get_reset_password_url( $userdata ) {
			delete_option( "um_cache_userdata_{$userdata->ID}" );

			// this link looks like WordPress native link e.g. wp-login.php?action=rp&key={key}&login={user_login}
			$url = add_query_arg(
				array(
					'action' => 'rp',
					'key'    => $this->maybe_generate_password_reset_key( $userdata ), // new reset password key via WordPress native field. It maybe already exists here but generated twice to make sure that emailed with a proper and fresh hash
					'login'  => $userdata->user_login,
				),
				um_get_predefined_page_url( 'password-reset' )
			);

			return $url;
		}

		/**
		 * @param int $user_id
		 */
		public function flush_reset_password_attempts( $user_id ) {
			$attempts = get_user_meta( $user_id, 'password_rst_attempts', true );
			$attempts = ( ! empty( $attempts ) && is_numeric( $attempts ) ) ? $attempts : 0;
			if ( $attempts > 0 ) {
				update_user_meta( $user_id, 'password_rst_attempts', 0 );
			}
			update_user_meta( $user_id, 'password_rst_attempts_timeout', '' );
		}

		/**
		 * @param int $user_id
		 *
		 * @return bool|string
		 */
		public function get_priority_user_role( $user_id ) {
			$user = get_userdata( $user_id );

			if ( empty( $user->roles ) ) {
				return false;
			}

			// User has roles so look for a UM Role one
			$um_roles_keys = get_option( 'um_roles', array() );

			if ( ! empty( $um_roles_keys ) ) {
				$um_roles_keys = array_map(
					function( $item ) {
						return 'um_' . $item;
					},
					$um_roles_keys
				);
			}

			$orders = array();
			foreach ( array_values( $user->roles ) as $userrole ) {
				if ( ! empty( $um_roles_keys ) && in_array( $userrole, $um_roles_keys, true ) ) {
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
		 * Restrict the edit/delete users via wp-admin screen by the UM role capabilities
		 *
		 * @param $allcaps
		 * @param $cap
		 * @param $args
		 * @param $user
		 *
		 * @return mixed
		 */
		public function map_caps_by_role( $allcaps, $cap, $args ) {
			if ( isset( $cap[0] ) && 'edit_users' === $cap[0] ) {
				if ( ! user_can( $args[1], 'administrator' ) && $args[0] == 'edit_user' ) {
					if ( ! UM()->roles()->um_current_user_can( 'edit', $args[2] ) ) {
						$allcaps[ $cap[0] ] = false;
					}
				}
			} elseif ( isset( $cap[0] ) && 'delete_users' === $cap[0] ) {
				if ( ! user_can( $args[1], 'administrator' ) && 'delete_user' === $args[0] ) {
					if ( ! UM()->roles()->um_current_user_can( 'delete', $args[2] ) ) {
						$allcaps[ $cap[0] ] = false;
					}
				}
			} elseif ( isset( $cap[0] ) && 'list_users' === $cap[0] ) {
				if ( ! user_can( $args[1], 'administrator' ) && 'list_users' === $args[0] ) {
					if ( ! um_user( 'can_view_all' ) ) {
						$allcaps[ $cap[0] ] = false;
					}
				}
			}

			return $allcaps;
		}

		/**
		 * Maybe flush reset password attempts count after retrieve password
		 *
		 * @param \WP_Error      $errors
		 * @param \WP_User|false $user_data
		 */
		public function reset_password_attempts( $errors, $user_data ) {
			if ( $errors->has_errors() ) {
				return;
			}

			if ( false === $user_data ) {
				return;
			}

			$this->flush_reset_password_attempts( $user_data->ID );
		}

		/**
		 * Handler on delete user.
		 *
		 * @param int $user_id User ID.
		 */
		public function delete_user_handler( $user_id ) {
			um_fetch_user( $user_id );

			$this->deleted_user_id = $user_id;
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_delete_user_hook
			 * @description On delete user
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_delete_user_hook', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_delete_user_hook', 'my_delete_user', 10 );
			 * function my_delete_user() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_delete_user_hook' );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_delete_user
			 * @description On delete user
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_delete_user', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_delete_user', 'my_delete_user', 10, 1 );
			 * function my_delete_user( $user_id ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_delete_user', $user_id );

			// remove uploads
			UM()->files()->remove_dir( UM()->files()->upload_temp );
			UM()->files()->remove_dir( UM()->uploader()->get_upload_base_dir() . $user_id . DIRECTORY_SEPARATOR );

			delete_transient( 'um_count_users_unassigned' );
			delete_transient( 'um_count_users_pending_dot' );

			// Send email notifications
			if ( $this->has_status( $user_id, 'approved' ) ) {
				$userdata = get_userdata( $user_id );
				// Don't send email notification to not approved user.
				UM()->common()->mail()->send( $userdata->user_email, 'deletion_email' );
			}

			// Send email notifications to administrator about user deletion anyway
			$emails = um_multi_admin_email();
			if ( ! empty( $emails ) ) {
				foreach ( $emails as $email ) {
					UM()->common()->mail()->send( $email, 'notification_deletion', array( 'admin' => true ) );
				}
			}
		}

		/**
		 * Check if the user can be approved.
		 *
		 * @param int $user_id User ID
		 *
		 * @return bool
		 */
		public function can_be_approved( $user_id ) {
			$status = $this->get_status( $user_id );
			if ( 'approved' === $status ) {
				// Break if the user already approved
				return false;
			}

			return true;
		}

		/**
		 * Approve user.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return bool `true` if the user has been approved
		 *              `false` on failure or if the user already has approved status.
		 */
		public function approve( $user_id ) {
			if ( ! $this->can_be_approved( $user_id ) ) {
				return false;
			}

			/**
			 * Fires before User has been approved.
			 *
			 * @since 3.0.0
			 * @hook um_before_user_is_approved
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_before_user_is_approved', $user_id );

			$result = $this->set_status( $user_id, 'approved' );

			// It's `false` on failure or if the user already has approved status.
			if ( false !== $result ) {
				$userdata = get_userdata( $user_id );

				// Reset cache and activation link hash.
				$this->remove_cache( $user_id );
				$this->reset_activation_link( $user_id );

				$email_slug     = 'welcome_email';
				$current_status = $this->get_status( $user_id );
				if ( 'awaiting_admin_review' === $current_status ) {
					$email_slug = 'approved_email';
					$this->maybe_generate_password_reset_key( $userdata );
				}

				UM()->common()->mail()->send( $userdata->user_email, $email_slug );

				/**
				 * Fires after User has been approved.
				 *
				 * @since 3.0.0
				 * @hook um_after_user_is_approved
				 *
				 * @param {int} $user_id User ID.
				 */
				do_action( 'um_after_user_is_approved', $user_id );
				return true;
			}

			return false;
		}

		/**
		 * Reset Activation link hash.
		 *
		 * @param int $user_id User ID.
		 */
		public function reset_activation_link( $user_id ) {
			delete_user_meta( $user_id, 'account_secret_hash' );
			delete_user_meta( $user_id, 'account_secret_hash_expiry' );
		}

		/**
		 * Set user's activation link hash
		 *
		 * @param int $user_id User ID.
		 */
		private function assign_secretkey( $user_id ) {
			if ( ! $this->has_status( $user_id, 'awaiting_email_confirmation' ) ) {
				return;
			}

			/**
			 * Fires before user activation link hash is generated.
			 *
			 * @since 3.0.0
			 * @hook um_before_user_hash_is_changed
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_before_user_hash_is_changed', $user_id );

			$hash = UM()->validation()->generate();
			update_user_meta( $user_id, 'account_secret_hash', $hash );

			$expiration  = '';
			$expiry_time = UM()->options()->get( 'activation_link_expiry_time' );
			if ( ! empty( $expiry_time ) && is_numeric( $expiry_time ) ) {
				$expiration = time() + $expiry_time;
				update_user_meta( $user_id, 'account_secret_hash_expiry', $expiration );
			}

			/**
			 * Fires after user activation link hash is changed.
			 *
			 * @since 3.0.0
			 * @hook um_before_user_hash_is_changed
			 *
			 * @param {int}    $user_id    User ID.
			 * @param {string} $hash       Activation link hash.
			 * @param {int}    $expiration Expiration timestamp.
			 */
			do_action( 'um_after_user_hash_is_changed', $user_id, $hash, $expiration );
		}

		/**
		 * Reset User cache
		 *
		 * @param int $user_id User ID.
		 */
		public function remove_cache( $user_id ) {
			delete_option( "um_cache_userdata_{$user_id}" );
		}

		/**
		 * Set user's account status.
		 *
		 * @param int    $user_id User ID.
		 * @param string $status  Status key.
		 *
		 * @return bool
		 */
		public function set_status( $user_id, $status ) {
			/**
			 * Fires before User status is set.
			 *
			 * @since 3.0.0
			 * @hook um_before_user_status_is_set
			 *
			 * @param {string} $status  Status key.
			 * @param {int}    $user_id User ID.
			 */
			do_action( 'um_before_user_status_is_set', $status, $user_id );

			$result = update_user_meta( $user_id, 'account_status', $status );

			// false on failure or if the value passed to the function is the same as the one that is already in the database.
			if ( false !== $result ) {
				/**
				 * Fires just after User status is changed.
				 *
				 * @since 3.0.0
				 * @hook um_after_user_status_is_changed
				 *
				 * @param {string} $status  Status key.
				 * @param {int}    $user_id User ID.
				 */
				do_action( 'um_after_user_status_is_changed', $status, $user_id );

				return true;
			}

			return false;
		}

		/**
		 * Set user account status.
		 *
		 * @param int $user_id User ID
		 *
		 * @return string
		 */
		public function get_status( $user_id ) {
			$status = get_user_meta( $user_id, 'account_status', true );
			return $status;
		}

		/**
		 * Check if user has selected account status.
		 *
		 * @param int    $user_id        User ID.
		 * @param string $status_control Status key.
		 *
		 * @return bool
		 */
		public function has_status( $user_id, $status_control ) {
			$status = $this->get_status( $user_id );
			return $status === $status_control;
		}

		/**
		 * @param $user_id
		 *
		 * @return bool
		 */
		public function can_be_deactivated( $user_id ) {
			$status = $this->get_status( $user_id );
			if ( 'inactive' === $status ) {
				// Break if the user already approved
				return false;
			}

			if ( 'approved' !== $status ) {
				// Break if the user already doesn't approved yet
				return false;
			}

			return true;
		}

		/**
		 * @param $user_id
		 *
		 * @return bool
		 */
		public function deactivate( $user_id ) {
			if ( ! $this->can_be_deactivated( $user_id ) ) {
				return false;
			}

			/**
			 * Fires before User has been deactivated.
			 *
			 * @since 3.0.0
			 * @hook um_before_user_is_deactivated
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_before_user_is_deactivated', $user_id );

			$result = $this->set_status( $user_id, 'inactive' );

			// It's `false` on failure or if the user already has approved status.
			if ( false !== $result ) {
				$userdata = get_userdata( $user_id );

				// Reset cache.
				$this->remove_cache( $user_id );

				UM()->common()->mail()->send( $userdata->user_email, 'inactive_email' );

				/**
				 * Fires after User has been deactivated.
				 *
				 * @since 3.0.0
				 * @hook um_after_user_is_inactive
				 *
				 * @param {int} $user_id User ID.
				 */
				do_action( 'um_after_user_is_inactive', $user_id );
				return true;
			}

			return false;
		}

		/**
		 * @param $user_id
		 *
		 * @return bool
		 */
		public function can_be_reactivated( $user_id ) {
			$status = $this->get_status( $user_id );
			if ( 'inactive' !== $status ) {
				// Break if the user doesn't have inactive status
				return false;
			}

			return true;
		}

		/**
		 * @param $user_id
		 *
		 * @return bool
		 */
		public function reactivate( $user_id ) {
			if ( ! $this->can_be_reactivated( $user_id ) ) {
				return false;
			}

			/**
			 * Fires before User has been reactivated.
			 *
			 * @since 3.0.0
			 * @hook um_before_user_is_reactivated
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_before_user_is_reactivated', $user_id );

			$result = $this->set_status( $user_id, 'approved' );

			// It's `false` on failure or if the user already has approved status.
			if ( false !== $result ) {
				$userdata = get_userdata( $user_id );

				// Reset cache and activation link hash.
				$this->remove_cache( $user_id );
				$this->reset_activation_link( $user_id );

				UM()->common()->mail()->send( $userdata->user_email, 'welcome_email' );

				/**
				 * Fires after User has been reactivated.
				 *
				 * @since 3.0.0
				 * @hook um_after_user_is_reactivated
				 *
				 * @param {int} $user_id User ID.
				 */
				do_action( 'um_after_user_is_reactivated', $user_id );
				return true;
			}

			return false;
		}

		/**
		 * @param $user_id
		 *
		 * @return bool
		 */
		public function can_be_rejected( $user_id ) {
			$status = $this->get_status( $user_id );
			if ( 'rejected' === $status ) {
				// Break if the user already rejected
				return false;
			}

			if ( 'approved' !== $status ) {
				// Break if the user already doesn't approved yet
				return false;
			}

			return true;
		}

		/**
		 * @param $user_id
		 *
		 * @return bool
		 */
		public function reject( $user_id ) {
			if ( ! $this->can_be_rejected( $user_id ) ) {
				return false;
			}

			/**
			 * Fires before User has been rejected.
			 *
			 * @since 3.0.0
			 * @hook um_before_user_is_rejected
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_before_user_is_rejected', $user_id );

			$result = $this->set_status( $user_id, 'rejected' );

			// It's `false` on failure or if the user already has rejected status.
			if ( false !== $result ) {
				$userdata = get_userdata( $user_id );

				// Reset cache.
				$this->remove_cache( $user_id );

				UM()->common()->mail()->send( $userdata->user_email, 'rejected_email' );

				/**
				 * Fires after User has been rejected.
				 *
				 * @since 3.0.0
				 * @hook um_after_user_is_rejected
				 *
				 * @param {int} $user_id User ID.
				 */
				do_action( 'um_after_user_is_rejected', $user_id );
				return true;
			}

			return false;
		}

		public function can_be_set_as_pending( $user_id ) {
			$status = $this->get_status( $user_id );
			if ( 'awaiting_admin_review' === $status ) {
				// Break if the user already awaiting_admin_review
				return false;
			}

			return true;
		}

		public function set_as_pending( $user_id ) {
			if ( ! $this->can_be_set_as_pending( $user_id ) ) {
				return false;
			}

			/**
			 * Fires before User has been set as pending admin review.
			 *
			 * @since 3.0.0
			 * @hook um_before_user_is_set_as_pending
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_before_user_is_set_as_pending', $user_id );

			$result = $this->set_status( $user_id, 'awaiting_admin_review' );

			// It's `false` on failure or if the user already has rejected status.
			if ( false !== $result ) {
				$userdata = get_userdata( $user_id );

				// Reset cache.
				$this->remove_cache( $user_id );

				UM()->common()->mail()->send( $userdata->user_email, 'pending_email' );

				/**
				 * Fires after User has been set as pending admin review.
				 *
				 * @since 3.0.0
				 * @hook um_after_user_is_set_as_pending
				 *
				 * @param {int} $user_id User ID.
				 */
				do_action( 'um_after_user_is_set_as_pending', $user_id );
				return true;
			}

			return false;
		}

		public function can_activation_resend( $user_id ) {
			$status = $this->get_status( $user_id );
			if ( 'awaiting_admin_review' === $status ) {
				// Break if the user already awaiting_admin_review
				return false;
			}

			return true;
		}

		public function resend_activation( $user_id ) {
			if ( ! $this->can_activation_resend( $user_id ) ) {
				return false;
			}

			/**
			 * Fires before User has been set as pending admin review.
			 *
			 * @since 3.0.0
			 * @hook um_before_user_is_set_as_pending
			 *
			 * @param {int} $user_id User ID.
			 */
			do_action( 'um_before_user_is_set_as_awaiting_email_confirmation', $user_id );

			$result = $this->set_status( $user_id, 'awaiting_email_confirmation' );

			// It's `false` on failure or if the user already has rejected status.
			if ( false !== $result ) {
				$userdata = get_userdata( $user_id );

				// Reset cache and set activation link hash.
				$this->remove_cache( $user_id );
				$this->assign_secretkey( $user_id );

				UM()->common()->mail()->send( $userdata->user_email, 'checkmail_email' );

				/**
				 * Fires after User has been set as pending admin review.
				 *
				 * @since 3.0.0
				 * @hook um_after_user_is_set_as_pending
				 *
				 * @param {int} $user_id User ID.
				 */
				do_action( 'um_after_user_is_set_as_awaiting_email_confirmation', $user_id );
				return true;
			}

			return false;
		}

		/**
		 * Getting account activation link.
		 *
		 * @param int $user_id User ID
		 *
		 * @return string|bool Account activation link. Or `false` when something is wrong.
		 */
		public function get_account_activation_link( $user_id ) {
			if ( ! $this->has_status( $user_id, 'awaiting_email_confirmation' ) ) {
				$this->reset_activation_link( $user_id );
				return false;
			}

			// Checking expiry time and if key is expired, re-generate new.
			$expiry_time = UM()->options()->get( 'activation_link_expiry_time' );
			if ( ! empty( $expiry_time ) && is_numeric( $expiry_time ) ) {
				$expiry_timestamp = get_user_meta( $user_id, 'account_secret_hash_expiry', true );
				if ( empty( $expiry_timestamp ) || time() > $expiry_timestamp ) {
					$this->assign_secretkey( $user_id );
				}
			}

			// Checking if hash is empty then generate it.
			$hash = get_user_meta( $user_id, 'account_secret_hash', true );
			if ( empty( $hash ) ) {
				$this->assign_secretkey( $user_id );
			}

			// Checking if hash is empty after regeneration then something went wrong and link break with false.
			$hash = get_user_meta( $user_id, 'account_secret_hash', true );
			if ( empty( $hash ) ) {
				return false;
			}

			/**
			 * Filters the activation URL base.
			 *
			 * @since 3.0.0
			 * @hook  um_activate_url_base
			 *
			 * @param {string} $base_url Base URL for activation link. It's home URL by default.
			 *
			 * @return {string} Base URL for activation link.
			 */
			$base_url = apply_filters( 'um_activate_url_base', home_url() );

			$url = add_query_arg(
				array(
					'act'     => 'activate_via_email',
					'hash'    => $hash,
					'user_id' => $user_id,
				),
				$base_url
			);
			/**
			 * Filters the activation URL.
			 *
			 * @since 1.0.0
			 * @hook  um_activate_url
			 *
			 * @param {string} $url      Account activation link.
			 * @param {string} $base_url Base URL for activation link. It's home URL by default.
			 * @param {string} $hash     User hash for the activation.
			 * @param {int}    $user_id  User ID.
			 *
			 * @return {string} Account activation link.
			 */
			$url = apply_filters( 'um_activate_url', $url, $base_url, $hash, $user_id );
			return $url;
		}

		/**
		 * Delete user
		 *
		 * @param int $user_id User ID.
		 *
		 * @return bool It's `true` if user deleted.
		 */
		public function delete( $user_id ) {
			if ( is_multisite() ) {
				if ( ! function_exists( 'wpmu_delete_user' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/ms.php' );
				}

				$result = wpmu_delete_user( $user_id );
			} else {
				if ( ! function_exists( 'wp_delete_user' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/user.php' );
				}

				$result = wp_delete_user( $user_id );
			}

			return $result;
		}

		/**
		 * @param int $user_id
		 *
		 * @return bool
		 */
		function exists_by_id( $user_id ) {
			$aux = get_userdata( absint( $user_id ) );
			if ( $aux == false ) {
				return false;
			} else {
				return $user_id;
			}
		}


		/**
		 * User exists by name
		 *
		 * @param string $value
		 *
		 * @return bool
		 */
		function exists_by_name( $value ) {
			// Permalink base
			$permalink_base = UM()->options()->get( 'permalink_base' );

			$raw_value = $value;
			$value     = UM()->validation()->safe_name_in_url( $value );
			$value     = um_clean_user_basename( $value );

			// Search by Profile Slug
			$args = array(
				'fields'     => 'ids',
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     =>  'um_user_profile_url_slug_' . $permalink_base,
						'value'   => strtolower( $raw_value ),
						'compare' => '=',
					),
				),
				'number'     => 1,
			);

			$query = new \WP_User_Query( $args );
			$total = $query->get_total();

			if ( $total > 0 ) {
				$user_id = current( $query->get_results() );
				return $user_id;
			}

			// Search by Display Name or ID
			$args = array(
				'fields'         => 'ids',
				'search'         => $value,
				'search_columns' => array( 'display_name', 'ID' ),
				'number'         => 1,
			);

			$query = new \WP_User_Query( $args );
			$total = $query->get_total();

			if ( $total > 0 ) {
				$user_id = current( $query->get_results() );
				return $user_id;
			}

			// Search By User Login
			$value = str_replace( ".", "_", $value );
			$value = str_replace( " ", "", $value );

			$args = array(
				'fields'         => 'ids',
				'search'         => $value,
				'search_columns' => array( 'user_login' ),
				'number'         => 1,
			);

			$query = new \WP_User_Query( $args );
			$total = $query->get_total();

			if ( $total > 0 ) {
				$user_id = current( $query->get_results() );
				return $user_id;
			}

			return false;
		}


		/**
		 * This method checks if a user exists or not in your site based on the user email as username
		 *
		 * @param string $slug A user slug must be passed to check if the user exists
		 *
		 * @usage <?php UM()->user()->user_exists_by_email_as_username( $slug ); ?>
		 *
		 * @return bool
		 *
		 * @example Basic Usage

		<?php

		$boolean = UM()->user()->user_exists_by_email_as_username( 'calumgmail-com' );
		if ( $boolean ) {
		// That user exists
		}

		?>
		 */
		function exists_by_email_as_username( $slug ) {
			$user_id = false;

			$args = array(
				'fields'   => 'ids',
				'meta_key' => 'um_email_as_username_' . $slug,
				'number'   => 1,
			);

			$query = new \WP_User_Query( $args );
			$total = $query->get_total();

			if ( $total > 0 ) {
				$user_id = current( $query->get_results() );
			}

			return $user_id;
		}


		/**
		 * Deletes all user roles
		 *
		 * @param int $user_id User ID.
		 */
		public function flush_roles( $user_id ) {
			$user = get_userdata( $user_id );

			if ( empty( $user->roles ) ) {
				return;
			}

			foreach ( $user->roles as $role ) {
				$user->remove_role( $role );
			}
		}
	}
}
