<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_Site_Health' ) ) {

	/**
	 * Class Admin_Settings
	 * @package um\admin\core
	 */
	class Admin_Site_Health {

		/**
		 * Admin_Settings constructor.
		 */
		public function __construct() {
			add_filter( 'debug_information', array( $this, 'debug_information' ), 20, 1 );
		}

		/**
		 * Add our data to Site Health information.
		 *
		 * @since 2.6.12
		 *
		 * @param array $info The Site Health information.
		 *
		 * @return array The updated Site Health information.
		 */
		public function debug_information( $info ) {
			$labels = array(
				'yes'     => __( 'Yes', 'ultimate-member' ),
				'no'      => __( 'No', 'ultimate-member' ),
				'all'     => __( 'All', 'ultimate-member' ),
				'default' => __( 'Default', 'ultimate-member' ),
				'nopages' => __( 'No predefined page', 'ultimate-member' ),
			);

			$info['ultimate-member'] = array(
				'label'       => __( 'Ultimate Member', 'ultimate-member' ),
				'description' => __( 'This debug information for your Ultimate Member installation can assist you in getting support.', 'ultimate-member' ),
				'fields'      => array(),
			);

			// Pages settings
			$pages = apply_filters(
				'um_debug_information_pages',
				array(
					'User'           => null !== UM()->options()->get( 'core_user' ) ? get_the_title( UM()->options()->get( 'core_user' ) ) . ' (ID#' . UM()->options()->get( 'core_user' ) . ') | ' . get_permalink( UM()->options()->get( 'core_user' ) ) : $labels['nopages'],
					'Login'          => null !== UM()->options()->get( 'core_login' ) ? get_the_title( UM()->options()->get( 'core_login' ) ) . ' (ID#' . UM()->options()->get( 'core_login' ) . ') | ' . get_permalink( UM()->options()->get( 'core_login' ) ) : $labels['nopages'],
					'Register'       => null !== UM()->options()->get( 'core_register' ) ? get_the_title( UM()->options()->get( 'core_register' ) ) . ' (ID#' . UM()->options()->get( 'core_register' ) . ') | ' . get_permalink( UM()->options()->get( 'core_register' ) ) : $labels['nopages'],
					'Members'        => null !== UM()->options()->get( 'core_members' ) ? get_the_title( UM()->options()->get( 'core_members' ) ) . ' (ID#' . UM()->options()->get( 'core_members' ) . ') | ' . get_permalink( UM()->options()->get( 'core_members' ) ) : $labels['nopages'],
					'Logout'         => null !== UM()->options()->get( 'core_logout' ) ? get_the_title( UM()->options()->get( 'core_logout' ) ) . ' (ID#' . UM()->options()->get( 'core_logout' ) . ') | ' . get_permalink( UM()->options()->get( 'core_logout' ) ) : $labels['nopages'],
					'Account'        => null !== UM()->options()->get( 'core_account' ) ? get_the_title( UM()->options()->get( 'core_account' ) ) . ' (ID#' . UM()->options()->get( 'core_account' ) . ') | ' . get_permalink( UM()->options()->get( 'core_account' ) ) : $labels['nopages'],
					'Password reset' => null !== UM()->options()->get( 'core_password' ) ? get_the_title( UM()->options()->get( 'core_password-reset' ) ) . ' (ID#' . UM()->options()->get( 'core_password-reset' ) . ') | ' . get_permalink( UM()->options()->get( 'core_password-reset' ) ) : $labels['nopages'],
				)
			);

			$pages_settings = array(
				'um-pages' => array(
					'label' => __( 'Pages', 'ultimate-member' ),
					'value' => $pages,
				),
			);

			// User settings
			$permalink_base = array(
				'user_login' => __( 'Username', 'ultimate-member' ),
				'name'       => __( 'First and Last Name with \'.\'', 'ultimate-member' ),
				'name_dash'  => __( 'First and Last Name with \'-\'', 'ultimate-member' ),
				'name_plus'  => __( 'First and Last Name with \'+\'', 'ultimate-member' ),
				'user_id'    => __( 'User ID', 'ultimate-member' ),
			);
			$display_name   = array(
				'default'        => __( 'Default WP Display Name', 'ultimate-member' ),
				'nickname'       => __( 'Nickname', 'ultimate-member' ),
				'username'       => __( 'Username', 'ultimate-member' ),
				'full_name'      => __( 'First name & last name', 'ultimate-member' ),
				'sur_name'       => __( 'Last name & first name', 'ultimate-member' ),
				'initial_name'   => __( 'First name & first initial of last name', 'ultimate-member' ),
				'initial_name_f' => __( 'First initial of first name & last name', 'ultimate-member' ),
				'first_name'     => __( 'First name only', 'ultimate-member' ),
				'field'          => __( 'Custom field(s)', 'ultimate-member' ),
			);

			$user_settings = array(
				'um-permalink_base'              => array(
					'label' => __( 'Profile Permalink Base', 'ultimate-member' ),
					'value' => isset( $permalink_base[ UM()->options()->get( 'permalink_base' ) ] ) ? $permalink_base[ UM()->options()->get( 'permalink_base' ) ] : $labels['no'],
				),
				'um-display_name'                => array(
					'label' => __( 'User Display Name', 'ultimate-member' ),
					'value' => isset( $display_name[ UM()->options()->get( 'display_name' ) ] ) ? $display_name[ UM()->options()->get( 'display_name' ) ] : $labels['no'],
				),
				'um-author_redirect'             => array(
					'label' => __( 'Automatically redirect author page to their profile?', 'ultimate-member' ),
					'value' => UM()->options()->get( 'author_redirect' ) ? $labels['yes'] : $labels['no'],
				),
				'um-members_page'                => array(
					'label' => __( 'Enable Members Directory', 'ultimate-member' ),
					'value' => UM()->options()->get( 'members_page' ) ? $labels['yes'] : $labels['no'],
				),
				'um-toggle_password'             => array(
					'label' => __( 'Show/hide password button', 'ultimate-member' ),
					'value' => UM()->options()->get( 'toggle_password' ) ? $labels['yes'] : $labels['no'],
				),
				'um-require_strongpass'          => array(
					'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
					'value' => UM()->options()->get( 'require_strongpass' ) ? $labels['yes'] : $labels['no'],
				),
				'um-password_min_chars'          => array(
					'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
					'value' => UM()->options()->get( 'password_min_chars' ),
				),
				'um-password_max_chars'          => array(
					'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
					'value' => UM()->options()->get( 'password_max_chars' ),
				),
				'um-profile_noindex'             => array(
					'label' => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_noindex' ) ? $labels['yes'] : $labels['no'],
				),
				'um-activation_link_expiry_time' => array(
					'label' => __( 'Activation link lifetime', 'ultimate-member' ),
					'value' => UM()->options()->get( 'activation_link_expiry_time' ),
				),
				'um-use_gravatars'               => array(
					'label' => __( 'Use Gravatars?', 'ultimate-member' ),
					'value' => UM()->options()->get( 'use_gravatars' ) ? $labels['yes'] : $labels['no'],
				),
			);

			if ( 1 === absint( UM()->options()->get( 'use_gravatars' ) ) ) {
				$gravatar_options = array(
					'default'   => __( 'Default', 'ultimate-member' ),
					'404'       => __( '404 ( File Not Found response )', 'ultimate-member' ),
					'mm'        => __( 'Mystery Man', 'ultimate-member' ),
					'identicon' => __( 'Identicon', 'ultimate-member' ),
					'monsterid' => __( 'Monsterid', 'ultimate-member' ),
					'wavatar'   => __( 'Wavatar', 'ultimate-member' ),
					'retro'     => __( 'Retro', 'ultimate-member' ),
					'blank'     => __( 'Blank ( a transparent PNG image )', 'ultimate-member' ),
				);

				$user_settings['um-use_um_gravatar_default_builtin_image'] = array(
					'label' => __( 'Use Gravatar builtin image', 'ultimate-member' ),
					'value' => $gravatar_options[ UM()->options()->get( 'use_um_gravatar_default_builtin_image' ) ],
				);
				if ( 'default' === UM()->options()->get( 'use_um_gravatar_default_builtin_image' ) ) {
					$user_settings['um-use_um_gravatar_default_image'] = array(
						'label' => __( 'Use Default plugin avatar as Gravatar\'s Default avatar', 'ultimate-member' ),
						'value' => UM()->options()->get( 'use_um_gravatar_default_image' ) ? $labels['yes'] : $labels['no'],
					);
				}
			}

			// Account settings
			$account_settings = array(
				'um-account_tab_password'                 => array(
					'label' => __( 'Password Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_tab_password' ) ? $labels['yes'] : $labels['no'],
				),
				'um-account_tab_privacy'                  => array(
					'label' => __( 'Privacy Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_tab_privacy' ) ? $labels['yes'] : $labels['no'],
				),
				'um-account_tab_notifications'            => array(
					'label' => __( 'Notifications Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_tab_notifications' ) ? $labels['yes'] : $labels['no'],
				),
				'um-account_tab_delete'                   => array(
					'label' => __( 'Delete Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_tab_delete' ) ? $labels['yes'] : $labels['no'],
				),
				'um-delete_account_text'                  => array(
					'label' => __( 'Account Deletion Custom Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'delete_account_text' ),
				),
				'um-delete_account_no_pass_required_text' => array(
					'label' => __( 'Account Deletion without password Custom Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'delete_account_no_pass_required_text' ),
				),
				'um-account_name'                         => array(
					'label' => __( 'Add a First & Last Name fields', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_name' ) ? $labels['yes'] : $labels['no'],
				),
			);

			if ( 1 === absint( UM()->options()->get( 'account_name' ) ) ) {
				$account_settings['um-account_name_disable'] = array(
					'label' => __( 'Disable First & Last name field editing', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_name_disable' ) ? $labels['yes'] : $labels['no'],
				);
				$account_settings['um-account_name_require'] = array(
					'label' => __( 'Require First & Last Name', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_name_require' ) ? $labels['yes'] : $labels['no'],
				);
			}

			$account_settings['um-account_hide_in_directory'] = array(
				'label' => __( 'Allow users to hide their profiles from directory', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_hide_in_directory' ) ? $labels['yes'] : $labels['no'],
			);

			if ( 1 === absint( UM()->options()->get( 'account_name' ) ) ) {
				$account_settings['um-account_hide_in_directory_default'] = array(
					'label' => __( 'Hide profiles from directory by default', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_hide_in_directory_default' ),
				);
			}

			// Uploads settings
			$profile_sizes_list = '';
			$profile_sizes      = UM()->options()->get( 'photo_thumb_sizes' );
			if ( ! empty( $profile_sizes ) ) {
				foreach ( $profile_sizes as $size ) {
					$profile_sizes_list = empty( $profile_sizes_list ) ? $size : $profile_sizes_list . ', ' . $size;
				}
			}
			$cover_sizes_list = '';
			$cover_sizes      = UM()->options()->get( 'cover_thumb_sizes' );
			if ( ! empty( $cover_sizes ) ) {
				foreach ( $cover_sizes as $size ) {
					$cover_sizes_list = empty( $cover_sizes_list ) ? $size : $cover_sizes_list . ', ' . $size;
				}
			}
			$uploads_settings = array(
				'um-profile_photo_max_size'    => array(
					'label' => __( 'Profile Photo Maximum File Size (bytes)', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_photo_max_size' ),
				),
				'um-cover_photo_max_size'      => array(
					'label' => __( 'Cover Photo Maximum File Size (bytes)', 'ultimate-member' ),
					'value' => UM()->options()->get( 'cover_photo_max_size' ),
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
					'value' => UM()->options()->get( 'image_orientation_by_exif' ) ? $labels['yes'] : $labels['no'],
				),
				'um-image_compression'         => array(
					'label' => __( 'Image Quality', 'ultimate-member' ),
					'value' => UM()->options()->get( 'image_compression' ),
				),
				'um-image_max_width'           => array(
					'label' => __( 'Image Upload Maximum Width (px)', 'ultimate-member' ),
					'value' => UM()->options()->get( 'image_max_width' ),
				),
				'um-cover_min_width'           => array(
					'label' => __( 'Cover Photo Minimum Width (px)', 'ultimate-member' ),
					'value' => UM()->options()->get( 'cover_min_width' ),
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
				'um-accessible' => array(
					'label' => __( 'Global Site Access', 'ultimate-member' ),
					'value' => 0 === UM()->options()->get( 'accessible' ) ? __( 'Site accessible to Everyone', 'ultimate-member' ) : __( 'Site accessible to Logged In Users', 'ultimate-member' ),
				),
			);

			if ( 2 === absint( UM()->options()->get( 'accessible' ) ) ) {
				$exclude_uris      = UM()->options()->get( 'access_exclude_uris' );
				$exclude_uris_list = '';
				if ( ! empty( $exclude_uris ) ) {
					foreach ( $exclude_uris as $key => $url ) {
						$exclude_uris_list = empty( $exclude_uris_list ) ? $url : $exclude_uris_list . ', ' . $url;
					}
				}
				$restrict_settings['um-access_redirect']          = array(
					'label' => __( 'Custom Redirect URL', 'ultimate-member' ),
					'value' => UM()->options()->get( 'access_redirect' ),
				);
				$restrict_settings['um-access_exclude_uris']      = array(
					'label' => __( 'Account Deletion Text', 'ultimate-member' ),
					'value' => $exclude_uris_list,
				);
				$restrict_settings['um-home_page_accessible']     = array(
					'label' => __( 'Allow Homepage to be accessible', 'ultimate-member' ),
					'value' => UM()->options()->get( 'home_page_accessible' ) ? $labels['yes'] : $labels['no'],
				);
				$restrict_settings['um-category_page_accessible'] = array(
					'label' => __( 'Allow Category pages to be accessible', 'ultimate-member' ),
					'value' => UM()->options()->get( 'category_page_accessible' ) ? $labels['yes'] : $labels['no'],
				);
			}

			$restrict_settings['um-restricted_post_title_replace'] = array(
				'label' => __( 'Restricted Content Titles', 'ultimate-member' ),
				'value' => UM()->options()->get( 'restricted_post_title_replace' ) ? $labels['yes'] : $labels['no'],
			);
			if ( 1 === absint( UM()->options()->get( 'restricted_post_title_replace' ) ) ) {
				$restrict_settings['um-restricted_access_post_title'] = array(
					'label' => __( 'Restricted Content Title Text', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get( 'restricted_access_post_title' ) ),
				);
			}

			$restrict_settings['um-restricted_access_message'] = array(
				'label' => __( 'Restricted Access Message', 'ultimate-member' ),
				'value' => stripslashes( UM()->options()->get( 'restricted_access_message' ) ),
			);
			$restrict_settings['um-restricted_blocks']         = array(
				'label' => __( 'Enable the "Content Restriction" settings for the Gutenberg Blocks', 'ultimate-member' ),
				'value' => UM()->options()->get( 'restricted_blocks' ) ? $labels['yes'] : $labels['no'],
			);
			if ( 1 === absint( UM()->options()->get( 'restricted_blocks' ) ) ) {
				$restrict_settings['um-restricted_block_message'] = array(
					'label' => __( 'Restricted Access Block Message', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get( 'restricted_block_message' ) ),
				);
			}
			$restrict_settings['um-restricted_access_post_metabox']     = array(
				'label' => __( 'Enable the "Content Restriction" settings for post types', 'ultimate-member' ),
				'value' => $restricted_posts_list,
			);
			$restrict_settings['um-restricted_access_taxonomy_metabox'] = array(
				'label' => __( 'Enable the "Content Restriction" settings for taxonomies', 'ultimate-member' ),
				'value' => $restricted_taxonomy_list,
			);

			// Access other settings
			$blocked_emails    = str_replace( '<br />', ', ', nl2br( UM()->options()->get( 'blocked_emails' ) ) );
			$blocked_words     = str_replace( '<br />', ', ', nl2br( UM()->options()->get( 'blocked_words' ) ) );
			$allowed_callbacks = str_replace( '<br />', ', ', nl2br( UM()->options()->get( 'allowed_choice_callbacks' ) ) );

			$access_other_settings = array(
				'um-enable_reset_password_limit' => array(
					'label' => __( 'Enable the Reset Password Limit?', 'ultimate-member' ),
					'value' => UM()->options()->get( 'enable_reset_password_limit' ) ? $labels['yes'] : $labels['no'],
				),
			);
			if ( 1 === absint( UM()->options()->get( 'enable_reset_password_limit' ) ) ) {
				$access_other_settings['um-reset_password_limit_number'] = array(
					'label' => __( 'Reset Password Limit ', 'ultimate-member' ),
					'value' => UM()->options()->get( 'reset_password_limit_number' ),
				);
			}
			$access_other_settings['um-change_password_request_limit'] = array(
				'label' => __( 'Change Password request limit ', 'ultimate-member' ),
				'value' => UM()->options()->get( 'change_password_request_limit' ),
			);
			$access_other_settings['um-blocked_emails']                = array(
				'label' => __( 'Blocked Email Addresses', 'ultimate-member' ),
				'value' => stripslashes( $blocked_emails ),
			);
			$access_other_settings['um-blocked_words']                 = array(
				'label' => __( 'Banned Usernames', 'ultimate-member' ),
				'value' => stripslashes( $blocked_words ),
			);
			$access_other_settings['um-allowed_choice_callbacks']      = array(
				'label' => __( 'Allowed Choice Callbacks', 'ultimate-member' ),
				'value' => stripslashes( $allowed_callbacks ),
			);
			$access_other_settings['um-allow_url_redirect_confirm']    = array(
				'label' => __( 'Allow external link redirect confirm ', 'ultimate-member' ),
				'value' => UM()->options()->get( 'allow_url_redirect_confirm' ) ? $labels['yes'] : $labels['no'],
			);

			// Email settings
			$email_settings = array(
				'um-admin_email'    => array(
					'label' => __( 'Admin E-mail Address', 'ultimate-member' ),
					'value' => UM()->options()->get( 'admin_email' ),
				),
				'um-mail_from'      => array(
					'label' => __( 'Mail appears from', 'ultimate-member' ),
					'value' => UM()->options()->get( 'mail_from' ),
				),
				'um-mail_from_addr' => array(
					'label' => __( 'Mail appears from address', 'ultimate-member' ),
					'value' => UM()->options()->get( 'mail_from_addr' ),
				),
				'um-email_html'     => array(
					'label' => __( 'Use HTML for E-mails?', 'ultimate-member' ),
					'value' => UM()->options()->get( 'email_html' ) ? $labels['yes'] : $labels['no'],
				),
			);

			$emails = UM()->config()->email_notifications;
			foreach ( $emails as $key => $email ) {
				if ( 1 == UM()->options()->get( $key . '_on' ) ) {
					$email_settings['um-' . $key ] = array(
						'label' => $email['title'] . __( ' Subject', 'ultimate-member' ),
						'value' => UM()->options()->get( $key . '_sub'),
					);

					$email_settings[ 'um-theme_' . $key ] = array(
						'label' => __( 'Template ', 'ultimate-member' ) . $email['title'] . __( ' in theme?', 'ultimate-member' ),
						'value' => '' !== locate_template( array( 'ultimate-member/emails/' . $key . '.php' ) ) ? $labels['yes'] : $labels['no'],
					);
				}
			}


			$misc_settings = array();

			$info['ultimate-member']['fields'] = array_merge( $info['ultimate-member']['fields'], $pages_settings, $user_settings, $account_settings, $uploads_settings, $restrict_settings, $access_other_settings, $email_settings, $misc_settings );

			return $info;
		}
	}
}
