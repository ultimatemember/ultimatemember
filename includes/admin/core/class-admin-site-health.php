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
				if ( 1 === absint( UM()->options()->get( $key . '_on' ) ) ) {
					$email_settings[ 'um-' . $key ] = array(
						'label' => $email['title'] . __( ' Subject', 'ultimate-member' ),
						'value' => UM()->options()->get( $key . '_sub' ),
					);

					$email_settings[ 'um-theme_' . $key ] = array(
						'label' => __( 'Template ', 'ultimate-member' ) . $email['title'] . __( ' in theme?', 'ultimate-member' ),
						'value' => '' !== locate_template( array( 'ultimate-member/emails/' . $key . '.php' ) ) ? $labels['yes'] : $labels['no'],
					);
				}
			}

			// Appearance settings
			$profile_icons_options       = array(
				'field' => __( 'Show inside text field', 'ultimate-member' ),
				'label' => __( 'Show with label', 'ultimate-member' ),
				'off'   => __( 'Turn off', 'ultimate-member' ),
			);
			$profile_header_menu_options = array(
				'bc' => __( 'Bottom of Icon', 'ultimate-member' ),
				'lc' => __( 'Left of Icon (right for RTL)', 'ultimate-member' ),
			);
			$register_align_options      = array(
				'center' => __( 'Centered', 'ultimate-member' ),
				'left'   => __( 'Left aligned', 'ultimate-member' ),
				'right'  => __( 'Right aligned', 'ultimate-member' ),
			);

			$appearance_settings = array(
				'um-profile_template'         => array(
					'label' => __( 'Profile Default Template', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_template' ),
				),
				'um-profile_max_width'        => array(
					'label' => __( 'Profile Maximum Width', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_max_width' ),
				),
				'um-profile_area_max_width'   => array(
					'label' => __( 'Profile Area Maximum Width', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_area_max_width' ),
				),
				'um-profile_icons'            => array(
					'label' => __( 'Profile Field Icons', 'ultimate-member' ),
					'value' => $profile_icons_options[ UM()->options()->get( 'profile_icons' ) ],
				),
				'um-profile_primary_btn_word' => array(
					'label' => __( 'Profile Primary Button Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_primary_btn_word' ),
				),
				'um-profile_secondary_btn'    => array(
					'label' => __( 'Profile Secondary Button', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_secondary_btn' ) ? $labels['yes'] : $labels['no'],
				),
			);
			if ( 1 === absint( UM()->options()->get( 'profile_secondary_btn' ) ) ) {
				$appearance_settings['um-profile_secondary_btn_word'] = array(
					'label' => __( 'Profile Secondary Button Text ', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_secondary_btn_word' ),
				);
			}
			$appearance_settings['um-default_avatar']               = array(
				'label' => __( 'Default Profile Photo', 'ultimate-member' ),
				'value' => UM()->options()->get( 'default_avatar' )['url'],
			);
			$appearance_settings['um-default_cover']                = array(
				'label' => __( 'Default Cover Photo', 'ultimate-member' ),
				'value' => UM()->options()->get( 'default_cover' )['url'],
			);
			$appearance_settings['um-disable_profile_photo_upload'] = array(
				'label' => __( 'Disable Profile Photo Upload', 'ultimate-member' ),
				'value' => UM()->options()->get( 'disable_profile_photo_upload' ) ? $labels['yes'] : $labels['no'],
			);
			$appearance_settings['um-profile_photosize']            = array(
				'label' => __( 'Profile Photo Size', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_photosize' ) . 'x' . UM()->options()->get( 'profile_photosize' ) . 'px',
			);
			$appearance_settings['um-profile_cover_enabled']        = array(
				'label' => __( 'Profile Cover Photos', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_cover_enabled' ) ? $labels['yes'] : $labels['no'],
			);
			if ( 1 === absint( UM()->options()->get( 'profile_cover_enabled' ) ) ) {
				$appearance_settings['um-profile_coversize']   = array(
					'label' => __( 'Profile Cover Size', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_coversize' ) . 'px',
				);
				$appearance_settings['um-profile_cover_ratio'] = array(
					'label' => __( 'Profile Cover Ratio', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_cover_ratio' ),
				);
			}
			$appearance_settings['um-profile_show_metaicon']     = array(
				'label' => __( 'Profile Header Meta Text Icon', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_show_metaicon' ) ? $labels['yes'] : $labels['no'],
			);
			$appearance_settings['um-profile_show_name']         = array(
				'label' => __( 'Show display name in profile header', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_show_name' ) ? $labels['yes'] : $labels['no'],
			);
			$appearance_settings['um-profile_show_social_links'] = array(
				'label' => __( 'Show social links in profile header', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_show_social_links' ) ? $labels['yes'] : $labels['no'],
			);
			$appearance_settings['um-profile_show_bio']          = array(
				'label' => __( 'Show user description in header', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_show_bio' ) ? $labels['yes'] : $labels['no'],
			);
			$appearance_settings['um-profile_show_html_bio']     = array(
				'label' => __( 'Enable HTML support for user description', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_show_html_bio' ) ? $labels['yes'] : $labels['no'],
			);
			$appearance_settings['um-profile_bio_maxchars']      = array(
				'label' => __( 'User description maximum chars', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_bio_maxchars' ),
			);
			$appearance_settings['um-profile_header_menu']       = array(
				'label' => __( 'Profile Header Menu Position', 'ultimate-member' ),
				'value' => $profile_header_menu_options[ UM()->options()->get( 'profile_header_menu' ) ],
			);
			$appearance_settings['um-profile_empty_text']        = array(
				'label' => __( 'Show a custom message if profile is empty', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_empty_text' ) ? $labels['yes'] : $labels['no'],
			);
			if ( 1 === absint( UM()->options()->get( 'profile_empty_text' ) ) ) {
				$appearance_settings['um-profile_empty_text_emo'] = array(
					'label' => __( 'Show the emoticon', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_empty_text_emo' ),
				);
			}
			$appearance_settings['um-profile_menu'] = array(
				'label' => __( 'Enable profile menu', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_menu' ) ? $labels['yes'] : $labels['no'],
			);
			if ( 1 === absint( UM()->options()->get( 'profile_menu' ) ) ) {
				/**
				 * Filters a privacy list extend.
				 *
				 * @since 2.6.13
				 * @hook um_profile_tabs_privacy_list
				 *
				 * @param {array} $privacy_option Add options for profile tabs' privacy.
				 *
				 * @return {array} Options for profile tabs' privacy.
				 *
				 * @example <caption>Add options for profile tabs' privacy.</caption>
				 * function um_profile_menu_link_attrs( $privacy_option ) {
				 *     // your code here
				 *     return $privacy_option;
				 * }
				 * add_filter( 'um_profile_tabs_privacy_list', 'um_profile_tabs_privacy_list', 10, 1 );
				 */
				$privacy_option = apply_filters(
					'um_profile_tabs_privacy_list',
					array(
						0 => __( 'Anyone', 'ultimate-member' ),
						1 => __( 'Guests only', 'ultimate-member' ),
						2 => __( 'Members only', 'ultimate-member' ),
						3 => __( 'Only the owner', 'ultimate-member' ),
						4 => __( 'Only specific roles', 'ultimate-member' ),
						5 => __( 'Owner and specific roles', 'ultimate-member' ),
					)
				);

				$appearance_settings['um-profile_tab_main'] = array(
					'label' => __( 'About Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_tab_main' ) ? $labels['yes'] : $labels['no'],
				);
				if ( 1 === absint( UM()->options()->get( 'profile_tab_main' ) ) ) {
					$appearance_settings['um-profile_tab_main_privacy'] = array(
						'label' => __( 'Who can see About Tab?', 'ultimate-member' ),
						'value' => $privacy_option[ UM()->options()->get( 'profile_tab_main_privacy' ) ],
					);
				}
				$appearance_settings['um-profile_tab_posts'] = array(
					'label' => __( 'Posts Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_tab_posts' ) ? $labels['yes'] : $labels['no'],
				);
				if ( 1 === absint( UM()->options()->get( 'profile_tab_posts' ) ) ) {
					$appearance_settings['um-profile_tab_posts_privacy'] = array(
						'label' => __( 'Who can see Posts Tab?', 'ultimate-member' ),
						'value' => $privacy_option[ UM()->options()->get( 'profile_tab_posts_privacy' ) ],
					);
				}
				$appearance_settings['um-profile_tab_comments'] = array(
					'label' => __( 'Comments Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_tab_comments' ) ? $labels['yes'] : $labels['no'],
				);
				if ( 1 === absint( UM()->options()->get( 'profile_tab_comments' ) ) ) {
					$appearance_settings['um-profile_tab_comments_privacy'] = array(
						'label' => __( 'Who can see Comments Tab?', 'ultimate-member' ),
						'value' => $privacy_option[ UM()->options()->get( 'profile_tab_comments_privacy' ) ],
					);
				}
				/**
				 * Filters appearance settings for Site Health extend.
				 *
				 * @since 2.6.13
				 * @hook um_profile_tabs_site_health
				 *
				 * @param {array} $appearance_settings Appearance settings for Site Health.
				 *
				 * @return {array} Appearance settings for Site Health.
				 *
				 * @example <caption>Add options for appearance settings for Site Health.</caption>
				 * function um_profile_tabs_site_health( $appearance_settings ) {
				 *     // your code here
				 *     return $appearance_settings;
				 * }
				 * add_filter( 'um_profile_tabs_site_health', 'um_profile_tabs_site_health', 10, 1 );
				 */
				$appearance_settings = apply_filters( 'um_profile_tabs_site_health', $appearance_settings );

				/**
				 * Filters extend user profile tabs
				 *
				 * @since 2.6.13
				 * @hook um_profile_tabs
				 *
				 * @param {array} $tabs tabs list.
				 *
				 * @return {array} tabs list.
				 *
				 * @example <caption>Add options for profile tabs' privacy.</caption>
				 * function um_profile_tabs( $tabs ) {
				 *     // your code here
				 *     return $tabs;
				 * }
				 * add_filter( 'um_profile_tabs', 'um_profile_tabs', 10, 1 );
				 */
				$tabs_options = apply_filters(
					'um_profile_tabs',
					array(
						'main'     => array(
							'name' => __( 'About', 'ultimate-member' ),
							'icon' => 'um-faicon-user',
						),
						'posts'    => array(
							'name' => __( 'Posts', 'ultimate-member' ),
							'icon' => 'um-faicon-pencil',
						),
						'comments' => array(
							'name' => __( 'Comments', 'ultimate-member' ),
							'icon' => 'um-faicon-comment',
						),
					)
				);

				$appearance_settings['um-profile_menu_default_tab'] = array(
					'label' => __( 'Profile menu default tab', 'ultimate-member' ),
					'value' => $tabs_options[ UM()->options()->get( 'profile_menu_default_tab' ) ],
				);
				$appearance_settings['um-profile_menu_icons']       = array(
					'label' => __( 'Enable menu icons in desktop view', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_menu_icons' ) ? $labels['yes'] : $labels['no'],
				);
			}

			$appearance_settings['um-register_template']         = array(
				'label' => __( 'Registration Default Template', 'ultimate-member' ),
				'value' => UM()->options()->get( 'register_template' ),
			);
			$appearance_settings['um-register_max_width']        = array(
				'label' => __( 'Registration Maximum Width', 'ultimate-member' ),
				'value' => UM()->options()->get( 'register_max_width' ),
			);
			$appearance_settings['um-register_align']            = array(
				'label' => __( 'Registration Shortcode Alignment', 'ultimate-member' ),
				'value' => $register_align_options[ UM()->options()->get( 'register_align' ) ],
			);
			$appearance_settings['um-register_icons']            = array(
				'label' => __( 'Registration Field Icons', 'ultimate-member' ),
				'value' => $profile_icons_options[ UM()->options()->get( 'register_icons' ) ],
			);
			$appearance_settings['um-register_primary_btn_word'] = array(
				'label' => __( 'Registration Primary Button Text ', 'ultimate-member' ),
				'value' => UM()->options()->get( 'register_primary_btn_word' ),
			);
			$appearance_settings['um-register_secondary_btn']    = array(
				'label' => __( 'Registration Secondary Button', 'ultimate-member' ),
				'value' => UM()->options()->get( 'register_secondary_btn' ) ? $labels['yes'] : $labels['no'],
			);
			if ( 1 === absint( UM()->options()->get( 'register_secondary_btn' ) ) ) {
				$appearance_settings['um-register_secondary_btn_word'] = array(
					'label' => __( 'Registration Secondary Button Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'register_secondary_btn_word' ),
				);
				$appearance_settings['um-register_secondary_btn_url']  = array(
					'label' => __( 'Registration Secondary Button URL', 'ultimate-member' ),
					'value' => UM()->options()->get( 'register_secondary_btn_url' ),
				);
			}
			$appearance_settings['um-register_role'] = array(
				'label' => __( 'Registration Default Role', 'ultimate-member' ),
				'value' => ! empty( UM()->options()->get( 'register_role' ) ) ? UM()->options()->get( 'register_role' ) : __( 'Default', 'ultimate-member' ),
			);

			$appearance_settings['um-login_template']         = array(
				'label' => __( 'Login Default Template', 'ultimate-member' ),
				'value' => UM()->options()->get( 'login_template' ),
			);
			$appearance_settings['um-login_max_width']        = array(
				'label' => __( 'Login Maximum Width', 'ultimate-member' ),
				'value' => UM()->options()->get( 'login_max_width' ),
			);
			$appearance_settings['um-login_align']            = array(
				'label' => __( 'Login Shortcode Alignment', 'ultimate-member' ),
				'value' => $register_align_options[ UM()->options()->get( 'login_align' ) ],
			);
			$appearance_settings['um-login_icons']            = array(
				'label' => __( 'Login Field Icons', 'ultimate-member' ),
				'value' => $profile_icons_options[ UM()->options()->get( 'login_icons' ) ],
			);
			$appearance_settings['um-login_primary_btn_word'] = array(
				'label' => __( 'Login Primary Button Text', 'ultimate-member' ),
				'value' => UM()->options()->get( 'login_primary_btn_word' ),
			);
			$appearance_settings['um-login_secondary_btn']    = array(
				'label' => __( 'Login Secondary Button', 'ultimate-member' ),
				'value' => UM()->options()->get( 'login_secondary_btn' ) ? $labels['yes'] : $labels['no'],
			);
			if ( 1 === absint( UM()->options()->get( 'login_secondary_btn' ) ) ) {
				$appearance_settings['um-login_secondary_btn_word'] = array(
					'label' => __( 'Login Secondary Button Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'login_secondary_btn_word' ),
				);
				$appearance_settings['um-login_secondary_btn_url']  = array(
					'label' => __( 'Login Secondary Button URL', 'ultimate-member' ),
					'value' => UM()->options()->get( 'login_secondary_btn_url' ),
				);
			}
			$appearance_settings['um-login_forgot_pass_link'] = array(
				'label' => __( 'Login Forgot Password Link', 'ultimate-member' ),
				'value' => UM()->options()->get( 'login_forgot_pass_link' ) ? $labels['yes'] : $labels['no'],
			);
			$appearance_settings['um-login_show_rememberme']  = array(
				'label' => __( 'Show "Remember Me"', 'ultimate-member' ),
				'value' => UM()->options()->get( 'login_show_rememberme' ) ? $labels['yes'] : $labels['no'],
			);

			// Misc settings
			$misc_settings = array(
				'um-form_asterisk'                   => array(
					'label' => __( 'Show an asterisk for required fields', 'ultimate-member' ),
					'value' => UM()->options()->get( 'form_asterisk' ) ? $labels['yes'] : $labels['no'],
				),
				'um-profile_title'                   => array(
					'label' => __( 'User Profile Title', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get( 'profile_title' ) ),
				),
				'um-profile_desc'                    => array(
					'label' => __( 'User Profile Dynamic Meta Description', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get( 'profile_desc' ) ),
				),
				'um-um_profile_object_cache_stop'    => array(
					'label' => __( 'Disable Cache User Profile', 'ultimate-member' ),
					'value' => UM()->options()->get( 'um_profile_object_cache_stop' ) ? $labels['yes'] : $labels['no'],
				),
				'um-enable_blocks'                   => array(
					'label' => __( 'Enable Gutenberg Blocks', 'ultimate-member' ),
					'value' => UM()->options()->get( 'enable_blocks' ) ? $labels['yes'] : $labels['no'],
				),
				'um-rest_api_version'                => array(
					'label' => __( 'REST API version', 'ultimate-member' ),
					'value' => UM()->options()->get( 'rest_api_version' ),
				),
				'um-disable_restriction_pre_queries' => array(
					'label' => __( 'Disable pre-queries for restriction content logic (advanced)', 'ultimate-member' ),
					'value' => UM()->options()->get( 'disable_restriction_pre_queries' ) ? $labels['yes'] : $labels['no'],
				),
				'um-member_directory_own_table'      => array(
					'label' => __( 'Enable custom table for usermeta', 'ultimate-member' ),
					'value' => UM()->options()->get( 'member_directory_own_table' ) ? $labels['yes'] : $labels['no'],
				),
				'um-uninstall_on_delete'             => array(
					'label' => __( 'Remove Data on Uninstall?', 'ultimate-member' ),
					'value' => UM()->options()->get( 'uninstall_on_delete' ) ? $labels['yes'] : $labels['no'],
				),
			);

			// Secure settings
			$secure_settings = array(
				'um-banned_capabilities'        => array(
					'label' => __( 'Banned Administrative Capabilities', 'ultimate-member' ),
					'value' => implode(', ', UM()->options()->get( 'banned_capabilities' ) ),
				),
				'um-lock_register_forms'        => array(
					'label' => __( 'Lock All Register Forms', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get( 'lock_register_forms' ) ) ? $labels['yes'] : $labels['no'],
				),
				'um-display_login_form_notice'  => array(
					'label' => __( 'Display Login form notice to reset passwords', 'ultimate-member' ),
					'value' => UM()->options()->get( 'display_login_form_notice' ) ? $labels['yes'] : $labels['no'],
				),
				'um-secure_ban_admins_accounts' => array(
					'label' => __( 'Enable ban for administrative capabilities', 'ultimate-member' ),
					'value' => UM()->options()->get( 'secure_ban_admins_accounts' ) ? $labels['yes'] : $labels['no'],
				),
			);
			if ( 1 === absint( UM()->options()->get( 'secure_ban_admins_accounts' ) ) ) {
				$secure_settings['um-secure_notify_admins_banned_accounts'] = array(
					'label' => __( 'Notify Administrators', 'ultimate-member' ),
					'value' => UM()->options()->get( 'secure_notify_admins_banned_accounts' ) ? $labels['yes'] : $labels['no'],
				);
				if ( 1 === absint( UM()->options()->get( 'secure_notify_admins_banned_accounts' ) ) ) {
					$secure_notify_admins_banned_accounts_options = array(
						'instant' => __( 'Send Immediately', 'ultimate-member' ),
						'hourly'  => __( 'Hourly', 'ultimate-member' ),
						'daily'   => __( 'Daily', 'ultimate-member' ),
					);

					$secure_settings['um-secure_notify_admins_banned_accounts__interval'] = array(
						'label' => __( 'Notification Schedule', 'ultimate-member' ),
						'value' => $secure_notify_admins_banned_accounts_options[ UM()->options()->get( 'secure_notify_admins_banned_accounts__interval' ) ],
					);
				}
			}

			$secure_allowed_redirect_hosts = UM()->options()->get( 'secure_allowed_redirect_hosts' );
			$secure_allowed_redirect_hosts = explode(PHP_EOL, $secure_allowed_redirect_hosts );

			$secure_settings['um-secure_allowed_redirect_hosts'] = array(
				'label' => __( 'Allowed hosts for safe redirect', 'ultimate-member' ),
				'value' => $secure_allowed_redirect_hosts,
			);

			// Licenses settings
			$license_settings = array(
				'um-licenses' => array(
					'label' => __( 'Licenses', 'ultimate-member' ),
					'value' => array(),
				),
			);

			/**
			 * Filters licenses settings for Site Health.
			 *
			 * @since 2.6.13
			 * @hook um_licenses_site_health
			 *
			 * @param {array} $license_settings licenses settings for Site Health.
			 *
			 * @return {array} licenses settings for Site Health.
			 *
			 * @example <caption>Extend licenses settings for Site Health.</caption>
			 * function um_licenses_site_health( $license_settings ) {
			 *     // your code here
			 *     return $license_settings;
			 * }
			 * add_filter( 'um_licenses_site_health', 'um_licenses_site_health', 10, 1 );
			 */
			$license_settings = apply_filters( 'um_licenses_site_health', $license_settings );

			$info['ultimate-member']['fields'] = array_merge( $info['ultimate-member']['fields'], $pages_settings, $user_settings, $account_settings, $uploads_settings, $restrict_settings, $access_other_settings, $email_settings, $appearance_settings, $license_settings, $misc_settings, $secure_settings );

			return $info;
		}
	}
}
