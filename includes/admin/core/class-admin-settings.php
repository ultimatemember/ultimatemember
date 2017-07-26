<?php
namespace um\admin\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Admin_Settings' ) ) {
    class Admin_Settings {

        var $settings_structure;
        var $previous_licenses;

        function __construct() {
            //init settings structure
            add_action( 'admin_init', array( &$this, 'init_variables' ), 9 );

            //admin menu
            add_action( 'admin_menu', array( &$this, 'primary_admin_menu' ), 0 );

            //settings structure handlers
            add_action( 'um_settings_page_before_email__content', array( $this, 'settings_before_email_tab' ) );
            add_filter( 'um_settings_section_email__content', array( $this, 'settings_email_tab' ), 10, 1 );

            //enqueue wp_media for profiles tab
            add_action( 'um_settings_page_appearance__before_section', array( $this, 'settings_appearance_profile_tab' ) );

            //custom content for licenses tab
            add_filter( 'um_settings_section_licenses__content', array( $this, 'settings_licenses_tab' ), 10, 2 );


            add_filter( 'um_settings_structure', array( $this, 'sorting_licenses_options' ), 9999, 1 );


            //save handlers
            add_action( 'admin_init', array( $this, 'save_settings_handler' ), 10 );

            //save pages options
            add_action( 'um_settings_save', array( $this, 'on_settings_save' ) );


            //save licenses options
            add_action( 'um_settings_before_save', array( $this, 'before_licenses_save' ) );
            add_action( 'um_settings_save', array( $this, 'licenses_save' ) );


            //invalid licenses notice
            add_action( 'admin_notices', array( $this, 'check_wrong_licenses' ) );
        }


        function init_variables() {
            $general_pages_fields = array(
                array(
                    'id'       		=> 'pages_settings',
                    'type'     		=> 'hidden',
                    'default'       => true,
                    'is_option'     => false
                )
            );

            $core_pages = UM()->config()->core_pages;

            foreach ( $core_pages as $page_s => $page ) {
                $have_pages = UM()->query()->wp_pages();
                $page_id = apply_filters( 'um_core_page_id_filter', 'core_' . $page_s );

                $page_title = ! empty( $page['title'] ) ? $page['title'] : '';

                if ( 'reached_maximum_limit' == $have_pages ) {
                    $general_pages_fields[] = array(
                        'id'       		=> $page_id,
                        'type'     		=> 'text',
                        'label'    		=> sprintf( __( '%s page', 'ultimate-member' ), $page_title ),
                        'placeholder' 	=> __('Add page ID','ultimate-member'),
                        'compiler' 		=> true,
                        'value' 		=> UM()->um_get_option( $page_id ),
                        'default' 		=> UM()->um_get_default( $page_id ),
                        'size'          => 'small'
                    );
                } else {
                    $general_pages_fields[] = array(
                        'id'       		=> $page_id,
                        'type'     		=> 'select',
                        'label'    		=> sprintf( __( '%s page', 'ultimate-member' ), $page_title ),
                        'options' 		=> UM()->query()->wp_pages(),
                        'placeholder' 	=> __('Choose a page...','ultimate-member'),
                        'compiler' 		=> true,
                        'value' 		=> UM()->um_get_option( $page_id ),
                        'default' 		=> UM()->um_get_default( $page_id ),
                        'size'          => 'small'
                    );
                }
            }



            $appearances_profile_menu_fields = array(
                array(
                    'id'       		=> 'profile_menu',
                    'type'     		=> 'checkbox',
                    'label'    		=> __('Enable profile menu','ultimate-member'),
                    'value' 		=> UM()->um_get_option( 'profile_menu' ),
                    'default' 		=> UM()->um_get_default( 'profile_menu' ),
                )
            );

            $tabs = UM()->profile()->tabs_primary();
            foreach( $tabs as $id => $tab ) {
                $appearances_profile_menu_fields = array_merge( $appearances_profile_menu_fields, array(
                    array(
                        'id'       		=> 'profile_tab_' . $id,
                        'type'     		=> 'checkbox',
                        'label'    		=> sprintf(__('%s Tab','ultimate-member'), $tab ),
                        'conditional'		=> array( 'profile_menu', '=', 1 ),
                        'value' 		=> UM()->um_get_option( 'profile_tab_' . $id ),
                        'default' 		=> UM()->um_get_default( 'profile_tab_' . $id ),
                    ),
                    array(
                        'id'       		=> 'profile_tab_' . $id . '_privacy',
                        'type'     		=> 'select',
                        'label'    		=> sprintf( __( 'Who can see %s Tab?','ultimate-member' ), $tab ),
                        'tooltip' 	=> __( 'Select which users can view this tab.','ultimate-member' ),
                        'options' 		=> UM()->profile()->tabs_privacy(),
                        'conditional'		=> array( 'profile_tab_' . $id, '=', 1 ),
                        'value' 		=> UM()->um_get_option( 'profile_tab_' . $id . '_privacy' ),
                        'default' 		=> UM()->um_get_default( 'profile_tab_' . $id . '_privacy' ),
                        'size'          => 'small'
                    ),
                    array(
                        'id'       		=> 'profile_tab_' . $id . '_roles',
                        'type'     		=> 'select',
                        'multi'         => true,
                        'label'    		=> __( 'Allowed roles','ultimate-member' ),
                        'tooltip' 	=> __( 'Select the the user roles allowed to view this tab.','ultimate-member' ),
                        'options' 		=> UM()->roles()->get_roles(),
                        'placeholder' 	=> __( 'Choose user roles...','ultimate-member' ),
                        'conditional'		=> array( 'profile_tab_' . $id . '_privacy', '=', 4 ),
                        'value' 		=> ! empty( UM()->um_get_option( 'profile_tab_' . $id . '_roles' ) ) ? UM()->um_get_option( 'profile_tab_' . $id . '_roles' ) : array(),
                        'default' 		=> UM()->um_get_default( 'profile_tab_' . $id . '_roles' ),
                        'size'          => 'small'
                    )
                ) );
            }

            $appearances_profile_menu_fields = array_merge( $appearances_profile_menu_fields, array(
                array(
                    'id'       		=> 'profile_menu_default_tab',
                    'type'     		=> 'select',
                    'label'    		=> __( 'Profile menu default tab','ultimate-member' ),
                    'tooltip' 	=> __( 'This will be the default tab on user profile page','ultimate-member' ),
                    'options' 		=> UM()->profile()->tabs_enabled(),
                    'conditional'	=> array( 'profile_menu', '=', 1 ),
                    'value' 		=> UM()->um_get_option( 'profile_menu_default_tab' ),
                    'default' 		=> UM()->um_get_default( 'profile_menu_default_tab' ),
                    'size'          => 'small'
                ),
                array(
                    'id'       		=> 'profile_menu_icons',
                    'type'     		=> 'checkbox',
                    'label'    		=> __('Enable menu icons in desktop view','ultimate-member'),
                    'conditional'		=> array( 'profile_menu', '=', 1 ),
                    'value' 		=> UM()->um_get_option( 'profile_menu_icons' ),
                    'default' 		=> UM()->um_get_default( 'profile_menu_icons' ),
                )
            ) );


            $all_post_types = get_post_types( array( 'public' => true ) );

            $all_taxonomies = get_taxonomies( array( 'public' => true ) );
            $exclude_taxonomies = array(
                'nav_menu',
                'link_category',
                'post_format',
                'um_user_tag',
                'um_hashtag',
            );

            foreach ( $all_taxonomies as $key => $taxonomy ) {
                if( in_array( $key , $exclude_taxonomies ) )
                    unset( $all_taxonomies[$key] );
            }

            $restricted_access_post_metabox_value = array();
            if ( $restricted_access_post_metabox = UM()->um_get_option( 'restricted_access_post_metabox' ) ) {
                foreach ( $restricted_access_post_metabox as $key => $value ) {
                    if ( $value )
                        $restricted_access_post_metabox_value[] = $key;
                }
            }


            $restricted_access_taxonomy_metabox_value = array();
            if ( $restricted_access_taxonomy_metabox = UM()->um_get_option( 'restricted_access_taxonomy_metabox' ) ) {
                foreach ( $restricted_access_taxonomy_metabox as $key => $value ) {
                    if ( $value )
                        $restricted_access_taxonomy_metabox_value[] = $key;
                }
            }

            $this->settings_structure = apply_filters( 'um_settings_structure', array(
                ''              => array(
                    'title'       => __( 'General', 'ultimate-member' ),
                    'sections'    => array(
                        ''          => array(
                            'title'     => __( 'Pages', 'ultimate-member' ),
                            'fields'    => $general_pages_fields
                        ),
                        'users'     => array(
                            'title'     => __( 'Users', 'ultimate-member' ),
                            'fields'    => array(
                                array(
                                    'id'       		=> 'permalink_base',
                                    'type'     		=> 'select',
                                    'size'          => 'small',
                                    'label'    		=> __( 'Profile Permalink Base','ultimate-member' ),
                                    'tooltip' 	=> __( 'Here you can control the permalink structure of the user profile URL globally e.g. ' . trailingslashit( um_get_core_page('user') ) . '<strong>username</strong>/','ultimate-member' ),
                                    'options' 		=> array(
                                        'user_login' 		=> __('Username','ultimate-member'),
                                        'name' 				=> __('First and Last Name with \'.\'','ultimate-member'),
                                        'name_dash' 		=> __('First and Last Name with \'-\'','ultimate-member'),
                                        'name_plus' 		=> __('First and Last Name with \'+\'','ultimate-member'),
                                        'user_id' 			=> __('User ID','ultimate-member'),
                                    ),
                                    'placeholder' 	=> __('Select...','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'permalink_base' ),
                                    'default' 		=> UM()->um_get_default( 'permalink_base' ),
                                ),
                                array(
                                    'id'       		=> 'display_name',
                                    'type'     		=> 'select',
                                    'size'          => 'medium',
                                    'label'    		=> __( 'User Display Name','ultimate-member' ),
                                    'tooltip' 	=> __( 'This is the name that will be displayed for users on the front end of your site. Default setting uses first/last name as display name if it exists','ultimate-member' ),
                                    'options' 		=> array(
                                        'default'			=> __('Default WP Display Name','ultimate-member'),
                                        'nickname'			=> __('Nickname','ultimate-member'),
                                        'username' 			=> __('Username','ultimate-member'),
                                        'full_name' 		=> __('First name & last name','ultimate-member'),
                                        'sur_name' 			=> __('Last name & first name','ultimate-member'),
                                        'initial_name'		=> __('First name & first initial of last name','ultimate-member'),
                                        'initial_name_f'	=> __('First initial of first name & last name','ultimate-member'),
                                        'first_name'		=> __('First name only','ultimate-member'),
                                        'field' 			=> __('Custom field(s)','ultimate-member'),
                                    ),
                                    'placeholder' 	=> __('Select...'),
                                    'value' 		=> UM()->um_get_option( 'display_name' ),
                                    'default' 		=> UM()->um_get_default( 'display_name' ),
                                ),
                                array(
                                    'id'       		=> 'display_name_field',
                                    'type'     		=> 'text',
                                    'label'   		=> __( 'Display Name Custom Field(s)','ultimate-member' ),
                                    'tooltip' 	=> __('Specify the custom field meta key or custom fields seperated by comma that you want to use to display users name on the frontend of your site','ultimate-member'),
                                    'conditional'   => array( 'display_name', '=', 'field' ),
                                    'value' 		=> UM()->um_get_option( 'display_name_field' ),
                                    'default' 		=> UM()->um_get_default( 'display_name_field' ),
                                ),
                                array(
                                    'id'       		=> 'author_redirect',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Automatically redirect author page to their profile?','ultimate-member'),
                                    'tooltip' 	=> __('If enabled, author pages will automatically redirect to the user\'s profile page','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'author_redirect' ),
                                    'default' 		=> UM()->um_get_default( 'author_redirect' ),
                                ),
                                array(
                                    'id'       		=> 'members_page',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Enable Members Directory','ultimate-member' ),
                                    'tooltip' 	=> __('Control whether to enable or disable member directories on this site','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'members_page' ),
                                    'default' 		=> UM()->um_get_default( 'members_page' ),
                                ),
                                array(
                                    'id'       		=> 'use_gravatars',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Use Gravatars?','ultimate-member' ),
                                    'tooltip' 	=> __('Do you want to use gravatars instead of the default plugin profile photo (If the user did not upload a custom profile photo / avatar)','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'use_gravatars' ),
                                    'default' 		=> UM()->um_get_default( 'use_gravatars' ),
                                ),
                                array(
                                    'id'       		=> 'use_um_gravatar_default_builtin_image',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Use Gravatar builtin image','ultimate-member' ),
                                    'tooltip' 	=> __( 'Gravatar has a number of built in options which you can also use as defaults','ultimate-member' ),
                                    'options' 		=> array(
                                        'default'		=> __('Default','ultimate-member'),
                                        '404'			=> __('404 ( File Not Found response )','ultimate-member'),
                                        'mm'			=> __('Mystery Man','ultimate-member'),
                                        'identicon'		=> __('Identicon','ultimate-member'),
                                        'monsterid'		=> __('Monsterid','ultimate-member'),
                                        'wavatar'		=> __('Wavatar','ultimate-member'),
                                        'retro'			=> __('Retro','ultimate-member'),
                                        'blank'			=> __('Blank ( a transparent PNG image )','ultimate-member'),
                                    ),
                                    'conditional'		=> array( 'use_gravatars', '=', 1 ),
                                    'value' 		=> UM()->um_get_option( 'use_um_gravatar_default_builtin_image' ),
                                    'default' 		=> UM()->um_get_default( 'use_um_gravatar_default_builtin_image' ),
                                    'size'          => 'medium'
                                ),
                                array(
                                    'id'       		=> 'use_um_gravatar_default_image',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Use Default plugin avatar as Gravatar\'s Default avatar','ultimate-member' ),
                                    'tooltip' 	=> __('Do you want to use the plugin default avatar instead of the gravatar default photo (If the user did not upload a custom profile photo / avatar)','ultimate-member'),
                                    'conditional'		=> array( 'use_um_gravatar_default_builtin_image', '=', 'default' ),
                                    'value' 		=> UM()->um_get_option( 'use_um_gravatar_default_image' ),
                                    'default' 		=> UM()->um_get_default( 'use_um_gravatar_default_image' ),
                                ),
                                array(
                                    'id'       		=> 'reset_require_strongpass',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Require a strong password? (when user resets password only)','ultimate-member' ),
                                    'tooltip' 	=> __('Enable or disable a strong password rules on password reset and change procedure','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'reset_require_strongpass' ),
                                    'default' 		=> UM()->um_get_default( 'reset_require_strongpass' ),
                                )
                            )
                        ),
                        'account'   => array(
                            'title'     => __( 'Account', 'ultimate-member' ),
                            'fields'    => array(
                                array(
                                    'id'       		=> 'account_tab_password',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Password Account Tab','ultimate-member' ),
                                    'tooltip' 	=> 'Enable/disable the Password account tab in account page',
                                    'value' 		=> UM()->um_get_option( 'account_tab_password' ),
                                    'default' 		=> UM()->um_get_default( 'account_tab_password' ),
                                ),
                                array(
                                    'id'       		=> 'account_tab_privacy',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Privacy Account Tab','ultimate-member' ),
                                    'tooltip' 	=> __('Enable/disable the Privacy account tab in account page','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'account_tab_privacy' ),
                                    'default' 		=> UM()->um_get_default( 'account_tab_privacy' ),
                                ),
                                array(
                                    'id'       		=> 'account_tab_notifications',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Notifications Account Tab','ultimate-member' ),
                                    'tooltip' 	=> __('Enable/disable the Notifications account tab in account page','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'account_tab_notifications' ),
                                    'default' 		=> UM()->um_get_default( 'account_tab_notifications' ),
                                ),
                                array(
                                    'id'       		=> 'account_tab_delete',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Delete Account Tab','ultimate-member' ),
                                    'tooltip' 	=> __('Enable/disable the Delete account tab in account page','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'account_tab_delete' ),
                                    'default' 		=> UM()->um_get_default( 'account_tab_delete' ),
                                ),
                                array(
                                    'id'       		=> 'delete_account_text',
                                    'type'    		=> 'textarea', // bug with wp 4.4? should be editor
                                    'label'    		=> __( 'Account Deletion Custom Text','ultimate-member' ),
                                    'tooltip' 	=> __('This is custom text that will be displayed to users before they delete their accounts from your site','ultimate-member'),
                                    'args'     		=> array(
                                        'textarea_rows'    => 6
                                    ),
                                    'value' 		=> UM()->um_get_option( 'delete_account_text' ),
                                    'default' 		=> UM()->um_get_default( 'delete_account_text' ),
                                ),
                                array(
                                    'id'       		=> 'account_name',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Add a First & Last Name fields','ultimate-member' ),
                                    'tooltip' 	=> __('Whether to enable these fields on the user account page by default or hide them.','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'account_name' ),
                                    'default' 		=> UM()->um_get_default( 'account_name' ),
                                ),
                                array(
                                    'id'       		=> 'account_name_disable',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Disable First & Last Name fields','ultimate-member' ),
                                    'tooltip' 	=> __('Whether to allow users changing their first and last name in account page.','ultimate-member'),
                                    'conditional'		=> array( 'account_name', '=', '1' ),
                                    'value' 		=> UM()->um_get_option( 'account_name_disable' ),
                                    'default' 		=> UM()->um_get_default( 'account_name_disable' ),
                                ),
                                array(
                                    'id'       		=> 'account_name_require',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Require First & Last Name','ultimate-member' ),
                                    'tooltip' 	=> __('Require first and last name?','ultimate-member'),
                                    'conditional'		=> array( 'account_name', '=', '1' ),
                                    'value' 		=> UM()->um_get_option( 'account_name_require' ),
                                    'default' 		=> UM()->um_get_default( 'account_name_require' ),
                                ),
                                array(
                                    'id'       		=> 'account_email',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Allow users to change e-mail','ultimate-member' ),
                                    'tooltip' 	=> __('Whether to allow users changing their email in account page.','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'account_email' ),
                                    'default' 		=> UM()->um_get_default( 'account_email' ),
                                ),
                                array(
                                    'id'       		=> 'account_hide_in_directory',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Allow users to hide their profiles from directory','ultimate-member' ),
                                    'tooltip' 	=> __('Whether to allow users changing their profile visibility from member directory in account page.','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'account_hide_in_directory' ),
                                    'default' 		=> UM()->um_get_default( 'account_hide_in_directory' ),
                                ),
                                array(
                                    'id'       		=> 'account_require_strongpass',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Require a strong password?','ultimate-member' ),
                                    'tooltip' 	=> __('Enable or disable a strong password rules on account page / change password tab','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'account_require_strongpass' ),
                                    'default' 		=> UM()->um_get_default( 'account_require_strongpass' ),
                                )
                            )
                        ),
                        'uploads'   => array(
                            'title'     => __( 'Uploads', 'ultimate-member' ),
                            'fields'    => array(
                                array(
                                    'id'       		=> 'photo_thumb_sizes',
                                    'type'     		=> 'multi_text',
                                    'size'     		=> 'small',
                                    'label'    		=> __( 'Profile Photo Thumbnail Sizes (px)','ultimate-member' ),
                                    'tooltip' 	=> __( 'Here you can define which thumbnail sizes will be created for each profile photo upload.','ultimate-member' ),
                                    'validate' 		=> 'numeric',
                                    'add_text'		=> __('Add New Size','ultimate-member'),
                                    'show_default_number' => 1,
                                    'value' 		=> UM()->um_get_option( 'photo_thumb_sizes' ),
                                    'default' 		=> UM()->um_get_default( 'photo_thumb_sizes' ),
                                ),
                                array(
                                    'id'       		=> 'cover_thumb_sizes',
                                    'type'     		=> 'multi_text',
                                    'size'     		=> 'small',
                                    'label'    		=> __( 'Cover Photo Thumbnail Sizes (px)','ultimate-member' ),
                                    'tooltip' 	=> __( 'Here you can define which thumbnail sizes will be created for each cover photo upload.','ultimate-member' ),
                                    'validate' 		=> 'numeric',
                                    'add_text'		=> __('Add New Size','ultimate-member'),
                                    'show_default_number' => 1,
                                    'value' 		=> UM()->um_get_option( 'cover_thumb_sizes' ),
                                    'default' 		=> UM()->um_get_default( 'cover_thumb_sizes' ),
                                )
                            )
                        )
                    )
                ),
                'access'        => array(
                    'title'       => __( 'Access', 'ultimate-member' ),
                    'sections'    => array(
                        ''      => array(
                            'title'     => __( 'Restriction Content', 'ultimate-member' ),
                            'fields'    => array(
                                array(
                                    'id'       		=> 'accessible',
                                    'type'     		=> 'select',
                                    'label'   		=> __( 'Global Site Access','ultimate-member' ),
                                    'tooltip' 	=> __('Globally control the access of your site, you can have seperate restrict options per post/page by editing the desired item.','ultimate-member'),
                                    'options' 		=> array(
                                        0 		=> 'Site accessible to Everyone',
                                        2 		=> 'Site accessible to Logged In Users'
                                    ),
                                    'value' 		=> UM()->um_get_option( 'accessible' ),
                                    'default' 		=> UM()->um_get_default( 'accessible' ),
                                    'size'          => 'medium'
                                ),
                                array(
                                    'id'       		=> 'access_redirect',
                                    'type'     		=> 'text',
                                    'label'   		=> __( 'Custom Redirect URL','ultimate-member' ),
                                    'tooltip' 	=> __('A logged out user will be redirected to this url If he is not permitted to access the site','ultimate-member'),
                                    'conditional'		=> array( 'accessible', '=', 2 ),
                                    'value' 		=> UM()->um_get_option( 'access_redirect' ),
                                    'default' 		=> UM()->um_get_default( 'access_redirect' ),
                                ),
                                array(
                                    'id'       		=> 'access_exclude_uris',
                                    'type'     		=> 'multi_text',
                                    'label'    		=> __( 'Exclude the following URLs','ultimate-member' ),
                                    'tooltip' 	=> __( 'Here you can exclude URLs beside the redirect URI to be accessible to everyone','ultimate-member' ),
                                    'add_text'		=> __('Add New URL','ultimate-member'),
                                    'conditional'		=> array( 'accessible', '=', 2 ),
                                    'show_default_number' => 1,
                                    'value' 		=> UM()->um_get_option( 'access_exclude_uris' ),
                                    'default' 		=> UM()->um_get_default( 'access_exclude_uris' ),
                                ),
                                array(
                                    'id'       		=> 'home_page_accessible',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Allow Homepage to be accessible','ultimate-member' ),
                                    'conditional'		=> array( 'accessible', '=', 2 ),
                                    'value' 		=> UM()->um_get_option( 'home_page_accessible' ),
                                    'default' 		=> UM()->um_get_default( 'home_page_accessible' ),
                                ),
                                array(
                                    'id'       		=> 'category_page_accessible',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Allow Category pages to be accessible','ultimate-member' ),
                                    'conditional'		=> array( 'accessible', '=', 2 ),
                                    'value' 		=> UM()->um_get_option( 'category_page_accessible' ),
                                    'default' 		=> UM()->um_get_default( 'category_page_accessible' ),
                                ),
                                array(
                                    'id'       		=> 'restricted_access_message',
                                    'type'     		=> 'wp_editor',
                                    'label'   		=> __( 'Restricted Access Message','ultimate-member' ),
                                    'tooltip'   => __( 'This is the message shown to users that do not have permission to view the content','ultimate-member' ),
                                    'value' 		=> UM()->um_get_option( 'restricted_access_message' ),
                                    'default' 		=> UM()->um_get_default( 'restricted_access_message' ),
                                ),
                                array(
                                    'id'       		=> 'restricted_access_post_metabox',
                                    'type'     		=> 'multi_checkbox',
                                    'label'   		=> __( 'Restricted Access to Posts','ultimate-member' ),
                                    'tooltip'   => __( 'Restriction content of the current Posts','ultimate-member' ),
                                    'options'       => $all_post_types,
                                    'columns'       => 3,
                                    'value' 		=> $restricted_access_post_metabox_value,
                                    'default' 		=> UM()->um_get_default( 'restricted_access_post_metabox' ),
                                ),
                                array(
                                    'id'       		=> 'restricted_access_taxonomy_metabox',
                                    'type'     		=> 'multi_checkbox',
                                    'label'   		=> __( 'Restricted Access to Taxonomies','ultimate-member' ),
                                    'tooltip'   => __( 'Restriction content of the current Taxonomies','ultimate-member' ),
                                    'options'       => $all_taxonomies,
                                    'columns'       => 3,
                                    'value' 		=> $restricted_access_taxonomy_metabox_value,
                                    'default' 		=> UM()->um_get_default( 'restricted_access_taxonomy_metabox' ),
                                ),
                            )
                        ),
                        'other' => array(
                            'title'     => __( 'Other', 'ultimate-member' ),
                            'fields'      => array(
                                array(
                                    'id'       		=> 'enable_reset_password_limit',
                                    'type'     		=> 'checkbox',
                                    'label'   		=> __( 'Enable the Reset Password Limit?','ultimate-member' ),
                                    'value' 		=> UM()->um_get_option( 'enable_reset_password_limit' ),
                                    'default' 		=> UM()->um_get_default( 'enable_reset_password_limit' ),
                                ),
                                array(
                                    'id'       		=> 'reset_password_limit_number',
                                    'type'     		=> 'text',
                                    'label'   		=> __( 'Reset Password Limit','ultimate-member' ),
                                    'tooltip' 	=> __('Set the maximum reset password limit. If reached the maximum limit, user will be locked from using this.','ultimate-member'),
                                    'validate'		=> 'numeric',
                                    'conditional'   => array('enable_reset_password_limit','=',1),
                                    'size'          => 'um-small-field',
                                    'value' 		=> UM()->um_get_option( 'reset_password_limit_number' ),
                                    'default' 		=> UM()->um_get_default( 'reset_password_limit_number' ),
                                ),
                                array(
                                    'id'       		=> 'blocked_emails',
                                    'type'     		=> 'textarea',
                                    'label'    		=> __( 'Blocked Email Addresses','ultimate-member' ),
                                    'tooltip'	=> __('This will block the specified e-mail addresses from being able to sign up or sign in to your site. To block an entire domain, use something like *@domain.com','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'blocked_emails' ),
                                    'default' 		=> UM()->um_get_default( 'blocked_emails' ),
                                ),
                                array(
                                    'id'       		=> 'blocked_words',
                                    'type'     		=> 'textarea',
                                    'label'    		=> __( 'Blacklist Words','ultimate-member' ),
                                    'tooltip'	=> __('This option lets you specify blacklist of words to prevent anyone from signing up with such a word as their username','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'blocked_words' ),
                                    'default' 		=> UM()->um_get_default( 'blocked_words' ),
                                )
                            )
                        ),
                    )
                ),
                'email'         => array(
                    'title'       => __( 'Email', 'ultimate-member' ),
                    'fields'      => array(
                        array(
                            'id'            => 'admin_email',
                            'type'          => 'text',
                            'label'         => __( 'Admin E-mail Address', 'ultimate-member' ),
                            'tooltip'   => __( 'e.g. admin@companyname.com','ultimate-member' ),
                            'value' 		=> UM()->um_get_option( 'admin_email' ),
                            'default' 		=> UM()->um_get_default( 'admin_email' ),
                        ),
                        array(
                            'id'            => 'mail_from',
                            'type'          => 'text',
                            'label'         => __( 'Mail appears from','ultimate-member' ),
                            'tooltip' 	=> __( 'e.g. Site Name','ultimate-member' ),
                            'value' 		=> UM()->um_get_option( 'mail_from' ),
                            'default' 		=> UM()->um_get_default( 'mail_from' ),
                        ),
                        array(
                            'id'            => 'mail_from_addr',
                            'type'          => 'text',
                            'label'         => __( 'Mail appears from address','ultimate-member' ),
                            'tooltip'   => __( 'e.g. admin@companyname.com','ultimate-member' ),
                            'value' 		=> UM()->um_get_option( 'mail_from_addr' ),
                            'default' 		=> UM()->um_get_default( 'mail_from_addr' ),
                        ),
                        array(
                            'id'            => 'email_html',
                            'type'          => 'checkbox',
                            'label'         => __( 'Use HTML for E-mails?','ultimate-member' ),
                            'tooltip'   => __('If you enable HTML for e-mails, you can customize the HTML e-mail templates found in <strong>templates/email</strong> folder.','ultimate-member'),
                            'value' 		=> UM()->um_get_option( 'email_html' ),
                            'default' 		=> UM()->um_get_default( 'email_html' ),
                        )
                    )
                ),
                'appearance'    => array(
                    'title'       => __( 'Appearance', 'ultimate-member' ),
                    'sections'    => array(
                        ''                  => array(
                            'title'     => __( 'Profile', 'ultimate-member' ),
                            'fields'    => array(
                                array(
                                    'id'       		=> 'profile_template',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Profile Default Template','ultimate-member' ),
                                    'tooltip' 	=> __( 'This will be the default template to output profile','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('profile_template'),
                                    'options' 		=> UM()->shortcodes()->get_templates( 'profile' ),
                                    'value' 		=> UM()->um_get_option( 'profile_template' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'      		=> 'profile_max_width',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Profile Maximum Width','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('profile_max_width'),
                                    'tooltip' 	=> 'The maximum width this shortcode can take from the page width',
                                    'value' 		=> UM()->um_get_option( 'profile_max_width' ),
                                    'size'          => 'small'
                                ),

                                array(
                                    'id'      		=> 'profile_area_max_width',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Profile Area Maximum Width','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('profile_area_max_width'),
                                    'tooltip' 	=> __('The maximum width of the profile area inside profile (below profile header)','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_area_max_width' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'       		=> 'profile_icons',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Profile Field Icons' ),
                                    'tooltip' 	=> __( 'This is applicable for edit mode only','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('profile_icons'),
                                    'options' 		=> array(
                                        'field' 			=> __('Show inside text field','ultimate-member'),
                                        'label' 			=> __('Show with label','ultimate-member'),
                                        'off' 				=> __('Turn off','ultimate-member'),
                                    ),
                                    'value' 		=> UM()->um_get_option( 'profile_icons' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'      		=> 'profile_primary_btn_word',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Profile Primary Button Text','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('profile_primary_btn_word'),
                                    'tooltip' 	=> __('The text that is used for updating profile button','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_primary_btn_word' ),
                                    'size'          => 'medium'
                                ),
                                array(
                                    'id'       		=> 'profile_secondary_btn',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Profile Secondary Button','ultimate-member' ),
                                    'default' 		=> um_get_metadefault('profile_secondary_btn'),
                                    'tooltip' 	=> __('Switch on/off the secondary button display in the form','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_secondary_btn' ),
                                ),
                                array(
                                    'id'      		=> 'profile_secondary_btn_word',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Profile Secondary Button Text','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('profile_secondary_btn_word'),
                                    'tooltip' 	=> __('The text that is used for cancelling update profile button','ultimate-member'),
                                    'conditional'		=> array( 'profile_secondary_btn', '=', 1 ),
                                    'value' 		=> UM()->um_get_option( 'profile_secondary_btn_word' ),
                                    'size'          => 'medium'
                                ),
                                array(
                                    'id'      			=> 'default_avatar',
                                    'type'     			=> 'media',
                                    'label'    			=> __('Default Profile Photo', 'ultimate-member'),
                                    'tooltip'     	=> __('You can change the default profile picture globally here. Please make sure that the photo is 300x300px.', 'ultimate-member'),
                                    'upload_frame_title'=> __('Select Default Profile Photo', 'ultimate-member'),
                                    'default'  			=> array(
                                        'url'		=> um_url . 'assets/img/default_avatar.jpg',
                                    ),
                                    'value' 		=> UM()->um_get_option( 'default_avatar' ),
                                ),
                                array(
                                    'id'      			=> 'default_cover',
                                    'type'     			=> 'media',
                                    'url'				=> true,
                                    'preview'			=> false,
                                    'label'    			=> __('Default Cover Photo', 'ultimate-member'),
                                    'tooltip'     	=> __('You can change the default cover photo globally here. Please make sure that the default cover is large enough and respects the ratio you are using for cover photos.', 'ultimate-member'),
                                    'upload_frame_title'=> __('Select Default Cover Photo', 'ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'default_cover' ),
                                ),
                                array(
                                    'id'      		=> 'profile_photosize',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Profile Photo Size','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('profile_photosize'),
                                    'tooltip' 	=> __('The global default of profile photo size. This can be overridden by individual form settings','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_photosize' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'       		=> 'profile_cover_enabled',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Profile Cover Photos','ultimate-member' ),
                                    'default' 		=> 1,
                                    'tooltip' 	=> __('Switch on/off the profile cover photos','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_cover_enabled' ),
                                ),
                                array(
                                    'id'       		=> 'profile_cover_ratio',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Profile Cover Ratio','ultimate-member' ),
                                    'tooltip' 	=> __( 'Choose global ratio for cover photos of profiles','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('profile_cover_ratio'),
                                    'options' 		=> array(
                                        '1.6:1' 			=> '1.6:1',
                                        '2.7:1' 			=> '2.7:1',
                                        '2.2:1' 			=> '2.2:1',
                                        '3.2:1' 			=> '3.2:1',
                                    ),
                                    'conditional'		=> array( 'profile_cover_enabled', '=', 1 ),
                                    'value' 		=> UM()->um_get_option( 'profile_cover_ratio' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'       		=> 'profile_show_metaicon',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Profile Header Meta Text Icon','ultimate-member' ),
                                    'default' 		=> 0,
                                    'tooltip' 	=> __('Display field icons for related user meta fields in header or not','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_show_metaicon' ),
                                ),
                                array(
                                    'id'       		=> 'profile_show_name',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Show display name in profile header','ultimate-member' ),
                                    'default' 		=> um_get_metadefault('profile_show_name'),
                                    'tooltip' 	=> __('Switch on/off the user name on profile header','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_show_name' ),
                                ),
                                array(
                                    'id'       		=> 'profile_show_social_links',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Show social links in profile header','ultimate-member' ),
                                    'default' 		=> um_get_metadefault('profile_show_social_links'),
                                    'tooltip' 	=> __('Switch on/off the social links on profile header','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_show_social_links' ),
                                ),
                                array(
                                    'id'       		=> 'profile_show_bio',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Show user description in header','ultimate-member' ),
                                    'default' 		=> um_get_metadefault('profile_show_bio'),
                                    'tooltip' 	=> __('Switch on/off the user description on profile header','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_show_bio' ),
                                ),
                                array(
                                    'id'       		=> 'profile_show_html_bio',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Enable html support for user description','ultimate-member' ),
                                    'default' 		=> um_get_metadefault('profile_show_html_bio'),
                                    'tooltip' 	=> __('Switch on/off to enable/disable support for html tags on user description.','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_show_html_bio' ),
                                ),
                                array(
                                    'id'       		=> 'profile_bio_maxchars',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'User description maximum chars','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('profile_bio_maxchars'),
                                    'tooltip' 	=> __('Maximum number of characters to allow in user description field in header.','ultimate-member'),
                                    'conditional'		=> array( 'profile_show_bio', '=', 1 ),
                                    'value' 		=> UM()->um_get_option( 'profile_bio_maxchars' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'       		=> 'profile_header_menu',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Profile Header Menu Position','ultimate-member' ),
                                    'default' 		=> um_get_metadefault('profile_header_menu'),
                                    'tooltip' 	=> __('For incompatible themes, please make the menu open from left instead of bottom by default.','ultimate-member'),
                                    'options' 		=> array(
                                        'bc' 		=> 'Bottom of Icon',
                                        'lc' 		=> 'Left of Icon',
                                    ),
                                    'value' 		=> UM()->um_get_option( 'profile_header_menu' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'       		=> 'profile_empty_text',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Show a custom message if profile is empty','ultimate-member' ),
                                    'default' 		=> um_get_metadefault('profile_empty_text'),
                                    'tooltip' 	=> __('Switch on/off the custom message that appears when the profile is empty','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'profile_empty_text' ),
                                ),
                                array(
                                    'id'       		=> 'profile_empty_text_emo',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Show the emoticon','ultimate-member' ),
                                    'default' 		=> um_get_metadefault('profile_empty_text_emo'),
                                    'tooltip' 	=> __('Switch on/off the emoticon (sad face) that appears above the message','ultimate-member'),
                                    'conditional'		=> array( 'profile_empty_text', '=', 1 ),
                                    'value' 		=> UM()->um_get_option( 'profile_empty_text_emo' ),
                                )
                            )
                        ),
                        'profile_menu'      => array(
                            'title'     => __( 'Profile Menu', 'ultimate-member' ),
                            'fields'    => $appearances_profile_menu_fields
                        ),
                        'registration_form' => array(
                            'title'     => __( 'Registration Form', 'ultimate-member' ),
                            'fields'    => array(
                                array(
                                    'id'       		=> 'register_template',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Registration Default Template','ultimate-member' ),
                                    'tooltip' 	=> __( 'This will be the default template to output registration' ),
                                    'default'  		=> um_get_metadefault('register_template'),
                                    'options' 		=> UM()->shortcodes()->get_templates( 'register' ),
                                    'value' 		=> UM()->um_get_option( 'register_template' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'      		=> 'register_max_width',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Registration Maximum Width','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('register_max_width'),
                                    'tooltip' 	=> __('The maximum width this shortcode can take from the page width','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'register_max_width' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'       		=> 'register_align',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Registration Shortcode Alignment','ultimate-member' ),
                                    'tooltip' 	=> __( 'The shortcode is centered by default unless you specify otherwise here','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('register_align'),
                                    'options' 		=> array(
                                        'center' 			=> __('Centered'),
                                        'left' 				=> __('Left aligned'),
                                        'right' 			=> __('Right aligned'),
                                    ),
                                    'value' 		=> UM()->um_get_option( 'register_align' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'       		=> 'register_icons',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Registration Field Icons','ultimate-member' ),
                                    'tooltip' 	=> __( 'This controls the display of field icons in the registration form','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('register_icons'),
                                    'options' 		=> array(
                                        'field' 			=> __('Show inside text field'),
                                        'label' 			=> __('Show with label'),
                                        'off' 				=> __('Turn off'),
                                    ),
                                    'value' 		=> UM()->um_get_option( 'register_icons' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'      		=> 'register_primary_btn_word',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Registration Primary Button Text','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('register_primary_btn_word'),
                                    'tooltip' 	   		=> __('The text that is used for primary button text','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'register_primary_btn_word' ),
                                    'size'          => 'medium'
                                ),
                                array(
                                    'id'       		=> 'register_secondary_btn',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Registration Secondary Button','ultimate-member' ),
                                    'default' 		=> 1,
                                    'tooltip' 	=> __('Switch on/off the secondary button display in the form','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'register_secondary_btn' ),
                                ),
                                array(
                                    'id'      		=> 'register_secondary_btn_word',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Registration Secondary Button Text','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('register_secondary_btn_word'),
                                    'tooltip' 	=> __('The text that is used for the secondary button text','ultimate-member'),
                                    'conditional'		=> array( 'register_secondary_btn', '=', 1 ),
                                    'value' 		=> UM()->um_get_option( 'register_secondary_btn_word' ),
                                    'size'          => 'medium'
                                ),
                                array(
                                    'id'      		=> 'register_secondary_btn_url',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Registration Secondary Button URL','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('register_secondary_btn_url'),
                                    'tooltip' 	=> __('You can replace default link for this button by entering custom URL','ultimate-member'),
                                    'conditional'		=> array( 'register_secondary_btn', '=', 1 ),
                                    'value' 		=> UM()->um_get_option( 'register_secondary_btn_url' ),
                                    'size'          => 'medium'
                                ),
                                array(
                                    'id'       		=> 'register_role',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Registration Default Role','ultimate-member' ),
                                    'tooltip' 	=> __( 'This will be the default role assigned to users registering thru registration form','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('register_role'),
                                    'options' 		=> UM()->roles()->get_roles( $add_default = 'Default' ),
                                    'value' 		=> UM()->um_get_option( 'register_role' ),
                                    'size'          => 'small'
                                )
                            )
                        ),
                        'login_form'        => array(
                            'title'     => __( 'Login Form', 'ultimate-member' ),
                            'fields'    => array(
                                array(
                                    'id'       		=> 'login_template',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Login Default Template','ultimate-member' ),
                                    'tooltip' 	=> __( 'This will be the default template to output login','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('login_template'),
                                    'options' 		=> UM()->shortcodes()->get_templates( 'login' ),
                                    'value' 		=> UM()->um_get_option( 'login_template' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'      		=> 'login_max_width',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Login Maximum Width','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('login_max_width'),
                                    'tooltip' 	=> __('The maximum width this shortcode can take from the page width','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'login_max_width' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'       		=> 'login_align',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Login Shortcode Alignment','ultimate-member' ),
                                    'tooltip' 	=> __( 'The shortcode is centered by default unless you specify otherwise here','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('login_align'),
                                    'options' 		=> array(
                                        'center' 			=> __('Centered','ultimate-member'),
                                        'left' 				=> __('Left aligned','ultimate-member'),
                                        'right' 			=> __('Right aligned','ultimate-member'),
                                    ),
                                    'value' 		=> UM()->um_get_option( 'login_align' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'       		=> 'login_icons',
                                    'type'     		=> 'select',
                                    'label'    		=> __( 'Login Field Icons','ultimate-member' ),
                                    'tooltip' 	=> __( 'This controls the display of field icons in the login form','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('login_icons'),
                                    'options' 		=> array(
                                        'field' 			=> __('Show inside text field','ultimate-member'),
                                        'label' 			=> __('Show with label','ultimate-member'),
                                        'off' 				=> __('Turn off','ultimate-member'),
                                    ),
                                    'value' 		=> UM()->um_get_option( 'login_icons' ),
                                    'size'          => 'small'
                                ),
                                array(
                                    'id'      		=> 'login_primary_btn_word',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Login Primary Button Text','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('login_primary_btn_word'),
                                    'tooltip' 	=> __('The text that is used for primary button text','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'login_primary_btn_word' ),
                                    'size'          => 'medium'
                                ),
                                array(
                                    'id'       		=> 'login_secondary_btn',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Login Secondary Button','ultimate-member' ),
                                    'default' 		=> 1,
                                    'tooltip' 	=> __('Switch on/off the secondary button display in the form','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'login_secondary_btn' ),
                                ),
                                array(
                                    'id'      		=> 'login_secondary_btn_word',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Login Secondary Button Text','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('login_secondary_btn_word'),
                                    'tooltip' 	=> __('The text that is used for the secondary button text','ultimate-member'),
                                    'conditional'		=> array( 'login_secondary_btn', '=', 1 ),
                                    'value' 		=> UM()->um_get_option( 'login_secondary_btn_word' ),
                                    'size'          => 'medium'
                                ),
                                array(
                                    'id'      		=> 'login_secondary_btn_url',
                                    'type'     		=> 'text',
                                    'label'    		=> __( 'Login Secondary Button URL','ultimate-member' ),
                                    'default'  		=> um_get_metadefault('login_secondary_btn_url'),
                                    'tooltip' 	=> __('You can replace default link for this button by entering custom URL','ultimate-member'),
                                    'conditional'		=> array( 'login_secondary_btn', '=', 1 ),
                                    'value' 		=> UM()->um_get_option( 'login_secondary_btn_url' ),
                                    'size'          => 'medium'
                                ),
                                array(
                                    'id'       		=> 'login_forgot_pass_link',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Login Forgot Password Link','ultimate-member' ),
                                    'default' 		=> 1,
                                    'tooltip' 	=> __('Switch on/off the forgot password link in login form','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'login_forgot_pass_link' ),
                                ),
                                array(
                                    'id'       		=> 'login_show_rememberme',
                                    'type'     		=> 'checkbox',
                                    'label'    		=> __( 'Show "Remember Me"','ultimate-member' ),
                                    'default' 		=> 1,
                                    'tooltip' 	=> __('Allow users to choose If they want to stay signed in even after closing the browser. If you do not show this option, the default will be to not remember login session.','ultimate-member'),
                                    'value' 		=> UM()->um_get_option( 'login_show_rememberme' ),
                                )
                            )
                        )
                    )
                ),
                'extensions'    => array(
                    'title'       => __( 'Extensions', 'ultimate-member' )
                ),
                'licenses'      => array(
                    'title'       => __( 'Licenses', 'ultimate-member' ),
                ),
                'misc'          => array(
                    'title'       => __( 'Misc', 'ultimate-member' ),
                    'fields'      => array(
                        array(
                            'id'       		=> 'form_asterisk',
                            'type'     		=> 'checkbox',
                            'label'    		=> __( 'Show an asterisk for required fields','ultimate-member' ),
                            'value' 		=> UM()->um_get_option( 'form_asterisk' ),
                            'default' 		=> UM()->um_get_default( 'form_asterisk' ),
                        ),
                        array(
                            'id'      		=> 'profile_title',
                            'type'     		=> 'text',
                            'label'    		=> __('User Profile Title','ultimate-member'),
                            'tooltip' 	=> __('This is the title that is displayed on a specific user profile','ultimate-member'),
                            'value' 		=> UM()->um_get_option( 'profile_title' ),
                            'default' 		=> UM()->um_get_default( 'profile_title' ),
                            'size'          => 'medium'
                        ),
                        array(
                            'id'       		=> 'profile_desc',
                            'type'     		=> 'textarea',
                            'label'    		=> __( 'User Profile Dynamic Meta Description','ultimate-member' ),
                            'tooltip'	=> __('This will be used in the meta description that is available for search-engines.','ultimate-member'),
                            'value' 		=> UM()->um_get_option( 'profile_desc' ),
                            'default' 		=> UM()->um_get_default( 'profile_desc' ),
                            'args'          => array(
                                'textarea_rows' => 6
                            )
                        ),
                        array(
                            'id'       		=> 'allow_tracking',
                            'type'     		=> 'checkbox',
                            'label'   		=> __( 'Allow Tracking','ultimate-member' ),
                            'value' 		=> UM()->um_get_option( 'allow_tracking' ),
                            'default' 		=> UM()->um_get_default( 'allow_tracking' ),
                        ),
                        array(
                            'id'       		=> 'uninstall_on_delete',
                            'type'     		=> 'checkbox',
                            'label'   		=> __( 'Remove Data on Uninstall?', 'ultimate-member' ),
                            'tooltip'	=> __( 'Check this box if you would like Ultimate Member to completely remove all of its data when the plugin/extensions are deleted.', 'ultimate-member' ),
                            'value' 		=> UM()->um_get_option( 'uninstall_on_delete' ),
                            'default' 		=> UM()->um_get_default( 'uninstall_on_delete' ),
                        )
                    )
                )
            ) );

        }


        function sorting_licenses_options( $settings ) {
            //sorting  licenses
            if ( empty( $settings['licenses']['fields'] ) )
                return $settings;
            $licenses = $settings['licenses']['fields'];
            @uasort( $licenses, create_function( '$a,$b', 'return strnatcasecmp($a["label"],$b["label"]);' ) );
            $settings['licenses']['fields'] = $licenses;


            //sorting extensions
            if ( empty( $settings['extensions']['sections'] ) )
                return $settings;

            $extensions = $settings['extensions']['sections'];
            @uasort( $extensions, create_function( '$a,$b', 'return strnatcasecmp($a["title"],$b["title"]);' ) );

            $keys = array_keys( $extensions );
            if ( $keys[0] != "" ) {
                $new_key = strtolower( str_replace( " ", "_", $extensions[""]['title'] ) );
                $temp = $extensions[""];
                $extensions[$new_key] = $temp;
                $extensions[""] = $extensions[$keys[0]];
                unset( $extensions[$keys[0]] );
                @uasort( $extensions, create_function( '$a,$b', 'return strnatcasecmp($a["title"],$b["title"]);' ) );
            }

            $settings['extensions']['sections'] = $extensions;

            return $settings;
        }


        function get_section_fields( $tab, $section ) {

            if ( empty( $this->settings_structure[$tab] ) )
                return array();

            if ( ! empty( $this->settings_structure[$tab]['sections'][$section]['fields'] ) ) {
                return $this->settings_structure[$tab]['sections'][$section]['fields'];
            } elseif ( ! empty( $this->settings_structure[$tab]['fields'] ) ) {
                return $this->settings_structure[$tab]['fields'];
            }

            return array();
        }


        /***
         ***	@setup admin menu
         ***/
        function primary_admin_menu() {
            add_submenu_page( 'ultimatemember', __( 'Settings', 'ultimate-member' ), __( 'Settings', 'ultimate-member' ), 'manage_options', 'um_options', array( &$this, 'settings_page' ) );
        }


        function settings_page() {
            $current_tab = empty( $_GET['tab'] ) ? '' : urldecode( $_GET['tab'] );
            $current_subtab = empty( $_GET['section'] ) ? '' : urldecode( $_GET['section'] );

            $settings_struct = $this->settings_structure[$current_tab];

            //remove not option hidden fields
            if ( ! empty( $settings_struct['fields'] ) ) {
                foreach ( $settings_struct['fields'] as $field_key=>$field_options ) {

                    if ( isset( $field_options['is_option'] ) && $field_options['is_option'] === false )
                        unset( $settings_struct['fields'][$field_key] );

                }
            }

            if ( empty( $settings_struct['fields'] ) && empty( $settings_struct['sections'] ) )
                um_js_redirect( add_query_arg( array( 'page' => 'um_options' ), admin_url( 'admin.php' ) ) );

            if ( ! empty( $settings_struct['sections'] ) ) {
                if ( empty( $settings_struct['sections'][$current_subtab] ) )
                    um_js_redirect( add_query_arg( array( 'page' => 'um_options', 'tab' => $current_tab ), admin_url( 'admin.php' ) ) );
            }

            echo $this->generate_tabs_menu() . $this->generate_subtabs_menu( $current_tab );

            do_action( "um_settings_page_before_" . $current_tab . "_" . $current_subtab . "_content" );

            if ( 'licenses' == $current_tab ) {
                do_action( "um_settings_page_" . $current_tab . "_" . $current_subtab . "_before_section" );

                $section_fields = $this->get_section_fields( $current_tab, $current_subtab );
                echo apply_filters( 'um_settings_section_' . $current_tab . '_' . $current_subtab . '_content', $this->render_settings_section( $section_fields, $current_tab, $current_subtab ), $section_fields );

            } else { ?>

                <form method="post" action="" name="um-settings-form" id="um-settings-form">
                    <input type="hidden" value="save" name="um-settings-action" />

                    <?php do_action( "um_settings_page_" . $current_tab . "_" . $current_subtab . "_before_section" );

                    $section_fields = $this->get_section_fields( $current_tab, $current_subtab );
                    echo apply_filters( 'um_settings_section_' . $current_tab . '_' . $current_subtab . '_content', $this->render_settings_section( $section_fields, $current_tab, $current_subtab ), $section_fields );
                    ?>

                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'ultimate-member' ) ?>" />
                    </p>
                </form>

            <?php }
        }



        /**
         * Generate pages tabs
         *
         * @param string $page
         * @return string
         */
        function generate_tabs_menu( $page = 'settings' ) {

            $tabs = '<h2 class="nav-tab-wrapper um-nav-tab-wrapper">';

            switch( $page ) {
                case 'settings':
                    $menu_tabs = array();
                    foreach ( $this->settings_structure as $slug => $tab ) {
                        if ( ! empty( $tab['fields'] ) ) {
                            foreach ( $tab['fields'] as $field_key=>$field_options ) {
                                if ( isset( $field_options['is_option'] ) && $field_options['is_option'] === false ) {
                                    unset( $tab['fields'][$field_key] );
                                }
                            }
                        }

                        if ( ! empty( $tab['fields'] ) || ! empty( $tab['sections'] ) )
                            $menu_tabs[$slug] = $tab['title'];
                    }

                    $current_tab = empty( $_GET['tab'] ) ? '' : urldecode( $_GET['tab'] );
                    foreach ( $menu_tabs as $name=>$label ) {
                        $active = ( $current_tab == $name ) ? 'nav-tab-active' : '';
                        $tabs .= '<a href="' . admin_url( 'admin.php?page=um_options' . ( empty( $name ) ? '' : '&tab=' . $name ) ) . '" class="nav-tab ' . $active . '">' .
                            $label .
                            '</a>';
                    }

                    break;
                default:
                    $tabs = apply_filters( 'um_generate_tabs_menu_' . $page, $tabs );
                    break;
            }

            return $tabs . '</h2>';
        }



        function generate_subtabs_menu( $tab = '' ) {
            if ( empty( $this->settings_structure[$tab]['sections'] ) )
                return '';

            $menu_subtabs = array();
            foreach ( $this->settings_structure[$tab]['sections'] as $slug => $subtab ) {
                $menu_subtabs[$slug] = $subtab['title'];
            }

            $subtabs = '<div><ul class="subsubsub">';

            $current_tab = empty( $_GET['tab'] ) ? '' : urldecode( $_GET['tab'] );
            $current_subtab = empty( $_GET['section'] ) ? '' : urldecode( $_GET['section'] );
            foreach ( $menu_subtabs as $name => $label ) {
                $active = ( $current_subtab == $name ) ? 'current' : '';
                $subtabs .= '<a href="' . admin_url( 'admin.php?page=um_options' . ( empty( $current_tab ) ? '' : '&tab=' . $current_tab ) . ( empty( $name ) ? '' : '&section=' . $name ) ) . '" class="' . $active . '">'
                    . $label .
                    '</a> | ';
            }

            return substr( $subtabs, 0, -3 ) . '</ul></div>';
        }


        /**
         * Handler for settings forms
         * when "Save Settings" button click
         *
         */
        function save_settings_handler() {
            if ( isset( $_POST['um-settings-action'] ) && 'save' == $_POST['um-settings-action'] && ! empty( $_POST['um_options'] ) ) {
                do_action( "um_settings_before_save" );

                foreach ( $_POST['um_options'] as $key=>$value ) {
                    um_update_option( $key, $value );
                }

                do_action( "um_settings_save" );


                //redirect after save settings
                $arg = array(
                    'page' => 'um_options',
                );

                if ( ! empty( $_GET['tab'] ) )
                    $arg['tab'] = $_GET['tab'];

                if ( ! empty( $_GET['section'] ) )
                    $arg['section'] = $_GET['section'];

                um_js_redirect( add_query_arg( $arg, admin_url( 'admin.php' ) ) );
            }
        }


        function on_settings_save() {
            if ( ! empty( $_POST['um_options'] ) ) {
                if ( ! empty( $_POST['pages_settings'] ) ) {
                    $post_ids = new \WP_Query( array(
                        'post_type' => 'page',
                        'meta_query' => array(
                            array(
                                'key'       => '_um_core',
                                'compare'   => 'EXISTS'
                            )
                        ),
                        'posts_per_page' => -1,
                        'fields'        => 'ids'
                    ) );

                    $post_ids = $post_ids->get_posts();

                    if ( ! empty( $post_ids ) ) {
                        foreach ( $post_ids as $post_id ) {
                            delete_post_meta( $post_id, '_um_core' );
                        }
                    }

                    foreach ( $_POST['um_options'] as $option_slug => $post_id ) {
                        $slug = str_replace( 'core_', '', $option_slug );
                        update_post_meta( $post_id, '_um_core', $slug );
                    }
                }
            }
        }


        function before_licenses_save() {
            if ( empty( $_POST['um_options'] ) || empty( $_POST['licenses_settings'] ) )
                return;

            foreach ( $_POST['um_options'] as $key => $value ) {
                $this->previous_licenses[$key] = um_get_option( $key );
            }
        }


        function licenses_save() {
            if ( empty( $_POST['um_options'] ) || empty( $_POST['licenses_settings'] ) )
                return;

            foreach ( $_POST['um_options'] as $key => $value ) {
                $edd_action = '';
                $license_key = '';
                if ( empty( $this->previous_licenses[$key] ) && ! empty( $value ) ) {
                    $edd_action = 'activate_license';
                    $license_key = $value;
                } elseif ( ! empty( $this->previous_licenses[$key] ) && empty( $value ) ) {
                    $edd_action = 'deactivate_license';
                    $license_key = $this->previous_licenses[$key];
                } elseif ( ! empty( $this->previous_licenses[$key] ) && ! empty( $value ) ) {
                    $edd_action = 'check_license';
                    $license_key = $value;
                }

                if ( empty( $edd_action ) )
                    continue;

                $item_name = false;
                $version = false;
                $author = false;
                foreach ( $this->settings_structure['licenses']['fields'] as $field_data ) {
                    if ( $field_data['id'] == $key ) {
                        $item_name = ! empty( $field_data['item_name'] ) ? $field_data['item_name'] : false;
                        $version = ! empty( $field_data['version'] ) ? $field_data['version'] : false;
                        $author = ! empty( $field_data['author'] ) ? $field_data['author'] : false;
                    }
                }

                $api_params = array(
                    'edd_action' => $edd_action,
                    'license'    => $license_key,
                    'item_name'  => $item_name,
                    'version'    => $version,
                    'author'     => $author,
                    'url'        => home_url(),
                );

                $request = wp_remote_post(
                    'https://ultimatemember.com/',
                    array(
                        'timeout'   => 15,
                        'sslverify' => false,
                        'body'      => $api_params
                    )
                );

                if ( ! is_wp_error( $request ) )
                    $request = json_decode( wp_remote_retrieve_body( $request ) );

                $request = ( $request ) ? maybe_unserialize( $request ) : false;

                if ( $edd_action == 'activate_license' || $edd_action == 'check_license' )
                    update_option( "{$key}_edd_answer", $request );
                else
                    delete_option( "{$key}_edd_answer" );

            }
        }


        function check_wrong_licenses() {
            $invalid_license = false;

            if ( empty( $this->settings_structure['licenses']['fields'] ) )
                return;

            foreach ( $this->settings_structure['licenses']['fields'] as $field_data ) {
                $license = get_option( "{$field_data['id']}_edd_answer" );

                if ( ( is_object( $license ) && 'valid' == $license->license ) || 'valid' == $license )
                    continue;

                $invalid_license = true;
                break;
            }

            if ( $invalid_license ) { ?>

                <div class="error">
                    <p>
                        <?php printf( __( 'You have invalid or expired license keys for %s. Please go to the <a href="%s">Licenses page</a> to correct this issue.', 'ultimate-member' ), ultimatemember_plugin_name, add_query_arg( array('page'=>'um_options', 'tab' => 'licenses'), admin_url( 'admin.php' ) ) ) ?>
                    </p>
                </div>

            <?php }
        }


        function settings_before_email_tab() {
            $email_key = empty( $_GET['email'] ) ? '' : urldecode( $_GET['email'] );
            $emails = UM()->config()->email_notifications;

            if ( empty( $email_key ) || empty( $emails[$email_key] ) )
                include_once um_path . 'includes/admin/core/list-tables/emails-list-table.php';
        }


        function settings_email_tab( $section ) {
            $email_key = empty( $_GET['email'] ) ? '' : urldecode( $_GET['email'] );
            $emails = UM()->config()->email_notifications;

            if ( empty( $email_key ) || empty( $emails[$email_key] ) )
                return $section;

            $section_fields = array(
                array(
                    'id'            => $email_key . '_on',
                    'type'          => 'checkbox',
                    'label'         => $emails[$email_key]['title'],
                    'tooltip'   => $emails[$email_key]['description'],
                    'value' 		=> UM()->um_get_option( $email_key . '_on' ),
                    'default' 		=> UM()->um_get_default( $email_key . '_on' ),
                ),
                array(
                    'id'       => $email_key . '_sub',
                    'type'     => 'text',
                    'label'    => __( 'Subject Line','ultimate-member' ),
                    'conditional' => array( $email_key . '_on', '=', 1 ),
                    'tooltip' => __('This is the subject line of the e-mail','ultimate-member'),
                    'value' 		=> UM()->um_get_option( $email_key . '_sub' ),
                    'default' 		=> UM()->um_get_default( $email_key . '_sub' ),
                ),
                array(
                    'id'       => $email_key,
                    'type'     => 'wp_editor',
                    'label'    => __( 'Message Body','ultimate-member' ),
                    'conditional' => array( $email_key . '_on', '=', 1 ),
                    'tooltip' 	   => __('This is the content of the e-mail','ultimate-member'),
                    'value' 		=> UM()->um_get_option( $email_key ),
                    'default' 		=> UM()->um_get_default( $email_key ),
                ),
            );

            return $this->render_settings_section( $section_fields, 'email', $email_key );
        }


        function settings_appearance_profile_tab() {
            wp_enqueue_media();
        }


        function settings_licenses_tab( $html, $section_fields ) {
            ob_start(); ?>

            <div class="wrap-licenses">
                <input type="hidden" id="licenses_settings" name="licenses_settings" value="1">
                <table class="form-table um-settings-section">
                    <tbody>
                    <?php foreach ( $section_fields as $field_data ) {
                        $option_value = um_get_option( $field_data['id'] );
                        $value = ! empty( $option_value ) ? $option_value : ( ! empty( $field_data['default'] ) ? $field_data['default'] : '' );

                        $license = get_option( "{$field_data['id']}_edd_answer" );

                        if ( is_object( $license ) ) {
                            // activate_license 'invalid' on anything other than valid, so if there was an error capture it
                            if ( false === $license->success ) {

                                if ( ! empty( $license->error ) ) {
                                    switch ( $license->error ) {

                                        case 'expired' :

                                            $class = 'expired';
                                            $messages[] = sprintf(
                                                __( 'Your license key expired on %s. Please <a href="%s" target="_blank">renew your license key</a>.', 'ultimate-member' ),
                                                date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
                                                'https://ultimatemember.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=expired'
                                            );

                                            $license_status = 'license-' . $class . '-notice';

                                            break;

                                        case 'revoked' :

                                            $class = 'error';
                                            $messages[] = sprintf(
                                                __( 'Your license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', 'ultimate-member' ),
                                                'https://ultimatemember.com/support?utm_campaign=admin&utm_source=licenses&utm_medium=revoked'
                                            );

                                            $license_status = 'license-' . $class . '-notice';

                                            break;

                                        case 'missing' :

                                            $class = 'error';
                                            $messages[] = sprintf(
                                                __( 'Invalid license. Please <a href="%s" target="_blank">visit your account page</a> and verify it.', 'ultimate-member' ),
                                                'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=missing'
                                            );

                                            $license_status = 'license-' . $class . '-notice';

                                            break;

                                        case 'invalid' :
                                        case 'site_inactive' :

                                            $class = 'error';
                                            $messages[] = sprintf(
                                                __( 'Your %s is not active for this URL. Please <a href="%s" target="_blank">visit your account page</a> to manage your license key URLs.', 'ultimate-member' ),
                                                $field_data['item_name'],
                                                'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=invalid'
                                            );

                                            $license_status = 'license-' . $class . '-notice';

                                            break;

                                        case 'item_name_mismatch' :

                                            $class = 'error';
                                            $messages[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'ultimate-member' ), $field_data['item_name'] );

                                            $license_status = 'license-' . $class . '-notice';

                                            break;

                                        case 'no_activations_left':

                                            $class = 'error';
                                            $messages[] = sprintf( __( 'Your license key has reached its activation limit. <a href="%s">View possible upgrades</a> now.', 'ultimate-member' ), 'https://ultimatemember.com/account' );

                                            $license_status = 'license-' . $class . '-notice';

                                            break;

                                        case 'license_not_activable':

                                            $class = 'error';
                                            $messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'ultimate-member' );

                                            $license_status = 'license-' . $class . '-notice';
                                            break;

                                        default :

                                            $class = 'error';
                                            $error = ! empty(  $license->error ) ?  $license->error : __( 'unknown_error', 'ultimate-member' );
                                            $messages[] = sprintf( __( 'There was an error with this license key: %s. Please <a href="%s">contact our support team</a>.', 'ultimate-member' ), $error, 'https://ultimatemember.com/support' );

                                            $license_status = 'license-' . $class . '-notice';
                                            break;
                                    }
                                } else {
                                    $class = 'error';
                                    $error = ! empty(  $license->error ) ?  $license->error : __( 'unknown_error', 'ultimate-member' );
                                    $messages[] = sprintf( __( 'There was an error with this license key: %s. Please <a href="%s">contact our support team</a>.', 'ultimate-member' ), $error, 'https://ultimatemember.com/support' );

                                    $license_status = 'license-' . $class . '-notice';
                                }

                            } else {

                                switch( $license->license ) {

                                    case 'expired' :

                                        $class = 'expired';
                                        $messages[] = sprintf(
                                            __( 'Your license key expired on %s. Please <a href="%s" target="_blank">renew your license key</a>.', 'ultimate-member' ),
                                            date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
                                            'https://ultimatemember.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=expired'
                                        );

                                        $license_status = 'license-' . $class . '-notice';

                                        break;

                                    case 'revoked' :

                                        $class = 'error';
                                        $messages[] = sprintf(
                                            __( 'Your license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', 'ultimate-member' ),
                                            'https://ultimatemember.com/support?utm_campaign=admin&utm_source=licenses&utm_medium=revoked'
                                        );

                                        $license_status = 'license-' . $class . '-notice';

                                        break;

                                    case 'missing' :

                                        $class = 'error';
                                        $messages[] = sprintf(
                                            __( 'Invalid license. Please <a href="%s" target="_blank">visit your account page</a> and verify it.', 'ultimate-member' ),
                                            'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=missing'
                                        );

                                        $license_status = 'license-' . $class . '-notice';

                                        break;

                                    case 'invalid' :
                                    case 'site_inactive' :

                                        $class = 'error';
                                        $messages[] = sprintf(
                                            __( 'Your %s is not active for this URL. Please <a href="%s" target="_blank">visit your account page</a> to manage your license key URLs.', 'ultimate-member' ),
                                            $field_data['item_name'],
                                            'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=invalid'
                                        );

                                        $license_status = 'license-' . $class . '-notice';

                                        break;

                                    case 'item_name_mismatch' :

                                        $class = 'error';
                                        $messages[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'ultimate-member' ), $field_data['item_name'] );

                                        $license_status = 'license-' . $class . '-notice';

                                        break;

                                    case 'no_activations_left':

                                        $class = 'error';
                                        $messages[] = sprintf( __( 'Your license key has reached its activation limit. <a href="%s">View possible upgrades</a> now.', 'ultimate-member' ), 'https://ultimatemember.com/account' );

                                        $license_status = 'license-' . $class . '-notice';

                                        break;

                                    case 'license_not_activable':

                                        $class = 'error';
                                        $messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'ultimate-member' );

                                        $license_status = 'license-' . $class . '-notice';
                                        break;

                                    case 'valid' :
                                    default:

                                        $class = 'valid';

                                        $now        = current_time( 'timestamp' );
                                        $expiration = strtotime( $license->expires, current_time( 'timestamp' ) );

                                        if( 'lifetime' === $license->expires ) {

                                            $messages[] = __( 'License key never expires.', 'ultimate-member' );

                                            $license_status = 'license-lifetime-notice';

                                        } elseif( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {

                                            $messages[] = sprintf(
                                                __( 'Your license key expires soon! It expires on %s. <a href="%s" target="_blank">Renew your license key</a>.', 'ultimate-member' ),
                                                date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
                                                'https://ultimatemember.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=renew'
                                            );

                                            $license_status = 'license-expires-soon-notice';

                                        } else {

                                            $messages[] = sprintf(
                                                __( 'Your license key expires on %s.', 'ultimate-member' ),
                                                date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) )
                                            );

                                            $license_status = 'license-expiration-date-notice';

                                        }

                                        break;

                                }

                            }

                        } else {
                            $class = 'empty';

                            $messages[] = sprintf(
                                __( 'To receive updates, please enter your valid %s license key.', 'ultimate-member' ),
                                $field_data['item_name']
                            );

                            $license_status = null;
                        } ?>

                        <tr class="um-settings-line">
                            <th><label for="um_options_<?php echo $field_data['id'] ?>"><?php echo $field_data['label'] ?></label></th>
                            <td>
                                <form method="post" action="" name="um-settings-form" class="um-settings-form">
                                    <input type="hidden" value="save" name="um-settings-action" />
                                    <input type="hidden" name="licenses_settings" value="1" />
                                    <input type="text" id="um_options_<?php echo $field_data['id'] ?>" name="um_options[<?php echo $field_data['id'] ?>]" value="<?php echo $value ?>" class="um-option-field um-long-field" data-field_id="<?php echo $field_data['id'] ?>" />
                                    <?php if ( ! empty( $field_data['description'] ) ) { ?>
                                        <div class="description"><?php echo $field_data['description'] ?></div>
                                    <?php } ?>

                                    <?php if ( ( is_object( $license ) && 'valid' == $license->license ) || 'valid' == $license ) { ?>
                                        <input type="button" class="button um_license_deactivate" id="<?php echo $field_data['id'] ?>_deactivate" value="<?php _e( 'Clear License',  'ultimate-member' ) ?>"/>
                                    <?php } elseif ( empty( $value ) ) { ?>
                                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Activate', 'ultimate-member' ) ?>" />
                                    <?php } else { ?>
                                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Re-Activate', 'ultimate-member' ) ?>" />
                                    <?php }

                                    if ( ! empty( $messages ) ) {
                                        foreach ( $messages as $message ) { ?>
                                            <div class="edd-license-data edd-license-<?php echo $class . ' ' . $license_status ?>">
                                                <p><?php echo $message ?></p>
                                            </div>
                                        <?php }
                                    } ?>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php $section = ob_get_clean();

            return $section;
        }

        /**
         * Render settings section
         *
         * @param $section_fields
         * @return string
         */
        function render_settings_section( $section_fields, $current_tab, $current_subtab ) {
            ob_start();

            UM()->admin_forms( array(
                'class'		=> 'um_options-' . $current_tab . '-' . $current_subtab . ' um-third-column',
                'prefix_id'	=> 'um_options',
                'fields' => $section_fields
            ) )->render_form(); ?>

            <?php $section = ob_get_clean();

            return $section;
        }


        /**
         * Render HTML for settings field
         *
         * @param $data
         * @return string
         */
        function render_setting_field( $data ) {
            if ( empty( $data['type'] ) )
                return '';

            $conditional = ! empty( $data['conditional'] ) ? 'data-conditional="' . esc_attr( json_encode( $data['conditional'] ) ) . '"' : '';

            $html = '';
            if ( $data['type'] != 'hidden' )
                $html .= '<tr class="um-settings-line" ' . $conditional . '><th><label for="um_options_' . $data['id'] . '">' . $data['label'] . '</label></th><td>';


            $option_value = UM()->um_get_option( $data['id'] );
            $default = ! empty( $data['default'] ) ? $data['default'] : UM()->um_get_default( $data['id'] );

            switch ( $data['type'] ) {
                case 'hidden':
                    $value = ! empty( $option_value ) ? $option_value : $default;

                    if ( empty( $data['is_option'] ) )
                        $html .= '<input type="hidden" id="' . $data['id'] . '" name="' . $data['id'] . '" value="' . $value . '" />';
                    else
                        $html .= '<input type="hidden" id="um_options_' . $data['id'] . '" name="um_options[' . $data['id'] . ']" value="' . $value . '" class="um-option-field" data-field_id="' . $data['id'] . '" />';

                    break;
                case 'text':
                    $value = ! empty( $option_value ) ? $option_value : $default;
                    $field_length = ! empty( $data['size'] ) ? $data['size'] : 'um-long-field';

                    $html .= '<input type="text" id="um_options_' . $data['id'] . '" name="um_options[' . $data['id'] . ']" value="' . $value . '" class="um-option-field ' . $field_length . '" data-field_id="' . $data['id'] . '" />';
                    break;
                case 'multi-text':
                    $values = ! empty( $option_value ) ? $option_value : $default;

                    $html .= '<ul class="um-multi-text-list" data-field_id="' . $data['id'] . '">';

                    if ( ! empty( $values ) ) {
                        foreach ( $values as $k=>$value ) {
                            $html .= '<li class="um-multi-text-option-line"><input type="text" id="um_options_' . $data['id'] . '-' . $k . '" name="um_options[' . $data['id'] . '][]" value="' . $value . '" class="um-option-field" data-field_id="' . $data['id'] . '" />
                                    <a href="javascript:void(0);" class="um-option-delete">' . __( 'Remove', 'ultimate-member' ) . '</a></li>';
                        }
                    }

                    $html .= '</ul><a href="javascript:void(0);" class="button button-primary um-multi-text-add-option" data-name="um_options[' . $data['id'] . '][]">' . $data['add_text'] . '</a>';
                    break;
                case 'textarea':
                    $value = ! empty( $option_value ) ? $option_value : $default;
                    $field_length = ! empty( $data['size'] ) ? $data['size'] : 'um-long-field';

                    $html .= '<textarea id="um_options_' . $data['id'] . '" name="um_options[' . $data['id'] . ']" rows="6" class="um-option-field ' . $field_length . '" data-field_id="' . $data['id'] . '">' . $value . '</textarea>';
                    break;
                case 'wp_editor':
                    $value = ! empty( $option_value ) ? $option_value : $default;

                    ob_start();
                    wp_editor( $value,
                        'um_options_' . $data['id'],
                        array(
                            'textarea_name' => 'um_options[' . $data['id'] . ']',
                            'textarea_rows' => 20,
                            'editor_height' => 425,
                            'wpautop'       => false,
                            'media_buttons' => false,
                            'editor_class'  => 'um-option-field'
                        )
                    );

                    $html .= ob_get_clean();
                    break;
                case 'checkbox':
                    $value = ( '' !== $option_value ) ? $option_value : $default;

                    $html .= '<input type="hidden" id="um_options_' . $data['id'] . '_hidden" name="um_options[' . $data['id'] . ']" value="0" /><input type="checkbox" ' . checked( $value, true, false ) . ' id="um_options_' . $data['id'] . '" name="um_options[' . $data['id'] . ']" value="1" class="um-option-field" data-field_id="' . $data['id'] . '" />';
                    break;
                case 'multi-checkbox':
                    $value = ( '' !== $option_value ) ? $option_value : $default;
                    $columns = ! empty( $data['columns'] ) ? $data['columns'] : 1;

                    $per_column = ceil( count( $data['options'] ) / $columns );

                    $html .= '<div class="multi-checkbox-line">';

                    $current_option = 1;
                    $iter = 1;
                    foreach ( $data['options'] as $key=>$option ) {
                        if ( $current_option == 1 )
                            $html .= '<div class="multi-checkbox-column" style="width:' . floor( 100/$columns ) . '%;">';

                        $html .= '<input type="hidden" id="um_options_' . $data['id'] . '_' . $key . '_hidden" name="um_options[' . $data['id'] . '][' . $key . ']" value="0" />
                        <label><input type="checkbox" ' . checked( $value[$key], true, false ) . ' id="um_options_' . $data['id'] . '" name="um_options[' . $data['id'] . '][' . $key . ']" value="1" class="um-option-field" data-field_id="' . $data['id'] . '" />' . $option . '</label>';

                        if ( $current_option == $per_column || $iter == count( $data['options'] ) ) {
                            $current_option = 1;
                            $html .= '</div>';
                        } else {
                            $current_option++;
                        }

                        $iter++;
                    }

                    $html .= '</div>';

                    break;
                case 'selectbox':
                    $value = ! empty( $option_value ) ? $option_value : $default;

                    $html .= '<select ' . ( ! empty( $data['multi'] ) ? 'multiple' : '' ) . ' id="um_options_' . $data['id'] . '" name="um_options[' . $data['id'] . ']' . ( ! empty( $data['multi'] ) ? '[]' : '' ) . '" class="um-option-field" data-field_id="' . $data['id'] . '">';
                    foreach ( $data['options'] as $key=>$option ) {
                        if ( ! empty( $data['multi'] ) ) {
                            $html .= '<option value="' . $key . '" ' . selected( in_array( $key, $value ), true, false ) . '>' . $option . '</option>';
                        } else {
                            $html .= '<option value="' . $key . '" ' . selected( $key == $value, true, false ) . '>' . $option . '</option>';
                        }
                    }
                    $html .= '</select>';

                    break;
                case 'media':
                    $upload_frame_title = ! empty( $data['upload_frame_title'] ) ? $data['upload_frame_title'] : __( 'Select media', 'ultimate-member' );
                    $value = ! empty( $option_value ) ? $option_value : $default;

                    $image_id = ! empty( $value['id'] ) ? $value['id'] : '';
                    $image_width = ! empty( $value['width'] ) ? $value['width'] : '';
                    $image_height = ! empty( $value['height'] ) ? $value['height'] : '';
                    $image_thumbnail = ! empty( $value['thumbnail'] ) ? $value['thumbnail'] : '';
                    $image_url = ! empty( $value['url'] ) ? $value['url'] : '';

                    $data_default = ! empty( $default ) ? 'data-default="' .  esc_attr( $default['url'] ) .'"' : '';

                    $html .= '<div class="um-media-upload">' .
                        '<input type="hidden" class="um-media-upload-data-id" name="um_options[' . $data['id'] . '][id]" id="um_options_' . $data['id'] . '_id" value="' . $image_id . '">' .
                        '<input type="hidden" class="um-media-upload-data-width" name="um_options[' . $data['id'] . '][width]" id="um_options_' . $data['id'] . '_width" value="' . $image_width . '">' .
                        '<input type="hidden" class="um-media-upload-data-height" name="um_options[' . $data['id'] . '][height]" id="um_options_' . $data['id'] . '_height" value="' . $image_height . '">' .
                        '<input type="hidden" class="um-media-upload-data-thumbnail" name="um_options[' . $data['id'] . '][thumbnail]" id="um_options_' . $data['id'] . '_thumbnail" value="' . $image_thumbnail . '">' .
                        '<input type="hidden" class="um-option-field um-media-upload-data-url" name="um_options[' . $data['id'] . '][url]" id="um_options_' . $data['id'] . '_url" value="' . $image_url . '" data-field_id="' . $data['id'] . '" ' .  $data_default . '>';

                    if ( ! isset( $data['preview'] ) || $data['preview'] !== false ) {
                        $html .= '<img src="' . ( ! empty( $value['url'] ) ? $value['url'] : '' ) . '" alt="" class="icon_preview"><div style="clear:both;"></div>';
                    }

                    if ( ! empty( $data['url'] ) ) {
                        $html .= '<input type="text" class="um-media-upload-url" readonly value="' . $image_url . '" /><div style="clear:both;"></div>';
                    }

                    $html .= '<input type="button" class="um-set-image button button-primary" value="' . __( 'Select', 'ultimate-member' ) . '" data-upload_frame="' . $upload_frame_title . '" />
                    <input type="button" class="um-clear-image button" value="' . __( 'Clear', 'ultimate-member' ) . '" /></div>';
                    break;
            }

            if ( ! empty( $data['description'] ) )
                $html .= '<div class="description">' . $data['description'] . '</div>';

            $html .= '</td></tr>';

            return $html;
        }

    }
}