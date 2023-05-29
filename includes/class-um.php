<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UM' ) ) {

	/**
	 * Main UM Class
	 *
	 * @class UM
	 * @version 3.0
	 */
	final class UM extends UM_Functions {

		/**
		 * @var UM the single instance of the class
		 */
		protected static $instance;

		/**
		 * @var array all plugin's classes
		 */
		public $classes = array();

		/**
		 * WP Native permalinks turned on?
		 *
		 * @var
		 */
		public $is_permalinks;

		/**
		 * @var
		 */
		public $is_legacy;

		/**
		 * @var string
		 */
		public $honeypot;

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
		public static function instance() {
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
		public function __construct() {
			parent::__construct();
		}

		/**
		 * UM pseudo-constructor.
		 *
		 * @since 2.0.18
		 */
		public function _um_construct() {
			$this->define_constants();

			//register autoloader for include UM classes
			spl_autoload_register( array( $this, 'um__autoloader' ) );

			if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {

				if ( get_option( 'permalink_structure' ) ) {
					$this->is_permalinks = true;
				}

				$is_legacy = get_option( 'um_is_legacy' );
				if ( $is_legacy ) {
					$this->is_legacy = ! $this->options()->get( 'enable_version_3_design' );
					if ( ! $this->is_legacy ) {
						$extension_plugins = $this->config()->get( 'extension_plugins' );

						$active_plugins = (array) get_option( 'active_plugins', array() );
						if ( is_multisite() ) {
							$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
						}

						$active_extension_plugins = array_intersect( $active_plugins, $extension_plugins );
						if ( ! empty( $active_extension_plugins ) ) {
							$this->is_legacy = true;
						}
					}
				} else {
					$this->is_legacy = false;
				}

				$this->honeypot = 'um_request';

				// run activation
				register_activation_hook( UM_PLUGIN, array( $this->install(), 'activation' ) );
				if ( is_multisite() && ! defined( 'DOING_AJAX' ) ) {
					add_action( 'wp_loaded', array( $this->install(), 'maybe_network_activation' ) );
				}

				register_deactivation_hook( UM_PLUGIN, array( &$this, 'deactivation' ) );

				// textdomain loading
				$this->localize();

				// include UM classes
				$this->includes();

				// include hook files
				add_action( 'plugins_loaded', array( &$this, 'init' ), 0 );
				//run hook for extensions init
				add_action( 'plugins_loaded', array( &$this, 'core_loaded_trigger' ), -19 );

				//include short non-class functions
				$is_legacy = get_option( 'um_is_legacy' );

				if ( ! $is_legacy || ! $this->is_legacy ) {
					require_once 'um-short-functions.php';
					require_once 'um-deprecated-functions.php';
				}/* elseif ( $is_legacy && $this->is_legacy ) {
					add_action( 'init', array( &$this, 'old_update_patch' ), 0 );

					require_once 'legacy/um-short-functions.php';
					require_once 'legacy/um-deprecated-functions.php';
				}*/
			}
		}

		/**
		 * Define Ultimate Member Constants.
		 *
		 * @since 3.0
		 */
		private function define_constants() {
			$this->define( 'UM_TEMPLATE_CONFLICT_TEST', false );
		}

		/**
		 * Getting the Install class instance
		 *
		 * @since 3.0
		 *
		 * @return um\common\Install()
		 */
		function install() {
			if ( empty( $this->classes['um\common\install'] ) ) {
				$this->classes['um\common\install'] = new um\common\Install();
			}
			return $this->classes['um\common\install'];
		}

		/**
		 * Plugin Deactivation
		 *
		 * @since 2.3
		 */
		function deactivation() {
			$this->common()->cron()->unschedule_events();
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

				$array                        = explode( '\\', strtolower( $class ) );
				$array[ count( $array ) - 1 ] = 'class-' . end( $array );

				if ( strpos( $class, 'umm' ) === 0 ) {
					// module namespace
					$module_slug = str_replace( '_', '-', $array[1] );
					$module_data = $this->modules()->get_data( $module_slug );

					if ( ! empty( $module_data['path'] ) ) {
						$full_path = $module_data['path'] . DIRECTORY_SEPARATOR;

						unset( $array[0], $array[1] );
						$path = implode( DIRECTORY_SEPARATOR, $array );
						$path = str_replace( '_', '-', $path );

						$full_path .= $path . '.php';
					}
				} elseif ( strpos( $class, 'um_ext' ) === 0 ) {
					$full_path = str_replace( 'ultimate-member', '', untrailingslashit( UM_PATH ) ) . str_replace( '_', '-', $array[1] ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
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
					$full_path =  UM_PATH . 'includes' . $path . '.php';
				}

				if ( isset( $full_path ) && file_exists( $full_path ) ) {
					include_once $full_path;
				}
			}
		}

		/**
		 *
		 */
		function core_loaded_trigger() {
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
			$is_legacy = get_option( 'um_is_legacy' );

			if ( ! $is_legacy || ! $this->is_legacy ) {
				// direct require file with core functions
				require 'um-core-functions.php';

				$this->modules();
				$this->integrations();

				$this->common()->includes();
				if ( $this->is_request( 'ajax' ) ) {
					$this->ajax()->includes();
				} elseif ( $this->is_request( 'admin' ) ) {
					$this->admin()->includes();
				} elseif ( $this->is_request( 'frontend' ) ) {
					$this->frontend()->includes();
				}

				if ( $this->is_request( 'ajax' ) ) {
					//$this->admin();
					$this->ajax_init();
					//$this->admin_ajax_hooks();
					//$this->admin_gdpr();
					//$this->columns();
					//$this->admin_navmenu();
					$this->plugin_updater();
					//$this->theme_updater();
				} elseif ( $this->is_request( 'admin' ) ) {
					//$this->admin();
					//$this->dragdrop();
					//$this->admin_gdpr();
					//$this->admin_navmenu();
					$this->plugin_updater();
					//$this->theme_updater();

					$this->account(); // for adding_shortcode
					//$this->password(); // for adding_shortcode
				} elseif ( $this->is_request( 'frontend' ) ) {
					$this->account();
					//$this->password();
					$this->login();
					$this->register();
					$this->user_posts();
					$this->logout();
				}

//				common includes
				$this->roles();
				$this->user();
				$this->profile();
				$this->builtin();
				$this->form();
				$this->permalinks();
				$this->modal();
				$this->mobile();
				$this->external_integrations();
				$this->gdpr();

//				if multisite networks active
				if ( is_multisite() ) {
					$this->multisite();
				}
			}/* elseif ( $is_legacy && $this->is_legacy ) {
				// legacy part
				$this->common();
				$this->access();

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
			}*/
		}

		/**
		 * Getting the Common class instance
		 *
		 * @since 2.0 but changed structure in 3.0
		 *
		 * @return um\common\Init()
		 */
		function common() {
			// legacy part
//			$is_legacy = get_option( 'um_is_legacy' );
//			if ( $is_legacy ) {
//				if ( UM()->is_legacy ) {
//					if ( empty( $this->classes['um\legacy\common'] ) ) {
//						$this->classes['um\legacy\common'] = new um\legacy\core\Common();
//					}
//					return $this->classes['um\legacy\common'];
//				}
//			}

			if ( empty( $this->classes['um\common\init'] ) ) {
				$this->classes['um\common\init'] = new um\common\Init();
			}
			return $this->classes['um\common\init'];
		}


		/**
		 * Getting the AJAX class instance
		 *
		 * @since 3.0
		 *
		 * @return um\ajax\Init
		 */
		function ajax() {
			if ( empty( $this->classes['um\ajax\init'] ) ) {
				$this->classes['um\ajax\init'] = new um\ajax\Init();
			}
			return $this->classes['um\ajax\init'];
		}


		/**
		 * Getting the Frontend class instance
		 *
		 * @since 3.0
		 *
		 * @return um\frontend\Init()
		 */
		function frontend() {
			if ( empty( $this->classes['um\frontend\init'] ) ) {
				$this->classes['um\frontend\init'] = new um\frontend\Init();
			}
			return $this->classes['um\frontend\init'];
		}


		/**
		 * Getting the Admin class instance
		 *
		 * @since 2.0 but changed structure in 3.0
		 *
		 * @return um\admin\Init()
		 */
		function admin() {
			// legacy part
//			$is_legacy = get_option( 'um_is_legacy' );
//			if ( $is_legacy ) {
//				if ( UM()->is_legacy ) {
//					if ( empty( $this->classes['um\legacy\admin'] ) ) {
//						$this->classes['um\legacy\admin'] = new um\legacy\admin\Admin();
//					}
//					return $this->classes['um\legacy\admin'];
//				}
//			}

			if ( empty( $this->classes['um\admin\init'] ) ) {
				$this->classes['um\admin\init'] = new um\admin\Init();
			}
			return $this->classes['um\admin\init'];
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
		 * @since 3.0
		 *
		 * @return um\integrations\Common()
		 */
		function integrations() {
			if ( empty( $this->classes['integrations\common'] ) ) {
				$this->classes['integrations\common'] = new um\integrations\Common();
			}
			return $this->classes['integrations\common'];
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
		 * @return um\common\Options()|um\legacy\core\Options
		 */
		public function options() {
			// legacy part
//			$is_legacy = get_option( 'um_is_legacy' );
//			if ( $is_legacy ) {
//				if ( UM()->is_legacy ) {
//					if ( empty( $this->classes['um\legacy\options'] ) ) {
//						$this->classes['um\legacy\options'] = new um\legacy\core\Options();
//					}
//					return $this->classes['um\legacy\options'];
//				}
//			}

			return $this->common()->options();
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
		 * @since 2.0
		 */
		function ajax_init() {
			new um\core\AJAX_Common();
		}

		/**
		 * GDPR privacy policy
		 *
		 * @since 2.0.14
		 *
		 * @return bool|um\core\GDPR()
		 */
		function gdpr() {
			if ( empty( $this->classes['gdpr'] ) ) {
				$this->classes['gdpr'] = new um\core\GDPR();
			}
			return $this->classes['gdpr'];
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
			if ( empty( $this->classes['um\core\query'] ) ) {
				$this->classes['um\core\query'] = new um\core\Query();
			}

			return $this->classes['um\core\query'];
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
		public function files() {
			if ( empty( $this->classes['um\core\files'] ) ) {
				$this->classes['um\core\files'] = new um\core\Files();
			}

			return $this->classes['um\core\files'];
		}


		/**
		 * @since 2.0.21
		 *
		 * @return um\core\Uploader
		 */
		function uploader() {
			if ( empty( $this->classes['um\core\uploader'] ) ) {
				$this->classes['um\core\uploader'] = new um\core\Uploader();
			}
			return $this->classes['um\core\uploader'];
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
			$is_legacy = get_option( 'um_is_legacy' );
			if ( ! $is_legacy || ! $this->is_legacy ) {
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
		}

		/**
		 * @since 3.0
		 *
		 * @return um\Modules
		 */
		function modules() {
			if ( empty( $this->classes['um\modules'] ) ) {
				$this->classes['um\modules'] = new um\Modules();
			}

			return $this->classes['um\modules'];
		}

		/**
		 * Get single module API
		 *
		 * @since 3.0
		 *
		 * @param $slug
		 *
		 * @return bool|mixed
		 */
		public function module( $slug ) {
			$data = $this->modules()->get_data( $slug );
			if ( ! empty( $data['path'] ) ) {
				$slug = $this->undash( $slug );

				$class = "umm\\{$slug}\\Init";

				if ( empty( $this->classes[ strtolower( $class ) ] ) ) {
					$this->classes[ strtolower( $class ) ] = $class::instance();
				}

				return $this->classes[ strtolower( $class ) ];
			} else {
				return false;
			}
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

UM();
