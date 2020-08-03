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
		 * @var array
		 */
		var $core_forms;


		/**
		 * @var array
		 */
		var $core_directories;


		/**
		 * @var mixed|void
		 */
		var $core_pages;


		/**
		 * @var array
		 */
		var $core_directory_meta = array();


		/**
		 * @var array
		 */
		var $core_global_meta_all;


		/**
		 * @var mixed|void
		 */
		var $core_form_meta_all;


		/**
		 * @var array
		 */
		var $core_form_meta = array();


		/**
		 * @var
		 */
		var $perms;


		/**
		 * @var
		 */
		var $nonadmin_perms;


		/**
		 * @var mixed|void
		 */
		var $email_notifications;


		/**
		 * @var mixed|void
		 */
		var $settings_defaults;


		/**
		 * @var array
		 */
		var $permalinks;


		/**
		 * Config constructor.
		 */
		function __construct() {
			$this->core_forms = array(
				'register',
				'login',
				'profile',
			);

			$this->core_directories = array(
				'members',
			);


			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_core_pages
			 * @description Extend UM core pages
			 * @input_vars
			 * [{"var":"$pages","type":"array","desc":"UM core pages"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_core_pages', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_core_pages', 'my_core_pages', 10, 1 );
			 * function my_core_pages( $pages ) {
			 *     // your code here
			 *     $pages['my_page_key'] = array( 'title' => __( 'My Page Title', 'my-translate-key' ) );
			 *     return $pages;
			 * }
			 * ?>
			 */
			$this->core_pages = apply_filters( 'um_core_pages', array(
				'user'              => array( 'title' => __( 'User', 'ultimate-member' ) ),
				'login'             => array( 'title' => __( 'Login', 'ultimate-member' ) ),
				'register'          => array( 'title' => __( 'Register', 'ultimate-member' ) ),
				'members'           => array( 'title' => __( 'Members', 'ultimate-member' ) ),
				'logout'            => array( 'title' => __( 'Logout', 'ultimate-member' ) ),
				'account'           => array( 'title' => __( 'Account', 'ultimate-member' ) ),
				'password-reset'    => array( 'title' => __( 'Password Reset', 'ultimate-member' ) ),
			) );

			$this->core_directory_meta['members'] = array(
				'_um_core'                      => 'members',
				'_um_template'                  => 'members',
				'_um_mode'                      => 'directory',
				'_um_view_types'                => array( 'grid' ),
				'_um_default_view'              => 'grid',
				'_um_roles'                     => array(),
				'_um_has_profile_photo'         => 0,
				'_um_has_cover_photo'           => 0,
				'_um_show_these_users'          => '',
				'_um_exclude_these_users'       => '',

				'_um_sortby'                    => 'user_registered_desc',
				'_um_sortby_custom'             => '',
				'_um_sortby_custom_label'       => '',
				'_um_enable_sorting'            => 0,
				'_um_sorting_fields'            => array(),

				'_um_profile_photo'             => '1',
				'_um_cover_photos'              => '1',
				'_um_show_name'                 => '1',
				'_um_show_tagline'              => 0,
				'_um_tagline_fields'            => array(),
				'_um_show_userinfo'             => 0,
				'_um_reveal_fields'             => array(),
				'_um_show_social'               => 0,
				'_um_userinfo_animate'          => '1',

				'_um_search'                    => 0,
				'_um_roles_can_search'          => array(),
				'_um_filters'                   => 0,
				'_um_roles_can_filter'          => array(),
				'_um_search_fields'             => array(),
				'_um_filters_expanded'          => 0,
				'_um_filters_is_collapsible'    => 1,
				'_um_search_filters'            => array(),

				'_um_must_search'               => 0,
				'_um_max_users'                 => '',
				'_um_profiles_per_page'         => 12,
				'_um_profiles_per_page_mobile'  => 6,
				'_um_directory_header'          => __( '{total_users} Members', 'ultimate-member' ),
				'_um_directory_header_single'   => __( '{total_users} Member', 'ultimate-member' ),
				'_um_directory_no_users'        => __( 'We are sorry. We cannot find any users who match your search criteria.', 'ultimate-member' ),
			);

			$this->core_global_meta_all = array(
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


			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_core_form_meta_all
			 * @description Extend UM forms meta keys
			 * @input_vars
			 * [{"var":"$meta","type":"array","desc":"UM forms meta"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_core_form_meta_all', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_core_form_meta_all', 'my_core_form_meta', 10, 1 );
			 * function my_core_form_meta( $meta ) {
			 *     // your code here
			 *     $meta['my_meta_key'] = 'my_meta_value';
			 *     return $meta;
			 * }
			 * ?>
			 */
			$this->core_form_meta_all = apply_filters( 'um_core_form_meta_all', array(
				/*Profile Form*/
				'_um_profile_show_name'             => 1,
				'_um_profile_show_social_links'     => 0,
				'_um_profile_show_bio'              => 1,
				'_um_profile_bio_maxchars'          => 180,
				'_um_profile_header_menu'           => 'bc',
				'_um_profile_empty_text'            => 1,
				'_um_profile_empty_text_emo'        => 1,
				'_um_profile_role'                  => array(),
				'_um_profile_template'              => 'profile',
				'_um_profile_max_width'             => '1000px',
				'_um_profile_area_max_width'        => '600px',
				'_um_profile_align'                 => 'center',
				'_um_profile_icons'                 => 'label',
				'_um_profile_disable_photo_upload'  => 0,
				'_um_profile_photosize'             => '190',
				'_um_profile_cover_enabled'         => 1,
				'_um_profile_coversize'             => 'original',
				'_um_profile_cover_ratio'           => '2.7:1',
				'_um_profile_photocorner'           => '1',
				'_um_profile_header_bg'             => '',
				'_um_profile_primary_btn_word'      => __( 'Update Profile', 'ultimate-member' ),
				'_um_profile_secondary_btn'         => '1',
				'_um_profile_secondary_btn_word'    => __( 'Cancel', 'ultimate-member' ),

				/*Registration Form*/
				'_um_register_role'                 => '0',
				'_um_register_template'             => 'register',
				'_um_register_max_width'            => '450px',
				'_um_register_align'                => 'center',
				'_um_register_icons'                => 'label',
				'_um_register_primary_btn_word'     => __( 'Register', 'ultimate-member' ),
				'_um_register_secondary_btn'        => 1,
				'_um_register_secondary_btn_word'   => __( 'Login', 'ultimate-member' ),
				'_um_register_secondary_btn_url'    => '',

				/*Login Form*/
				'_um_login_template'                => 'login',
				'_um_login_max_width'               => '450px',
				'_um_login_align'                   => 'center',
				'_um_login_icons'                   => 'label',
				'_um_login_primary_btn_word'        => __( 'Login', 'ultimate-member' ),
				'_um_login_forgot_pass_link'        => 1,
				'_um_login_show_rememberme'         => 1,
				'_um_login_secondary_btn'           => 1,
				'_um_login_secondary_btn_word'      => __( 'Register', 'ultimate-member' ),
				'_um_login_secondary_btn_url'       => '',

				/*Member Directory*/
				'_um_directory_template'            => 'members',
				'_um_directory_header'              => __( '{total_users} Members', 'ultimate-member' ),
				'_um_directory_header_single'       => __( '{total_users} Member', 'ultimate-member' ),
			) );

			$this->core_form_meta['register'] = array(
				'_um_custom_fields' => 'a:6:{s:10:"user_login";a:15:{s:5:"title";s:8:"Username";s:7:"metakey";s:10:"user_login";s:4:"type";s:4:"text";s:5:"label";s:8:"Username";s:8:"required";i:1;s:6:"public";i:1;s:8:"editable";i:0;s:8:"validate";s:15:"unique_username";s:9:"min_chars";i:3;s:9:"max_chars";i:24;s:8:"position";s:1:"1";s:6:"in_row";s:9:"_um_row_1";s:10:"in_sub_row";s:1:"0";s:9:"in_column";s:1:"1";s:8:"in_group";s:0:"";}s:10:"user_email";a:13:{s:5:"title";s:14:"E-mail Address";s:7:"metakey";s:10:"user_email";s:4:"type";s:4:"text";s:5:"label";s:14:"E-mail Address";s:8:"required";i:0;s:6:"public";i:1;s:8:"editable";i:1;s:8:"validate";s:12:"unique_email";s:8:"position";s:1:"4";s:6:"in_row";s:9:"_um_row_1";s:10:"in_sub_row";s:1:"0";s:9:"in_column";s:1:"1";s:8:"in_group";s:0:"";}s:13:"user_password";a:16:{s:5:"title";s:8:"Password";s:7:"metakey";s:13:"user_password";s:4:"type";s:8:"password";s:5:"label";s:8:"Password";s:8:"required";i:1;s:6:"public";i:1;s:8:"editable";i:1;s:9:"min_chars";i:8;s:9:"max_chars";i:30;s:15:"force_good_pass";i:1;s:18:"force_confirm_pass";i:1;s:8:"position";s:1:"5";s:6:"in_row";s:9:"_um_row_1";s:10:"in_sub_row";s:1:"0";s:9:"in_column";s:1:"1";s:8:"in_group";s:0:"";}s:10:"first_name";a:12:{s:5:"title";s:10:"First Name";s:7:"metakey";s:10:"first_name";s:4:"type";s:4:"text";s:5:"label";s:10:"First Name";s:8:"required";i:0;s:6:"public";i:1;s:8:"editable";i:1;s:8:"position";s:1:"2";s:6:"in_row";s:9:"_um_row_1";s:10:"in_sub_row";s:1:"0";s:9:"in_column";s:1:"1";s:8:"in_group";s:0:"";}s:9:"last_name";a:12:{s:5:"title";s:9:"Last Name";s:7:"metakey";s:9:"last_name";s:4:"type";s:4:"text";s:5:"label";s:9:"Last Name";s:8:"required";i:0;s:6:"public";i:1;s:8:"editable";i:1;s:8:"position";s:1:"3";s:6:"in_row";s:9:"_um_row_1";s:10:"in_sub_row";s:1:"0";s:9:"in_column";s:1:"1";s:8:"in_group";s:0:"";}s:9:"_um_row_1";a:4:{s:4:"type";s:3:"row";s:2:"id";s:9:"_um_row_1";s:8:"sub_rows";s:1:"1";s:4:"cols";s:1:"1";}}',
				'_um_mode' => 'register',
				'_um_core' => 'register',
				'_um_register_use_custom_settings' => 0,
			);

			$this->core_form_meta['login'] = array(
				'_um_custom_fields' => 'a:3:{s:8:"username";a:13:{s:5:"title";s:18:"Username or E-mail";s:7:"metakey";s:8:"username";s:4:"type";s:4:"text";s:5:"label";s:18:"Username or E-mail";s:8:"required";i:1;s:6:"public";i:1;s:8:"editable";i:0;s:8:"validate";s:24:"unique_username_or_email";s:8:"position";s:1:"1";s:6:"in_row";s:9:"_um_row_1";s:10:"in_sub_row";s:1:"0";s:9:"in_column";s:1:"1";s:8:"in_group";s:0:"";}s:13:"user_password";a:16:{s:5:"title";s:8:"Password";s:7:"metakey";s:13:"user_password";s:4:"type";s:8:"password";s:5:"label";s:8:"Password";s:8:"required";i:1;s:6:"public";i:1;s:8:"editable";i:1;s:9:"min_chars";i:8;s:9:"max_chars";i:30;s:15:"force_good_pass";i:1;s:18:"force_confirm_pass";i:1;s:8:"position";s:1:"2";s:6:"in_row";s:9:"_um_row_1";s:10:"in_sub_row";s:1:"0";s:9:"in_column";s:1:"1";s:8:"in_group";s:0:"";}s:9:"_um_row_1";a:4:{s:4:"type";s:3:"row";s:2:"id";s:9:"_um_row_1";s:8:"sub_rows";s:1:"1";s:4:"cols";s:1:"1";}}',
				'_um_mode' => 'login',
				'_um_core' => 'login',
				'_um_login_use_custom_settings' => 0,
			);

			$this->core_form_meta['profile'] = array(
				'_um_custom_fields' => 'a:1:{s:9:"_um_row_1";a:4:{s:4:"type";s:3:"row";s:2:"id";s:9:"_um_row_1";s:8:"sub_rows";s:1:"1";s:4:"cols";s:1:"1";}}',
				'_um_mode' => 'profile',
				'_um_core' => 'profile',
				'_um_profile_use_custom_settings' => 0,
			);


			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_email_notifications
			 * @description Extend UM email notifications
			 * @input_vars
			 * [{"var":"$emails","type":"array","desc":"UM email notifications"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_email_notifications', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_email_notifications', 'my_email_notifications', 10, 1 );
			 * function my_email_notifications( $emails ) {
			 *     // your code here
			 *    $emails['my_email'] = array(
			 *        'key'           => 'my_email',
			 *        'title'         => __( 'my_email_title','ultimate-member' ),
			 *        'subject'       => 'my_email_subject',
			 *        'body'          => 'my_email_body',
			 *        'description'   => 'my_email_description',
			 *        'recipient'     => 'user', // set 'admin' for make administrator as recipient
			 *        'default_active' => true // can be false for make disabled by default
			 *     );
			 *
			 *     return $emails;
			 * }
			 * ?>
			 */
			$this->email_notifications = apply_filters( 'um_email_notifications', array(
				'welcome_email' => array(
					'key'           => 'welcome_email',
					'title'         => __( 'Account Welcome Email','ultimate-member' ),
					'subject'       => 'Welcome to {site_name}!',
					'body'          => 'Hi {display_name},<br /><br />' .
					                   'Thank you for signing up with {site_name}! Your account is now active.<br /><br />' .
					                   'To login please visit the following url:<br /><br />' .
					                   '{login_url} <br /><br />' .
					                   'Your account e-mail: {email} <br />' .
					                   'Your account username: {username} <br /><br />' .
					                   'If you have any problems, please contact us at {admin_email}<br /><br />' .
					                   'Thanks,<br />' .
					                   '{site_name}',
					'description'   => __('Whether to send the user an email when his account is automatically approved','ultimate-member'),
					'recipient'   => 'user',
					'default_active' => true
				),
				'checkmail_email' => array(
					'key'           => 'checkmail_email',
					'title'         => __( 'Account Activation Email','ultimate-member' ),
					'subject'       => 'Please activate your account',
					'body'          => 'Hi {display_name},<br /><br />' .
					                   'Thank you for signing up with {site_name}! To activate your account, please click the link below to confirm your email address:<br /><br />' .
					                   '{account_activation_link} <br /><br />' .
					                   'If you have any problems, please contact us at {admin_email}<br /><br />' .
					                   'Thanks, <br />' .
					                   '{site_name}',
					'description'   => __('Whether to send the user an email when his account needs e-mail activation','ultimate-member'),
					'recipient'   => 'user'
				),
				'pending_email' => array(
					'key'           => 'pending_email',
					'title'         => __( 'Your account is pending review','ultimate-member' ),
					'subject'       => '[{site_name}] New user account',
					'body'          => 'Hi {display_name}, <br /><br />' .
					                   'Thank you for signing up with {site_name}! Your account is currently being reviewed by a member of our team.<br /><br />' .
					                   'Please allow us some time to process your request.<br /><br />' .
					                   'If you have any problems, please contact us at {admin_email}<br /><br />' .
					                   'Thanks,<br />' .
					                   '{site_name}',
					'description'   => __('Whether to send the user an email when his account needs admin review','ultimate-member'),
					'recipient'   => 'user'
				),
				'approved_email' => array(
					'key'           => 'approved_email',
					'title'         => __( 'Account Approved Email','ultimate-member' ),
					'subject'       => 'Your account at {site_name} is now active',
					'body'          => 'Hi {display_name},<br /><br />' .
					                   'Thank you for signing up with {site_name}! Your account has been approved and is now active.<br /><br />' .
					                   'To login please visit the following url:<br /><br />' .
					                   '{login_url}<br /><br />' .
					                   'Your account e-mail: {email}<br />' .
					                   'Your account username: {username}<br />' .
					                   'Set your account password: {password_reset_link}<br /><br />' .
					                   'If you have any problems, please contact us at {admin_email}<br /><br />' .
					                   'Thanks,<br />' .
					                   '{site_name}',
					'description'   => __('Whether to send the user an email when his account is approved','ultimate-member'),
					'recipient'   => 'user'
				),
				'rejected_email' => array(
					'key'           => 'rejected_email',
					'title'         => __( 'Account Rejected Email','ultimate-member' ),
					'subject'       => 'Your account has been rejected',
					'body'          => 'Hi {display_name},<br /><br />' .
					                   'Thank you for applying for membership to {site_name}! We have reviewed your information and unfortunately we are unable to accept you as a member at this moment.<br /><br />' .
					                   'Please feel free to apply again at a future date.<br /><br />' .
					                   'Thanks,<br />' .
					                   '{site_name}',
					'description'   => __('Whether to send the user an email when his account is rejected','ultimate-member'),
					'recipient'   => 'user'
				),
				'inactive_email' => array(
					'key'           => 'inactive_email',
					'title'         => __( 'Account Deactivated Email','ultimate-member' ),
					'subject'       => 'Your account has been deactivated',
					'body'          => 'Hi {display_name},<br /><br />' .
					                   'This is an automated email to let you know your {site_name} account has been deactivated.<br /><br />' .
					                   'If you would like your account to be reactivated please contact us at {admin_email}<br /><br />' .
					                   'Thanks,<br />' .
					                   '{site_name}',
					'description'   => __('Whether to send the user an email when his account is deactivated','ultimate-member'),
					'recipient'   => 'user',
					'default_active' => true
				),
				'deletion_email' => array(
					'key'           => 'deletion_email',
					'title'         => __( 'Account Deleted Email','ultimate-member' ),
					'subject'       => 'Your account has been deleted',
					'body'          => 'Hi {display_name},<br /><br />' .
					                   'This is an automated email to let you know your {site_name} account has been deleted. All of your personal information has been permanently deleted and you will no longer be able to login to {site_name}.<br /><br />' .
					                   'If your account has been deleted by accident please contact us at {admin_email} <br />' .
					                   'Thanks,<br />' .
					                   '{site_name}',
					'description'   => __('Whether to send the user an email when his account is deleted','ultimate-member'),
					'recipient'   => 'user',
					'default_active' => true
				),
				'resetpw_email' => array(
					'key'           => 'resetpw_email',
					'title'         => __( 'Password Reset Email','ultimate-member' ),
					'subject'       => 'Reset your password',
					'body'          => 'Hi {display_name},<br /><br />' .
					                   'We received a request to reset the password for your account. If you made this request, click the link below to change your password:<br /><br />' .
					                   '{password_reset_link}<br /><br />' .
					                   'If you didn\'t make this request, you can ignore this email <br /><br />' .
					                   'Thanks,<br />' .
					                   '{site_name}',
					'description'   => __('Whether to send an email when users changed their password (Recommended, please keep on)','ultimate-member'),
					'recipient'   => 'user',
					'default_active' => true
				),
				'changedpw_email' => array(
					'key'           => 'changedpw_email',
					'title'         => __( 'Password Changed Email','ultimate-member' ),
					'subject'       => 'Your {site_name} password has been changed',
					'body'          => 'Hi {display_name},<br /><br />' .
					                   'You recently changed the password associated with your {site_name} account.<br /><br />' .
					                   'If you did not make this change and believe your {site_name} account has been compromised, please contact us at the following email address: {admin_email}<br /><br />' .
					                   'Thanks,<br />' .
					                   '{site_name}',
					'description'   => __('Whether to send the user an email when he request to reset password (Recommended, please keep on)','ultimate-member'),
					'recipient'   => 'user',
					'default_active' => true
				),
				'changedaccount_email' => array(
					'key'           => 'changedaccount_email',
					'title'         => __( 'Account Updated Email','ultimate-member' ),
					'subject'       => 'Your account at {site_name} was updated',
					'body'          => 'Hi {display_name},<br /><br />' .
					                   'You recently updated your {site_name} account.<br /><br />' .
					                   'If you did not make this change and believe your {site_name} account has been compromised, please contact us at the following email address: {admin_email}<br /><br />' .
					                   'Thanks,<br />' .
					                   '{site_name}',
					'description'   => __('Whether to send the user an email when he updated their account','ultimate-member'),
					'recipient'     => 'user',
					'default_active'=> true
				),
				'notification_new_user' => array(
					'key'           => 'notification_new_user',
					'title'         => __( 'New User Notification','ultimate-member' ),
					'subject'       => '[{site_name}] New user account',
					'body'          => '{display_name} has just created an account on {site_name}. To view their profile click here:<br /><br />' .
					                   '{user_profile_link}<br /><br />' .
					                   'Here is the submitted registration form:<br /><br />' .
					                   '{submitted_registration}',
					'description'   => __('Whether to receive notification when a new user account is approved','ultimate-member'),
					'recipient'   => 'admin',
					'default_active' => true
				),
				'notification_review' => array(
					'key'           => 'notification_review',
					'title'         => __( 'Account Needs Review Notification','ultimate-member' ),
					'subject'       => '[{site_name}] New user awaiting review',
					'body'          => '{display_name} has just applied for membership to {site_name} and is waiting to be reviewed.<br /><br />' .
					                   'To review this member please click the following link:<br /><br />' .
					                   '{user_profile_link}<br /><br />' .
					                   'Here is the submitted registration form:<br /><br />' .
					                   '{submitted_registration}',
					'description'   => __('Whether to receive notification when an account needs admin review','ultimate-member'),
					'recipient'   => 'admin'
				),
				'notification_deletion' => array(
					'key'           => 'notification_deletion',
					'title'         => __( 'Account Deletion Notification','ultimate-member' ),
					'subject'       => '[{site_name}] Account deleted',
					'body'          => '{display_name} has just deleted their {site_name} account.',
					'description'   => __('Whether to receive notification when an account is deleted','ultimate-member'),
					'recipient'   => 'admin'
				)
			) );


			//settings defaults
			$this->settings_defaults = array(
				'restricted_access_post_metabox'        => array( 'post' => 1, 'page' => 1 ),
				'uninstall_on_delete'                   => 0,
				'permalink_base'                        => 'user_login',
				'display_name'                          => 'full_name',
				'display_name_field'                    => '',
				'author_redirect'                       => 1,
				'members_page'                          => 1,
				'use_gravatars'                         => 0,
				'use_um_gravatar_default_builtin_image' => 'default',
				'use_um_gravatar_default_image'         => 0,
				'reset_require_strongpass'              => 0,
				'account_tab_password'                  => 1,
				'account_tab_privacy'                   => 1,
				'account_tab_notifications'             => 1,
				'account_tab_delete'                    => 1,
				'delete_account_text'                   => __( 'Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account enter your password below.', 'ultimate-member' ),
				'delete_account_no_pass_required_text'  => __( 'Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account, click on the button below.', 'ultimate-member' ),
				'account_name'                          => 1,
				'account_name_disable'                  => 0,
				'account_name_require'                  => 1,
				'account_email'                         => 1,
				'account_general_password'              => 0,
				'account_hide_in_directory'             => 1,
				'account_hide_in_directory_default'     => 'No',
				'account_require_strongpass'            => 0,
				'photo_thumb_sizes'                     => array( 40, 80, 190 ),
				'cover_thumb_sizes'                     => array( 300, 600 ),
				'accessible'                            => 0,
				'access_redirect'                       => '',
				'access_exclude_uris'                   => array(),
				'home_page_accessible'                  => 1,
				'category_page_accessible'              => 1,
				'restricted_access_message'             => '',
				'restricted_blocks'                     => 0,
				'enable_blocks'                         => 0,
				'restricted_block_message'              => '',
				'enable_reset_password_limit'           => 1,
				'reset_password_limit_number'           => 3,
				'blocked_emails'                        => '',
				'blocked_words'                         => 'admin' . "\r\n" . 'administrator' . "\r\n" . 'webmaster' . "\r\n" . 'support' . "\r\n" . 'staff',
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
				'rest_api_version'                      => '2.0',
				'member_directory_own_table'         => 0,
				'profile_show_html_bio'                 => 0,
			);

			add_filter( 'um_get_tabs_from_config', '__return_true' );

			$tabs = UM()->profile()->tabs();

			foreach ( $tabs as $id => $tab ) {

				if ( ! empty( $tab['hidden'] ) ) {
					continue;
				}

				$this->settings_defaults[ 'profile_tab_' . $id ] = 1;

				if ( ! isset( $tab['default_privacy'] ) ) {
					$this->settings_defaults[ 'profile_tab_' . $id . '_privacy' ] = 0;
					$this->settings_defaults[ 'profile_tab_' . $id . '_roles' ] = '';
				}
			}

			foreach ( $this->email_notifications as $key => $notification ) {
				$this->settings_defaults[ $key . '_on' ] = ! empty( $notification['default_active'] );
				$this->settings_defaults[ $key . '_sub' ] = $notification['subject'];
				$this->settings_defaults[ $key ] = $notification['body'];
			}

			foreach ( $this->core_pages as $page_s => $page ) {
				$page_id = UM()->options()->get_core_page_id( $page_s );
				$this->settings_defaults[ $page_id ] = '';
			}

			foreach( $this->core_form_meta_all as $key => $value ) {
				$this->settings_defaults[ str_replace( '_um_', '', $key ) ] = $value;
			}


			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_default_settings_values
			 * @description Extend UM default settings
			 * @input_vars
			 * [{"var":"$settings","type":"array","desc":"UM default settings"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_default_settings_values', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_default_settings_values', 'my_default_settings_values', 10, 1 );
			 * function my_default_settings_values( $settings ) {
			 *     // your code here
			 *     return $settings;
			 * }
			 * ?>
			 */
			$this->settings_defaults = apply_filters( 'um_default_settings_values', $this->settings_defaults );

			$this->permalinks = $this->get_core_pages();



			$this->default_roles_metadata = array(
				/*
                 * All caps map
                 *
                 * '_um_can_access_wpadmin'            => 1,
                    '_um_can_not_see_adminbar'          => 0,
                    '_um_can_edit_everyone'             => 1,
                    '_um_can_edit_roles'                => '',
                    '_um_can_delete_everyone'           => 1,
                    '_um_can_delete_roles'              => '',
                    '_um_after_delete'                  => '',
                    '_um_delete_redirect_url'           => '',
                    '_um_can_edit_profile'              => 1,
                    '_um_can_delete_profile'            => 1,
                    '_um_default_homepage'              => 1,
                    '_um_redirect_homepage'             => '',
                    '_um_after_login'                   => 'redirect_admin',
                    '_um_login_redirect_url'            => '',
                    '_um_after_logout'                  => 'redirect_home',
                    '_um_logout_redirect_url'           => '',
                    '_um_can_view_all'                  => 1,
                    '_um_can_view_roles'                => '',
                    '_um_can_make_private_profile'      => 1,
                    '_um_can_access_private_profile'    => 1,
                    '_um_status'                        => 'approved',
                    '_um_auto_approve_act'              => 'redirect_profile',
                    '_um_auto_approve_url'              => '',
                    '_um_login_email_activate'          => '',
                    '_um_checkmail_action'              => '',
                    '_um_checkmail_message'             => '',
                    '_um_checkmail_url'                 => '',
                    '_um_url_email_activate'            => '',
                    '_um_pending_action'                => '',
                    '_um_pending_message'               => '',
                    '_um_pending_url'                   => '',
                 *
                 * */


				'subscriber' => array(
					'_um_can_access_wpadmin'            => 0,
					'_um_can_not_see_adminbar'          => 1,
					'_um_can_edit_everyone'             => 0,
					'_um_can_delete_everyone'           => 0,
					'_um_can_edit_profile'              => 1,
					'_um_can_delete_profile'            => 1,
					'_um_after_login'                   => 'redirect_profile',
					'_um_after_logout'                  => 'redirect_home',
					'_um_default_homepage'              => 1,
					'_um_can_view_all'                  => 1,
					'_um_can_make_private_profile'      => 0,
					'_um_can_access_private_profile'    => 0,
					'_um_status'                        => 'approved',
					'_um_auto_approve_act'              => 'redirect_profile',
				),
				'author' => array(
					'_um_can_access_wpadmin'            => 0,
					'_um_can_not_see_adminbar'          => 1,
					'_um_can_edit_everyone'             => 0,
					'_um_can_delete_everyone'           => 0,
					'_um_can_edit_profile'              => 1,
					'_um_can_delete_profile'            => 1,
					'_um_after_login'                   => 'redirect_profile',
					'_um_after_logout'                  => 'redirect_home',
					'_um_default_homepage'              => 1,
					'_um_can_view_all'                  => 1,
					'_um_can_make_private_profile'      => 0,
					'_um_can_access_private_profile'    => 0,
					'_um_status'                        => 'approved',
					'_um_auto_approve_act'              => 'redirect_profile',
				),
				'contributor' => array(
					'_um_can_access_wpadmin'            => 0,
					'_um_can_not_see_adminbar'          => 1,
					'_um_can_edit_everyone'             => 0,
					'_um_can_delete_everyone'           => 0,
					'_um_can_edit_profile'              => 1,
					'_um_can_delete_profile'            => 1,
					'_um_after_login'                   => 'redirect_profile',
					'_um_after_logout'                  => 'redirect_home',
					'_um_default_homepage'              => 1,
					'_um_can_view_all'                  => 1,
					'_um_can_make_private_profile'      => 0,
					'_um_can_access_private_profile'    => 0,
					'_um_status'                        => 'approved',
					'_um_auto_approve_act'              => 'redirect_profile',
				),
				'editor' => array(
					'_um_can_access_wpadmin'            => 0,
					'_um_can_not_see_adminbar'          => 1,
					'_um_can_edit_everyone'             => 0,
					'_um_can_delete_everyone'           => 0,
					'_um_can_edit_profile'              => 1,
					'_um_can_delete_profile'            => 1,
					'_um_after_login'                   => 'redirect_profile',
					'_um_after_logout'                  => 'redirect_home',
					'_um_default_homepage'              => 1,
					'_um_can_view_all'                  => 1,
					'_um_can_make_private_profile'      => 0,
					'_um_can_access_private_profile'    => 0,
					'_um_status'                        => 'approved',
					'_um_auto_approve_act'              => 'redirect_profile',
				),
				'administrator' => array(
					'_um_can_access_wpadmin'            => 1,
					'_um_can_not_see_adminbar'          => 0,
					'_um_can_edit_everyone'             => 1,
					'_um_can_delete_everyone'           => 1,
					'_um_can_edit_profile'              => 1,
					'_um_can_delete_profile'            => 1,
					'_um_default_homepage'              => 1,
					'_um_after_login'                   => 'redirect_admin',
					'_um_after_logout'                  => 'redirect_home',
					'_um_can_view_all'                  => 1,
					'_um_can_make_private_profile'      => 1,
					'_um_can_access_private_profile'    => 1,
					'_um_status'                        => 'approved',
					'_um_auto_approve_act'              => 'redirect_profile',
				),
			);
		}


		/**
		 * Get UM Pages
		 *
		 * @return array
		 */
		function get_core_pages() {
			$permalink = array();
			$core_pages = array_keys( $this->core_pages );
			if ( empty( $core_pages ) ) {
				return $permalink;
			}

			foreach ( $core_pages as $page_key ) {
				$page_option_key = UM()->options()->get_core_page_id( $page_key );
				$permalink[ $page_key ] = UM()->options()->get( $page_option_key );
			}

			return $permalink;
		}


		/**
		 * @todo make config class not cycled
		 */
		function set_core_page() {
			$this->core_pages = apply_filters( 'um_core_pages', array(
				'user'              => array( 'title' => __( 'User', 'ultimate-member' ) ),
				'login'             => array( 'title' => __( 'Login', 'ultimate-member' ) ),
				'register'          => array( 'title' => __( 'Register', 'ultimate-member' ) ),
				'members'           => array( 'title' => __( 'Members', 'ultimate-member' ) ),
				'logout'            => array( 'title' => __( 'Logout', 'ultimate-member' ) ),
				'account'           => array( 'title' => __( 'Account', 'ultimate-member' ) ),
				'password-reset'    => array( 'title' => __( 'Password Reset', 'ultimate-member' ) ),
			) );
		}
		//end class
	}
}