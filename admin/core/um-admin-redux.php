<?php

    if ( ! class_exists( 'UM_Redux_Framework_Config' ) ) {

        class UM_Redux_Framework_Config {

            public $args = array();
            public $sections = array();
            public $theme;
            public $ReduxFramework;

            public function __construct() {

                if ( ! class_exists( 'ReduxFramework' ) ) {
                    return;
                }

                // This is needed. Bah WordPress bugs.  ;)
                if ( true == Redux_Helpers::isTheme( __FILE__ ) ) {
                    $this->initSettings();
                } else {
					//add_action( 'plugins_loaded', array( $this, 'initSettings' ), 10 );
                    add_action( 'wp_loaded', array( $this, 'initSettings' ), 10 );
                }

            }

            public function initSettings() {

                $this->setArguments();
                $this->setHelpTabs();
                $this->setSections();

                if ( ! isset( $this->args['opt_name'] ) ) { // No errors please
                    return;
                }

                $this->ReduxFramework = new ReduxFramework( $this->sections, $this->args );
            }

            public function setSections() {
			
				include_once um_path . 'um-config.php';

            }

        public function setHelpTabs() {

        }

        public function setArguments() {

            $this->args = array(
                'opt_name'          => 'um_options',            // This is where your data is stored in the database and also becomes your global variable name.
                'display_name'      => 'Settings',     // Name that appears at the top of your panel
                'display_version'   => ULTIMATEMEMBER_VERSION,  // Version that appears at the top of your panel
                'menu_type'         => 'submenu',                  //Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
                'allow_sub_menu'    => false,                    // Show the sections below the admin menu item or not
                'menu_title'        => __('Settings', 'redux-framework-demo'),
                'page_title'        => __('Settings', 'redux-framework-demo'),
               
                'google_api_key' => '', // Must be defined to add google fonts to the typography module
                'async_typography'  => true,                    // Use a asynchronous font on the front end or font string
                'admin_bar'         => false,                    // Show the panel pages on the admin bar
                'global_variable'   => '',                      // Set a different name for your global variable other than the opt_name
                'dev_mode'          => false,                    // Show the time the page took to load, etc
                'customizer'        => false,                    // Enable basic customizer support
                'page_priority'     => null,                    // Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
                'page_parent'       => 'ultimatemember',            // For a full list of options, visit: http://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters
                'page_permissions'  => 'manage_options',        // Permissions needed to access the options panel.
                'menu_icon'         => 'dashicons-admin-users', // Specify a custom URL to an icon
                'last_tab'          => '',                      // Force your panel to always open to a specific tab (by id)
                'page_icon'         => 'icon-themes',           // Icon displayed in the admin panel next to your menu_title
                'page_slug'         => 'um_options',              // Page slug used to denote the panel
                'save_defaults'     => true,                    // On load save the defaults to DB before user clicks save or not
                'default_show'      => false,                   // If true, shows the default value next to each field that is not the default value.
                'default_mark'      => '',                      // What to print by the field's title if the value shown is default. Suggested: *
                'show_import_export' => false,                   // Shows the Import/Export panel when not used as a field.

                'transient_time'    => 60 * MINUTE_IN_SECONDS,
                'output'            => true,                    // Global shut-off for dynamic CSS output by the framework. Will also disable google fonts output
                'output_tag'        => true,                    // Allows dynamic CSS to be generated for customizer and google fonts, but stops the dynamic CSS from going to the head
                'footer_credit'     => '',                   // Disable the footer credit of Redux. Please leave if you can help it.

                'hints' => array(
                    'icon'          => 'icon-question-sign',
                    'icon_position' => 'right',
                    'icon_color'    => 'lightgray',
                    'icon_size'     => 'normal',
                    'tip_style'     => array(
                        'color'         => 'light',
                        'shadow'        => true,
                        'rounded'       => false,
                        'style'         => '',
                    ),
                    'tip_position'  => array(
                        'my' => 'top left',
                        'at' => 'bottom right',
                    ),
                    'tip_effect'    => array(
                        'show'          => array(
                            'effect'        => 'slide',
                            'duration'      => '500',
                            'event'         => 'mouseover',
                        ),
                        'hide'      => array(
                            'effect'    => 'slide',
                            'duration'  => '500',
                            'event'     => 'click mouseleave',
                        ),
                    ),
                )
				
            );
			
            // SOCIAL ICONS -> Setup custom links in the footer for quick links in your panel footer icons.
            $this->args['share_icons'][] = array(
                'url'   => 'https://github.com/ultimatemember/ultimatemember',
                'title' => 'GitHub Repository',
                'icon'  => 'um-icon-github'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'https://trello.com/b/30quaczv/ultimate-member',
                'title' => 'Roadmap',
                'icon'  => 'um-icon-trello'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'https://facebook.com/pages/Ultimate-Member/1413909622233054',
                'title' => 'Like us on Facebook',
                'icon'  => 'um-icon-facebook'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'https://twitter.com/umplugin',
                'title' => 'Follow us on Twitter',
                'icon'  => 'um-icon-twitter'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'https://google.com/+ultimatemember',
                'title' => 'Follow us on Google+',
                'icon'  => 'um-icon-google-plus'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'https://youtube.com/user/umplugin',
                'title' => 'We\'re on YouTube',
                'icon'  => 'um-icon-youtube-alt'
            );

        }

            public function validate_callback_function( $field, $value, $existing_value ) {
                $error = true;
                $value = 'just testing';

                $return['value'] = $value;
                $field['msg']    = 'your custom error message';
                if ( $error == true ) {
                    $return['error'] = $field;
                }

                return $return;
            }

            public function class_field_callback( $field, $value ) {
                print_r( $field );
                echo '<br/>CLASS CALLBACK';
                print_r( $value );
            }

        }

        global $reduxConfig;
        $reduxConfig = new UM_Redux_Framework_Config();
		
    }

    if ( ! function_exists( 'redux_my_custom_field' ) ):
        function redux_my_custom_field( $field, $value ) {
            print_r( $field );
            echo '<br/>';
            print_r( $value );
        }
    endif;

    if ( ! function_exists( 'redux_validate_callback_function' ) ):
        function redux_validate_callback_function( $field, $value, $existing_value ) {
            $error = true;
            $value = 'just testing';

            $return['value'] = $value;
            $field['msg']    = 'your custom error message';
            if ( $error == true ) {
                $return['error'] = $field;
            }

            return $return;
        }
    endif;
