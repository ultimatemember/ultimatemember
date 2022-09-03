<?php
namespace um;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\Config' ) ) {


	/**
	 * Class Config
	 *
	 * Class with global variables for UM
	 *
	 * @package um
	 */
	class Config {


		/**
		 * @since 3.0
		 *
		 * @var array
		 */
		var $modules = array();


		/**
		 * @var array
		 */
		var $extension_plugins = array();


		/**
		 * @since 3.0
		 *
		 * @var array
		 */
		var $default_settings = array();


		/**
		 * @since 3.0
		 *
		 * @var array
		 */
		var $predefined_pages = array();


		/**
		 * @var array
		 */
		var $email_notification = array();


		/**
		 * @since 3.0
		 *
		 * @var array
		 */
		var $roles_meta = array();


		/**
		 * @since 3.0
		 *
		 * @var array
		 */
		var $form_meta_list = array();


		/**
		 * @since 3.0
		 *
		 * @var array
		 */
		var $form_meta = array();


		/**
		 * Legacy variable
		 *
		 * @since 3.0
		 *
		 * @var array
		 */
		var $global_meta;


		/**
		 * @since 3.0
		 *
		 * @var int
		 */
		var $password_reset_attempts_timeout;


		/**
		 * Config constructor.
		 */
		function __construct() {
		}


		/**
		 * Get variable from config
		 *
		 * @param string $key
		 *
		 * @return mixed
		 *
		 * @since 3.0
		 */
		function get( $key ) {
			if ( empty( $this->$key ) ) {
				call_user_func( [ &$this, 'init_' . $key ] );
			}
			return apply_filters( 'um_config_get', $this->$key, $key );
		}


		/**
		 *
		 */
		public function init_modules() {
			$this->modules = array(
				'jobboardwp'       => array(
					'title'            => __( 'JobBoardWP integration', 'ultimate-member' ),
					'description'      => __( 'Integrates Ultimate Member with JobBoardWP.', 'ultimate-member' ),
					'plugin_slug'      => 'um-jobboardwp/um-jobboardwp.php',
					'docs_url'         => 'https://docs.ultimatemember.com/article/1574-ultimate-member-jobboardwp',
					'type'             => 'free',
					'plugins_required' => array(
						'jobboardwp/jobboardwp.php' => array(
							'name' => 'JobBoardWP – Job Board Listings and Submissions',
							'url'  => 'https://wordpress.org/plugins/jobboardwp/',
						),
					),
				),
				'forumwp'          => array(
					'title'            => __( 'ForumWP integration', 'ultimate-member' ),
					'description'      => __( 'Integrates Ultimate Member with ForumWP.', 'ultimate-member' ),
					'plugin_slug'      => 'um-forumwp/um-forumwp.php',
					'docs_url'         => 'https://docs.ultimatemember.com/article/1501-forumwp-setup',
					'type'             => 'free',
					'plugins_required' => array(
						'forumwp/forumwp.php' => array(
							'name' => 'ForumWP – Forum & Discussion Board Plugin',
							'url'  => 'https://wordpress.org/plugins/forumwp/',
						),
					),
				),
				'member-directory' => array(
					'title'       => __( 'Member Directory', 'ultimate-member' ),
					'description' => __( 'Add a member directory functionality.', 'ultimate-member' ),
					'docs_url'    => 'https://docs.ultimatemember.com/article/1513-member-directories-2-1-0',
					'type'        => 'free',
				),
				'online'           => array(
					'title'       => __( 'Online', 'ultimate-member' ),
					'description' => __( 'Display online users and show the user online status on your site.', 'ultimate-member' ),
					'plugin_slug' => 'um-online/um-online.php',
					'docs_url'    => 'https://docs.ultimatemember.com/category/81-online-users',
					'type'        => 'free',
				),
				'recaptcha'        => array(
					'title'       => __( 'Google reCAPTCHA', 'ultimate-member' ),
					'description' => __( 'Protect your website from spam and integrate Google reCAPTCHA into your Ultimate Member forms.', 'ultimate-member' ),
					'plugin_slug' => 'um-recaptcha/um-recaptcha.php',
					'docs_url'    => 'https://docs.ultimatemember.com/article/72-google-recaptcha',
					'type'        => 'free',
				),
				'terms-conditions' => array(
					'title'       => __( 'Terms & Conditions', 'ultimate-member' ),
					'description' => __( 'Add a terms and condition checkbox to your registration forms & require users to agree to your T&Cs before registering on your site.', 'ultimate-member' ),
					'plugin_slug' => 'um-terms-conditions/um-terms-conditions.php',
					'docs_url'    => 'https://docs.ultimatemember.com/article/260-terms-conditions',
					'type'        => 'free',
				),
			);

			foreach ( $this->modules as $slug => &$data ) {
				$data['key'] = $slug;

				$data['path'] = UM_PATH . 'modules' . DIRECTORY_SEPARATOR . $slug;
				$data['url'] = UM_URL . "modules/{$slug}/";
			}
		}


		/**
		 *
		 */
		public function init_extension_plugins() {
			$this->extension_plugins = array(
				'um-bbpress/um-bbpress.php',
				'um-followers/um-followers.php',
				'um-forumwp/um-forumwp.php',
				'um-friends/um-friends.php',
				'um-groups/um-groups.php',
				'um-instagram/um-instagram.php',
				'um-jobboardwp/um-jobboardwp.php',
				'um-mailchimp/um-mailchimp.php',
				'um-messaging/um-messaging.php',
				'um-mycred/um-mycred.php',
				'um-notices/um-notices.php',
				'um-notifications/um-notifications.php',
				'um-online/um-online.php',
				'um-private-content/um-private-content.php',
				'um-profile-completeness/um-profile-completeness.php',
				'um-profile-tabs/um-profile-tabs.php',
				'um-recaptcha/um-recaptcha.php',
				'um-reviews/um-reviews.php',
				'um-social-activity/um-social-activity.php',
				'um-social-login/um-social-login.php',
				'um-terms-conditions/um-terms-conditions.php',
				'um-unsplash/um-unsplash.php',
				'um-user-bookmarks/um-user-bookmarks.php',
				'um-user-locations/um-user-locations.php',
				'um-user-notes/um-user-notes.php',
				'um-user-photos/um-user-photos.php',
				'um-user-tags/um-user-tags.php',
				'um-verified-users/um-verified-users.php',
				'um-woocommerce/um-woocommerce.php',
			);
		}


		/**
		 * Init legacy global option that have been deprecated in 2.0
		 *
		 * @since 3.0
		 */
		function init_global_meta() {
			$this->global_meta = array(
				'_um_primary_btn_color',
				'_um_primary_btn_hover',
				'_um_primary_btn_text',
				'_um_secondary_btn_color',
				'_um_secondary_btn_hover',
				'_um_secondary_btn_text',
				'_um_form_border',
				'_um_form_border_hover',
				'_um_form_bg_color',
				'_um_form_bg_color_focus',
				'_um_form_placeholder',
				'_um_form_icon_color',
				'_um_form_asterisk_color',
				'_um_form_field_label',
				'_um_form_text_color',
				'_um_active_color',
				'_um_help_tip_color',
				'_um_secondary_color',
			);
		}


		/**
		 * Init plugin default settings
		 *
		 * @since 3.0
		 */
		function init_default_settings() {
			$this->default_settings = array(
				'restricted_access_post_metabox'        => array( 'post' => 1, 'page' => 1 ),
				'disable_restriction_pre_queries'       => 0,
				'uninstall_on_delete'                   => 0,
				'permalink_base'                        => 'user_login',
				'display_name'                          => 'full_name',
				'display_name_field'                    => '',
				'author_redirect'                       => 1,
				'use_gravatars'                         => 0,
				'use_um_gravatar_default_builtin_image' => 'default',
				'use_um_gravatar_default_image'         => 0,
				'require_strongpass'                    => 0,
				'password_min_chars'                    => 8,
				'password_max_chars'                    => 30,
				'account_tab_password'                  => 1,
				'account_tab_privacy'                   => 1,
				'account_tab_notifications'             => 1,
				'account_tab_delete'                    => 1,
				'delete_account_password_requires'      => 1,
				'delete_account_text'                   => __( 'Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account enter your password below.', 'ultimate-member' ),
				'delete_account_no_pass_required_text'  => __( 'Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account, click on the button below.', 'ultimate-member' ),
				'account_name'                          => 1,
				'account_name_disable'                  => 0,
				'account_name_require'                  => 1,
				'account_email'                         => 1,
				'account_general_password'              => 0,
				'photo_thumb_sizes'                     => array( 40, 80, 190 ),
				'cover_thumb_sizes'                     => array( 300, 600 ),
				'accessible'                            => 0,
				'access_redirect'                       => '',
				'access_exclude_uris'                   => array(),
				'home_page_accessible'                  => 1,
				'category_page_accessible'              => 1,
				'restricted_post_title_replace'         => 1,
				'restricted_access_post_title'          => __( 'Restricted content', 'ultimate-member' ),
				'restricted_access_message'             => '',
				'restricted_blocks'                     => 0,
				'enable_blocks'                         => 0,
				'restricted_block_message'              => '',
				'enable_reset_password_limit'           => 1,
				'reset_password_limit_number'           => 3,
				'blocked_emails'                        => '',
				'blocked_words'                         => 'admin' . "\r\n" . 'administrator' . "\r\n" . 'webmaster' . "\r\n" . 'support' . "\r\n" . 'staff',
				'allowed_choice_callbacks'              => '',
				'allow_url_redirect_confirm'            => 1,
				'default_avatar'                        => '',
				'default_cover'                         => '',
				'disable_profile_photo_upload'          => 0,
				'profile_show_metaicon'                 => 0,
				'profile_menu'                          => 1,
				'profile_menu_default_tab'              => 'main',
				'profile_menu_icons'                    => 1,
				'form_asterisk'                         => 0,
				'profile_title'                         => '{display_name} | {site_name}',
				'profile_desc'                          => '{display_name} is on {site_name}. Join {site_name} to view {display_name}\'s profile',
				'admin_email'                           => get_bloginfo('admin_email'),
				'mail_from'                             => get_bloginfo('name'),
				'mail_from_addr'                        => get_bloginfo('admin_email'),
				'email_html'                            => 1,
				'image_orientation_by_exif'             => 0,
				'image_compression'                     => 60,
				'image_max_width'                       => 1000,
				'cover_min_width'                       => 1000,
				'profile_photo_max_size'                => 999999999,
				'cover_photo_max_size'                  => 999999999,
				'custom_roles_increment'                => 1,
				'um_profile_object_cache_stop'          => 0,
				'profile_show_html_bio'                 => 0,
				'profile_noindex'                       => 0,
				'activation_link_expiry_time'           => '',
			);

			$is_legacy = get_option( 'um_is_legacy' );
			if ( $is_legacy ) {
				$this->default_settings['enable_version_3_design'] = 0;
			}

			add_filter( 'um_get_tabs_from_config', '__return_true' );

			$tabs = UM()->profile()->tabs();

			foreach ( $tabs as $id => $tab ) {

				if ( ! empty( $tab['hidden'] ) ) {
					continue;
				}

				$this->default_settings[ 'profile_tab_' . $id ] = 1;

				if ( ! isset( $tab['default_privacy'] ) ) {
					$this->default_settings[ 'profile_tab_' . $id . '_privacy' ] = 0;
					$this->default_settings[ 'profile_tab_' . $id . '_roles' ] = '';
				}
			}

			foreach ( $this->get( 'email_notifications' ) as $key => $notification ) {
				$this->default_settings[ $key . '_on' ] = ! empty( $notification['default_active'] );
				$this->default_settings[ $key . '_sub' ] = $notification['subject'];
			}

			foreach ( array_keys( $this->get( 'predefined_pages' ) ) as $slug ) {
				$this->default_settings[ UM()->options()->get_predefined_page_option_key( $slug ) ] = '';
			}

			foreach( $this->get( 'form_meta_list' ) as $key => $value ) {
				$this->default_settings[ str_replace( '_um_', '', $key ) ] = $value;
			}

			$this->default_settings = apply_filters( 'um_default_settings', $this->default_settings );

			// since 3.0 legacy code
			// @todo remove in 3.1 version
			$this->settings_defaults = apply_filters( 'um_default_settings_values', $this->default_settings );
		}


		/**
		 * Init plugin core pages
		 *
		 * @since 3.0
		 */
		function init_predefined_pages() {
			$core_forms       = get_option( 'um_core_forms', array() );
			$setup_shortcodes = array_merge( array(
				'profile'  => '',
				'login'    => '',
				'register' => '',
			), $core_forms );

			$this->predefined_pages = array(
				'user'           => array(
					'title'   => __( 'User', 'ultimate-member' ),
					'content' => ! empty( $setup_shortcodes['profile'] ) ? '[ultimatemember form_id="' . $setup_shortcodes['profile'] . '"]' : '',
				),
				'login'          => array(
					'title'   => __( 'Login', 'ultimate-member' ),
					'content' => ! empty( $setup_shortcodes['login'] ) ? '[ultimatemember form_id="' . $setup_shortcodes['login'] . '"]' : '',
				),
				'register'       => array(
					'title'   => __( 'Register', 'ultimate-member' ),
					'content' => ! empty( $setup_shortcodes['register'] ) ? '[ultimatemember form_id="' . $setup_shortcodes['register'] . '"]' : '',
				),
				'logout'         => array(
					'title'   => __( 'Logout', 'ultimate-member' ),
					'content' => '',
				),
				'account'        => array(
					'title'   => __( 'Account', 'ultimate-member' ),
					'content' => '[ultimatemember_account]',
				),
				'password-reset' => array(
					'title'   => __( 'Password Reset', 'ultimate-member' ),
					'content' => '[ultimatemember_password]',
				),
			);

			$this->predefined_pages = apply_filters( 'um_predefined_pages', $this->predefined_pages );

			// since 3.0 legacy hook
			// @todo remove in 3.1 version
			$this->predefined_pages = apply_filters( 'um_core_pages', $this->predefined_pages );
			$this->core_pages = $this->predefined_pages;
		}


		/**
		 * Init plugin email notifications
		 *
		 * @since 3.0
		 */
		function init_email_notifications() {
			$this->email_notifications = apply_filters( 'um_email_notifications', array(
				'welcome_email'         => array(
					'key'            => 'welcome_email',
					'title'          => __( 'Account Welcome Email', 'ultimate-member' ),
					'subject'        => __( 'Welcome to {site_name}!', 'ultimate-member' ),
					'description'    => __( 'Whether to send the user an email when his account is automatically approved.', 'ultimate-member' ),
					'recipient'      => 'user',
					'default_active' => true,
				),
				'checkmail_email'       => array(
					'key'         => 'checkmail_email',
					'title'       => __( 'Account Activation Email', 'ultimate-member' ),
					'subject'     => __( 'Please activate your account', 'ultimate-member' ),
					'description' => __( 'Whether to send the user an email when his account needs e-mail activation.', 'ultimate-member' ),
					'recipient'   => 'user',
				),
				'pending_email'         => array(
					'key'         => 'pending_email',
					'title'       => __( 'Your account is pending review', 'ultimate-member' ),
					'subject'     => __( '[{site_name}] New user account', 'ultimate-member' ),
					'description' => __( 'Whether to send the user an email when his account needs admin review', 'ultimate-member' ),
					'recipient'   => 'user',
				),
				'approved_email'        => array(
					'key'         => 'approved_email',
					'title'       => __( 'Account Approved Email', 'ultimate-member' ),
					'subject'     => __( 'Your account at {site_name} is now active', 'ultimate-member' ),
					'description' => __( 'Whether to send the user an email when his account is approved', 'ultimate-member' ),
					'recipient'   => 'user',
				),
				'rejected_email'        => array(
					'key'         => 'rejected_email',
					'title'       => __( 'Account Rejected Email', 'ultimate-member' ),
					'subject'     => __( 'Your account has been rejected', 'ultimate-member' ),
					'description' => __( 'Whether to send the user an email when his account is rejected', 'ultimate-member' ),
					'recipient'   => 'user',
				),
				'inactive_email'        => array(
					'key'            => 'inactive_email',
					'title'          => __( 'Account Deactivated Email', 'ultimate-member' ),
					'subject'        => __( 'Your account has been deactivated', 'ultimate-member' ),
					'description'    => __( 'Whether to send the user an email when his account is deactivated', 'ultimate-member' ),
					'recipient'      => 'user',
					'default_active' => true,
				),
				'deletion_email'        => array(
					'key'            => 'deletion_email',
					'title'          => __( 'Account Deleted Email', 'ultimate-member' ),
					'subject'        => __( 'Your account has been deleted', 'ultimate-member' ),
					'description'    => __( 'Whether to send the user an email when his account is deleted', 'ultimate-member' ),
					'recipient'      => 'user',
					'default_active' => true,
				),
				'reset-password'        => array(
					'key'            => 'reset-password',
					'title'          => __( 'Password Reset Email', 'ultimate-member' ),
					'subject'        => __( 'Reset your password', 'ultimate-member' ),
					'description'    => __( 'Whether to send an email when users changed their password (Recommended, please keep on)', 'ultimate-member' ),
					'recipient'      => 'user',
					'default_active' => true,
				),
				'password-changed'      => array(
					'key'            => 'password-changed',
					'title'          => __( 'Password Changed Email', 'ultimate-member' ),
					'subject'        => __( 'Your {site_name} password has been changed', 'ultimate-member' ),
					'description'    => __( 'Whether to send the user an email when he request to reset password (Recommended, please keep on)', 'ultimate-member' ),
					'recipient'      => 'user',
					'default_active' => true,
				),
				'changedaccount_email'  => array(
					'key'            => 'changedaccount_email',
					'title'          => __( 'Account Updated Email', 'ultimate-member' ),
					'subject'        => __( 'Your account at {site_name} was updated', 'ultimate-member' ),
					'description'    => __( 'Whether to send the user an email when he updated their account', 'ultimate-member' ),
					'recipient'      => 'user',
					'default_active' => true,
				),
				'notification_new_user' => array(
					'key'            => 'notification_new_user',
					'title'          => __( 'New User Notification', 'ultimate-member' ),
					'subject'        => __( '[{site_name}] New user account', 'ultimate-member' ),
					'description'    => __( 'Whether to receive notification when a new user account is created', 'ultimate-member' ),
					'recipient'      => 'admin',
					'default_active' => true,
				),
				'notification_review'   => array(
					'key'         => 'notification_review',
					'title'       => __( 'Account Needs Review Notification', 'ultimate-member' ),
					'subject'     => __( '[{site_name}] New user awaiting review', 'ultimate-member' ),
					'description' => __( 'Whether to receive notification when an account needs admin review', 'ultimate-member' ),
					'recipient'   => 'admin',
				),
				'notification_deletion' => array(
					'key'         => 'notification_deletion',
					'title'       => __( 'Account Deletion Notification', 'ultimate-member' ),
					'subject'     => __( '[{site_name}] Account deleted', 'ultimate-member' ),
					'description' => __( 'Whether to receive notification when an account is deleted', 'ultimate-member' ),
					'recipient'   => 'admin',
				),
			) );
		}


		/**
		 * Init plugin roles meta for WP native roles
		 *
		 * @since 3.0
		 */
		function init_roles_meta() {
			$this->roles_meta = array(

// All Caps map
//				'_um_can_access_wpadmin'            => 1,
//				'_um_can_not_see_adminbar'          => 0,
//				'_um_can_edit_everyone'             => 1,
//				'_um_can_edit_roles'                => '',
//				'_um_can_delete_everyone'           => 1,
//				'_um_can_delete_roles'              => '',
//				'_um_after_delete'                  => '',
//				'_um_delete_redirect_url'           => '',
//				'_um_can_edit_profile'              => 1,
//				'_um_can_delete_profile'            => 1,
//				'_um_default_homepage'              => 1,
//				'_um_redirect_homepage'             => '',
//				'_um_after_login'                   => 'redirect_admin',
//				'_um_login_redirect_url'            => '',
//				'_um_after_logout'                  => 'redirect_home',
//				'_um_logout_redirect_url'           => '',
//				'_um_can_view_all'                  => 1,
//				'_um_can_view_roles'                => '',
//				'_um_can_make_private_profile'      => 1,
//				'_um_can_access_private_profile'    => 1,
//				'_um_status'                        => 'approved',
//				'_um_auto_approve_act'              => 'redirect_profile',
//				'_um_auto_approve_url'              => '',
//				'_um_login_email_activate'          => '',
//				'_um_checkmail_action'              => '',
//				'_um_checkmail_message'             => '',
//				'_um_checkmail_url'                 => '',
//				'_um_url_email_activate'            => '',
//				'_um_pending_action'                => '',
//				'_um_pending_message'               => '',
//				'_um_pending_url'                   => '',

				'subscriber'    => array(
					'_um_can_access_wpadmin'         => 0,
					'_um_can_not_see_adminbar'       => 1,
					'_um_can_edit_everyone'          => 0,
					'_um_can_delete_everyone'        => 0,
					'_um_can_edit_profile'           => 1,
					'_um_can_delete_profile'         => 1,
					'_um_after_login'                => 'redirect_profile',
					'_um_after_logout'               => 'redirect_home',
					'_um_default_homepage'           => 1,
					'_um_can_view_all'               => 1,
					'_um_can_make_private_profile'   => 0,
					'_um_can_access_private_profile' => 0,
					'_um_status'                     => 'approved',
					'_um_auto_approve_act'           => 'redirect_profile',
				),
				'author'        => array(
					'_um_can_access_wpadmin'         => 0,
					'_um_can_not_see_adminbar'       => 1,
					'_um_can_edit_everyone'          => 0,
					'_um_can_delete_everyone'        => 0,
					'_um_can_edit_profile'           => 1,
					'_um_can_delete_profile'         => 1,
					'_um_after_login'                => 'redirect_profile',
					'_um_after_logout'               => 'redirect_home',
					'_um_default_homepage'           => 1,
					'_um_can_view_all'               => 1,
					'_um_can_make_private_profile'   => 0,
					'_um_can_access_private_profile' => 0,
					'_um_status'                     => 'approved',
					'_um_auto_approve_act'           => 'redirect_profile',
				),
				'contributor'   => array(
					'_um_can_access_wpadmin'         => 0,
					'_um_can_not_see_adminbar'       => 1,
					'_um_can_edit_everyone'          => 0,
					'_um_can_delete_everyone'        => 0,
					'_um_can_edit_profile'           => 1,
					'_um_can_delete_profile'         => 1,
					'_um_after_login'                => 'redirect_profile',
					'_um_after_logout'               => 'redirect_home',
					'_um_default_homepage'           => 1,
					'_um_can_view_all'               => 1,
					'_um_can_make_private_profile'   => 0,
					'_um_can_access_private_profile' => 0,
					'_um_status'                     => 'approved',
					'_um_auto_approve_act'           => 'redirect_profile',
				),
				'editor'        => array(
					'_um_can_access_wpadmin'         => 0,
					'_um_can_not_see_adminbar'       => 1,
					'_um_can_edit_everyone'          => 0,
					'_um_can_delete_everyone'        => 0,
					'_um_can_edit_profile'           => 1,
					'_um_can_delete_profile'         => 1,
					'_um_after_login'                => 'redirect_profile',
					'_um_after_logout'               => 'redirect_home',
					'_um_default_homepage'           => 1,
					'_um_can_view_all'               => 1,
					'_um_can_make_private_profile'   => 0,
					'_um_can_access_private_profile' => 0,
					'_um_status'                     => 'approved',
					'_um_auto_approve_act'           => 'redirect_profile',
				),
				'administrator' => array(
					'_um_can_access_wpadmin'         => 1,
					'_um_can_not_see_adminbar'       => 0,
					'_um_can_edit_everyone'          => 1,
					'_um_can_delete_everyone'        => 1,
					'_um_can_edit_profile'           => 1,
					'_um_can_delete_profile'         => 1,
					'_um_default_homepage'           => 1,
					'_um_after_login'                => 'redirect_admin',
					'_um_after_logout'               => 'redirect_home',
					'_um_can_view_all'               => 1,
					'_um_can_make_private_profile'   => 1,
					'_um_can_access_private_profile' => 1,
					'_um_status'                     => 'approved',
					'_um_auto_approve_act'           => 'redirect_profile',
				),
			);
			$this->roles_meta = apply_filters( 'um_roles_meta', $this->roles_meta );

			// since 3.0 legacy code
			// @todo remove in 3.1 version
			$this->default_roles_metadata = $this->roles_meta;
		}


		/**
		 * Init default forms' meta
		 *
		 * @since 3.0
		 */
		function init_form_meta_list() {
			$this->form_meta_list = apply_filters( 'um_form_meta_list', array(
				'_um_profile_show_name'            => 1,
				'_um_profile_show_social_links'    => 0,
				'_um_profile_show_bio'             => 1,
				'_um_profile_bio_maxchars'         => 180,
				'_um_profile_header_menu'          => 'bc',
				'_um_profile_empty_text'           => 1,
				'_um_profile_empty_text_emo'       => 1,
				'_um_profile_role'                 => array(),
				'_um_profile_template'             => 'profile',
				'_um_profile_max_width'            => '1000px',
				'_um_profile_area_max_width'       => '600px',
				'_um_profile_align'                => 'center',
				'_um_profile_icons'                => 'label',
				'_um_profile_disable_photo_upload' => 0,
				'_um_profile_photosize'            => '190',
				'_um_profile_cover_enabled'        => 1,
				'_um_profile_coversize'            => 'original',
				'_um_profile_cover_ratio'          => '2.7:1',
				'_um_profile_photocorner'          => 1,
				'_um_profile_header_bg'            => '',
				'_um_profile_primary_btn_word'     => __( 'Update Profile', 'ultimate-member' ),
				'_um_register_role'                => '0',
				'_um_register_template'            => 'register',
				'_um_register_primary_btn_word'    => __( 'Register', 'ultimate-member' ),
				'_um_login_template'               => 'login',
				'_um_login_primary_btn_word'       => __( 'Login', 'ultimate-member' ),
				'_um_login_forgot_pass_link'       => 1,
				'_um_login_show_rememberme'        => 1,
			) );

			// since 3.0 legacy code
			// @todo remove in 3.1 version
			$this->core_form_meta_all = apply_filters( 'um_core_form_meta_all', $this->form_meta_list );
		}


		/**
		 * Init default form meta data for the 1st install
		 *
		 * @since 3.0
		 */
		public function init_form_meta() {
			$this->form_meta = apply_filters(
				'um_form_meta',
				array(
					'register' => array(
						'_um_custom_fields' => array(
							'user_login'    => array(
								'title'      => __( 'Username', 'ultimate-member' ),
								'metakey'    => 'user_login',
								'type'       => 'text',
								'label'      => __( 'Username', 'ultimate-member' ),
								'required'   => 1,
								'public'     => 1,
								'editable'   => 0,
								'validate'   => 'unique_username',
								'min_chars'  => 3,
								'max_chars'  => 24,
								'position'   => '1',
								'in_row'     => '_um_row_1',
								'in_sub_row' => '0',
								'in_column'  => '1',
								'in_group'   => '',
							),
							'user_email'    => array(
								'title'      => __( 'E-mail Address', 'ultimate-member' ),
								'metakey'    => 'user_email',
								'type'       => 'text',
								'label'      => __( 'E-mail Address', 'ultimate-member' ),
								'required'   => 0,
								'public'     => 1,
								'editable'   => 1,
								'validate'   => 'unique_email',
								'position'   => '4',
								'in_row'     => '_um_row_1',
								'in_sub_row' => '0',
								'in_column'  => '1',
								'in_group'   => '',
							),
							'user_password' => array(
								'title'              => __( 'Password', 'ultimate-member' ),
								'metakey'            => 'user_password',
								'type'               => 'password',
								'label'              => __( 'Password', 'ultimate-member' ),
								'required'           => 1,
								'public'             => 1,
								'editable'           => 1,
								'min_chars'          => 8,
								'max_chars'          => 30,
								'force_good_pass'    => 1,
								'force_confirm_pass' => 1,
								'position'           => '5',
								'in_row'             => '_um_row_1',
								'in_sub_row'         => '0',
								'in_column'          => '1',
								'in_group'           => '',
							),
							'first_name'    => array(
								'title'      => __( 'First Name', 'ultimate-member' ),
								'metakey'    => 'first_name',
								'type'       => 'text',
								'label'      => __( 'First Name', 'ultimate-member' ),
								'required'   => 0,
								'public'     => 1,
								'editable'   => 1,
								'position'   => '2',
								'in_row'     => '_um_row_1',
								'in_sub_row' => '0',
								'in_column'  => '1',
								'in_group'   => '',
							),
							'last_name'     => array(
								'title'      => __( 'Last Name', 'ultimate-member' ),
								'metakey'    => 'last_name',
								'type'       => 'text',
								'label'      => __( 'Last Name', 'ultimate-member' ),
								'required'   => 0,
								'public'     => 1,
								'editable'   => 1,
								'position'   => '3',
								'in_row'     => '_um_row_1',
								'in_sub_row' => '0',
								'in_column'  => '1',
								'in_group'   => '',
							),
							'_um_row_1'     => array(
								'type'     => 'row',
								'id'       => '_um_row_1',
								'sub_rows' => '1',
								'cols'     => '1',
							),
						),
						'_um_mode'          => 'register',
						'_um_core'          => 'register',
						'title'             => __( 'Default Registration', 'ultimate-member' ),
					),
					'login'    => array(
						'_um_custom_fields' => array(
							'username'      => array(
								'title'      => __( 'Username or E-mail', 'ultimate-member' ),
								'metakey'    => 'username',
								'type'       => 'text',
								'label'      => __( 'Username or E-mail', 'ultimate-member' ),
								'required'   => 1,
								'public'     => 1,
								'editable'   => 0,
								'validate'   => 'unique_username_or_email',
								'position'   => '1',
								'in_row'     => '_um_row_1',
								'in_sub_row' => '0',
								'in_column'  => '1',
								'in_group'   => '',
							),
							'user_password' => array(
								'title'              => __( 'Password', 'ultimate-member' ),
								'metakey'            => 'user_password',
								'type'               => 'password',
								'label'              => __( 'Password', 'ultimate-member' ),
								'required'           => 1,
								'public'             => 1,
								'editable'           => 1,
								'min_chars'          => 8,
								'max_chars'          => 30,
								'force_good_pass'    => 1,
								'force_confirm_pass' => 1,
								'position'           => '2',
								'in_row'             => '_um_row_1',
								'in_sub_row'         => '0',
								'in_column'          => '1',
								'in_group'           => '',
							),
							'_um_row_1'     => array(
								'type'     => 'row',
								'id'       => '_um_row_1',
								'sub_rows' => '1',
								'cols'     => '1',
							),
						),
						'_um_mode'          => 'login',
						'_um_core'          => 'login',
						'title'             => __( 'Default Login', 'ultimate-member' ),
					),
					'profile'  => array(
						'_um_custom_fields' => array(
							'_um_row_1' => array(
								'type'     => 'row',
								'id'       => '_um_row_1',
								'sub_rows' => '1',
								'cols'     => '1',
							),
						),
						'_um_mode'          => 'profile',
						'_um_core'          => 'profile',
						'title'             => __( 'Default Profile', 'ultimate-member' ),
					),
				)
			);

			// since 3.0 legacy code
			// @todo remove in 3.1 version
			$this->core_form_meta = $this->form_meta;
		}

		/**
		 * Init reset password attempts timeout
		 * This variable set usermeta timeout for the `password_rst_attempts` usermeta
		 *
		 * @since 3.0
		 */
		public function init_password_reset_attempts_timeout() {
			$this->password_reset_attempts_timeout = DAY_IN_SECONDS / 2;
		}







		/**
		 * @deprecated 3.0
		 *
		 * @var array
		 */
		var $permalinks = array();


		/**
		 * @deprecated 3.0
		 *
		 * @var array
		 */
		var $settings_defaults;


		/**
		 * @deprecated 3.0
		 *
		 * @var array
		 */
		var $default_roles_metadata;


		/**
		 * @deprecated 3.0
		 *
		 * @var array
		 */
		var $perms;


		/**
		 * @deprecated 3.0
		 *
		 * @var array
		 */
		var $nonadmin_perms;


		/**
		 * @deprecated 3.0
		 *
		 * @var mixed|void
		 */
		var $core_form_meta_all;



		/**
		 * @deprecated 3.0
		 *
		 * @var array
		 */
		var $core_forms = array();


		/**
		 * @deprecated 3.0
		 *
		 * @var array
		 */
		var $core_form_meta = array();


		/**
		 * @deprecated 3.0
		 *
		 * @var array
		 */
		var $core_pages = array();

		/**
		 * Get UM Pages
		 *
		 * @deprecated 3.0
		 *
		 * @return array
		 */
		function get_core_pages() {
			_deprecated_function( __METHOD__, '3.0' );

			$permalink = array();
			$core_pages = array_keys( $this->core_pages );
			if ( empty( $core_pages ) ) {
				return $permalink;
			}

			foreach ( $core_pages as $page_key ) {
				$page_option_key = UM()->options()->get_predefined_page_option_key( $page_key );
				$permalink[ $page_key ] = UM()->options()->get( $page_option_key );
			}

			return $permalink;
		}
	}
}
