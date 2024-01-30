<?php
namespace um\common;

use WP_Comment;
use WP_Post;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Users
 *
 * @package um\common
 */
class Users {

	/**
	 * Hooks function.
	 */
	public function hooks() {
		$this->add_filters();

		add_filter( 'avatar_defaults', array( $this, 'remove_filters' ) );
		add_filter( 'default_avatar_select', array( $this, 'add_filters_cb' ) );
	}

	public function add_filters() {
		add_filter( 'pre_get_avatar_data', array( $this, 'change_avatar' ), 10, 2 );
	}

	public function remove_filters( $avatar_defaults ) {
		remove_filter( 'pre_get_avatar_data', array( $this, 'change_avatar' ) );
		return $avatar_defaults;
	}

	public function add_filters_cb( $avatar_list ) {
		$this->add_filters();
		return $avatar_list;
	}

	/**
	 * Set UM default avatar data to avoid WordPress native handler and make it faster.
	 *
	 * Passing a non-null value in the 'url' member of the return array will
	 * effectively short circuit get_avatar_data(), passing the value through
	 * the {@see 'get_avatar_data'} filter and returning early.
	 *
	 * @param array $args Arguments passed to get_avatar_data(), after processing.
	 * @param mixed $id_or_email The avatar to retrieve. Accepts a user ID, Gravatar MD5 hash,
	 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
	 *
	 * @return array
	 */
	public function change_avatar( $args, $id_or_email ) {
		$user = false;
		if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
			$id_or_email = get_comment( $id_or_email );
		}

		// Process the user identifier.
		if ( is_numeric( $id_or_email ) ) {
			$user = get_user_by( 'id', absint( $id_or_email ) );
		} elseif ( $id_or_email instanceof WP_User ) {
			// User object.
			$user = $id_or_email;
		} elseif ( $id_or_email instanceof WP_Post ) {
			// Post object.
			$user = get_user_by( 'id', (int) $id_or_email->post_author );
		} elseif ( $id_or_email instanceof WP_Comment ) {
			// Comment object.
			if ( ! empty( $id_or_email->user_id ) && is_avatar_comment_type( get_comment_type( $id_or_email ) ) ) {
				$user = get_user_by( 'id', (int) $id_or_email->user_id );
			}
		}

		// Escape from this callback because there isn't user.
		if ( ! $user || is_wp_error( $user ) ) {
			return $args;
		}

		$url           = '';
		$profile_photo = get_user_meta( $user->ID, 'profile_photo', true );
		if ( ! empty( $profile_photo ) ) {
			$ext       = '.' . pathinfo( $profile_photo, PATHINFO_EXTENSION );
			$all_sizes = UM()->config()->get( 'avatar_thumbnail_sizes' );
			sort( $all_sizes );

			$size = '';
			if ( array_key_exists( 'size', $args ) ) {
				$size = UM()->get_closest_value( $all_sizes, $args['size'] );
			}

			$locate = array();
			if ( '' !== $size ) {
				foreach ( $all_sizes as $pre_size ) {
					if ( $size > $pre_size ) {
						continue;
					}

					$locate[] = "profile_photo-{$pre_size}x{$pre_size}{$ext}";
				}
			}
			$locate[] = "profile_photo{$ext}";

			if ( is_multisite() ) {
				// Multisite fix for old customers
				$multisite_fix_dir = UM()->uploader()->get_upload_base_dir();
				$multisite_fix_url = UM()->uploader()->get_upload_base_url();
				$multisite_fix_dir = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $multisite_fix_dir );
				$multisite_fix_url = str_replace( '/sites/' . get_current_blog_id() . '/', '/', $multisite_fix_url );

				foreach ( $locate as $avatar_basename ) {
					if ( file_exists( $multisite_fix_dir . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $avatar_basename ) ) {
						$url = $multisite_fix_url . um_user( 'ID' ) . '/' . $avatar_basename;
						break;
					}
				}
			}

			if ( empty( $url ) ) {
				foreach ( $locate as $avatar_basename ) {
					if ( file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $avatar_basename ) ) {
						$url = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . '/' . $avatar_basename;
						break;
					}
				}
			}
		}

		if ( empty( $url ) ) {
			$replace_gravatar = UM()->options()->get( 'use_um_gravatar_default_image' );
			if ( $replace_gravatar ) {
				$default_avatar = UM()->options()->get( 'default_avatar' );
				$url            = ! empty( $default_avatar['url'] ) ? $default_avatar['url'] : '';
				if ( empty( $url ) ) {
					$url = UM_URL . 'assets/img/default_avatar.jpg';
				}

				$args['url'] = set_url_scheme( $url );
				//$args['um_default'] = true;
				if ( ! empty( $args['class'] ) ) {
					$args['class'][] = 'um-avatar-default';
				} else {
					$args['class'] = array( 'um-avatar-default' );
				}
			}
		} else {
			if ( array_key_exists( 'um-cache', $args ) && false === $args['um-cache'] ) {
				$url = add_query_arg( array( 't' => time() ), $url );
			}

			$args['url'] = set_url_scheme( $url );
			//$args['um_uploaded']  = true;
			$args['found_avatar'] = true;
			if ( ! empty( $args['class'] ) ) {
				$args['class'][] = 'um-avatar-uploaded';
			} else {
				$args['class'] = array( 'um-avatar-uploaded' );
			}
		}

		return $args;
	}

	/**
	 * Delete a main user photo.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type    User photo type. Uses 'profile_photo', 'cover_photo'
	 *
	 * @return bool
	 */
	public function delete_photo( $user_id, $type ) {
		delete_user_meta( $user_id, $type );
		delete_user_meta( $user_id, $type . '_metadata_temp' );

		/**
		 * Fires for make actions after delete user profile or cover photo meta and before delete related files.
		 *
		 * Internal Ultimate Member Pro callbacks:
		 * ### um_after_remove_profile_photo:
		 * * myCRED deduct points.
		 * * Social Login synced photo.
		 * ### um_after_remove_cover_photo:
		 * * myCRED deduct points.
		 * * Unsplash cover photo.
		 *
		 * @since 1.3.x
		 * @hook um_after_remove_{$type}
		 *
		 * @param {int} $user_id User ID.
		 *
		 * @example <caption>Make any custom action after delete profile photo.</caption>
		 * function my_custom_remove_profile_photo( $user_id ) {
		 *     // your code here
		 * }
		 * add_action( 'um_after_remove_profile_photo', 'my_custom_remove_profile_photo' );
		 *
		 * @example <caption>Make any custom action after delete cover photo.</caption>
		 * function my_custom_remove_cover_photo( $user_id ) {
		 *     // your code here
		 * }
		 * add_action( 'um_after_remove_cover_photo', 'my_custom_remove_cover_photo' );
		 */
		do_action( "um_after_remove_{$type}", $user_id );

		$dir = UM()->files()->upload_basedir . $user_id . DIRECTORY_SEPARATOR;
		chdir( $dir );

		// Searching files via the pattern and remove them.
		$matches = glob( $type . '*', GLOB_MARK );
		if ( is_array( $matches ) && ! empty( $matches ) ) {
			foreach ( $matches as $match ) {
				if ( is_file( $dir . $match ) ) {
					unlink( $dir . $match );
				}
			}
		}

		// Checking if the user's directory is empty.
		if ( count( glob( "$dir/*" ) ) === 0 ) {
			rmdir( $dir );
		}

		// Flush the user's cache.
		$this->remove_cache( $user_id );

		return ! $this->has_photo( $user_id, $type );
	}

	public function remove_cache( $user_id ) {
		delete_option( "um_cache_userdata_{$user_id}" );
	}

	public function has_photo( $user_id, $type ) {
		$meta = get_user_meta( $user_id, $type, true );
		return ! empty( $meta );
	}
}
