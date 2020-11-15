<?php if ( ! defined( 'ABSPATH' ) ) exit;


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
	 * @method UM_Mailchimp Mailchimp()
	 * @method UM_Messaging_API Messaging_API()
	 * @method UM_myCRED myCRED()
	 * @method UM_Notices Notices()
	 * @method UM_Notifications_API Notifications_API()
	 * @method UM_Online Online()
	 * @method UM_Profile_Completeness_API Profile_Completeness_API()
	 * @method UM_reCAPTCHA reCAPTCHA()
	 * @method UM_Reviews Reviews()
	 * @method UM_Activity_API Activity_API()
	 * @method UM_Social_Login_API Social_Login_API()
	 * @method UM_User_Tags User_Tags()
	 * @method UM_Verified_Users_API Verified_Users_API()
	 * @method UM_WooCommerce_API WooCommerce_API()
	 * @method UM_Terms_Conditions Terms_Conditions()
	 * @method UM_Private_Content Private_Content()
	 * @method UM_User_Locations User_Locations()
	 * @method UM_Photos_API Photos_API()
	 * @method UM_Groups Groups()
	 * @method UM_Frontend_Posting Frontend_Posting()
	 * @method UM_Notes Notes()
	 * @method UM_User_Bookmarks User_Bookmarks()
	 * @method UM_Unsplash Unsplash()
	 * @method UM_ForumWP ForumWP()
	 * @method UM_Profile_Tabs Profile_Tabs()
	 * @method UM_JobBoardWP JobBoardWP()
	 * @method UM_Google_Authenticator Google_Authenticator()
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
		 *
		 * @todo deprecate this variable
		 */
		public $is_filtering;


		/**
		 * WP Native permalinks turned on?
		 *
		 * @var
		 */
		public $is_permalinks;


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
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
				self::$instance->_um_construct();
			}

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

			if ( empty( $this->classes[ $name ] ) ) {

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_call_object_{$class_name}
				 * @description Extend call classes of Extensions for use UM()->class_name()->method|function
				 * @input_vars
				 * [{"var":"$class","type":"object","desc":"Class Instance"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_call_object_{$class_name}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_call_object_{$class_name}', 'my_extension_class', 10, 1 );
				 * function my_extension_class( $class ) {
				 *     // your code here
				 *     return $class;
				 * }
				 * ?>
				 */
				$this->classes[ $name ] = apply_filters( 'um_call_object_' . $name, false );
			}

			return $this->classes[ $name ];

		}


		/**
		 * Function for add classes to $this->classes
		 * for run using UM()
		 *
		 * @since 2.0
		 *
		 * @param string $class_name
		 * @param bool $instance
		 */
		public function set_class( $class_name, $instance = false ) {
			if ( empty( $this->classes[ $class_name ] ) ) {
				$class = 'UM_' . $class_name;
				$this->classes[ $class_name ] = $instance ? $class::instance() : new $class;
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
		 *
		 * @since 1.0
		 */
		function __construct() {
			parent::__construct();
		}


		/**
		 * UM pseudo-constructor.
		 *
		 * @since 2.0.18
		 */
		function _um_construct() {
			//register autoloader for include UM classes
			spl_autoload_register( array( $this, 'um__autoloader' ) );

			if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {

				if ( get_option( 'permalink_structure' ) ) {
					$this->is_permalinks = true;
				}

				$this->is_filtering = 0;
				$this->honeypot = 'um_request';

				// textdomain loading
				$this->localize();

				// include UM classes
				$this->includes();

				// include hook files
				add_action( 'plugins_loaded', array( &$this, 'init' ), 0 );
				//run hook for extensions init
				add_action( 'plugins_loaded', array( &$this, 'extensions_init' ), -19 );

				add_action( 'init', array( &$this, 'old_update_patch' ), 0 );

				//run activation
				register_activation_hook( um_plugin, array( &$this, 'activation' ) );

				if ( is_multisite() && ! defined( 'DOING_AJAX' ) ) {
					add_action( 'wp_loaded', array( $this, 'maybe_network_activation' ) );
				}

				// init widgets
				add_action( 'widgets_init', array( &$this, 'widgets_init' ) );

				//include short non class functions
				require_once 'um-short-functions.php';
				require_once 'um-deprecated-functions.php';
			}
		}


		/**
		 * Loading UM textdomain
		 *
		 * 'ultimate-member' by default
		 */
		function localize() {
			$language_locale = ( get_locale() != '' ) ? get_locale() : 'en_US';

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_language_locale
			 * @description Change UM language locale
			 * @input_vars
			 * [{"var":"$locale","type":"string","desc":"UM language locale"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_language_locale', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_language_locale', 'my_language_locale', 10, 1 );
			 * function my_language_locale( $locale ) {
			 *     // your code here
			 *     return $locale;
			 * }
			 * ?>
			 */
			$language_locale = apply_filters( 'um_language_locale', $language_locale );


			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_language_textdomain
			 * @description Change UM textdomain
			 * @input_vars
			 * [{"var":"$domain","type":"string","desc":"UM Textdomain"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_language_textdomain', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_language_textdomain', 'my_textdomain', 10, 1 );
			 * function my_textdomain( $domain ) {
			 *     // your code here
			 *     return $domain;
			 * }
			 * ?>
			 */
			$language_domain = apply_filters( 'um_language_textdomain', 'ultimate-member' );

			$language_file = WP_LANG_DIR . '/plugins/' . $language_domain . '-' . $language_locale . '.mo';

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_language_file
			 * @description Change UM language file path
			 * @input_vars
			 * [{"var":"$language_file","type":"string","desc":"UM language file path"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_language_file', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_language_file', 'my_language_file', 10, 1 );
			 * function my_language_file( $language_file ) {
			 *     // your code here
			 *     return $language_file;
			 * }
			 * ?>
			 */
			$language_file = apply_filters( 'um_language_file', $language_file );

			load_textdomain( $language_domain, $language_file );
		}


		/**
		 * 1.3.x active extensions deactivate for properly running 2.0.x AJAX upgrades
		 */
		function old_update_patch() {
			global $um_woocommerce, $um_bbpress, $um_followers, $um_friends, $um_mailchimp, $um_messaging, $um_mycred, $um_notices, $um_notifications, $um_online, $um_private_content, $um_profile_completeness, $um_recaptcha, $um_reviews, $um_activity, $um_social_login, $um_user_tags, $um_verified;

			if ( is_object( $um_woocommerce ) ) {
				remove_action( 'init', array( $um_woocommerce, 'plugin_check' ), 1 );
				$um_woocommerce->plugin_inactive = true;
			}

			if ( is_object( $um_bbpress ) ) {
				remove_action( 'init', array( $um_bbpress, 'plugin_check' ), 4 );
				$um_bbpress->plugin_inactive = true;
			}

			if ( is_object( $um_followers ) ) {
				remove_action( 'init', array( $um_followers, 'plugin_check' ), 1 );
				$um_followers->plugin_inactive = true;
			}

			if ( is_object( $um_friends ) ) {
				remove_action( 'init', array( $um_friends, 'plugin_check' ), 1 );
				$um_friends->plugin_inactive = true;
			}

			if ( is_object( $um_mailchimp ) ) {
				remove_action( 'init', array( $um_mailchimp, 'plugin_check' ), 1 );
				$um_mailchimp->plugin_inactive = true;
			}

			if ( is_object( $um_messaging ) ) {
				remove_action( 'init', array( $um_messaging, 'plugin_check' ), 1 );
				$um_messaging->plugin_inactive = true;
			}

			if ( is_object( $um_mycred ) ) {
				remove_action( 'init', array( $um_mycred, 'plugin_check' ), 1 );
				$um_mycred->plugin_inactive = true;
			}

			if ( is_object( $um_notices ) ) {
				remove_action( 'init', array( $um_notices, 'plugin_check' ), 1 );
				$um_notices->plugin_inactive = true;
			}

			if ( is_object( $um_notifications ) ) {
				remove_action( 'init', array( $um_notifications, 'plugin_check' ), 1 );
				$um_notifications->plugin_inactive = true;
			}

			if ( is_object( $um_online ) ) {
				remove_action( 'init', array( $um_online, 'plugin_check' ), 1 );
				$um_online->plugin_inactive = true;
			}

			if ( is_object( $um_private_content ) ) {
				remove_action( 'init', array( $um_private_content, 'plugin_check' ), 1 );
				$um_private_content->plugin_inactive = true;
			}

			if ( is_object( $um_profile_completeness ) ) {
				remove_action( 'init', array( $um_profile_completeness, 'plugin_check' ), 1 );
				$um_profile_completeness->plugin_inactive = true;
			}

			if ( is_object( $um_recaptcha ) ) {
				remove_action( 'init', array( $um_recaptcha, 'plugin_check' ), 1 );
				$um_recaptcha->plugin_inactive = true;
			}

			if ( is_object( $um_reviews ) ) {
				remove_action( 'init', array( $um_reviews, 'plugin_check' ), 1 );
				$um_reviews->plugin_inactive = true;
			}

			if ( is_object( $um_activity ) ) {
				remove_action( 'init', array( $um_activity, 'plugin_check' ), 1 );
				$um_activity->plugin_inactive = true;
			}

			if ( is_object( $um_social_login ) ) {
				remove_action( 'init', array( $um_social_login, 'plugin_check' ), 1 );
				$um_social_login->plugin_inactive = true;
			}

			if ( is_object( $um_user_tags ) ) {
				remove_action( 'init', array( $um_user_tags, 'plugin_check' ), 1 );
				$um_user_tags->plugin_inactive = true;
			}

			if ( is_object( $um_verified ) ) {
				remove_action( 'init', array( $um_verified, 'plugin_check' ), 1 );
				$um_verified->plugin_inactive = true;
			}
		}


		/**
		 * Autoload UM classes handler
		 *
		 * @since 2.0
		 *
		 * @param $class
		 */
		function um__autoloader( $class ) {
			if ( strpos( $class, 'um' ) === 0 ) {

				$array = explode( '\\', strtolower( $class ) );
				$array[ count( $array ) - 1 ] = 'class-'. end( $array );
				if ( strpos( $class, 'um_ext' ) === 0 ) {
					$full_path = str_replace( 'ultimate-member', '', untrailingslashit( um_path ) ) . str_replace( '_', '-', $array[1] ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
					unset( $array[0], $array[1] );
					$path = implode( DIRECTORY_SEPARATOR, $array );
					$path = str_replace( '_', '-', $path );
					$full_path .= $path . '.php';
				} else if ( strpos( $class, 'um\\' ) === 0 ) {
					$class = implode( '\\', $array );
					$slash = DIRECTORY_SEPARATOR;
					$path = str_replace(
						array( 'um\\', '_', '\\' ),
						array( $slash, '-', $slash ),
						$class );
					$full_path =  um_path . 'includes' . $path . '.php';
				}

				if( isset( $full_path ) && file_exists( $full_path ) ) {
					include_once $full_path;
				}
			}
		}


		/**
		 * Plugin Activation
		 *
		 * @since 2.0
		 */
		function activation() {
			$this->single_site_activation();
			if ( is_multisite() ) {
				update_network_option( get_current_network_id(), 'um_maybe_network_wide_activation', 1 );
			}
		}


		/**
		 * Maybe need multisite activation process
		 *
		 * @since 2.1.7
		 */
		function maybe_network_activation() {
			$maybe_activation = get_network_option( get_current_network_id(), 'um_maybe_network_wide_activation' );

			if ( $maybe_activation ) {

				delete_network_option( get_current_network_id(), 'um_maybe_network_wide_activation' );

				if ( is_plugin_active_for_network( um_plugin ) ) {
					// get all blogs
					$blogs = get_sites();
					if ( ! empty( $blogs ) ) {
						foreach( $blogs as $blog ) {
							switch_to_blog( $blog->blog_id );
							//make activation script for each sites blog
							$this->single_site_activation();
							restore_current_blog();
						}
					}
				}
			}
		}


		/**
		 * Single site plugin activation handler
		 */
		function single_site_activation() {
			//first install
			$version = get_option( 'um_version' );
			if ( ! $version ) {
				update_option( 'um_last_version_upgrade', ultimatemember_version );

				add_option( 'um_first_activation_date', time() );

				//show avatars on first install
				if ( ! get_option( 'show_avatars' ) ) {
					update_option( 'show_avatars', 1 );
				}
			} else {
				UM()->options()->update( 'rest_api_version', '1.0' );
			}

			if ( $version != ultimatemember_version ) {
				update_option( 'um_version', ultimatemember_version );
			}

			//run setup
			$this->common()->create_post_types();
			$this->setup()->run_setup();
		}


		/**
		 *
		 */
		function extensions_init() {
			do_action( 'um_core_loaded' );
		}


		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 2.0
		 *
		 * @return void
		 */
		public function includes() {

			$this->common();

			if ( $this->is_request( 'ajax' ) ) {
				$this->admin();
				$this->ajax_init();
				$this->admin_ajax_hooks();
				$this->metabox();
				$this->admin_upgrade()->init_packages_ajax_handlers();
				$this->admin_gdpr();
				$this->columns();
				$this->admin()->notices();
				$this->admin_navmenu();
				$this->access();
				$this->plugin_updater();
				$this->theme_updater();
			} elseif ( $this->is_request( 'admin' ) ) {
				$this->admin();
				$this->admin_menu();
				$this->admin_upgrade();
				$this->admin_settings();
				$this->columns();
				$this->admin_enqueue();
				$this->metabox();
				$this->admin()->notices();
				$this->users();
				$this->dragdrop();
				$this->admin_gdpr();
				$this->admin_navmenu();
				$this->plugin_updater();
				$this->theme_updater();
			} elseif ( $this->is_request( 'frontend' ) ) {
				$this->enqueue();
				$this->account();
				$this->password();
				$this->login();
				$this->register();
				$this->user_posts();
				$this->access();
				$this->logout();
			}

			//common includes
			$this->rewrite();
			$this->mail();
			$this->rest_api();
			$this->shortcodes();
			$this->roles();
			$this->user();
			$this->profile();
			$this->builtin();
			$this->form();
			$this->permalinks();
			$this->modal();
			$this->cron();
			$this->mobile();
			$this->external_integrations();
			$this->gdpr();
			$this->member_directory();

			//if multisite networks active
			if ( is_multisite() ) {
				$this->multisite();
			}

		}


		/**
		 * @since 2.1.0
		 *
		 * @return um\core\Member_Directory()
		 */
		function member_directory() {
			if ( empty( $this->classes['member_directory'] ) ) {

				$search_in_table = $this->options()->get( 'member_directory_own_table' );

				if ( ! empty( $search_in_table ) ) {
					$this->classes['member_directory'] = new um\core\Member_Directory_Meta();
				} else {
					$this->classes['member_directory'] = new um\core\Member_Directory();
				}
			}
			return $this->classes['member_directory'];
		}


		/**
		 * Get extension API
		 *
		 * @since 2.0.34
		 *
		 * @param $slug
		 *
		 * @return um_ext\um_bbpress\Init
		 */
		function extension( $slug ) {
			if ( empty( $this->classes[ $slug ] ) ) {
				$class = "um_ext\um_{$slug}\Init";

				/**
				 * @var $class um_ext\um_bbpress\Init
				 */
				$this->classes[ $slug ] = $class::instance();
			}

			return $this->classes[ $slug ];
		}


		/**
		 * @param $class
		 *
		 * @return mixed
		 */
		function call_class( $class ) {
			$key = strtolower( $class );

			if ( empty( $this->classes[ $key ] ) ) {
				$this->classes[ $key ] = new $class;
			}

			return $this->classes[ $key ];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Common()
		 */
		function common() {
			if ( empty( $this->classes['common'] ) ) {
				$this->classes['common'] = new um\core\Common();
			}
			return $this->classes['common'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\External_Integrations()
		 */
		function external_integrations() {
			if ( empty( $this->classes['external_integrations'] ) ) {
				$this->classes['external_integrations'] = new um\core\External_Integrations();
			}
			return $this->classes['external_integrations'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Options()
		 */
		function options() {
			if ( empty( $this->classes['options'] ) ) {
				$this->classes['options'] = new um\core\Options();
			}
			return $this->classes['options'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Plugin_Updater()
		 */
		function plugin_updater() {
			if ( empty( $this->classes['plugin_updater'] ) ) {
				$this->classes['plugin_updater'] = new um\core\Plugin_Updater();
			}
			return $this->classes['plugin_updater'];
		}


		/**
		 * @since 2.0.45
		 * @return um\admin\core\Admin_Theme_Updater()
		 */
		function theme_updater() {
			if ( empty( $this->classes['theme_updater'] ) ) {
				$this->classes['theme_updater'] = new um\admin\core\Admin_Theme_Updater();
			}
			return $this->classes['theme_updater'];
		}


		/**
		 * @since 2.0
		 */
		function ajax_init() {
			new um\core\AJAX_Common();
		}


		/**
		 * @since 2.0.30
		 */
		function admin_ajax_hooks() {
			if ( empty( $this->classes['admin_ajax_hooks'] ) ) {
				$this->classes['admin_ajax_hooks'] = new um\admin\core\Admin_Ajax_Hooks();
			}
			return $this->classes['admin_ajax_hooks'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\admin\Admin()
		 */
		function admin() {
			if ( empty( $this->classes['admin'] ) ) {
				$this->classes['admin'] = new um\admin\Admin();
			}
			return $this->classes['admin'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\admin\core\Admin_Menu()
		 */
		function admin_menu() {
			if ( empty( $this->classes['admin_menu'] ) ) {
				$this->classes['admin_menu'] = new um\admin\core\Admin_Menu();
			}
			return $this->classes['admin_menu'];
		}


		/**
		 * @since 2.0.26
		 *
		 * @return um\admin\core\Admin_Navmenu()
		 */
		function admin_navmenu() {
			if ( empty( $this->classes['admin_navmenu'] ) ) {
				$this->classes['admin_navmenu'] = new um\admin\core\Admin_Navmenu();
			}
			return $this->classes['admin_navmenu'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\admin\core\Admin_Settings()
		 */
		function admin_settings() {
			if ( empty( $this->classes['admin_settings'] ) ) {
				$this->classes['admin_settings'] = new um\admin\core\Admin_Settings();
			}
			return $this->classes['admin_settings'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\admin\core\Admin_Upgrade()
		 */
		function admin_upgrade() {
			if ( empty( $this->classes['admin_upgrade'] ) ) {
				$this->classes['admin_upgrade'] = um\admin\core\Admin_Upgrade::instance();
				//$this->classes['admin_upgrade'] = new um\admin\core\Admin_Upgrade();
			}
			return $this->classes['admin_upgrade'];
		}


		/**
		 * GDPR privacy policy
		 *
		 * @since 2.0.14
		 *
		 * @return bool|um\admin\core\Admin_GDPR()
		 */
		function admin_gdpr() {
			global $wp_version;

			if ( version_compare( $wp_version, '4.9.6', '<' ) ) {
				return false;
			}

			if ( empty( $this->classes['admin_gdpr'] ) ) {
				$this->classes['admin_gdpr'] = new um\admin\core\Admin_GDPR();
			}
			return $this->classes['admin_gdpr'];
		}


		/**
		 * GDPR privacy policy
		 *
		 * @since 2.0.14
		 *
		 * @return bool|um\core\GDPR()
		 */
		function gdpr() {
			global $wp_version;

			if ( version_compare( $wp_version, '4.9.6', '<' ) ) {
				return false;
			}

			if ( empty( $this->classes['gdpr'] ) ) {
				$this->classes['gdpr'] = new um\core\GDPR();
			}
			return $this->classes['gdpr'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\admin\core\Admin_Columns()
		 */
		function columns() {
			if ( empty( $this->classes['admin_columns'] ) ) {
				$this->classes['admin_columns'] = new um\admin\core\Admin_Columns();
			}
			return $this->classes['admin_columns'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\admin\core\Admin_Enqueue()
		 */
		function admin_enqueue() {
			if ( empty( $this->classes['admin_enqueue'] ) ) {
				$this->classes['admin_enqueue'] = new um\admin\core\Admin_Enqueue();
			}
			return $this->classes['admin_enqueue'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\admin\core\Admin_Metabox()
		 */
		function metabox() {
			if ( empty( $this->classes['admin_metabox'] ) ) {
				$this->classes['admin_metabox'] = new um\admin\core\Admin_Metabox();
			}
			return $this->classes['admin_metabox'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\admin\core\Admin_Users()
		 */
		function users() {
			if ( empty( $this->classes['admin_users'] ) ) {
				$this->classes['admin_users'] = new um\admin\core\Admin_Users();
			}
			return $this->classes['admin_users'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\admin\core\Admin_Builder()
		 */
		function builder() {
			if ( empty( $this->classes['admin_builder'] ) ) {
				$this->classes['admin_builder'] = new um\admin\core\Admin_Builder();
			}
			return $this->classes['admin_builder'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\admin\core\Admin_DragDrop()
		 */
		function dragdrop() {
			if ( empty( $this->classes['admin_dragdrop'] ) ) {
				$this->classes['admin_dragdrop'] = new um\admin\core\Admin_DragDrop();
			}
			return $this->classes['admin_dragdrop'];
		}


		/**
		 * @since 2.0
		 *
		 * @param bool|array $data
		 * @return um\admin\core\Admin_Forms()
		 */
		function admin_forms( $data = false ) {
			if ( ! isset( $this->classes[ 'admin_forms_' . $data['class'] ] ) || empty( $this->classes[ 'admin_forms_' . $data['class'] ] ) ) {
				$this->classes[ 'admin_forms_' . $data['class'] ] = new um\admin\core\Admin_Forms( $data );
			}
			return $this->classes[ 'admin_forms_' . $data['class'] ];
		}


		/**
		 * @since 2.0
		 *
		 * @param bool|array $data
		 * @return um\admin\core\Admin_Forms_Settings()
		 */
		function admin_forms_settings( $data = false ) {
			if ( ! isset( $this->classes[ 'admin_forms_settings_' . $data['class'] ] ) || empty( $this->classes[ 'admin_forms_settings_' . $data['class'] ] ) ) {
				$this->classes[ 'admin_forms_settings_' . $data['class'] ] = new um\admin\core\Admin_Forms_Settings( $data );
			}
			return $this->classes[ 'admin_forms_settings_' . $data['class'] ];
		}


		/**
		 * @since 2.0.34
		 *
		 * @return um\Extensions
		 */
		function extensions() {
			if ( empty( $this->classes['extensions'] ) ) {
				$this->classes['extensions'] = new um\Extensions();
			}

			return $this->classes['extensions'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\Dependencies
		 */
		function dependencies() {
			if ( empty( $this->classes['dependencies'] ) ) {
				$this->classes['dependencies'] = new um\Dependencies();
			}

			return $this->classes['dependencies'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\Config
		 */
		function config() {
			if ( empty( $this->classes['config'] ) ) {
				$this->classes['config'] = new um\Config();
			}

			return $this->classes['config'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\rest\API_v1|um\core\rest\API_v2
		 */
		function rest_api() {

			$api_version = $this->options()->get( 'rest_api_version' );

			if ( empty( $this->classes['rest_api'] ) ) {
				if ( '1.0' === $api_version ) {
					$this->classes['rest_api'] = new um\core\rest\API_v1();
				} elseif ( '2.0' === $api_version ) {
					$this->classes['rest_api'] = new um\core\rest\API_v2();
				} else {
					$this->classes['rest_api'] = new um\core\rest\API_v1();
				}
			}

			return $this->classes['rest_api'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Rewrite
		 */
		function rewrite() {
			if ( empty( $this->classes['rewrite'] ) ) {
				$this->classes['rewrite'] = new um\core\Rewrite();
			}

			return $this->classes['rewrite'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Setup
		 */
		function setup() {
			if ( empty( $this->classes['setup'] ) ) {
				$this->classes['setup'] = new um\core\Setup();
			}

			return $this->classes['setup'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\FontIcons
		 */
		function fonticons() {
			if ( empty( $this->classes['fonticons'] ) ) {
				$this->classes['fonticons'] = new um\core\FontIcons();
			}

			return $this->classes['fonticons'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Login
		 */
		function login() {
			if ( empty( $this->classes['login'] ) ) {
				$this->classes['login'] = new um\core\Login();
			}

			return $this->classes['login'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Register
		 */
		function register() {
			if ( empty( $this->classes['register'] ) ) {
				$this->classes['register'] = new um\core\Register();
			}

			return $this->classes['register'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Enqueue
		 */
		function enqueue() {
			if ( empty( $this->classes['enqueue'] ) ) {
				$this->classes['enqueue'] = new um\core\Enqueue();
			}

			return $this->classes['enqueue'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Shortcodes
		 */
		function shortcodes() {
			if ( empty( $this->classes['shortcodes'] ) ) {
				$this->classes['shortcodes'] = new um\core\Shortcodes();
			}

			return $this->classes['shortcodes'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Account
		 */
		function account() {
			if ( empty( $this->classes['account'] ) ) {
				$this->classes['account'] = new um\core\Account();
			}

			return $this->classes['account'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Password
		 */
		function password() {
			if ( empty( $this->classes['password'] ) ) {
				$this->classes['password'] = new um\core\Password();
			}

			return $this->classes['password'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Form
		 */
		function form() {
			if ( empty( $this->classes['form'] ) ) {
				$this->classes['form'] = new um\core\Form();
			}

			return $this->classes['form'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Fields
		 */
		function fields() {
			if ( empty( $this->classes['fields'] ) ) {
				$this->classes['fields'] = new um\core\Fields();
			}

			return $this->classes['fields'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\User
		 */
		function user() {
			if ( empty( $this->classes['user'] ) ) {
				$this->classes['user'] = new um\core\User();
			}

			return $this->classes['user'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Roles_Capabilities
		 */
		function roles() {
			if ( empty( $this->classes['roles'] ) ) {
				$this->classes['roles'] = new um\core\Roles_Capabilities();
			}

			return $this->classes['roles'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\User_posts
		 */
		function user_posts() {
			if ( empty( $this->classes['user_posts'] ) ) {
				$this->classes['user_posts'] = new um\core\User_posts();
			}

			return $this->classes['user_posts'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Profile
		 */
		function profile() {
			if ( empty( $this->classes['profile'] ) ) {
				$this->classes['profile'] = new um\core\Profile();
			}

			return $this->classes['profile'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Query
		 */
		function query() {
			if ( empty( $this->classes['query'] ) ) {
				$this->classes['query'] = new um\core\Query();
			}

			return $this->classes['query'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Date_Time
		 */
		function datetime() {
			if ( empty( $this->classes['datetime'] ) ) {
				$this->classes['datetime'] = new um\core\Date_Time();
			}

			return $this->classes['datetime'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Builtin
		 */
		function builtin() {
			if ( empty( $this->classes['builtin'] ) ) {
				$this->classes['builtin'] = new um\core\Builtin();
			}

			return $this->classes['builtin'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Files
		 */
		function files() {
			if ( empty( $this->classes['files'] ) ) {
				$this->classes['files'] = new um\core\Files();
			}

			return $this->classes['files'];
		}

		
		/**
		 * @since 2.0.21
		 *
		 * @return um\core\Uploader
		 */
		function uploader() {
			if ( empty( $this->classes['uploader'] ) ) {
				$this->classes['uploader'] = new um\core\Uploader();
			}
			return $this->classes['uploader'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Validation
		 */
		function validation() {
			if ( empty( $this->classes['validation'] ) ) {
				$this->classes['validation'] = new um\core\Validation();
			}

			return $this->classes['validation'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Access
		 */
		function access() {
			if ( empty( $this->classes['access'] ) ) {
				$this->classes['access'] = new um\core\Access();
			}

			return $this->classes['access'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Permalinks
		 */
		function permalinks() {
			if ( empty( $this->classes['permalinks'] ) ) {
				$this->classes['permalinks'] = new um\core\Permalinks();
			}

			return $this->classes['permalinks'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Mail
		 */
		function mail() {
			if ( empty( $this->classes['mail'] ) ) {
				$this->classes['mail'] = new um\core\Mail();
			}

			return $this->classes['mail'];
		}


		/**
		 * @deprecated 2.1.0
		 *
		 * @since 2.0
		 *
		 * @return um\core\Members
		 */
		function members() {
			um_deprecated_function( 'UM()->members()', '2.1.0', 'UM()->member_directory()' );

			if ( empty( $this->classes['members'] ) ) {
				$this->classes['members'] = new um\core\Members();
			}

			return $this->classes['members'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Logout
		 */
		function logout() {
			if ( empty( $this->classes['logout'] ) ) {
				$this->classes['logout'] = new um\core\Logout();
			}

			return $this->classes['logout'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Modal
		 */
		function modal() {
			if ( empty( $this->classes['modal'] ) ) {
				$this->classes['modal'] = new um\core\Modal();
			}

			return $this->classes['modal'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Cron
		 */
		function cron() {
			if ( empty( $this->classes['cron'] ) ) {
				$this->classes['cron'] = new um\core\Cron();
			}

			return $this->classes['cron'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\core\Templates
		 */
		function templates() {
			if ( empty( $this->classes['templates'] ) ) {
				$this->classes['templates'] = new um\core\Templates();
			}

			return $this->classes['templates'];
		}


		/**
		 * @since 2.0
		 *
		 * @return um\lib\mobiledetect\Um_Mobile_Detect
		 */
		function mobile() {
			if ( empty( $this->classes['mobile'] ) ) {
				$this->classes['mobile'] = new um\lib\mobiledetect\Um_Mobile_Detect();
			}

			return $this->classes['mobile'];
		}


		/**
		 * @since 2.0.44
		 *
		 * @return um\core\Multisite
		 */
		function multisite() {

			if ( empty( $this->classes['multisite'] ) ) {
				$this->classes['multisite'] = new um\core\Multisite();
			}

			return $this->classes['multisite'];
		}


		/**
		 * Include files with hooked filters/actions
		 *
		 * @since 2.0
		 */
		function init() {

			ob_start();

			require_once 'core/um-actions-form.php';
			require_once 'core/um-actions-access.php';
			require_once 'core/um-actions-wpadmin.php';
			require_once 'core/um-actions-core.php';
			require_once 'core/um-actions-ajax.php';
			require_once 'core/um-actions-login.php';
			require_once 'core/um-actions-register.php';
			require_once 'core/um-actions-profile.php';
			require_once 'core/um-actions-account.php';
			require_once 'core/um-actions-global.php';
			require_once 'core/um-actions-user.php';
			require_once 'core/um-actions-save-profile.php';
			require_once 'core/um-actions-misc.php';

			require_once 'core/um-filters-login.php';
			require_once 'core/um-filters-fields.php';
			require_once 'core/um-filters-files.php';
			require_once 'core/um-filters-navmenu.php';
			require_once 'core/um-filters-avatars.php';
			require_once 'core/um-filters-user.php';

			require_once 'core/um-filters-profile.php';
			require_once 'core/um-filters-account.php';
			require_once 'core/um-filters-misc.php';
			require_once 'core/um-filters-commenting.php';

		}


		/**
		 * Init UM widgets
		 *
		 * @since 2.0
		 */
		function widgets_init() {
			register_widget( 'um\widgets\UM_Search_Widget' );
		}
	}
}


/**
 * Function for calling UM methods and variables
 *
 * @since 2.0
 *
 * @return UM
 */
function UM() {
	return UM::instance();
}


// Global for backwards compatibility.
$GLOBALS['ultimatemember'] = UM();