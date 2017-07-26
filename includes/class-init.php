<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'UM' ) ) {
    /**
     * Main UM Class
     *
     * @class UM
     * @version 2.0
     *
     * @method UM_bbPress_API bbPress_API()
     * @method UM_Followers_API Followers_API()
     * @method UM_Friends_API Friends_API()
     * @method UM_Instagram_API Instagram_API()
     * @method UM_Invitations_API Invitations_API()
     * @method UM_Mailchimp_API Mailchimp_API()
     * @method UM_Messaging_API Messaging_API()
     * @method UM_myCRED_API myCRED_API()
     * @method UM_Notices_API Notices_API()
     * @method UM_Notifications_API Notifications_API()
     * @method UM_Online_API Online_API()
     * @method UM_Profile_Completeness_API Profile_Completeness_API()
     * @method UM_reCAPTCHA_API reCAPTCHA_API()
     * @method UM_Reviews_API Reviews_API()
     * @method UM_Activity_API Activity_API()
     * @method UM_Social_Login_API Social_Login_API()
     * @method UM_User_Tags_API User_Tags_API()
     * @method UM_Verified_Users_API Verified_Users_API()
     * @method UM_WooCommerce_API WooCommerce_API()
     *
     */
    final class UM extends UM_Functions {

        /**
         * @var UM the single instance of the class
         */
        protected static $instance = null;


        /**
         * @var array all plugin's classes
         */
        public $classes = array();


        /**
         * @var bool Old variable
         */
        public $is_filtering;


        /**
         * @var array Languages
         */
        var $available_languages;


        /**
         * Main UM Instance
         *
         * Ensures only one instance of UM is loaded or can be loaded.
         *
         * @since 1.0
         * @static
         * @see UM()
         * @return UM - Main instance
         */
        static public function instance() {
            if ( is_null( self::$instance ) )
                self::$instance = new self();

            return self::$instance;
        }


        /**
         * Create plugin classes - not sure if it needs!!!!!!!!!!!!!!!
         *
         * @since 1.0
         * @see UM()
         *
         * @param $name
         * @param array $params
         * @return mixed
         */
        public function __call( $name, array $params ) {

            if ( empty( $this->classes[ $name ] ) )
                $this->classes[ $name ] = apply_filters( 'um_call_object_' . $name, false );

            return $this->classes[ $name ];

        }


        /**
         * Function for add classes to $this->classes
         * for run using UM()
         *
         * @param $class_name
         * @param bool $instance
         */
        public function set_class( $class_name, $instance = false ) {
            if ( empty( $this->classes[$class_name] ) ) {
                $class = 'UM_' . $class_name;
                $this->classes[$class_name] = $instance ? $class::instance() : new $class;
            }
        }


        /**
         * Cloning is forbidden.
         * @since 1.0
         */
        public function __clone() {
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'ultimate-member' ), '1.0' );
        }


        /**
         * Unserializing instances of this class is forbidden.
         * @since 1.0
         */
        public function __wakeup() {
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'ultimate-member' ), '1.0' );
        }


        /**
         * UM constructor.
         */
        function __construct() {
            parent::__construct();

            spl_autoload_register( array( $this, 'um__autoloader' ) );

            if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {

                $this->is_filtering = 0;
                $this->honeypot = 'request';
                $this->available_languages = array(
                    'en_US' => 'English (US)',
                    'es_ES' => 'Español',
                    'es_MX' => 'Español (México)',
                    'fr_FR' => 'Français',
                    'it_IT' => 'Italiano',
                    'de_DE' => 'Deutsch',
                    'nl_NL' => 'Nederlands',
                    'pt_BR' => 'Português do Brasil',
                    'fi_FI' => 'Suomi',
                    'ro_RO' => 'Română',
                    'da_DK' => 'Dansk',
                    'sv_SE' => 'Svenska',
                    'pl_PL' => 'Polski',
                    'cs_CZ' => 'Czech',
                    'el' => 'Greek',
                    'id_ID' => 'Indonesian',
                    'zh_CN' => '简体中文',
                    'ru_RU' => 'Русский',
                    'tr_TR' => 'Türkçe',
                    'fa_IR' => 'Farsi',
                    'he_IL' => 'Hebrew',
                    'ar' => 'العربية',
                );

                $this->includes();

                register_activation_hook( um_plugin, array( &$this, 'activation' ) );

                $language_domain = 'ultimate-member';
                $language_domain = apply_filters( 'um_language_textdomain', $language_domain );

                $language_locale = ( get_locale() != '' ) ? get_locale() : 'en_US';
                $language_locale = apply_filters( 'um_language_locale', $language_locale );

                $language_file = WP_LANG_DIR . '/plugins/' . $language_domain . '-' . $language_locale . '.mo';
                $language_file = apply_filters( 'um_language_file', $language_file );

                load_textdomain( $language_domain, $language_file );

                require_once 'um-short-functions.php';

                add_action( 'plugins_loaded', array( &$this, 'init' ), 0 );
                // init widgets
                add_action( 'widgets_init', array( &$this, 'widgets_init' ) );

                add_action( 'admin_init', array( &$this, 'redirect_to_about' ) );
            }
        }


        function um__autoloader( $class ) {
            if ( strpos( $class, 'um' ) === 0 ) {

                $array = explode( '\\', strtolower( $class ) );
                $array[ count( $array ) - 1 ] = 'class-'. end( $array );
                if ( strpos( $class, 'um_ext' ) === 0 ) {
                    $full_path = str_replace( 'ultimate-member', '', rtrim( um_path, '/' ) ) . str_replace( '_', '-', $array[1] ) . '/includes/';
                    unset( $array[0], $array[1] );
                    $path = implode( DIRECTORY_SEPARATOR, $array );
                    $path = str_replace( '_', '-', $path );
                    $full_path .= $path . '.php';
                } else {
                    $class = implode( '\\', $array );
                    $slash = DIRECTORY_SEPARATOR;
                    $path = str_replace(
                        array( 'um\\', '_', '\\' ),
                        array( $slash, '-', $slash ),
                        $class );
                    $full_path =  um_path . 'includes' . $path . '.php';
                }

                include_once $full_path;
            }
        }


        /**
         * Plugin Activation
         */
        function activation() {
            //first install
            $version = get_option( 'um_version' );
            if ( ! $version )
                update_option( 'um_last_version_upgrade', ultimatemember_version );

            if ( $version != ultimatemember_version )
                update_option( 'um_version', ultimatemember_version );

            //run setup
            $this->setup()->run_setup();

            if ( $version != ultimatemember_version ) {
                update_option( 'um_need_show_about', true );
            }
        }


        function redirect_to_about() {
            if ( get_option( 'um_need_show_about' ) ) {
                delete_option( 'um_need_show_about' );
                wp_redirect( admin_url( 'admin.php?page=ultimatemember-about' ) );
                exit;
            }
        }


        /**
         * Include required core files used in admin and on the frontend.
         *
         * @return void
         */
        public function includes() {

            $this->common();

            if ( $this->is_request( 'ajax' ) ) {
                $this->admin();
                $this->ajax_init();
                $this->metabox();
            } elseif ( $this->is_request( 'admin' ) ) {
                $this->admin();
                $this->admin_menu();
                $this->admin_upgrade();
                $this->admin_settings();
                $this->columns();
                $this->admin_enqueue();
                $this->functions();
                $this->metabox();
                $this->notices();
                $this->users();
                $this->dragdrop();
            } elseif ( $this->is_request( 'frontend' ) ) {
                $this->rewrite();
                $this->account();
                $this->password();
                $this->login();
                $this->register();
                $this->user_posts();
                $this->access();
                $this->mail();
                $this->members();
                $this->logout();
            }

            //common includes
            $this->rest_api();
            $this->enqueue();
            $this->shortcodes();
            $this->roles();
            $this->user();
            $this->profile();
            $this->builtin();
            $this->form();
            $this->permalinks();
            $this->modal();
            $this->cron();
            $this->tracking();
            $this->mobile();
        }


        function common() {
            if ( empty( $this->classes['common'] ) ) {
                $this->classes['common'] = new um\core\Common();
            }
            return $this->classes['common'];
        }


        function ajax_init() {
            new um\core\AJAX_Common();
        }


        /**
         * @return um\admin\Admin()
         */
        function admin() {
            if ( empty( $this->classes['admin'] ) ) {
                $this->classes['admin'] = new um\admin\Admin();
            }
            return $this->classes['admin'];
        }


        /**
         * @return um\admin\core\Admin_Menu()
         */
        function admin_menu() {
            if ( empty( $this->classes['admin_menu'] ) ) {
                $this->classes['admin_menu'] = new um\admin\core\Admin_Menu();
            }
            return $this->classes['admin_menu'];
        }


        /**
         * @return um\admin\core\Admin_Settings()
         */
        function admin_settings() {
            if ( empty( $this->classes['admin_settings'] ) ) {
                $this->classes['admin_settings'] = new um\admin\core\Admin_Settings();
            }
            return $this->classes['admin_settings'];
        }


        /**
         * @return um\admin\core\Admin_Upgrade()
         */
        function admin_upgrade() {
            if ( empty( $this->classes['admin_upgrade'] ) ) {
                $this->classes['admin_upgrade'] = new um\admin\core\Admin_Upgrade();
            }
            return $this->classes['admin_upgrade'];
        }


        /**
         * @return um\admin\core\Admin_Columns()
         */
        function columns() {
            if ( empty( $this->classes['admin_columns'] ) ) {
                $this->classes['admin_columns'] = new um\admin\core\Admin_Columns();
            }
            return $this->classes['admin_columns'];
        }


        /**
         * @return um\admin\core\Admin_Enqueue()
         */
        function admin_enqueue() {
            if ( empty( $this->classes['admin_enqueue'] ) ) {
                $this->classes['admin_enqueue'] = new um\admin\core\Admin_Enqueue();
            }
            return $this->classes['admin_enqueue'];
        }


        /**
         * @return um\admin\core\Admin_Functions()
         */
        function functions() {
            if ( empty( $this->classes['admin_functions'] ) ) {
                $this->classes['admin_functions'] = new um\admin\core\Admin_Functions();
            }
            return $this->classes['admin_functions'];
        }


        /**
         * @return um\admin\core\Admin_Metabox()
         */
        function metabox() {
            if ( empty( $this->classes['admin_metabox'] ) ) {
                $this->classes['admin_metabox'] = new um\admin\core\Admin_Metabox();
            }
            return $this->classes['admin_metabox'];
        }


        /**
         * @return um\admin\core\Admin_Notices()
         */
        function notices() {
            if ( empty( $this->classes['admin_notices'] ) ) {
                $this->classes['admin_notices'] = new um\admin\core\Admin_Notices();
            }
            return $this->classes['admin_notices'];
        }


        /**
         * @return um\admin\core\Admin_Users()
         */
        function users() {
            if ( empty( $this->classes['admin_users'] ) ) {
                $this->classes['admin_users'] = new um\admin\core\Admin_Users();
            }
            return $this->classes['admin_users'];
        }


        /**
         * @return um\admin\core\Admin_Builder()
         */
        function builder() {
            if ( empty( $this->classes['admin_builder'] ) ) {
                $this->classes['admin_builder'] = new um\admin\core\Admin_Builder();
            }
            return $this->classes['admin_builder'];
        }


        /**
         * @return um\admin\core\Admin_DragDrop()
         */
        function dragdrop() {
            if ( empty( $this->classes['admin_dragdrop'] ) ) {
                $this->classes['admin_dragdrop'] = new um\admin\core\Admin_DragDrop();
            }
            return $this->classes['admin_dragdrop'];
        }


        /**
         * @param $data array
         * @return um\admin\core\Admin_Forms()
         */
        function admin_forms( $data ) {
            if ( empty( $this->classes['admin_forms_' . $data['class']] ) ) {
                $this->classes['admin_forms_' . $data['class']] = new um\admin\core\Admin_Forms( $data );
            }
            return $this->classes['admin_forms_' . $data['class']];
        }



        /**
         * @return um\Dependencies
         */
        function dependencies() {
            if ( empty( $this->classes['dependencies'] ) ) {
                $this->classes['dependencies'] = new um\Dependencies();
            }

            return $this->classes['dependencies'];
        }


        /**
         * @return um\Config
         */
        function config() {
            if ( empty( $this->classes['config'] ) ) {
                $this->classes['config'] = new um\Config();
            }

            return $this->classes['config'];
        }


        /**
         * @return um\core\REST_API
         */
        function rest_api() {
            if ( empty( $this->classes['rest_api'] ) ) {
                $this->classes['rest_api'] = new um\core\REST_API();
            }

            return $this->classes['rest_api'];
        }


        /**
         * @return um\core\Rewrite
         */
        function rewrite() {
            if ( empty( $this->classes['rewrite'] ) ) {
                $this->classes['rewrite'] = new um\core\Rewrite();
            }

            return $this->classes['rewrite'];
        }


        /**
         * @return um\core\Setup
         */
        function setup() {
            if ( empty( $this->classes['setup'] ) ) {
                $this->classes['setup'] = new um\core\Setup();
            }

            return $this->classes['setup'];
        }


        /**
         * @return um\core\FontIcons
         */
        function fonticons() {
            if ( empty( $this->classes['fonticons'] ) ) {
                $this->classes['fonticons'] = new um\core\FontIcons();
            }

            return $this->classes['fonticons'];
        }


        /**
         * @return um\core\Login
         */
        function login() {
            if ( empty( $this->classes['login'] ) ) {
                $this->classes['login'] = new um\core\Login();
            }

            return $this->classes['login'];
        }


        /**
         * @return um\core\Register
         */
        function register() {
            if ( empty( $this->classes['register'] ) ) {
                $this->classes['register'] = new um\core\Register();
            }

            return $this->classes['register'];
        }


        /**
         * @return um\core\Enqueue
         */
        function enqueue() {
            if ( empty( $this->classes['enqueue'] ) ) {
                $this->classes['enqueue'] = new um\core\Enqueue();
            }

            return $this->classes['enqueue'];
        }


        /**
         * @return um\core\Shortcodes
         */
        function shortcodes() {
            if ( empty( $this->classes['shortcodes'] ) ) {
                $this->classes['shortcodes'] = new um\core\Shortcodes();
            }

            return $this->classes['shortcodes'];
        }


        /**
         * @return um\core\Account
         */
        function account() {
            if ( empty( $this->classes['account'] ) ) {
                $this->classes['account'] = new um\core\Account();
            }

            return $this->classes['account'];
        }


        /**
         * @return um\core\Password
         */
        function password() {
            if ( empty( $this->classes['password'] ) ) {
                $this->classes['password'] = new um\core\Password();
            }

            return $this->classes['password'];
        }


        /**
         * @return um\core\Form
         */
        function form() {
            if ( empty( $this->classes['form'] ) ) {
                $this->classes['form'] = new um\core\Form();
            }

            return $this->classes['form'];
        }


        /**
         * @return um\core\Fields
         */
        function fields() {
            if ( empty( $this->classes['fields'] ) ) {
                $this->classes['fields'] = new um\core\Fields();
            }

            return $this->classes['fields'];
        }


        /**
         * @return um\core\User
         */
        function user() {
            if ( empty( $this->classes['user'] ) ) {
                $this->classes['user'] = new um\core\User();
            }

            return $this->classes['user'];
        }


        /**
         * @return um\core\Roles_Capabilities
         */
        function roles() {
            if ( empty( $this->classes['roles'] ) ) {
                $this->classes['roles'] = new um\core\Roles_Capabilities();
            }

            return $this->classes['roles'];
        }


        /**
         * @return um\core\User_posts
         */
        function user_posts() {
            if ( empty( $this->classes['user_posts'] ) ) {
                $this->classes['user_posts'] = new um\core\User_posts();
            }

            return $this->classes['user_posts'];
        }


        /**
         * @return um\core\Profile
         */
        function profile() {
            if ( empty( $this->classes['profile'] ) ) {
                $this->classes['profile'] = new um\core\Profile();
            }

            return $this->classes['profile'];
        }


        /**
         * @return um\core\Query
         */
        function query() {
            if ( empty( $this->classes['query'] ) ) {
                $this->classes['query'] = new um\core\Query();
            }

            return $this->classes['query'];
        }


        /**
         * @return um\core\Date_Time
         */
        function datetime() {
            if ( empty( $this->classes['datetime'] ) ) {
                $this->classes['datetime'] = new um\core\Date_Time();
            }

            return $this->classes['datetime'];
        }


        /**
         * @return um\core\Chart
         */
        function chart() {
            if ( empty( $this->classes['chart'] ) ) {
                $this->classes['chart'] = new um\core\Chart();
            }

            return $this->classes['chart'];
        }


        /**
         * @return um\core\Builtin
         */
        function builtin() {
            if ( empty( $this->classes['builtin'] ) ) {
                $this->classes['builtin'] = new um\core\Builtin();
            }

            return $this->classes['builtin'];
        }


        /**
         * @return um\core\Files
         */
        function files() {
            if ( empty( $this->classes['files'] ) ) {
                $this->classes['files'] = new um\core\Files();
            }

            return $this->classes['files'];
        }


        /**
         * @return um\core\Validation
         */
        function validation() {
            if ( empty( $this->classes['validation'] ) ) {
                $this->classes['validation'] = new um\core\Validation();
            }

            return $this->classes['validation'];
        }


        /**
         * @return um\core\Menu
         */
        function menu() {
            if ( empty( $this->classes['menu'] ) ) {
                $this->classes['menu'] = new um\core\Menu();
            }

            return $this->classes['menu'];
        }


        /**
         * @return um\core\Access
         */
        function access() {
            if ( empty( $this->classes['access'] ) ) {
                $this->classes['access'] = new um\core\Access();
            }

            return $this->classes['access'];
        }


        /**
         * @return um\core\Permalinks
         */
        function permalinks() {
            if ( empty( $this->classes['permalinks'] ) ) {
                $this->classes['permalinks'] = new um\core\Permalinks();
            }

            return $this->classes['permalinks'];
        }


        /**
         * @return um\core\Mail
         */
        function mail() {
            if ( empty( $this->classes['mail'] ) ) {
                $this->classes['mail'] = new um\core\Mail();
            }

            return $this->classes['mail'];
        }


        /**
         * @return um\core\Members
         */
        function members() {
            if ( empty( $this->classes['members'] ) ) {
                $this->classes['members'] = new um\core\Members();
            }

            return $this->classes['members'];
        }


        /**
         * @return um\core\Logout
         */
        function logout() {
            if ( empty( $this->classes['logout'] ) ) {
                $this->classes['logout'] = new um\core\Logout();
            }

            return $this->classes['logout'];
        }


        /**
         * @return um\core\Modal
         */
        function modal() {
            if ( empty( $this->classes['modal'] ) ) {
                $this->classes['modal'] = new um\core\Modal();
            }

            return $this->classes['modal'];
        }


        /**
         * @return um\core\Cron
         */
        function cron() {
            if ( empty( $this->classes['cron'] ) ) {
                $this->classes['cron'] = new um\core\Cron();
            }

            return $this->classes['cron'];
        }


        /**
         * @return um\core\Tracking
         */
        function tracking() {
            if ( empty( $this->classes['tracking'] ) ) {
                $this->classes['tracking'] = new um\core\Tracking();
            }

            return $this->classes['tracking'];
        }


        /**
         * @return um\lib\mobiledetect\Mobile_Detect
         */
        function mobile() {
            if ( empty( $this->classes['mobile'] ) ) {
                $this->classes['mobile'] = new um\lib\mobiledetect\Mobile_Detect();
            }

            return $this->classes['mobile'];
        }


        /***
         ***	@Init
         */
        function init() {

            ob_start();

            require_once 'core/um-navmenu.php';

            require_once 'core/um-actions-form.php';
            require_once 'core/um-actions-access.php';
            require_once 'core/um-actions-wpadmin.php';
            require_once 'core/um-actions-core.php';
            require_once 'core/um-actions-ajax.php';
            require_once 'core/um-actions-login.php';
            require_once 'core/um-actions-register.php';
            require_once 'core/um-actions-profile.php';
            require_once 'core/um-actions-account.php';
            require_once 'core/um-actions-password.php';
            require_once 'core/um-actions-members.php';
            require_once 'core/um-actions-global.php';
            require_once 'core/um-actions-user.php';
            require_once 'core/um-actions-save-profile.php';
            require_once 'core/um-actions-misc.php';

            require_once 'core/um-filters-language.php';
            require_once 'core/um-filters-login.php';
            require_once 'core/um-filters-fields.php';
            require_once 'core/um-filters-files.php';
            require_once 'core/um-filters-navmenu.php';
            require_once 'core/um-filters-avatars.php';
            require_once 'core/um-filters-arguments.php';
            require_once 'core/um-filters-user.php';
            require_once 'core/um-filters-members.php';
            require_once 'core/um-filters-profile.php';
            require_once 'core/um-filters-account.php';
            require_once 'core/um-filters-misc.php';
            require_once 'core/um-filters-commenting.php';

            if ( ! get_option( 'show_avatars' ) )
                update_option( 'show_avatars', 1 );

        }

        function widgets_init() {
            register_widget( 'um\widgets\UM_Search_Widget' );
        }

    }
}


function UM() {
    return UM::instance();
}

// Global for backwards compatibility.
$GLOBALS['ultimatemember'] = UM();