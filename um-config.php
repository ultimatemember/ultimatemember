<?php

	global $ultimatemember;


/***
***	@
***/
	
$this->sections[] = array(

    'icon'       => 'um-faicon-user',
    'title'      => __( 'Users','ultimatemember'),
    'fields'     => array(
		
		array(
				'id'       		=> 'default_role',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Default New User Role' ),
                'desc' 	   		=> __( 'Select the default role that will be assigned to user after registration If you did not specify custom role settings per form.' ),
                'default'  		=> 'member',
				'options' 		=> $ultimatemember->query->get_roles( ),
				'placeholder' 	=> __('Choose user role...'),
        ),
		
		array(
				'id'       		=> 'permalink_base',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Profile Permalink Base' ),
                'desc' 	   		=> __( 'Here you can control the permalink structure of the user profile URL globally' ),
                'default'  		=> 'user_login',
				'desc'			=> 'e.g. ' . trailingslashit( um_get_core_page('user') ) .'<strong>username</strong>/',
				'options' 		=> array(
									'user_login' 		=> 'Username',
									'name' 				=> 'First and Last Name',
									'user_id' 			=> 'User ID',
				),
				'placeholder' 	=> __('Select...')
        ),
		
		array(
				'id'       		=> 'display_name',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'User Display Name' ),
                'desc' 	   		=> __( 'This is the name that will be displayed for users on the front end of your site. Default setting uses first/last name as display name if it exists' ),
                'default'  		=> 'full_name',
				'options' 		=> array(
									'username' 			=> 'Username',
									'full_name' 		=> 'First name & last name',
									'sur_name' 			=> 'Last name & first name',
									'initial_name'		=> 'First name & first initial of last name',
									'initial_name_f'	=> 'First initial of first name & last name',
									'first_name'		=> 'First name only',
									'public_name'		=> 'Display name',
									'field' 			=> 'Custom field(s)',
				),
				'placeholder' 	=> __('Select...')
        ),
		
        array(
                'id'       		=> 'display_name_field',
                'type'     		=> 'text',
                'title'   		=> __( 'Display Name Custom Field(s)' ),
				'desc' 	   		=> 'Specify the custom field meta key or custom fields seperated by comma that you want to use to display users name on the frontend of your site',
				'required'		=> array( 'display_name', '=', 'field' ),
        ),
		
        array(
                'id'       		=> 'author_redirect',
                'type'     		=> 'switch',
                'title'   		=> __( 'Automatically redirect author page to their profile?' ),
				'default' 		=> 1,
				'desc' 	   		=> __('If enabled, author pages will automatically redirect to the user\'s profile page'),
				'on'			=> __('Yes','ultimatemember'),
				'off'			=> __('No','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'members_page',
                'type'     		=> 'switch',
                'title'   		=> __( 'Enable Members Directory' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Control whether to enable or disable member directories on this site',
				'on'			=> __('Yes','ultimatemember'),
				'off'			=> __('No','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'use_gravatars',
                'type'     		=> 'switch',
                'title'   		=> __( 'Use Gravatars?' ),
				'default' 		=> 0,
				'desc' 	   		=> __('Do you want to use gravatars instead of the default plugin profile photo (If the user did not upload a custom profile photo / avatar)','ultimatemember'),
				'on'			=> __('Yes','ultimatemember'),
				'off'			=> __('No','ultimatemember'),
        ),
		
	)

);

/***
***	@
***/
	
$this->sections[] = array(

    'icon'       => 'um-faicon-cog',
    'title'      => __( 'Account','ultimatemember'),
    'fields'     => array(

        array(
                'id'       		=> 'account_tab_password',
                'type'     		=> 'switch',
                'title'   		=> __( 'Password Account Tab' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Enable/disable the Password account tab in account page',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'account_tab_privacy',
                'type'     		=> 'switch',
                'title'   		=> __( 'Privacy Account Tab' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Enable/disable the Privacy account tab in account page',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'account_tab_notifications',
                'type'     		=> 'switch',
                'title'   		=> __( 'Notifications Account Tab' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Enable/disable the Notifications account tab in account page',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
		array(
                'id'       		=> 'account_tab_delete',
                'type'     		=> 'switch',
                'title'   		=> __( 'Delete Account Tab' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Enable/disable the Delete account tab in account page',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'delete_account_text',
                'type'    		=> 'editor',
                'title'    		=> __( 'Account Deletion Custom Text' ),
                'default'  		=> 'Are you sure you want to delete your account? This will erase all of your account data from the site. To delete your account enter your password below',
				'desc' 	   		=> __('This is custom text that will be displayed to users before they delete their accounts from your site','ultimatemember'),
				'args'     		=> array(
								'teeny'            => false,
								'media_buttons'    => false,
								'textarea_rows'    => 6
				),
        ),

	)

);

/***
***	@
***/

$this->sections[] = array(

    'icon'       => 'um-faicon-lock',
    'title'      => __( 'Access','ultimatemember'),
    'fields'     => array(

        array(
                'id'       		=> 'panic_key',
                'type'     		=> 'text',
                'title'   		=> __( 'Panic Key','ultimatemember' ),
				'desc' 	   		=> 'Panic Key is a random generated key that allow you to access the WordPress backend always regardless of backend settings.',
				'default'		=> $ultimatemember->validation->randomize(),
				'desc'			=> trailingslashit( get_bloginfo('url') ).'wp-admin/?um_panic_key=<strong>your_panic_key</strong>'
        ),
		
        array(
                'id'       		=> 'accessible',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'   		=> __( 'Global Site Access','ultimatemember' ),
				'default' 		=> 0,
				'desc' 	   		=> 'Globally control the access of your site, you can have seperate restrict options per post/page by editing the desired item.',
				'options' 		=> array(
									0 		=> 'Site accessible to Everyone',
									2 		=> 'Site accessible to Logged In Users'
				)
        ),

        array(
                'id'       		=> 'access_redirect',
                'type'     		=> 'text',
                'title'   		=> __( 'Custom Redirect URL','ultimatemember' ),
				'desc' 	   		=> 'A logged out user will be redirected to this url If he is not permitted to access the site',
				'required'		=> array( 'accessible', '=', 2 ),
        ),
		
		array(
				'id'       		=> 'access_exclude_uris',
                'type'     		=> 'multi_text',
				'default'		=> array(),
                'title'    		=> __( 'Exclude the following URLs','ultimatemember' ),
                'desc' 	   		=> __( 'Here you can exclude URLs beside the redirect URI to be accessible to everyone','ultimatemember' ),
				'add_text'		=> __('Add New URL','ultimatemember'),
				'required'		=> array( 'accessible', '=', 2 ),
		),
		
        array(
                'id'       		=> 'exclude_from_main_loop',
                'type'     		=> 'switch',
                'title'   		=> __( 'Exclude restricted pages from main loop','ultimatemember' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Whether to exclude restricted pages from main loop',
				'on'			=> __('Yes','ultimatemember'),
				'off'			=> __('No','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'exclude_from_search_loop',
                'type'     		=> 'switch',
                'title'   		=> __( 'Exclude restricted pages from search loop','ultimatemember' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Whether to exclude restricted pages from search results',
				'on'			=> __('Yes','ultimatemember'),
				'off'			=> __('No','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'exclude_from_archive_loop',
                'type'     		=> 'switch',
                'title'   		=> __( 'Exclude restricted pages from archive loop','ultimatemember' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Whether to exclude restricted pages from archives',
				'on'			=> __('Yes','ultimatemember'),
				'off'			=> __('No','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'wpadmin_login',
                'type'     		=> 'switch',
                'title'   		=> __( 'Allow Backend Login Screen for Guests','ultimatemember' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Control whether guests are able to access the WP-admin login screen or not',
				'on'			=> __('Yes','ultimatemember'),
				'off'			=> __('No','ultimatemember'),
        ),
		
		array(
				'id'       		=> 'wpadmin_login_redirect',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Redirect to alternative login page','ultimatemember' ),
                'desc' 	   		=> __( 'If you disable backend access to login screen, specify here where a user will be redirected','ultimatemember' ),
				'required'		=> array( 'wpadmin_login', '=', 0 ),
                'default'  		=> 'um_login_page',
				'options' 		=> array(
									'um_login_page' 	=> 'UM Login Page',
									'custom_url' 		=> 'Custom URL',
				)
        ),
		
        array(
                'id'       		=> 'wpadmin_login_redirect_url',
                'type'     		=> 'text',
                'title'   		=> __( 'Custom URL','ultimatemember' ),
				'desc' 	   		=> 'Enter an alternate url here to redirect a user If they try to access the backend register screen',
				'required'		=> array( 'wpadmin_login_redirect', '=', 'custom_url' ),
        ),
		
        array(
                'id'       		=> 'wpadmin_register',
                'type'     		=> 'switch',
                'title'   		=> __( 'Allow Backend Register Screen for Guests','ultimatemember' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Control whether guests are able to access the WP-admin register screen or not',
				'on'			=> __('Yes','ultimatemember'),
				'off'			=> __('No','ultimatemember'),
        ),
		
		array(
				'id'       		=> 'wpadmin_register_redirect',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Redirect to alternative register page','ultimatemember' ),
                'desc' 	   		=> __( 'If you disable backend access to register screen, specify here where a user will be redirected','ultimatemember' ),
				'required'		=> array( 'wpadmin_register', '=', 0 ),
                'default'  		=> 'um_register_page',
				'options' 		=> array(
									'um_register_page' 	=> 'UM Register Page',
									'custom_url' 		=> 'Custom URL',
				)
        ),
		
        array(
                'id'       		=> 'wpadmin_register_redirect_url',
                'type'     		=> 'text',
                'title'   		=> __( 'Custom URL','ultimatemember' ),
				'desc' 	   		=> 'Enter an alternate url here to redirect a user If they try to access the backend register screen',
				'required'		=> array( 'wpadmin_register_redirect', '=', 'custom_url' ),
        ),
		
        array(
				'id'       		=> 'wpadmin_allow_ips',
                'type'     		=> 'textarea',
                'title'    		=> __( 'Whitelisted Backend IPs','ultimatemember' ),
				'desc'			=> __('Always allow the specified IP addresses to access the backend login screen and WP-admin to avoid being locked from site backend.')
        ),
		
        array(
				'id'       		=> 'blocked_ips',
                'type'     		=> 'textarea',
                'title'    		=> __( 'Blocked IP Addresses','ultimatemember' ),
				'desc'			=> __('This will block the listed IPs from signing up or signing in to the site, you can use full IP numbers or target specific range with a wildcard')
        ),
		
        array(
				'id'       		=> 'blocked_emails',
                'type'     		=> 'textarea',
                'title'    		=> __( 'Blocked Email Addresses','ultimatemember' ),
				'desc'			=> __('This will block the specified e-mail addresses from being able to sign up or sign in to your site.')
        ),
		
        array(
				'id'       		=> 'blocked_words',
                'type'     		=> 'textarea',
                'title'    		=> __( 'Blacklist Words','ultimatemember' ),
				'desc'			=> __('This option lets you specify blacklist of words to prevent anyone from signing up with such a word as their username'),
				'default'		=>  'admin' . "\r\n" . 'administrator' . "\r\n" . 'webmaster' . "\r\n" . 'support' . "\r\n" . 'staff'
        ),
		
	)

);

/***
***	@
***/
	
$this->sections[] = array(

    'icon'       => 'um-faicon-envelope-o',
    'title'      => __( 'Emails','ultimatemember'),
    'fields'     => array(

		array(
				'id'       => 'mail_from',
                'type'     => 'text',
                'title'    => __( 'Mail appears from','ultimatemember' ),
                'desc' 	   => __( 'e.g. Site Name','ultimatemember' ),
                'default'  => get_bloginfo('name'),
        ),

        array(
                'id'       => 'mail_from_addr',
                'type'     => 'text',
                'title'    => __( 'Mail appears from address','ultimatemember' ),
                'desc' => __( 'e.g. admin@companyname.com','ultimatemember' ),
                'default'  => get_bloginfo('admin_email'),
        ),

        array(
                'id'       => 'welcome_email_on',
                'type'     => 'switch',
                'title'    => __( 'Account Welcome Email','ultimatemember' ),
				'default'  => 1,
				'desc' 	   => 'Whether to send the user an email when his account is automatically approved',
        ),
		
        array(
                'id'       => 'welcome_email_sub',
                'type'     => 'text',
                'title'    => __( 'Account Welcome Email','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => 'Welcome to {site_name}!',
				'required' => array( 'welcome_email_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),

        array(
				'id'       => 'welcome_email',
                'type'     => 'textarea',
                'title'    => __( 'Account Welcome Email','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
				'required' => array( 'welcome_email_on', '=', 1 ),
                'default'  => 'Hi {display_name},' . "\r\n\r\n" .
										  'Thank you for signing up with {site_name}! Your account is now active.' . "\r\n\r\n" .
										  'To login please visit the following url:'  . "\r\n\r\n" .
										  '{login_url}'  . "\r\n\r\n" .
										  'Your account e-mail: {email}' . "\r\n" .
										  'Your account username: {username}' . "\r\n" .
										  'Your account password: {password}' . "\r\n\r\n" .
										  'If you have any problems, please contact us at {admin_email}'  . "\r\n\r\n" .
										  'Thanks,' . "\r\n" .
										  '{site_name}',
        ),
		
        array(
                'id'       => 'checkmail_email_on',
                'type'     => 'switch',
                'title'    => __( 'Account Activation Email','ultimatemember' ),
				'default'  => 1,
				'desc' 	   => 'Whether to send the user an email when his account needs e-mail activation',
        ),
		
        array(
                'id'       => 'checkmail_email_sub',
                'type'     => 'text',
                'title'    => __( 'Account Activation Email','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => 'Please activate your account',
				'required' => array( 'checkmail_email_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),

        array(
                'id'       => 'checkmail_email',
                'type'     => 'textarea',
                'title'    => __( 'Account Activation Email','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
				'required' => array( 'checkmail_email_on', '=', 1 ),
                'default'  => 'Hi {display_name},' . "\r\n\r\n" .
										  'Thank you for signing up with {site_name}! To activate your account, please click the link below to confirm your email address:' . "\r\n\r\n" .
										  '{account_activation_link}'  . "\r\n\r\n" .
										  'If you have any problems, please contact us at {admin_email}'  . "\r\n\r\n" .
										  'Thanks,' . "\r\n" .
										  '{site_name}',
        ),
		
        array(
                'id'       => 'pending_email_on',
                'type'     => 'switch',
                'title'    => __( 'Pending Review Email','ultimatemember' ),
				'default'  => 1,
				'desc' 	   => 'Whether to send the user an email when his account needs admin review',
        ),
		
        array(
                'id'       => 'pending_email_sub',
                'type'     => 'text',
                'title'    => __( 'Pending Review Email','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => 'Your account is pending review',
				'required' => array( 'pending_email_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),

        array(
                'id'       => 'pending_email',
                'type'     => 'textarea',
                'title'    => __( 'Pending Review Email','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
				'required' => array( 'pending_email_on', '=', 1 ),
                'default'  => 'Hi {display_name},' . "\r\n\r\n" .
										  'Thank you for signing up with {site_name}! Your account is currently being reviewed by a member of our team.' . "\r\n\r\n" .
										  'Please allow us some time to process your request.' . "\r\n\r\n" .
										  'If you have any problems, please contact us at {admin_email}'  . "\r\n\r\n" .
										  'Thanks,' . "\r\n" .
										  '{site_name}',
        ),
		
        array(
                'id'       => 'approved_email_on',
                'type'     => 'switch',
                'title'    => __( 'Account Approved Email','ultimatemember' ),
				'default'  => 1,
				'desc' 	   => 'Whether to send the user an email when his account is approved',
        ),
		
        array(
                'id'       => 'approved_email_sub',
                'type'     => 'text',
                'title'    => __( 'Account Approved Email','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => 'Your account at {site_name} is now active',
				'required' => array( 'approved_email_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),

        array(
				'id'       => 'approved_email',
                'type'     => 'textarea',
                'title'    => __( 'Account Approved Email','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
				'required' => array( 'approved_email_on', '=', 1 ),
                'default'  => 'Hi {display_name},' . "\r\n\r\n" .
										  'Thank you for signing up with {site_name}! Your account has been approved and is now active.' . "\r\n\r\n" .
										  'To login please visit the following url:'  . "\r\n\r\n" .
										  '{login_url}'  . "\r\n\r\n" .
										  'Your account e-mail: {email}' . "\r\n" .
										  'Your account username: {username}' . "\r\n" .
										  'Your account password: {password}' . "\r\n\r\n" .
										  'If you have any problems, please contact us at {admin_email}'  . "\r\n\r\n" .
										  'Thanks,' . "\r\n" .
										  '{site_name}',
        ),
		
        array(
                'id'       => 'rejected_email_on',
                'type'     => 'switch',
                'title'    => __( 'Account Rejected Email','ultimatemember' ),
				'default'  => 1,
				'desc' 	   => 'Whether to send the user an email when his account is rejected',
        ),
		
        array(
                'id'       => 'rejected_email_sub',
                'type'     => 'text',
                'title'    => __( 'Account Rejected Email','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => 'Your account has been rejected',
				'required' => array( 'rejected_email_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),

        array(
                'id'       => 'rejected_email',
                'type'     => 'textarea',
                'title'    => __( 'Account Rejected Email','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
				'required' => array( 'rejected_email_on', '=', 1 ),
                'default'  => 'Hi {display_name},' . "\r\n\r\n" .
										  'Thank you for applying for membership to {site_name}! We have reviewed your information and unfortunately we are unable to accept you as a member at this moment.'  . "\r\n\r\n" .
										  'Please feel free to apply again at a future date.'  . "\r\n\r\n" .
										  'Thanks,' . "\r\n" .
										  '{site_name}',
        ),

        array(
                'id'       => 'inactive_email_on',
                'type'     => 'switch',
                'title'    => __( 'Account Deactivated Email','ultimatemember' ),
				'default'  => 1,
				'desc' 	   => 'Whether to send the user an email when his account is deactivated',
        ),
		
        array(
                'id'       => 'inactive_email_sub',
                'type'     => 'text',
                'title'    => __( 'Account Deactivated Email','ultimatemember' ),
                'subtitle' => __( 'Subject Line' ),
                'default'  => 'Your account has been deactivated',
				'required' => array( 'inactive_email_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),
						
        array(
                'id'       => 'inactive_email',
                'type'     => 'textarea',
                'title'    => __( 'Account Deactivated Email','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
				'required' => array( 'inactive_email_on', '=', 1 ),
                'default'  => 'Hi {display_name},' . "\r\n\r\n" .
										  'This is an automated email to let you know your {site_name} account has been deactivated.'  . "\r\n\r\n" .
										  'If you would like your account to be reactivated please contact us at {admin_email}'  . "\r\n\r\n" .
										  'Thanks,' . "\r\n" .
										  '{site_name}',
        ),

        array(
                'id'       => 'deletion_email_on',
                'type'     => 'switch',
                'title'    => __( 'Account Deleted Email','ultimatemember' ),
				'default'  => 1,
				'desc' 	   => 'Whether to send the user an email when his account is deleted',
        ),
		
        array(
                'id'       => 'deletion_email_sub',
                'type'     => 'text',
                'title'    => __( 'Account Deleted Email','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => 'Your account has been deleted',
				'required' => array( 'deletion_email_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
		),

        array(
                'id'       => 'deletion_email',
                'type'     => 'textarea',
                'title'    => __( 'Account Deleted Email','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
				'required' => array( 'deletion_email_on', '=', 1 ),
                'default'  => 'Hi {display_name},' . "\r\n\r\n" .
										  'This is an automated email to let you know your {site_name} account has been deleted. All of your personal information has been permanently deleted and you will no longer be able to login to {site_name}.'  . "\r\n\r\n" .
										  'If your account has been deleted by accident please contact us at {admin_email}'  . "\r\n\r\n" .
										  'Thanks,' . "\r\n" .
										  '{site_name}',
        ),
		
        array(
                'id'       => 'resetpw_email_on',
                'type'     => 'switch',
                'title'    => __( 'Password Reset Email','ultimatemember' ),
				'default'  => 1,
				'desc' 	   => 'Whether to send the user an email when he request to reset password (Recommended, please keep on)',
        ),
		
        array(
                'id'       => 'resetpw_email_sub',
                'type'     => 'text',
                'title'    => __( 'Password Reset Email','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => 'Reset your password',
				'required' => array( 'resetpw_email_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),

        array(
                'id'       => 'resetpw_email',
                'type'     => 'textarea',
                'title'    => __( 'Password Reset Email','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
				'required' => array( 'resetpw_email_on', '=', 1 ),
                'default'  => 'Hi {display_name},' . "\r\n\r\n" .
										'We received a request to reset the password for your account. If you made this request, click the link below to change your password:'  . "\r\n\r\n" .
										'{password_reset_link}'  . "\r\n\r\n" . 
										'If you didn\'t make this request, you can ignore this email'  . "\r\n\r\n" .
										'Thanks,' . "\r\n" .
										'{site_name}',
        ),
		
        array(
                'id'       => 'changedpw_email_on',
                'type'     => 'switch',
                'title'    => __( 'Password Changed Email','ultimatemember' ),
				'default'  => 1,
				'desc' 	   => 'Whether to send the user an email when he request to reset password (Recommended, please keep on)',
        ),
		
        array(
                'id'       => 'changedpw_email_sub',
                'type'     => 'text',
                'title'    => __( 'Password Changed Email','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => 'Your {site_name} password has been changed',
				'required' => array( 'changedpw_email_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),

        array(
                'id'       => 'changedpw_email',
                'type'     => 'textarea',
                'title'    => __( 'Password Changed Email','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
				'required' => array( 'changedpw_email_on', '=', 1 ),
                'default'  => 'Hi {display_name},' . "\r\n\r\n" .
										'You recently changed the password associated with your {site_name} account.'  . "\r\n\r\n" .
										'If you did not make this change and believe your {site_name} account has been compromised, please contact us at the following email address: {admin_email}'  . "\r\n\r\n" . 
										'Thanks,' . "\r\n" .
										'{site_name}',
        ),

	)

);

/***
***	@
***/

$this->sections[] = array(
    
	'icon'    => 'um-faicon-bell-o',
    'title'   => __( 'Notifications','ultimatemember' ),
    'fields'  => array(

        array(
                'id'       => 'admin_email',
                'type'     => 'text',
                'title'    => __( 'Admin E-mail Address','ultimatemember' ),
                'default'  => get_bloginfo('admin_email'),
				'desc' => __( 'e.g. admin@companyname.com','ultimatemember' ),
        ),

        array(
                'id'       => 'notification_new_user_on',
                'type'     => 'switch',
                'title'    => __( 'New User Notification','ultimatemember' ),
				'default'  => 1,
				'desc' 	   => 'Whether to receive notification when a new user account is approved',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),

        array(
                'id'       => 'notification_new_user_sub',
                'type'     => 'text',
                'title'    => __( 'New User Notification','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => '[{site_name}] New user account',
				'required' => array( 'notification_new_user_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),

        array(
                'id'       => 'notification_new_user',
                'type'     => 'textarea',
                'title'    => __( 'New User Notification','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
                'default'  => '{display_name} has just created an account on {site_name}. To view their profile click here:' . "\r\n\r\n" .
								'{user_profile_link}'  . "\r\n\r\n" .
								'Here is the submitted registration form:' . "\r\n\r\n" .
								'{submitted_registration}',
				'required' => array( 'notification_new_user_on', '=', 1 ),
				'desc' 	   => 'This is the content of the e-mail',
        ),

        array(
                'id'       => 'notification_review_on',
                'type'     => 'switch',
                'title'    => __( 'Account Needs Review Notification','ultimatemember' ),
				'default'  => 0,
				'desc' 	   => 'Whether to receive notification when an account needs admin review',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),

        array(
                'id'       => 'notification_review_sub',
                'type'     => 'text',
                'title'    => __( 'Account Needs Review Notification','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => '[{site_name}] New user awaiting review',
				'required' => array( 'notification_review_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),

        array(
                'id'       => 'notification_review',
                'type'     => 'textarea',
                'title'    => __( 'Account Needs Review Notification','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
                'default'  => '{display_name} has just applied for membership to {site_name} and is waiting to be reviewed.' . "\r\n\r\n" .
								'To review this member please click the following link:'  . "\r\n\r\n" .
								'{user_profile_link}'  . "\r\n\r\n" .
								'Here is the submitted registration form:' . "\r\n\r\n" .
								'{submitted_registration}',
				'required' => array( 'notification_review_on', '=', 1 ),
				'desc' 	   => 'This is the content of the e-mail',
		),

        array(
                'id'       => 'notification_deletion_on',
                'type'     => 'switch',
                'title'    => __( 'Account Deletion Notification','ultimatemember' ),
				'default'  => 0,
				'desc' 	   => 'Whether to receive notification when an account is deleted',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),

        array(
                'id'       => 'notification_deletion_sub',
                'type'     => 'text',
                'title'    => __( 'Account Deletion Notification','ultimatemember' ),
                'subtitle' => __( 'Subject Line','ultimatemember' ),
                'default'  => '[{site_name}] Account deleted',
				'required' => array( 'notification_deletion_on', '=', 1 ),
				'desc' 	   => __('This is the subject line of the e-mail','ultimatemember'),
        ),

        array(
                'id'       => 'notification_deletion',
                'type'     => 'textarea',
                'title'    => __( 'Account Deletion Notification','ultimatemember' ),
                'subtitle' => __( 'Message Body','ultimatemember' ),
                'default'  => '{display_name} has just deleted their {site_name} account.',
				'required' => array( 'notification_deletion_on', '=', 1 ),
				'desc' 	   => 'This is the content of the e-mail',
        ),

	)
   
);

/***
***	@
***/
	
$this->sections[] = array(

    'icon'       => 'um-faicon-cloud-upload',
    'title'      => __( 'Uploads','ultimatemember'),
    'fields'     => array(
		
		array(
				'id'       		=> 'photo_thumb_sizes',
                'type'     		=> 'multi_text',
                'title'    		=> __( 'Profile Photo Thumbnail Sizes','ultimatemember' ),
                'desc' 	   		=> __( 'Here you can define which thumbnail sizes will be created for each profile photo upload.','ultimatemember' ),
                'default'  		=> array( 40, 80, 190 ),
				'validate' 		=> 'numeric',
				'add_text'		=> __('Add New Size','ultimatemember'),
		),
		
		array(
				'id'       		=> 'cover_thumb_sizes',
                'type'     		=> 'multi_text',
                'title'    		=> __( 'Cover Photo Thumbnail Sizes','ultimatemember' ),
                'desc' 	   		=> __( 'Here you can define which thumbnail sizes will be created for each cover photo upload.','ultimatemember' ),
                'default'  		=> array( 300, 600 ),
				'validate' 		=> 'numeric',
				'add_text'		=> __('Add New Size','ultimatemember'),
		),
		
		array(
				'id'       		=> 'image_compression',
                'type'     		=> 'text',
                'title'    		=> __( 'Image Quality','ultimatemember' ),
                'desc' 	   		=> __( 'Quality is used to determine quality of image uploads, and ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file). The default range is 60.','ultimatemember' ),
                'default'  		=> 60,
				'validate' 		=> 'numeric',
        ),
		
		array(
				'id'       		=> 'image_max_width',
                'type'     		=> 'text',
                'title'    		=> __( 'Image Upload Maximum Width','ultimatemember' ),
                'desc' 	   		=> __( 'Any image upload above this width will be resized to this limit automatically.','ultimatemember' ),
                'default'  		=> 1000,
				'validate' 		=> 'numeric',
        ),
		
		array(
				'id'       		=> 'cover_min_width',
                'type'     		=> 'text',
                'title'    		=> __( 'Cover Photo Minimum Width','ultimatemember' ),
                'desc' 	   		=> __( 'This will be the minimum width for cover photo uploads','ultimatemember' ),
                'default'  		=> 1000,
				'validate' 		=> 'numeric',
        ),

	)

);

/***
***	@
***/
	
$this->sections[] = array(

    'icon'       => 'um-faicon-search',
    'title'      => __( 'SEO','ultimatemember'),
    'fields'     => array(

        array(
                'id'      		=> 'profile_title',
                'type'     		=> 'text',
                'title'    		=> __( 'User Profile Title','ultimatemember' ),
                'default'  		=> '{display_name} | ' . get_bloginfo('name'),
				'desc' 	   		=> 'This is the title that is displayed on a specific user profile',
        ),

        array(
				'id'       		=> 'profile_desc',
                'type'     		=> 'textarea',
				'default'		=> '{display_name} is on {site_name}. Join {site_name} to view {display_name}\'s profile',
                'title'    		=> __( 'User Profile Dynamic Meta Description','ultimatemember' ),
				'desc'			=> __('This will be used in the meta description that is available for search-engines.','ultimatemember')
        ),
		
	)

);

/***
***	@
***/
	
$this->sections[] = array(

    'icon'       => 'um-faicon-paint-brush',
    'title'      => __( 'Appearance','ultimatemember'),
    'fields'     => array(
		
	)

);

$this->sections[] = array(

    'subsection' => true,
    'title'      => __( 'General','ultimatemember'),
    'fields'     => array(
	
		array(
				'id'       		=> 'directory_template',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Members Default Template','ultimatemember' ),
                'desc' 	   		=> __( 'This will be the default template to output member directory','ultimatemember' ),
                'default'  		=> um_get_metadefault('directory_template'),
				'options' 		=> $ultimatemember->shortcodes->get_templates( 'members' ),
				'required'		=> array( 'xxxxxxxxxxxxx', '=', 'sssssssssssssssss' ),
        ),
		
        array(
				'id'       		=> 'active_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('active_color'),
                'title'    		=> __( 'General Active Color','ultimatemember' ),
                'validate' 		=> 'color',
				'desc'			=> __('Active color is used commonly with many plugin elements as highlighted color or active selection for example. This color demonstrates the primary active color of the plugin','ultimatemember'),
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'secondary_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('secondary_color'),
                'title'    		=> __( 'General Secondary Color','ultimatemember' ),
                'validate' 		=> 'color',
				'desc'			=> __('Secondary color is used for hovers, or active state for some elements of the plugin','ultimatemember'),
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'primary_btn_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('primary_btn_color'),
                'title'    		=> __( 'Default Primary Button Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'primary_btn_hover',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('primary_btn_hover'),
                'title'    		=> __( 'Default Primary Button Hover Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'primary_btn_text',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('primary_btn_text'),
                'title'    		=> __( 'Default Primary Button Text Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'secondary_btn_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('secondary_btn_color'),
                'title'    		=> __( 'Default Secondary Button Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'secondary_btn_hover',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('secondary_btn_hover'),
                'title'    		=> __( 'Default Secondary Button Hover Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'secondary_btn_text',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('secondary_btn_text'),
                'title'    		=> __( 'Default Secondary Button Text Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'help_tip_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('help_tip_color'),
                'title'    		=> __( 'Default Help Icon Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
	)
	
);

$this->sections[] = array(

    'subsection' => true,
    'title'      => __( 'Form Inputs','ultimatemember'),
    'fields'     => array(
	
        array(
				'id'       		=> 'form_field_label',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('form_field_label'),
                'title'    		=> __( 'Field Label Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
                'id'      		=> 'form_border',
                'type'     		=> 'text',
                'title'    		=> __( 'Field Border Style','ultimatemember' ),
                'default'  		=> um_get_metadefault('form_border'),
				'desc' 	   		=> 'The default border-style for input/fields in UM forms',
        ),
		
        array(
				'id'       		=> 'form_bg_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('form_bg_color'),
                'title'    		=> __( 'Field Background Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'form_text_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('form_text_color'),
                'title'    		=> __( 'Field Text Color' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'form_placeholder',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('form_placeholder'),
                'title'    		=> __( 'Field Placeholder Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'form_icon_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('form_icon_color'),
                'title'    		=> __( 'Field Font Icon Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
                'id'       		=> 'form_asterisk',
                'type'     		=> 'switch',
                'title'    		=> __( 'Show an asterisk for required fields','ultimatemember' ),
				'default'  		=> 0,
				'on'			=> __('Yes','ultimatemember'),
				'off'			=> __('No','ultimatemember'),
        ),
		
        array(
				'id'       		=> 'form_asterisk_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('form_asterisk_color'),
                'title'    		=> __( 'Field Required Asterisk Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
				'required'		=> array( 'form_asterisk', '=', '1' ),
        ),
		
	)
	
);

$this->sections[] = array(
	
    'subsection' => true,
    'title'      => __( 'Profile','ultimatemember'),
    'fields'     => array(

		array(
				'id'       		=> 'profile_template',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Profile Default Template','ultimatemember' ),
                'desc' 	   		=> __( 'This will be the default template to output profile','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_template'),
				'options' 		=> $ultimatemember->shortcodes->get_templates( 'profile' ),
        ),
		
        array(
                'id'      		=> 'profile_max_width',
                'type'     		=> 'text',
                'title'    		=> __( 'Profile Maximum Width','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_max_width'),
				'desc' 	   		=> 'The maximum width this shortcode can take from the page width',
        ),
		
        array(
                'id'      		=> 'profile_area_max_width',
                'type'     		=> 'text',
                'title'    		=> __( 'Profile Area Maximum Width','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_area_max_width'),
				'desc' 	   		=> 'The maximum width of the profile area inside profile (below profile header)',
        ),
		
		array(
				'id'       		=> 'profile_align',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Profile Shortcode Alignment','ultimatemember' ),
                'desc' 	   		=> __( 'The shortcode is centered by default unless you specify otherwise here' ),
                'default'  		=> um_get_metadefault('profile_align'),
				'options' 		=> array(
									'center' 			=> __('Centered','ultimatemember'),
									'left' 				=> __('Left aligned','ultimatemember'),
									'right' 			=> __('Right aligned','ultimatemember'),
				),
        ),
		
		array(
				'id'       		=> 'profile_icons',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Profile Field Icons' ),
                'desc' 	   		=> __( 'This is applicable for edit mode only','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_icons'),
				'options' 		=> array(
									'field' 			=> __('Show inside text field','ultimatemember'),
									'label' 			=> __('Show with label','ultimatemember'),
									'off' 				=> __('Turn off','ultimatemember'),
				),
        ),
		
        array(
                'id'      		=> 'profile_primary_btn_word',
                'type'     		=> 'text',
                'title'    		=> __( 'Profile Primary Button Text','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_primary_btn_word'),
				'desc' 	   		=> 'The text that is used for updating profile button',
        ),
		
        array(
                'id'       		=> 'profile_secondary_btn',
                'type'     		=> 'switch',
                'title'    		=> __( 'Profile Secondary Button','ultimatemember' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Switch on/off the secondary button display in the form',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
        array(
                'id'      		=> 'profile_secondary_btn_word',
                'type'     		=> 'text',
                'title'    		=> __( 'Profile Secondary Button Text','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_secondary_btn_word'),
				'desc' 	   		=> 'The text that is used for cancelling update profile button',
				'required'		=> array( 'profile_secondary_btn', '=', 1 ),
        ),
		
		array(
				'id'       		=> 'profile_role',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Profile Associated Role','ultimatemember' ),
                'desc' 	   		=> __( 'Normally, you can leave this to default as this restricts the profile per specified role only','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_role'),
				'options' 		=> $ultimatemember->query->get_roles( $add_default = 'Not specific' ),
        ),
		
        array(
				'id'       		=> 'profile_main_bg',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('profile_main_bg'),
                'title'    		=> __( 'Profile Base Background Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'profile_header_bg',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('profile_header_bg'),
                'title'    		=> __( 'Profile Header Background Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
		array(
			'id'      			=> 'default_avatar',
			'type'     			=> 'media',
			'title'    			=> __('Default Profile Photo', 'ultimatemember'),
			'desc'     			=> __('You can change the default profile picture globally here. Please make sure that the photo is 300x300px.', 'ultimatemember'),
			'default'  			=> array(
					'url'		=> um_url . 'assets/img/default_avatar.jpg',
			),
		),
		
		array(
			'id'      			=> 'default_cover',
			'type'     			=> 'media',
			'url'				=> true,
			'preview'			=> false,
			'title'    			=> __('Default Cover Photo', 'ultimatemember'),
			'desc'     			=> __('You can change the default cover photo globally here. Please make sure that the default cover is large enough and respects the ratio you are using for cover photos.', 'ultimatemember'),
		),
		
        array(
                'id'      		=> 'profile_photosize',
                'type'     		=> 'text',
                'title'    		=> __( 'Profile Photo Size','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_photosize'),
				'desc' 	   		=> 'The global default of profile photo size. This can be overridden by individual form settings',
        ),
		
		array(
				'id'       		=> 'profile_photocorner',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Profile Photo Style','ultimatemember' ),
                'desc' 	   		=> __( 'Whether to have rounded profile images, rounded corners, or none for the profile photo','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_photocorner'),
				'options' 		=> array(
									'1' 			=> __('Circle','ultimatemember'),
									'2' 			=> __('Rounded Corners','ultimatemember'),
									'3' 			=> __('Square','ultimatemember'),
				),
        ),
		
        array(
                'id'       		=> 'profile_cover_enabled',
                'type'     		=> 'switch',
                'title'    		=> __( 'Profile Cover Photos','ultimatemember' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Switch on/off the profile cover photos',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
		array(
				'id'       		=> 'profile_cover_ratio',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Profile Cover Ratio','ultimatemember' ),
                'desc' 	   		=> __( 'Choose global ratio for cover photos of profiles','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_cover_ratio'),
				'options' 		=> array(
									'2.7:1' 			=> '2.7:1',
									'2.2:1' 			=> '2.2:1',
									'3.2:1' 			=> '3.2:1',
				),
				'required'		=> array( 'profile_cover_enabled', '=', 1 ),
        ),
		
        array(
				'id'       		=> 'profile_header_text',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('profile_header_text'),
                'title'    		=> __( 'Profile Header Meta Text Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'profile_header_link_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('profile_header_link_color'),
                'title'    		=> __( 'Profile Header Link Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'profile_header_link_hcolor',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('profile_header_link_hcolor'),
                'title'    		=> __( 'Profile Header Link Hover','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'profile_header_icon_color',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('profile_header_icon_color'),
                'title'    		=> __( 'Profile Header Icon Link Color','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
				'id'       		=> 'profile_header_icon_hcolor',
                'type'     		=> 'color',
				'default'		=> um_get_metadefault('profile_header_icon_hcolor'),
                'title'    		=> __( 'Profile Header Icon Link Hover','ultimatemember' ),
                'validate' 		=> 'color',
				'transparent'	=> false,
        ),
		
        array(
                'id'       		=> 'profile_show_name',
                'type'     		=> 'switch',
                'title'    		=> __( 'Show display name in profile header','ultimatemember' ),
				'default' 		=> um_get_metadefault('profile_show_name'),
				'desc' 	   		=> 'Switch on/off the user name on profile header',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'profile_show_bio',
                'type'     		=> 'switch',
                'title'    		=> __( 'Show user description in header','ultimatemember' ),
				'default' 		=> um_get_metadefault('profile_show_bio'),
				'desc' 	   		=> 'Switch on/off the user description on profile header',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'profile_bio_maxchars',
                'type'     		=> 'text',
                'title'    		=> __( 'User description maximum chars','ultimatemember' ),
                'default'  		=> um_get_metadefault('profile_bio_maxchars'),
				'desc' 	   		=> 'Maximum number of characters to allow in user description field in header.',
				'required'		=> array( 'profile_show_bio', '=', 1 ),
        ),
		
        array(
                'id'       		=> 'profile_header_menu',
                'type'     		=> 'select',
                'title'    		=> __( 'Profile Header Menu Position','ultimatemember' ),
				'default' 		=> um_get_metadefault('profile_header_menu'),
				'desc' 	   		=> __('For incompatible themes, please make the menu open from left instead of bottom by default.','ultimatemember'),
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
				'options' 		=> array(
									'bc' 		=> 'Bottom of Icon',
									'lc' 		=> 'Left of Icon',
				),
        ),
		
        array(
                'id'       		=> 'profile_empty_text',
                'type'     		=> 'switch',
                'title'    		=> __( 'Show a custom message if profile is empty','ultimatemember' ),
				'default' 		=> um_get_metadefault('profile_empty_text'),
				'desc' 	   		=> 'Switch on/off the custom message that appears when the profile is empty',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
        array(
                'id'       		=> 'profile_empty_text_emo',
                'type'     		=> 'switch',
                'title'    		=> __( 'Show the emoticon','ultimatemember' ),
				'default' 		=> um_get_metadefault('profile_empty_text_emo'),
				'desc' 	   		=> 'Switch on/off the emoticon (sad face) that appears above the message',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
				'required'		=> array( 'profile_empty_text', '=', 1 ),
        ),
		
	)
	
);

$tabs = $ultimatemember->profile->tabs_primary();
$tab_options[] = array(
                'id'       		=> 'profile_menu',
                'type'     		=> 'switch',
                'title'    		=> __('Enable profile menu','ultimatemember'),
				'default' 		=> 1,
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
);

foreach( $tabs as $id => $tab ) {
$tab_options[] = array(
                'id'       		=> 'profile_tab_' . $id,
                'type'     		=> 'switch',
                'title'    		=> sprintf(__('%s Tab','ultimatemember'), $tab ),
				'default' 		=> 1,
				'required'		=> array( 'profile_menu', '=', 1 ),
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
);
}

$tab_options[] = array(
                'id'       		=> 'profile_menu_default_tab',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Profile menu default tab','ultimatemember' ),
                'desc' 	   		=> __( 'This will be the default tab on user profile page','ultimatemember' ),
                'default'  		=> 'main',
				'options' 		=> $ultimatemember->profile->tabs_primary(),
				'required'		=> array( 'profile_menu', '=', 1 ),
);

$tab_options[] = array(
                'id'       		=> 'profile_menu_icons',
                'type'     		=> 'switch',
                'title'    		=> __('Enable menu icons in desktop view','ultimatemember'),
				'default' 		=> 1,
				'required'		=> array( 'profile_menu', '=', 1 ),
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
);

$tab_options[] = array(
                'id'       		=> 'profile_menu_counts',
                'type'     		=> 'switch',
                'title'    		=> __('Enable counts in menu','ultimatemember'),
				'default' 		=> 1,
				'required'		=> array( 'profile_menu', '=', 1 ),
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
);

$this->sections[] = array(
	
    'subsection' => true,
    'title'      => __( 'Profile Menu','ultimatemember'),
    'fields'     => $tab_options
	
);

$this->sections[] = array(
	
    'subsection' => true,
    'title'      => __( 'Registration Form','ultimatemember'),
    'fields'     => array(
		
		array(
				'id'       		=> 'register_template',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Registration Default Template','ultimatemember' ),
                'desc' 	   		=> __( 'This will be the default template to output registration' ),
                'default'  		=> um_get_metadefault('register_template'),
				'options' 		=> $ultimatemember->shortcodes->get_templates( 'register' ),
        ),
		
        array(
                'id'      		=> 'register_max_width',
                'type'     		=> 'text',
                'title'    		=> __( 'Registration Maximum Width','ultimatemember' ),
                'default'  		=> um_get_metadefault('register_max_width'),
				'desc' 	   		=> 'The maximum width this shortcode can take from the page width',
        ),
		
		array(
				'id'       		=> 'register_align',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Registration Shortcode Alignment','ultimatemember' ),
                'desc' 	   		=> __( 'The shortcode is centered by default unless you specify otherwise here','ultimatemember' ),
                'default'  		=> um_get_metadefault('register_align'),
				'options' 		=> array(
									'center' 			=> __('Centered'),
									'left' 				=> __('Left aligned'),
									'right' 			=> __('Right aligned'),
				),
        ),
		
		array(
				'id'       		=> 'register_icons',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Registration Field Icons','ultimatemember' ),
                'desc' 	   		=> __( 'This controls the display of field icons in the registration form','ultimatemember' ),
                'default'  		=> um_get_metadefault('register_icons'),
				'options' 		=> array(
									'field' 			=> __('Show inside text field'),
									'label' 			=> __('Show with label'),
									'off' 				=> __('Turn off'),
				),
        ),
		
        array(
                'id'      		=> 'register_primary_btn_word',
                'type'     		=> 'text',
                'title'    		=> __( 'Registration Primary Button Text','ultimatemember' ),
                'default'  		=> um_get_metadefault('register_primary_btn_word'),
				'desc' 	   		=> 'The text that is used for primary button text',
        ),
		
        array(
                'id'       		=> 'register_secondary_btn',
                'type'     		=> 'switch',
                'title'    		=> __( 'Registration Secondary Button','ultimatemember' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Switch on/off the secondary button display in the form',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
        array(
                'id'      		=> 'register_secondary_btn_word',
                'type'     		=> 'text',
                'title'    		=> __( 'Registration Secondary Button Text','ultimatemember' ),
                'default'  		=> um_get_metadefault('register_secondary_btn_word'),
				'desc' 	   		=> 'The text that is used for the secondary button text',
				'required'		=> array( 'register_secondary_btn', '=', 1 ),
        ),
		
		array(
				'id'       		=> 'register_role',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Registration Default Role','ultimatemember' ),
                'desc' 	   		=> __( 'This will be the default role assigned to users registering thru registration form','ultimatemember' ),
                'default'  		=> um_get_metadefault('register_role'),
				'options' 		=> $ultimatemember->query->get_roles( $add_default = 'Default' ),
        ),
		
	)
	
);

$this->sections[] = array(
	
    'subsection' => true,
    'title'      => __( 'Login Form'),
    'fields'     => array(
	
		array(
				'id'       		=> 'login_template',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Login Default Template','ultimatemember' ),
                'desc' 	   		=> __( 'This will be the default template to output login','ultimatemember' ),
                'default'  		=> um_get_metadefault('login_template'),
				'options' 		=> $ultimatemember->shortcodes->get_templates( 'login' ),
        ),
		
        array(
                'id'      		=> 'login_max_width',
                'type'     		=> 'text',
                'title'    		=> __( 'Login Maximum Width','ultimatemember' ),
                'default'  		=> um_get_metadefault('login_max_width'),
				'desc' 	   		=> 'The maximum width this shortcode can take from the page width',
        ),
		
		array(
				'id'       		=> 'login_align',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Login Shortcode Alignment','ultimatemember' ),
                'desc' 	   		=> __( 'The shortcode is centered by default unless you specify otherwise here','ultimatemember' ),
                'default'  		=> um_get_metadefault('login_align'),
				'options' 		=> array(
									'center' 			=> __('Centered','ultimatemember'),
									'left' 				=> __('Left aligned','ultimatemember'),
									'right' 			=> __('Right aligned','ultimatemember'),
				),
        ),
		
		array(
				'id'       		=> 'login_icons',
                'type'     		=> 'select',
				'select2'		=> array( 'allowClear' => 0, 'minimumResultsForSearch' => -1 ),
                'title'    		=> __( 'Login Field Icons','ultimatemember' ),
                'desc' 	   		=> __( 'This controls the display of field icons in the login form','ultimatemember' ),
                'default'  		=> um_get_metadefault('login_icons'),
				'options' 		=> array(
									'field' 			=> __('Show inside text field','ultimatemember'),
									'label' 			=> __('Show with label','ultimatemember'),
									'off' 				=> __('Turn off','ultimatemember'),
				),
        ),
		
        array(
                'id'      		=> 'login_primary_btn_word',
                'type'     		=> 'text',
                'title'    		=> __( 'Login Primary Button Text','ultimatemember' ),
                'default'  		=> um_get_metadefault('login_primary_btn_word'),
				'desc' 	   		=> 'The text that is used for primary button text',
        ),
		
        array(
                'id'       		=> 'login_secondary_btn',
                'type'     		=> 'switch',
                'title'    		=> __( 'Login Secondary Button','ultimatemember' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Switch on/off the secondary button display in the form',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
        array(
                'id'      		=> 'login_secondary_btn_word',
                'type'     		=> 'text',
                'title'    		=> __( 'Login Secondary Button Text','ultimatemember' ),
                'default'  		=> um_get_metadefault('login_secondary_btn_word'),
				'desc' 	   		=> 'The text that is used for the secondary button text',
				'required'		=> array( 'login_secondary_btn', '=', 1 ),
        ),
		
        array(
                'id'       		=> 'login_forgot_pass_link',
                'type'     		=> 'switch',
                'title'    		=> __( 'Login Forgot Password Link','ultimatemember' ),
				'default' 		=> 1,
				'desc' 	   		=> 'Switch on/off the forgot password link in login form',
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
	)
	
);

$this->sections[] = array(
	
    'subsection' => true,
    'title'      => __( 'Custom CSS','ultimatemember'),
    'fields'     => array(

        array(
				'id'       		=> 'custom_css',
                'type'     		=> 'textarea',
                'title'    		=> __( 'Custom CSS','ultimatemember' ),
				'desc'			=> __('Any custom css rules that you specify here will be applied globally to the plugin.','ultimatemember'),
				'rows'			=> 20,
        ),
		
	)
	
);

/***
***	@
***/
	
$this->sections[] = array(

    'icon'       => 'um-faicon-wrench',
    'title'      => __('Advanced','ultimatemember'),
    'fields'     => array(
		
        array(
                'id'       		=> 'disable_minify',
                'type'     		=> 'switch',
                'title'   		=> __( 'Disable JS/CSS Compression','ultimatemember' ),
				'default' 		=> 0,
				'desc' 	   		=> __('Not recommended. This will load all plugin js and css files separately and may slow down your website. Use this setting for development or debugging purposes only.','ultimatemember'),
				'on'			=> __('On','ultimatemember'),
				'off'			=> __('Off','ultimatemember'),
        ),
		
		array(
				'id'       		=> 'js_css_exclude',
                'type'     		=> 'multi_text',
				'default'		=> array(),
                'title'    		=> __( 'Never load plugin JS and CSS on the following pages','ultimatemember' ),
                'desc' 	   		=> __( 'Enter a url or page slug (e.g /about/) to disable loading the plugin\'s css and js on that page.','ultimatemember' ),
				'add_text'		=> __('Add New Page','ultimatemember'),
		),
		
        array(
                'id'       		=> 'allow_tracking',
                'type'     		=> 'switch',
                'title'   		=> __( 'Allow Tracking','ultimatemember' ),
				'default' 		=> 0,
				'desc' 	   		=> 'Help us improve Ultimate Member’s compatibility with other plugins and themes by allowing us to track non-sensitive data on your site. Click <a href="http://ultimatemember.com/tracking/">here</a> to see what data we track.',
				'on'			=> __('Allow tracking','ultimatemember'),
				'off'			=> __('Do not allow','ultimatemember'),
        ),

	)

);