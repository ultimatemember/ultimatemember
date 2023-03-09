<?php
namespace um;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		 * Login redirect options
		 *
		 * @since 3.0
		 *
		 * @var array
		 */
		var $login_redirect_options = array();

		/**
		 * Build-in avatar sizes
		 *
		 * @since 3.0
		 *
		 * @var array
		 */
		var $avatar_sizes = array();

		/**
		 * Build-in cover ratio
		 *
		 * @since 3.0
		 *
		 * @var array
		 */
		var $cover_ratio = array();

		/**
		 * Build-in cover sizes
		 *
		 * @since 3.0
		 *
		 * @var array
		 */
		var $cover_sizes = array();

		/**
		 * Build-in field types used in fields groups and forms builders
		 *
		 * @since 3.0
		 *
		 * @var array
		 */
		var $field_type_categories = array();

		var $static_field_settings = array();

		/**
		 * Build-in field types used in fields groups and forms builders
		 *
		 * @since 3.0
		 *
		 * @var array
		 */
		var $field_conditional_rules = array();

		/**
		 * @var array
		 */
		var $field_settings_tabs = array();

		/**
		 * @var array
		 */
		var $field_privacy_settings = array();

		/**
		 * @var array
		 */
		var $field_visibility_settings = array();

		/**
		 * @var array
		 */
		var $field_validation_settings = array();

		/**
		 * Build-in field types used in fields groups and forms builders
		 *
		 * @since 3.0
		 *
		 * @var array
		 */
		var $field_types = array();

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
				'use_um_gravatar_default_image'         => 0,
				'default_avatar'                        => '',
				'use_cover_photos'                      => 0,
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
				'styling'                          => '',
				'button_backcolor'                      => '#eee',
				'button_backcolor_hover'                => '#ddd',
				'button_forecolor'                      => '#333',
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
		 * Init login redirect types.
		 *
		 * @since 3.0
		 */
		public function init_login_redirect_options() {
			$this->login_redirect_options = array(
				'redirect_profile' => __( 'Redirect to profile', 'ultimate-member' ),
				'redirect_url'     => __( 'Redirect to URL', 'ultimate-member' ),
				'refresh'          => __( 'Refresh active page', 'ultimate-member' ),
				'redirect_admin'   => __( 'Redirect to WordPress Admin', 'ultimate-member' ),
			);
		}

		/**
		 * Init build-in avatar sizes
		 *
		 * @since 3.0
		 */
		public function init_avatar_sizes() {
			// Avatar is a square so size means width = height.
			$this->avatar_sizes = array(
				'xl' => array(
					'title' => __( 'Profile page', 'ultimate-member' ),
					'size' => 190,
				),
				'l'  => array(
					'title' => __( 'Member Directory', 'ultimate-member' ),
					'size' => 96, // default WordPress avatar size
				),
				'm'  => array(
					'title' => __( 'Activity|Messages', 'ultimate-member' ),
					'size' => 40,
				),
				's'  => array(
					'title' => __( 'Widgets', 'ultimate-member' ),
					'size' => 20,
				),
			);
		}

		/**
		 * Init build-in cover ratio
		 *
		 * @since 3.0
		 */
		public function init_cover_ratio() {
			// size it's height, width will be automatically based on ratio
			$this->cover_ratio = array(
				'1.6:1' => __( '1.6:1', 'ultimate-member' ),
				'2.7:1' => __( '2.7:1', 'ultimate-member' ),
				'2.2:1' => __( '2.2:1', 'ultimate-member' ),
				'3.2:1' => __( '3.2:1', 'ultimate-member' ),
			);
		}

		/**
		 * Init build-in cover sizes
		 *
		 * @since 3.0
		 */
		public function init_cover_sizes() {
			// size it's height, width will be automatically based on ratio
			$this->cover_sizes = array(
				'l'  => array(
					'title' => __( 'Large version', 'ultimate-member' ),
					'size' => 600,
				),
				'm'  => array(
					'title' => __( 'User Profile', 'ultimate-member' ),
					'size' => 300,
				),
				's'  => array(
					'title' => __( 'Member Directory', 'ultimate-member' ),
					'size' => 100,
				),
			);
		}

		public function init_field_type_categories() {
			$this->field_type_categories = array(
				'basic'   => __( 'Basic', 'ultimate-member' ),
				'choice'  => __( 'Choice', 'ultimate-member' ),
				'content' => __( 'Content', 'ultimate-member' ),
				'js'      => __( 'JS', 'ultimate-member' ),
				'layout'  => __( 'Layout', 'ultimate-member' ),
			);
		}

		public function init_field_conditional_rules() {
			$this->field_conditional_rules = array(
				'=='         => __( 'Value is equal to', 'ultimate-member' ),
				'!='         => __( 'Value is not equal to', 'ultimate-member' ),
				'!=empty'    => __( 'Has any value', 'ultimate-member' ),
				'==empty'    => __( 'Has no value', 'ultimate-member' ),
				'==contains' => __( 'Value contains', 'ultimate-member' ),
				'==pattern'  => __( 'Value matches pattern', 'ultimate-member' ),
				'>'          => __( 'Value is greater than', 'ultimate-member' ),
				'<'          => __( 'Value is less than', 'ultimate-member' ),
			);
		}

		public function init_field_settings_tabs() {
			$this->field_settings_tabs = array(
				'general'      => __( 'General', 'ultimate-member' ),
				'presentation' => __( 'Presentation', 'ultimate-member' ),
				'validation'   => __( 'Validation', 'ultimate-member' ),
				'privacy'      => __( 'Privacy & Permissions', 'ultimate-member' ),
				'conditional'  => __( 'Conditional Logic', 'ultimate-member' ),
				'advanced'     => __( 'Advanced', 'ultimate-member' ),
			);
		}

		public function init_field_privacy_settings() {
			$this->field_privacy_settings = array(
				'1'  => __( 'Everyone', 'ultimate-member' ),
				'2'  => __( 'Members', 'ultimate-member' ),
				'-1' => __( 'Only visible to profile owner and users who can edit other member accounts', 'ultimate-member' ),
				'-3' => __( 'Only visible to profile owner and specific roles', 'ultimate-member' ),
				'-2' => __( 'Only specific member roles', 'ultimate-member' ),
			);

			$this->field_privacy_settings = apply_filters( 'um_field_privacy_options', $this->field_privacy_settings );
		}

		public function init_field_visibility_settings() {
			$this->field_visibility_settings = array(
				'edit'     => __( 'Profile Edit mode', 'ultimate-member' ),
				'view'     => __( 'Profile View mode', 'ultimate-member' ),
				'register' => __( 'Register', 'ultimate-member' ),
			);
		}

		public function init_field_validation_settings() {
			$this->field_validation_settings = array(
				''                         => __( 'None', 'ultimate-member' ),
				'alphabetic'               => __( 'Alphabetic value only', 'ultimate-member' ),
				'alpha_numeric'            => __( 'Alpha-numeric value', 'ultimate-member' ),
				'english'                  => __( 'English letters only', 'ultimate-member' ),
				'facebook_url'             => __( 'Facebook URL', 'ultimate-member' ),
				'google_url'               => __( 'Google URL', 'ultimate-member' ),
				'instagram_url'            => __( 'Instagram URL', 'ultimate-member' ),
				'linkedin_url'             => __( 'LinkedIn URL', 'ultimate-member' ),
				'lowercase'                => __( 'Lowercase only', 'ultimate-member' ),
				'numeric'                  => __( 'Numeric value only', 'ultimate-member' ),
				'phone_number'             => __( 'Phone Number', 'ultimate-member' ),
				'skype'                    => __( 'Skype ID', 'ultimate-member' ),
				'soundcloud'               => __( 'SoundCloud Profile', 'ultimate-member' ),
				'twitter_url'              => __( 'Twitter URL', 'ultimate-member' ),
				'is_email'                 => __( 'E-mail( Not Unique )', 'ultimate-member' ),
				'unique_email'             => __( 'Unique E-mail', 'ultimate-member' ),
				'unique_value'             => __( 'Unique Metakey value', 'ultimate-member' ),
				'unique_username'          => __( 'Unique Username', 'ultimate-member' ),
				'unique_username_or_email' => __( 'Unique Username/E-mail', 'ultimate-member' ),
				'url'                      => __( 'Website URL', 'ultimate-member' ),
				'youtube_url'              => __( 'YouTube Profile', 'ultimate-member' ),
				'telegram_url'             => __( 'Telegram URL', 'ultimate-member' ),
				'discord'                  => __( 'Discord ID', 'ultimate-member' ),
				'custom'                   => __( 'Custom Validation', 'ultimate-member' ),
			);
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_field_validation_hook
			 * @description Extend validation types
			 * @input_vars
			 * [{"var":"$types","type":"array","desc":"Validation Types"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_field_validation_hook', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_field_validation_hook', 'my_admin_field_validation', 10, 1 );
			 * function my_admin_field_validation( $types ) {
			 *     // your code here
			 *     return $types;
			 * }
			 * ?>
			 */
			$this->field_validation_settings = apply_filters( 'um_admin_field_validation_hook', $this->field_validation_settings );
		}

		public function init_static_field_settings() {
			$field_types_options = array();
			$field_types         = $this->get( 'field_types' );
			$categories          = $this->get( 'field_type_categories' );
			foreach ( $categories as $cat_key => $cat_title ) {
				$field_types_options[ $cat_key ] = array(
					'title'   => $cat_title,
					'options' => array(),
				);
				foreach ( $field_types as $field_key => $field_data ) {
					if ( $cat_key !== $field_data['category'] ) {
						continue;
					}
					$field_types_options[ $cat_key ]['options'][ $field_key ] = $field_data['title'];
				}
			}

			$this->static_field_settings = array(
				'general'      => array(
					'type'  => array(
						'id'       => 'type',
						'type'     => 'select',
						'class'    => 'um-field-groups-field-type-select',
						'label'    => __( 'Field type', 'ultimate-member' ),
						'options'  => $field_types_options,
						'sanitize' => 'key',
					),
					'title' => array(
						'id'          => 'title',
						'type'        => 'text',
						'class'       => 'um-field-groups-field-title-input',
						'label'       => __( 'Field title', 'ultimate-member' ),
						'description' => __( 'Shown internally for administrator who set up fields group', 'ultimate-member' ),
						'required'    => true,
						'sanitize'    => 'text',
					),
				),
				'presentation' => array(),
				'validation'   => array(),
				'privacy'      => array(
					'privacy'       => array(
						'id'          => 'privacy',
						'type'        => 'select',
						'options'     => $this->get( 'field_privacy_settings' ),
						'label'       => __( 'Privacy', 'ultimate-member' ),
						'description' => __( 'Field privacy allows you to select who can view this field on the front-end. The site admin can view all fields regardless of the option set here.', 'ultimate-member' ),
						'sanitize'    => 'text',
					),
					'privacy_roles' => array(
						'id'          => 'privacy_roles',
						'type'        => 'select',
						'options'     => UM()->roles()->get_roles(),
						'label'       => __( 'Select member roles', 'ultimate-member' ),
						'description' => __( 'Select the member roles that can view this field on the front-end.', 'ultimate-member' ),
						'sanitize'    => 'key',
						'conditional' => array( 'privacy', '=', '-2' ),
					),
					'visibility'    => array(
						'id'          => 'visibility',
						'type'        => 'select',
						'multi'       => true,
						'options'     => $this->get( 'field_visibility_settings' ),
						'label'       => __( 'Visibility', 'ultimate-member' ),
						'description' => __( 'Select where this field should appear. This option allows you to show a field in selected profile mode (edit or view) or in register forms. Leave empty to show everywhere.', 'ultimate-member' ),
						'sanitize'    => 'key',
					),
				),
				'conditional'  => array(
					'conditional_logic'  => array(
						'id'       => 'conditional_logic',
						'type'     => 'checkbox',
						'label'    => __( 'Conditional Logic', 'ultimate-member' ),
						'sanitize' => 'bool',
					),
					'conditional_action' => array(
						'id'          => 'conditional_action',
						'type'        => 'select',
						'label'       => __( 'Action', 'ultimate-member' ),
						'options'     => array(
							'show' => __( 'Show', 'ultimate-member' ),
							'hide' => __( 'Hide', 'ultimate-member' ),
						),
						'sanitize'    => 'key',
						'conditional' => array( 'conditional_logic', '=', 1 ),
					),
					'conditional_rules'  => array(
						'id'          => 'conditional_rules',
						'type'        => 'conditional_rules',
						'label'       => __( 'Rules', 'ultimate-member' ),
						'sanitize'    => 'conditional_rules',
						'conditional' => array( 'conditional_logic', '=', 1 ),
					),
				),
				'advanced'     => array(),
			);
		}

		public function init_field_types() {
			// size it's height, width will be automatically based on ratio
			$this->field_types = array(
				'bool'      => array(
					'title'             => __( 'Single Checkbox', 'ultimate-member' ),
					'category'          => 'choice',
					'conditional_rules' => array(
						'==',
						'!=',
					),
				),
				'radio'     => array(
					'title'    => __( 'Radio', 'ultimate-member' ),
					'category' => 'choice',
				),
				'checkbox'  => array(
					'title'     => __( 'Checkbox', 'ultimate-member' ),
					'category' => 'choice',
				),
				'hidden'    => array(
					'title'     => __( 'Hidden', 'ultimate-member' ),
					'category' => 'basic',
					'conditional_rules' => array(
						'==',
						'!=',
						'!=empty',
						'==empty',
						'==pattern',
						'==contains',
						'>',
						'<',
					),
				),
				'date'      => array(
					'title'     => __( 'Date', 'ultimate-member' ),
					'category' => 'basic',
					'conditional_rules' => array(
						'==',
						'!=',
						'!=empty',
						'==empty',
						'>',
						'<',
					),
				),
				'time'      => array(
					'title'     => __( 'Time', 'ultimate-member' ),
					'category' => 'basic',
					'conditional_rules' => array(
						'==',
						'!=',
						'!=empty',
						'==empty',
						'>',
						'<',
					),
				),
				'number'    => array(
					'title'             => __( 'Number', 'ultimate-member' ),
					'category'          => 'basic',
					'conditional_rules' => array(
						'==',
						'!=',
						'!=empty',
						'==empty',
						'==contains',
						'>',
						'<',
					),
				),
				'password'  => array(
					'title' => __( 'Password', 'ultimate-member' ),
					'category' => 'basic',
					'conditional_rules' => array(
						'!=empty',
						'==empty',
					),
				),
				'email'     => array(
					'title' => __( 'Email', 'ultimate-member' ),
					'category' => 'basic',
					'conditional_rules' => array(
						'==',
						'!=',
						'!=empty',
						'==empty',
						'==pattern',
						'==contains',
					),
				),
				'url'       => array(
					'title'             => __( 'URL', 'ultimate-member' ),
					'category'          => 'basic',
					'conditional_rules' => array(
						'==',
						'!=',
						'!=empty',
						'==empty',
						'==pattern',
						'==contains',
					),
					'settings'          => array(
						'general'      => array(
							'label'         => array(
								'id'          => 'label',
								'type'        => 'text',
								'label'       => __( 'Field label', 'ultimate-member' ),
								'description' => __( 'The field label that appears on your front-end form. Leave blank to not show a label.', 'ultimate-member' ),
								'sanitize'    => 'text',
							),
							'meta_key'      => array(
								'id'          => 'meta_key',
								'type'        => 'text',
								'label'       => __( 'Meta key', 'ultimate-member' ),
								'description' => __( 'A meta key is required to store the entered info in this field in the database. The meta key should be unique to this field and be written in lowercase with an underscore ( _ ) separating words e.g country_list or job_title.', 'ultimate-member' ),
								'sanitize'    => 'text',
							),
							'default_value' => array(
								'id'          => 'default_value',
								'type'        => 'url',
								'label'       => __( 'Default value', 'ultimate-member' ),
								'description' => __( 'This option allows you to pre-fill the field with a default value prior to the user entering a value in the field. Leave blank to have no default value.', 'ultimate-member' ),
								'sanitize'    => 'url',
							),
						),
						'presentation' => array(
							'placeholder' => array(
								'id'          => 'placeholder',
								'type'        => 'text',
								'label'       => __( 'Placeholder', 'ultimate-member' ),
								'description' => __( 'This is the text that appears within the field e.g please enter your email address. Leave blank to not show any placeholder text.', 'ultimate-member' ),
								'sanitize'    => 'text',
							),
							'description' => array(
								'id'          => 'description',
								'type'        => 'textarea',
								'label'       => __( 'Description', 'ultimate-member' ),
								'description' => __( 'This is the text that appears below the field on your front-end. Description is useful for providing users with more information about what they should enter in the field. Leave blank if no description is needed for field.', 'ultimate-member' ),
								'args'        => array(
									'textarea_rows' => 5,
								),
								'sanitize'    => 'textarea',
							),
							'target'      => array(
								'id'          => 'target',
								'type'        => 'select',
								'options'     => array(
									'_blank' => __( 'Open in new window', 'ultimate-member' ),
									'_self'  => __( 'Same window', 'ultimate-member' ),
								),
								'label'       => __( 'Link Target', 'ultimate-member' ),
								'description' => __( 'Choose whether to open this link in same window or in a new window.', 'ultimate-member' ),
								'sanitize'    => 'key',
							),
							'rel'      => array(
								'id'          => 'rel',
								'type'        => 'select',
								'options'     => array(
									'alternate'  => __( 'Provides a link to an alternate representation of the document (i.e. print page, translated or mirror)', 'ultimate-member' ),
									'author'     => __( 'Provides a link to the author of the document', 'ultimate-member' ),
									'bookmark'   => __( 'Permanent URL used for bookmarking', 'ultimate-member' ),
									'external'   => __( 'Indicates that the referenced document is not part of the same site as the current document', 'ultimate-member' ),
									'help'       => __( 'Provides a link to a help document', 'ultimate-member' ),
									'license'    => __( 'Provides a link to licensing information for the document', 'ultimate-member' ),
									'next'       => __( 'Provides a link to the next document in the series', 'ultimate-member' ),
									'nofollow'   => __( 'Links to an unendorsed document, like a paid link.', 'ultimate-member' ),
									'noopener'   => __( 'Requires that any browsing context created by following the hyperlink must not have an opener browsing context', 'ultimate-member' ),
									'noreferrer' => __( 'Makes the referrer unknown. No referer header will be included when the user clicks the hyperlink', 'ultimate-member' ),
									'prev'       => __( 'The previous document in a selection', 'ultimate-member' ),
									'search'     => __( 'Links to a search tool for the document', 'ultimate-member' ),
									'tag'        => __( 'A tag (keyword) for the current document', 'ultimate-member' ),
								),
								'label'       => __( 'Link Relationship for SEO', 'ultimate-member' ),
								'description' => __( 'Choose whether to open this link in same window or in a new window.', 'ultimate-member' ),
								'sanitize'    => 'key',
							),
							'alt_text'      => array(
								'id'          => 'alt_text',
								'type'        => 'text',
								'label'       => __( 'Link Alt Text', 'ultimate-member' ),
								'description' => __( 'Entering custom text here will replace the url with a text link.', 'ultimate-member' ),
								'sanitize'    => 'text',
							),
						),
						'validation'   => array(
							'required'        => array(
								'id'          => 'required',
								'type'        => 'checkbox',
								'label'       => __( 'Is this field required?', 'ultimate-member' ),
								'description' => __( 'This option allows you to set whether the field must be filled in before the form can be processed.', 'ultimate-member' ),
								'sanitize'    => 'bool',
							),
							'min_chars'       => array(
								'id'          => 'min_chars',
								'type'        => 'number',
								'label'       => __( 'Minimum length', 'ultimate-member' ),
								'description' => __( 'If you want to enable a minimum number of characters to be input in this field. Leave empty to disable this setting.', 'ultimate-member' ),
								'sanitize'    => 'empty_absint',
							),
							'max_chars'       => array(
								'id'          => 'max_chars',
								'type'        => 'number',
								'label'       => __( 'Maximum length', 'ultimate-member' ),
								'description' => __( 'If you want to enable a maximum number of characters to be input in this field. Leave empty to disable this setting.', 'ultimate-member' ),
								'sanitize'    => 'empty_absint',
							),
							'validate'        => array(
								'id'          => 'validate',
								'type'        => 'select',
								'label'       => __( 'Validation', 'ultimate-member' ),
								'description' => __( 'Does this field require a special validation?', 'ultimate-member' ),
								'options'     => $this->get( 'field_validation_settings' ),
								'sanitize'    => 'key',
							),
							'custom_validate' => array(
								'id'          => 'custom_validate',
								'type'        => 'text',
								'label'       => __( 'Custom validation action', 'ultimate-member' ),
								'description' => __( 'If you want to apply your custom validation, you can use action hooks to add custom validation. Please refer to documentation for further details.', 'ultimate-member' ),
								'conditional' => array( 'validate', '=', 'custom' ),
								'sanitize'    => 'text',
							),
						),
						'privacy'      => array(
							'required' => array(
								'id'          => 'required',
								'type'        => 'checkbox',
								'label'       => __( 'Mark as readonly', 'ultimate-member' ),
								'description' => __( 'Enable to prevent users from editing this field. Note: if the profile editing option is set to publicly editable, the field will still be visible within the account page but will not be customizable.', 'ultimate-member' ),
								'sanitize'    => 'bool',
							),
						),
						'advanced'     => array(
							'wrapper_class' => array(
								'id'          => 'wrapper_class',
								'type'        => 'text',
								'label'       => __( 'Wrapper class', 'ultimate-member' ),
								'description' => __( 'CSS class added to the field wrapper element.', 'ultimate-member' ),
								'sanitize'    => 'key',
							),
							'wrapper_id'    => array(
								'id'          => 'wrapper_id',
								'type'        => 'text',
								'label'       => __( 'Wrapper id', 'ultimate-member' ),
								'description' => __( 'ID added to the field wrapper element.', 'ultimate-member' ),
								'sanitize'    => 'key',
							),
						),
					),
				),
				'text'      => array(
					'title'             => __( 'Text Box', 'ultimate-member' ),
					'category'          => 'basic',
					'conditional_rules' => array(
						'==',
						'!=',
						'!=empty',
						'==empty',
						'==pattern',
						'==contains',
					),
					'settings'          => array(
						'general'      => array(
							'label'         => array(
								'id'          => 'label',
								'type'        => 'text',
								'label'       => __( 'Field label', 'ultimate-member' ),
								'description' => __( 'The field label that appears on your front-end form. Leave blank to not show a label.', 'ultimate-member' ),
								'sanitize'    => 'text',
							),
							'meta_key'      => array(
								'id'          => 'meta_key',
								'type'        => 'text',
								'label'       => __( 'Meta key', 'ultimate-member' ),
								'description' => __( 'A meta key is required to store the entered info in this field in the database. The meta key should be unique to this field and be written in lowercase with an underscore ( _ ) separating words e.g country_list or job_title.', 'ultimate-member' ),
								'sanitize'    => 'text',
							),
							'default_value' => array(
								'id'          => 'default_value',
								'type'        => 'text',
								'label'       => __( 'Default value', 'ultimate-member' ),
								'description' => __( 'This option allows you to pre-fill the field with a default value prior to the user entering a value in the field. Leave blank to have no default value.', 'ultimate-member' ),
								'sanitize'    => 'text',
							),
						),
						'presentation' => array(
							'placeholder' => array(
								'id'          => 'placeholder',
								'type'        => 'text',
								'label'       => __( 'Placeholder', 'ultimate-member' ),
								'description' => __( 'This is the text that appears within the field e.g please enter your email address. Leave blank to not show any placeholder text.', 'ultimate-member' ),
								'sanitize'    => 'text',
							),
							'description' => array(
								'id'          => 'description',
								'type'        => 'textarea',
								'label'       => __( 'Description', 'ultimate-member' ),
								'description' => __( 'This is the text that appears below the field on your front-end. Description is useful for providing users with more information about what they should enter in the field. Leave blank if no description is needed for field.', 'ultimate-member' ),
								'args'        => array(
									'textarea_rows' => 5,
								),
								'sanitize'    => 'textarea',
							),
						),
						'validation'   => array(
							'required'        => array(
								'id'          => 'required',
								'type'        => 'checkbox',
								'label'       => __( 'Is this field required?', 'ultimate-member' ),
								'description' => __( 'This option allows you to set whether the field must be filled in before the form can be processed.', 'ultimate-member' ),
								'sanitize'    => 'bool',
							),
							'min_chars'       => array(
								'id'          => 'min_chars',
								'type'        => 'number',
								'label'       => __( 'Minimum length', 'ultimate-member' ),
								'description' => __( 'If you want to enable a minimum number of characters to be input in this field. Leave empty to disable this setting.', 'ultimate-member' ),
								'sanitize'    => 'empty_absint',
							),
							'max_chars'       => array(
								'id'          => 'max_chars',
								'type'        => 'number',
								'label'       => __( 'Maximum length', 'ultimate-member' ),
								'description' => __( 'If you want to enable a maximum number of characters to be input in this field. Leave empty to disable this setting.', 'ultimate-member' ),
								'sanitize'    => 'empty_absint',
							),
							'pattern'         => array(
								'id'          => 'pattern',
								'type'        => 'text',
								'label'       => __( 'Input mask (pattern)', 'ultimate-member' ),
								'description' => __( 'A regular <a target="_blank" href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/text#specifying_a_pattern">expression</a> to validate input format.', 'ultimate-member' ),
								'sanitize'    => 'text',
							),
							'validate'        => array(
								'id'          => 'validate',
								'type'        => 'select',
								'label'       => __( 'Validation', 'ultimate-member' ),
								'description' => __( 'Does this field require a special validation?', 'ultimate-member' ),
								'options'     => $this->get( 'field_validation_settings' ),
								'sanitize'    => 'key',
							),
							'custom_validate' => array(
								'id'          => 'custom_validate',
								'type'        => 'text',
								'label'       => __( 'Custom validation action', 'ultimate-member' ),
								'description' => __( 'If you want to apply your custom validation, you can use action hooks to add custom validation. Please refer to documentation for further details.', 'ultimate-member' ),
								'conditional' => array( 'validate', '=', 'custom' ),
								'sanitize'    => 'text',
							),
						),
						'privacy'      => array(
							'required' => array(
								'id'          => 'required',
								'type'        => 'checkbox',
								'label'       => __( 'Mark as readonly', 'ultimate-member' ),
								'description' => __( 'Enable to prevent users from editing this field. Note: if the profile editing option is set to publicly editable, the field will still be visible within the account page but will not be customizable.', 'ultimate-member' ),
								'sanitize'    => 'bool',
							),
						),
						'advanced'     => array(
							'wrapper_class' => array(
								'id'          => 'wrapper_class',
								'type'        => 'text',
								'label'       => __( 'Wrapper class', 'ultimate-member' ),
								'description' => __( 'CSS class added to the field wrapper element.', 'ultimate-member' ),
								'sanitize'    => 'key',
							),
							'wrapper_id'    => array(
								'id'          => 'wrapper_id',
								'type'        => 'text',
								'label'       => __( 'Wrapper id', 'ultimate-member' ),
								'description' => __( 'ID added to the field wrapper element.', 'ultimate-member' ),
								'sanitize'    => 'key',
							),
						),
					),
				),
				'select'    => array(
					'title' => __( 'Dropdown', 'ultimate-member' ),
					'category' => 'choice',
				),
				'textarea'  => array(
					'title'     => __( 'Textarea', 'ultimate-member' ),
					'category' => 'basic',
					'conditional_rules' => array(
						'==',
						'!=',
						'!=empty',
						'==empty',
						'==pattern',
						'==contains',
					),
				),
				'file'      => array(
					'title'             => __( 'File/Image', 'ultimate-member' ),
					'category'          => 'content',
					'conditional_rules' => array(
						'!=empty',
						'==empty',
					),
				),
				'repeater'  => array(
					'title'             => __( 'Repeater', 'ultimate-member' ),
					'category'          => 'layout',
					'conditional_rules' => array(),
				),
				'block'     => array(
					'title'             => __( 'Content', 'ultimate-member' ),
					'category'          => 'layout',
					'conditional_rules' => array(),
				),
				'shortcode' => array(
					'title'             => __( 'Shortcode', 'ultimate-member' ),
					'category'          => 'layout',
					'conditional_rules' => array(),
				),
				'spacing'   => array(
					'title'             => __( 'Spacing', 'ultimate-member' ),
					'category'          => 'layout',
					'conditional_rules' => array(),
				),
				'divider'   => array(
					'title'             => __( 'Divider', 'ultimate-member' ),
					'category'          => 'layout',
					'conditional_rules' => array(),
				),
				'rating'    => array(
					'title'             => __( 'Rating', 'ultimate-member' ),
					'category'          => 'js',
					'conditional_rules' => array(
						'==',
						'!=',
						'!=empty',
						'==empty',
						'>',
						'<',
					),
				),
				'googlemap' => array(
					'title' => __( 'Google Map', 'ultimate-member' ),
					'category' => 'js',
					'conditional_rules' => array(
						'!=empty',
						'==empty',
					),
				),
				'oembed'    => array(
					'title' => __( 'oEmbed', 'ultimate-member' ),
					'category' => 'content',
				),
//				'youtube_video'    => array(
//					'title' => __( 'YouTube Video', 'ultimate-member' ),
//					'category' => __( 'Content', 'ultimate-member' ),
//				),
//				'vimeo_video'    => array(
//					'title' => __( 'Vimeo Video', 'ultimate-member' ),
//					'category' => __( 'Content', 'ultimate-member' ),
//				),
//				'soundcloud_track'    => array(
//					'title' => __( 'SoundCloud Track', 'ultimate-member' ),
//					'category' => __( 'Content', 'ultimate-member' ),
//				),
			);


//			$this->core_fields = array(
//
//				/*Group is the repeatable block with 1 pre-defined repeat*/
////				'group' => [
////					'name'      => __( 'Fields Group', 'ultimate-member' ),
////					'tabs'      => [
////						'general'       => [
////							'key'   => 'general',
////							'label' => __( 'General', 'ultimate-member' ),
////						],
////						'privacy'       => [
////							'key'   => 'privacy',
////							'label' => __( 'Privacy & Validation', 'ultimate-member' ),
////						],
////						'conditional'   => [
////							'key'   => 'conditional',
////							'label' => __( 'Conditional Logic', 'ultimate-member' ),
////						],
////					],
////					'col1'      => [ '_title', '_max_entries' ],
////					'col2'      => [ '_label', '_public', '_roles' ],
////					'validate'  => [
////						'_title'    => [
////							'mode'  => 'required',
////							'error' => 'You must provide a title',
////						],
////						'_metakey'  => [
////							'mode'  => 'unique',
////						],
////					],
////				],
//
//
//
//				'radio'    => array(
//					'name'     => __( 'Radio', 'ultimate-member' ),
//					'col1'     => array( '_title', '_metakey', '_description', '_options' ),
//					'col2'     => array( '_label', '_visibility', '_public', '_roles', '_custom_dropdown_options_source', '_parent_dropdown_relationship' ),
//					'col3'     => array( '_required', '_editable', '_choices_layout' ),
//					'validate' => array(
//						'_title'   => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//						'_options' => array(
//							'mode' => 'unique_options',
//						),
//					),
//				),
//
//				'checkbox' => array(
//					'name'     => __( 'Checkbox', 'ultimate-member' ),
//					'col1'     => array( '_title', '_metakey', '_description', '_options' ),
//					'col2'     => array( '_label', '_visibility', '_public', '_roles', '_custom_dropdown_options_source', '_parent_dropdown_relationship', '_min_selections', '_max_selections' ),
//					'col3'     => array( '_required', '_editable', '_choices_layout' ),
//					'validate' => array(
//						'_title'   => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//						'_options' => array(
//							'mode' => 'unique_options',
//						),
//					),
//				),
//
//				'hidden'   => array(
//					'name' => __( 'Hidden', 'ultimate-member' ),
//					'col1' => array( '_title', '_value' ),
//					'col2' => array( '_metakey' ),
//					'col3' => array(),
//					'validate' => array(
//						'_title' => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					)
//				),
//
//				'date'     => array(
//					'name'      => __( 'Date', 'ultimate-member' ),
//					'col1'      => array( '_title', '_metakey', '_description' ),
//					'col2'      => array( '_label', '_visibility', '_public', '_roles', '_min', '_max' ),
//					'col3'      => array( '_required', '_editable', '_default' ),
//					'validate'  => array(
//						'_title'        => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' )
//						),
//						'_metakey'      => array(
//							'mode'  => 'unique',
//						),
//					),
//				),
//
//				'time'     => array(
//					'name'     => __( 'Time', 'ultimate-member' ),
//					'col1'     => array( '_title', '_metakey', '_description' ),
//					'col2'     => array( '_label', '_visibility', '_public', '_roles', '_step', '_min', '_max' ),
//					'col3'     => array( '_required', '_editable', '_default' ),
//					'validate' => array(
//						'_title'   => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title.', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					),
//				),
//
//				'number'   => array(
//					'name' => __( 'Number', 'ultimate-member' ),
//					'col1' => array( '_title', '_metakey', '_placeholder', '_description' ),
//					'col2' => array( '_label','_visibility', '_public', '_roles', '_validate', '_custom_validate', '_step', '_min', '_max' ),
//					'col3' => array( '_required', '_editable', '_default' ),
//					'validate' => array(
//						'_title' => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					)
//				),
//
//				'password' => array(
//					'name' => __( 'Password', 'ultimate-member' ),
//					'col1' => array( '_title', '_metakey', '_description', '_min_chars', '_max_chars' ),
//					'col2' => array( '_label', '_placeholder', '_visibility', '_public', '_roles', '_force_confirm_pass', '_label_confirm_pass', '_placeholder_confirm_pass', '_description_confirm_pass', '_pattern' ),
//					'col3' => array( '_required', '_editable', '_force_good_pass' ),
//					'validate' => array(
//						'_title' => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					),
//				),
//
//				'email'    => array(
//					'name' => __( 'Email', 'ultimate-member' ),
//					'col1' => array( '_title', '_metakey', '_description', '_min_chars', '_max_chars' ),
//					'col2' => array( '_label', '_placeholder', '_visibility', '_public', '_roles', '_validate', '_custom_validate', '_pattern' ),
//					'col3' => array( '_required', '_editable', '_default' ),
//					'validate' => array(
//						'_title' => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					),
//				),
//
//				'url'      => array(
//					'name' => __( 'URL', 'ultimate-member' ),
//					'col1' => array('_title','_metakey','_description', '_min_chars', '_max_chars'),
//					'col2' => array('_label','_placeholder','_visibility','_public','_roles','_validate','_custom_validate', '_pattern','_url_text','_url_target','_url_rel'),
//					'col3' => array( '_required', '_editable', '_default' ),
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					)
//				),
//
//				'tel'      => array(
//					'name'     => __( 'Telephone Box', 'ultimate-member' ),
//					'col1'     => array( '_title', '_metakey', '_description', '_min_chars', '_max_chars' ),
//					'col2'     => array( '_label', '_placeholder', '_visibility', '_public', '_roles', '_validate', '_custom_validate', '_pattern' ),
//					'col3'     => array( '_required', '_editable', '_default' ),
//					'validate' => array(
//						'_title'   => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					),
//				),
//
//				'text'     => array(
//					'name' => __( 'Text Box', 'ultimate-member' ),
//					'col1' => array( '_title', '_metakey', '_description', '_min_chars', '_max_chars'),
//					'col2' => array( '_label', '_placeholder', '_visibility', '_public', '_roles', '_validate', '_custom_validate', '_pattern' ),
//					'col3' => array( '_required', '_editable', '_default' ),
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					),
//				),
//
//				'select'   => array(
//					'name' => __( 'Dropdown', 'ultimate-member' ),
//					'col1' => array( '_title', '_metakey', '_description', '_is_multi', '_options' ),
//					'col2' => array( '_label', '_placeholder', '_visibility', '_public', '_roles', '_custom_dropdown_options_source', '_parent_dropdown_relationship', '_min_selections', '_max_selections' ),
//					'col3' => array( '_required', '_editable' ),
//					'validate' => array(
//						'_title'   => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//						'_options' => array(
//							'mode' => 'unique_options',
//						),
//					),
//				),
//
//				'textarea' => array(
//					'name'     => __( 'Textarea', 'ultimate-member' ),
//					'col1'     => array( '_title', '_metakey', '_description', '_rows', '_min_chars', '_max_chars', '_max_words' ),
//					'col2'     => array( '_label', '_placeholder', '_visibility', '_public', '_roles', '_default' ),
//					'col3'     => array( '_required', '_editable', '_html' ),
//					'validate' => array(
//						'_title'   => array(
//							'mode'  => 'required',
//							'error' => __( 'You must provide a title', 'ultimate-member' ),
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					),
//				),
//
//				'image' => array(
//					'name' => 'Image Upload',
//					'col1' => array('_title','_metakey','_description','_allowed_types','_max_size','_crop','_visibility'),
//					'col2' => array('_label','_public','_roles','_upload_text','_upload_help_text','_button_text'),
//					'col3' => array('_required','_editable'),
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//						'_max_size' => array(
//							'mode' => 'numeric',
//							'error' => __('Please enter a valid size','ultimate-member')
//						),
//					)
//				),
//
//				'file' => array(
//					'name' => 'File Upload',
//					'col1' => array('_title','_metakey','_description','_allowed_types','_max_size','_visibility'),
//					'col2' => array('_label','_public','_roles','_upload_text','_upload_help_text','_button_text'),
//					'col3' => array('_required','_editable'),
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//						'_max_size' => array(
//							'mode' => 'numeric',
//							'error' => __( 'Please enter a valid size', 'ultimate-member' )
//						),
//					)
//				),
//
//
//
//				'block'     => array(
//					'name' => 'Content Block',
//					'col1' => array('_title','_visibility'),
//					'col2' => array('_public','_roles'),
//					'col_full' => array('_content'),
//					'mce_content' => true,
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//					)
//				),
//
//				'shortcode' => array(
//					'name' => 'Shortcode',
//					'col1' => array('_title','_visibility'),
//					'col2' => array('_public','_roles'),
//					'col_full' => array('_content'),
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//						'_content' => array(
//							'mode' => 'required',
//							'error' => __('You must add a shortcode to the content area','ultimate-member')
//						),
//					)
//				),
//
//				'spacing'   => array(
//					'name' => 'Spacing',
//					'col1' => array('_title','_visibility'),
//					'col2' => array('_spacing'),
//					'form_only' => true,
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//					)
//				),
//
//				'divider'   => array(
//					'name' => 'Divider',
//					'col1' => array('_title','_width','_divider_text','_visibility'),
//					'col2' => array('_style','_color','_public','_roles'),
//					'form_only' => true,
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//					)
//				),
//
//				'rating'           => array(
//					'name' => __( 'Rating', 'ultimate-member' ),
//					'col1' => array('_title','_metakey','_description','_visibility'),
//					'col2' => array('_label','_public','_roles','_number','_default'),
//					'col3' => array('_required','_editable'),
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					)
//				),
//
//				'googlemap'        => array(
//					'name' => 'Google Map',
//					'col1' => array('_title','_metakey','_description','_visibility'),
//					'col2' => array('_label','_placeholder','_public','_roles','_validate','_custom_validate'),
//					'col3' => array('_required','_editable'),
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					)
//				),
//
//				'youtube_video'    => array(
//					'name' => 'YouTube Video',
//					'col1' => array('_title','_metakey','_description','_visibility'),
//					'col2' => array('_label','_placeholder','_public','_roles','_validate','_custom_validate'),
//					'col3' => array('_required','_editable'),
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					)
//				),
//
//				'vimeo_video'      => array(
//					'name' => 'Vimeo Video',
//					'col1' => array('_title','_metakey','_description','_visibility'),
//					'col2' => array('_label','_placeholder','_public','_roles','_validate','_custom_validate'),
//					'col3' => array('_required','_editable'),
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					)
//				),
//
//				'soundcloud_track' => array(
//					'name' => 'SoundCloud Track',
//					'col1' => array('_title','_metakey','_description','_visibility'),
//					'col2' => array('_label','_placeholder','_public','_roles','_validate','_custom_validate'),
//					'col3' => array('_required','_editable'),
//					'validate' => array(
//						'_title' => array(
//							'mode' => 'required',
//							'error' => __('You must provide a title','ultimate-member')
//						),
//						'_metakey' => array(
//							'mode' => 'unique',
//						),
//					)
//				),
//			);
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
