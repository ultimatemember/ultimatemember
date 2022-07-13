<?php
namespace um\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\admin\Site_Health' ) ) {


	/**
	 * Class Site_Health
	 *
	 * @package um\admin
	 */
	class Site_Health {


		/**
		 * Site_Health constructor.
		 */
		public function __construct() {
			add_filter( 'debug_information', array( $this, 'debug_information' ) );
		}


		private function get_roles() {
			return UM()->roles()->get_roles();
		}


		/**
		 * Add our data to Site Health information.
		 *
		 * @since 3.0
		 *
		 * @param array $info The Site Health information.
		 *
		 * @return array The updated Site Health information.
		 */
		public function debug_information( $info ) {
			$labels = array(
				'yes' => __( 'Yes', 'ultimate-member' ),
				'no'  => __( 'No', 'ultimate-member' ),
			);

			// User roles settings
			$info['ultimate-member-user-roles'] = array(
				'label'       => __( 'User roles', 'ultimate-member' ),
				'description' => __( 'This debug information about user roles.', 'ultimate-member' ),
				'fields'      => array(
					'um-roles'         => array(
						'label' => __( 'User Roles', 'ultimate-member' ),
						'value' => $this->get_roles(),
					),
				),
			);

			$info['ultimate-member'] = array(
				'label'       => __( 'Ultimate Member', 'ultimate-member' ),
				'description' => __( 'This debug information for your Ultimate Member installation can assist you in getting support.', 'ultimate-member' ),
				'fields'      => array(
					'um-register_role' => array(
						'label' => __( 'Default New User Role', 'ultimate-member' ),
						'value' => get_option( 'default_role' ),
					),
				),
			);

			// Pages settings
			$pages = apply_filters( 'um_debug_information_pages', array(
				'User'           => get_the_title( UM()->options()->get('core_user') ) . ' (' . UM()->options()->get('core_user') . '), ' . get_permalink( UM()->options()->get('core_user') ),
				'Account'        => get_the_title( UM()->options()->get('core_account') ) . ' (' . UM()->options()->get('core_account') . '), ' . get_permalink( UM()->options()->get('core_account') ),
				'Register'       => get_the_title( UM()->options()->get('core_register') ) . ' (' . UM()->options()->get('core_register') . '), ' . get_permalink( UM()->options()->get('core_register') ),
				'Login'          => get_the_title( UM()->options()->get('core_login') ) . ' (' . UM()->options()->get('core_login') . '), ' . get_permalink( UM()->options()->get('core_login') ),
				'Logout'         => get_the_title( UM()->options()->get('core_logout') ) . ' (' . UM()->options()->get('core_logout') . '), ' . get_permalink( UM()->options()->get('core_logout') ),
				'Password reset' => get_the_title( UM()->options()->get('core_password-reset') ) . ' (' . UM()->options()->get('core_password-reset') . '), ' . get_permalink( UM()->options()->get('core_password-reset') ),
			) );

			$pages_settings = array(
				'um-pages' => array(
					'label' => __( 'Pages', 'ultimate-member' ),
					'value' => $pages,
				),
			);

			// User settings
			$user_settings = array(
				'um-permalink_base'              => array(
					'label' => __( 'Profile Permalink Base', 'ultimate-member' ),
					'value' => UM()->options()->get('permalink_base'),
				),
				'um-display_name'                => array(
					'label' => __( 'User Display Name', 'ultimate-member' ),
					'value' => UM()->options()->get('display_name'),
				),
				'um-author_redirect'             => array(
					'label' => __( 'Automatically redirect author page to their profile?', 'ultimate-member' ),
					'value' => UM()->options()->get('author_redirect') ? $labels['yes'] : $labels['no'],
				),
				'um-profile_noindex'             => array(
					'label' => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
					'value' => UM()->options()->get('profile_noindex') ? $labels['yes'] : $labels['no'],
				),
				'um-activation_link_expiry_time' => array(
					'label' => __( 'Email activation link expiration (days)', 'ultimate-member' ),
					'value' => UM()->options()->get('activation_link_expiry_time'),
				),
				'um-default_avatar'              => array(
					'label' => __( 'Default Profile Photo', 'ultimate-member' ),
					'value' => um_get_default_avatar_uri(),
				),
				'um-default_cover'               => array(
					'label' => __( 'Default Cover Photo', 'ultimate-member' ),
					'value' => um_get_default_cover_uri(),
				),
				'um-require_strongpass'          => array(
					'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
					'value' => UM()->options()->get('require_strongpass') == 1 ? $labels['yes'] : $labels['no'],
				),
			);

			if ( 1 == UM()->options()->get('require_strongpass') ) {
				$user_settings['um-password_min_chars'] =  array(
					'label' => __( 'Password minimum length', 'ultimate-member' ),
					'value' => UM()->options()->get('password_min_chars'),
				);
				$user_settings['um-password_max_chars'] =  array(
					'label' => __( 'Password maximum length', 'ultimate-member' ),
					'value' => UM()->options()->get('password_max_chars'),
				);
			}

			$user_settings['um-use_gravatars'] = array(
				'label' => __( 'Use Gravatars', 'ultimate-member' ),
				'value' => UM()->options()->get('use_gravatars') ? $labels['yes'] : $labels['no'],
			);

			if ( 1 == UM()->options()->get('use_gravatars') ) {
				$user_settings['um-use_um_gravatar_default_builtin_image'] = array(
					'label' => __( 'Use Gravatar builtin image', 'ultimate-member' ),
					'value' => UM()->options()->get('use_um_gravatar_default_builtin_image'),
				);
				if ( 'default' == UM()->options()->get('use_um_gravatar_default_builtin_image') ) {
					$user_settings['um-use_um_gravatar_default_image'] = array(
						'label' => __( 'Use Default plugin avatar as Gravatar\'s Default avatar', 'ultimate-member' ),
						'value' => UM()->options()->get('use_um_gravatar_default_image') ? $labels['yes'] : $labels['no'],
					);
				}
			}

			// Account settings
			$account_settings = array(
				'um-account_tab_password'      => array(
					'label' => __( 'Password Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get('account_tab_password') ? $labels['yes'] : $labels['no'],
				),
				'um-account_tab_privacy'       => array(
					'label' => __( 'Privacy Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get('account_tab_privacy') ? $labels['yes'] : $labels['no'],
				),
				'um-account_tab_notifications' => array(
					'label' => __( 'Notifications Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get('account_tab_notifications') ? $labels['yes'] : $labels['no'],
				),
				'um-account_email'             => array(
					'label' => __( 'Allow users to change email', 'ultimate-member' ),
					'value' => UM()->options()->get('account_email') ? $labels['yes'] : $labels['no'],
				),
				'um-account_general_password'  => array(
					'label' => __( 'Require password to update account', 'ultimate-member' ),
					'value' => UM()->options()->get('account_general_password') ? $labels['yes'] : $labels['no'],
				),
				'um-account_name'              => array(
					'label' => __( 'Display First & Last name fields', 'ultimate-member' ),
					'value' => UM()->options()->get('account_name') ? $labels['yes'] : $labels['no'],
				),
			);

			if ( 1 == UM()->options()->get('account_name') ) {
				$account_settings['um-account_name_disable'] = array(
					'label' => __( 'Disable First & Last name field editing', 'ultimate-member' ),
					'value' => UM()->options()->get('account_name_disable') ? $labels['yes'] : $labels['no'],
				);
				$account_settings['um-account_name_require'] = array(
					'label' => __( 'Require First & Last Name', 'ultimate-member' ),
					'value' => UM()->options()->get('account_name_require') ? $labels['yes'] : $labels['no'],
				);
			}

			$account_settings['um-account_tab_delete'] = array(
				'label' => __( 'Delete Account Tab', 'ultimate-member' ),
				'value' => UM()->options()->get('account_tab_delete') ? $labels['yes'] : $labels['no'],
			);

			if ( 1 == UM()->options()->get('account_tab_delete') ) {
				$account_settings['um-delete_account_password_requires'] = array(
					'label' => __( 'Account deletion password requires', 'ultimate-member' ),
					'value' => UM()->options()->get('delete_account_password_requires') ? $labels['yes'] : $labels['no'],
				);
				if ( 1 == UM()->options()->get('delete_account_password_requires') ) {
					$account_settings['um-delete_account_text'] = array(
						'label' => __( 'Account Deletion Text', 'ultimate-member' ),
						'value' => stripslashes( UM()->options()->get('delete_account_text') ),
					);
				} else {
					$account_settings['um-delete_account_no_pass_required_text'] = array(
						'label' => __( 'Account Deletion Text', 'ultimate-member' ),
						'value' => stripslashes( UM()->options()->get('delete_account_no_pass_required_text') ),
					);
				}

			}

			// Uploads settings
			$profile_sizes_list = '';
			$profile_sizes      = UM()->options()->get( 'photo_thumb_sizes' );
			if ( ! empty( $profile_sizes ) ) {
				foreach ( $profile_sizes as $size ) {
					$profile_sizes_list = empty ( $profile_sizes_list ) ? $size : $profile_sizes_list . ', ' . $size;
				}
			}
			$cover_sizes_list = '';
			$cover_sizes      = UM()->options()->get( 'cover_thumb_sizes' );
			if ( ! empty( $cover_sizes ) ) {
				foreach ( $cover_sizes as $size ) {
					$cover_sizes_list = empty ( $cover_sizes_list ) ? $size : $cover_sizes_list . ', ' . $size;
				}
			}
			$uploads_settings = array(
				'um-profile_photo_max_size'    => array(
					'label' => __( 'Profile Photo Maximum File Size (bytes)', 'ultimate-member' ),
					'value' => UM()->options()->get('profile_photo_max_size'),
				),
				'um-cover_min_width'           => array(
					'label' => __( 'Cover Photo Minimum Width (px)', 'ultimate-member' ),
					'value' => UM()->options()->get('cover_min_width'),
				),
				'um-cover_photo_max_size'      => array(
					'label' => __( 'Cover Photo Maximum File Size (bytes)', 'ultimate-member' ),
					'value' => UM()->options()->get('cover_photo_max_size'),
				),
				'um-photo_thumb_sizes'         => array(
					'label' => __( 'Profile Photo Thumbnail Sizes (px)', 'ultimate-member' ),
					'value' => $profile_sizes_list,
				),
				'um-cover_thumb_sizes'         => array(
					'label' => __( 'Cover Photo Thumbnail Sizes (px)', 'ultimate-member' ),
					'value' => $cover_sizes_list,
				),
				'um-image_orientation_by_exif' => array(
					'label' => __( 'Change image orientation', 'ultimate-member' ),
					'value' => UM()->options()->get('image_orientation_by_exif') ? $labels['yes'] : $labels['no'],
				),
				'um-image_compression'         => array(
					'label' => __( 'Image Quality', 'ultimate-member' ),
					'value' => UM()->options()->get('image_compression'),
				),
				'um-image_max_width'           => array(
					'label' => __( 'Image Upload Maximum Width (px)', 'ultimate-member' ),
					'value' => UM()->options()->get('image_max_width'),
				),
				'um-profile_photosize'         => array(
					'label' => __( 'Profile Photo Size', 'ultimate-member' ),
					'value' => UM()->options()->get('profile_photosize'),
				),
				'um-profile_coversize'         => array(
					'label' => __( 'Profile Cover Size', 'ultimate-member' ),
					'value' => UM()->options()->get('profile_coversize'),
				),
				'um-profile_cover_ratio'       => array(
					'label' => __( 'Profile Cover Ratio', 'ultimate-member' ),
					'value' => UM()->options()->get('profile_cover_ratio'),
				),
			);

			// Content Restriction settings
			$restricted_posts      = UM()->options()->get( 'restricted_access_post_metabox' );
			$restricted_posts_list = '';
			if ( ! empty( $restricted_posts ) ) {
				foreach ( $restricted_posts as $key => $posts ) {
					$restricted_posts_list = empty ( $restricted_posts_list ) ? $key : $restricted_posts_list . ', ' . $key;
				}
			}
			$restricted_taxonomy      = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
			$restricted_taxonomy_list = '';
			if ( ! empty( $restricted_taxonomy ) ) {
				foreach ( $restricted_taxonomy as $key => $posts ) {
					$restricted_taxonomy_list = empty ( $restricted_taxonomy_list ) ? $key : $restricted_taxonomy_list . ', ' . $key;
				}
			}

			$restrict_settings = array(
				'um-restricted_access_post_metabox'     => array(
					'label' => __( 'Enable the "Content Restriction" settings for post types', 'ultimate-member' ),
					'value' => $restricted_posts_list,
				),
				'um-restricted_access_taxonomy_metabox' => array(
					'label' => __( 'Enable the "Content Restriction" settings for post types', 'ultimate-member' ),
					'value' => $restricted_taxonomy_list,
				),
				'um-accessible'                         => array(
					'label' => __( 'Global Site Access', 'ultimate-member' ),
					'value' => UM()->options()->get('accessible') == 0 ? __( 'Site accessible to Everyone', 'ultimate-member' ) : __( 'Site accessible to Logged In Users', 'ultimate-member' ),
				),
			);

			if ( 2 == UM()->options()->get('accessible') ) {
				$exclude_uris = UM()->options()->get( 'access_exclude_uris' );
				$exclude_uris_list = '';
				if ( ! empty( $exclude_uris ) ) {
					foreach ( $exclude_uris as $key => $url ) {
						$exclude_uris_list = empty ( $exclude_uris_list ) ? $url : $exclude_uris_list . ', ' . $url;
					}
				}
				$restrict_settings['um-access_redirect']          = array(
					'label' => __( 'Custom Redirect URL', 'ultimate-member' ),
					'value' => UM()->options()->get('access_redirect'),
				);
				$restrict_settings['um-access_exclude_uris']      = array(
					'label' => __( 'Account Deletion Text', 'ultimate-member' ),
					'value' => $exclude_uris_list,
				);
				$restrict_settings['um-home_page_accessible']     = array(
					'label' => __( 'Allow Homepage to be accessible', 'ultimate-member' ),
					'value' => UM()->options()->get('home_page_accessible') ? $labels['yes'] : $labels['no'],
				);
				$restrict_settings['um-category_page_accessible'] = array(
					'label' => __( 'Allow Category pages to be accessible', 'ultimate-member' ),
					'value' => UM()->options()->get('category_page_accessible') ? $labels['yes'] : $labels['no'],
				);
			}

			$restrict_settings['um-restricted_post_title_replace']    = array(
				'label' => __( 'Restricted Content Titles', 'ultimate-member' ),
				'value' => UM()->options()->get('restricted_post_title_replace') ? $labels['yes'] : $labels['no'],
			);

			if ( 1 == UM()->options()->get('restricted_post_title_replace') ) {
				$restrict_settings['um-restricted_access_post_title'] = array(
					'label' => __( 'Restricted Content Title Text', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get('restricted_access_post_title') ),
				);
			}

			$restrict_settings['um-restricted_access_message']        = array(
				'label' => __( 'Restricted Access Message', 'ultimate-member' ),
				'value' => stripslashes( UM()->options()->get('restricted_access_message') ),
			);
			$restrict_settings['um-restricted_blocks']                = array(
				'label' => __( 'Enable the "Content Restriction" settings for the Gutenberg Blocks', 'ultimate-member' ),
				'value' => UM()->options()->get('restricted_blocks') ? $labels['yes'] : $labels['no'],
			);

			if ( 1 == UM()->options()->get('restricted_blocks') ) {
				$restrict_settings['um-restricted_block_message'] = array(
					'label' => __( 'Restricted Access Block Message', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get('restricted_block_message') ),
				);
			}

			// Access other settings
			$blocked_emails = str_replace( '<br />', ', ', nl2br( UM()->options()->get('blocked_emails') ) );
			$blocked_words  = str_replace( '<br />', ', ', nl2br( UM()->options()->get('blocked_words') ) );
			$access_other_settings = array(
				'um-blocked_emails'              => array(
					'label' => __( 'Blocked Email Addresses', 'ultimate-member' ),
					'value' => stripslashes( $blocked_emails ),
				),
				'um-blocked_words'               => array(
					'label' => __( 'Banned Usernames', 'ultimate-member' ),
					'value' => stripslashes( $blocked_words ),
				),
				'um-enable_reset_password_limit' => array(
					'label' => __( 'Password reset limit', 'ultimate-member' ),
					'value' => UM()->options()->get('enable_reset_password_limit') ? $labels['yes'] : $labels['no'],
				),
			);

			if ( 1 == UM()->options()->get('enable_reset_password_limit') ) {
				$access_other_settings['um-reset_password_limit_number'] = array(
					'label' => __( 'Enter password reset limit', 'ultimate-member' ),
					'value' => UM()->options()->get('reset_password_limit_number'),
				);
			}

			// Email settings
			$email_settings = array(
				'um-admin_email'    => array(
					'label' => __( 'Admin E-mail Address', 'ultimate-member' ),
					'value' => UM()->options()->get('admin_email'),
				),
				'um-mail_from'      => array(
					'label' => __( 'Mail appears from', 'ultimate-member' ),
					'value' => UM()->options()->get('mail_from'),
				),
				'um-mail_from_addr' => array(
					'label' => __( 'Mail appears from address', 'ultimate-member' ),
					'value' => UM()->options()->get('mail_from_addr'),
				),
				'um-email_html'     => array(
					'label' => __( 'Use HTML for E-mails?', 'ultimate-member' ),
					'value' => UM()->options()->get('email_html') ? $labels['yes'] : $labels['no'],
				),
			);

			$emails      = UM()->config()->get( 'email_notifications' );
			foreach ( $emails as $key => $email ) {
				if ( 1 == UM()->options()->get( $key . '_on' ) ) {
					$email_settings['um-' . $key ] = array(
						'label' => $email['title'] . __( ' Subject', 'ultimate-member' ),
						'value' => UM()->options()->get( $key . '_sub'),
					);

					$email_settings['um-theme_' . $key ] = array(
						'label' => __( 'Template ', 'ultimate-member' ) . $email['title'] . __( ' in theme?', 'ultimate-member' ),
						'value' => '' != locate_template( array( 'ultimate-member/email/' . $key . '.php' ) ) ? $labels['yes'] : $labels['no'],
					);
				}
			}

			// Misc settings
			$misc_settings = array(
				'um-form_asterisk'                   => array(
					'label' => __( 'Show an asterisk for required fields', 'ultimate-member' ),
					'value' => UM()->options()->get('form_asterisk') ? $labels['yes'] : $labels['no'],
				),
				'um-profile_title'                   => array(
					'label' => __( 'User Profile Title', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get('profile_title') ),
				),
				'um-profile_desc'                    => array(
					'label' => __( 'User Profile Dynamic Meta Description', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get('profile_desc') ),
				),
				'um-um_profile_object_cache_stop'    => array(
					'label' => __( 'Disable Cache User Profile', 'ultimate-member' ),
					'value' => UM()->options()->get('um_profile_object_cache_stop') ? $labels['yes'] : $labels['no'],
				),
				'um-enable_blocks'                   => array(
					'label' => __( 'Enable Gutenberg Blocks', 'ultimate-member' ),
					'value' => UM()->options()->get('enable_blocks') ? $labels['yes'] : $labels['no'],
				),
				'um-disable_restriction_pre_queries' => array(
					'label' => __( 'Disable pre-queries for restriction content logic (advanced)', 'ultimate-member' ),
					'value' => UM()->options()->get('disable_restriction_pre_queries') ? $labels['yes'] : $labels['no'],
				),
				'um-uninstall_on_delete'             => array(
					'label' => __( 'Remove Data on Uninstall?', 'ultimate-member' ),
					'value' => UM()->options()->get('uninstall_on_delete') ? $labels['yes'] : $labels['no'],
				),
			);


			$info['ultimate-member']['fields'] = array_merge( $info['ultimate-member']['fields'], $pages_settings, $user_settings, $account_settings, $uploads_settings, $restrict_settings, $access_other_settings, $email_settings, $misc_settings );

			return $info;
		}
	}
}
