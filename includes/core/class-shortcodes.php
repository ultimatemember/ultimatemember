<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Shortcodes' ) ) {

	/**
	 * Class Shortcodes
	 * @package um\core
	 */
	class Shortcodes {

		/**
		 * @var array
		 */
		public $forms_exist = array();

		/**
		 * @var string
		 */
		public $profile_role = '';

		/**
		 * @var bool
		 */
		public $message_mode = false;

		/**
		 * @var string
		 */
		public $custom_message = '';

		/**
		 * @var array
		 */
		public $loop = array();

		/**
		 * @var array
		 */
		public $emoji = array();

		/**
		 * @var null|int
		 */
		public $form_id = null;

		/**
		 * @var null|string
		 */
		public $form_status = null;

		/**
		 * @var array
		 */
		public $set_args = array();

		/**
		 * Shortcodes constructor.
		 */
		public function __construct() {
			add_shortcode( 'ultimatemember', array( &$this, 'ultimatemember' ) );

			add_shortcode( 'ultimatemember_login', array( &$this, 'ultimatemember_login' ) );
			add_shortcode( 'ultimatemember_register', array( &$this, 'ultimatemember_register' ) );
			add_shortcode( 'ultimatemember_profile', array( &$this, 'ultimatemember_profile' ) );
			add_shortcode( 'ultimatemember_directory', array( &$this, 'ultimatemember_directory' ) );

			add_shortcode( 'um_loggedin', array( &$this, 'um_loggedin' ) );
			add_shortcode( 'um_loggedout', array( &$this, 'um_loggedout' ) );
			add_shortcode( 'um_show_content', array( &$this, 'um_shortcode_show_content_for_role' ) );
			add_shortcode( 'ultimatemember_searchform', array( &$this, 'ultimatemember_searchform' ) );

			add_shortcode( 'um_author_profile_link', array( &$this, 'author_profile_link' ) );

			add_filter( 'body_class', array( &$this, 'body_class' ), 0 );

			add_filter( 'um_shortcode_args_filter', array( &$this, 'display_logout_form' ), 99 );
			add_filter( 'um_shortcode_args_filter', array( &$this, 'parse_shortcode_args' ), 99 );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_emoji_base_uri
			 * @description Change Emoji base URL
			 * @input_vars
			 * [{"var":"$url","type":"string","desc":"Base URL"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_emoji_base_uri', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_emoji_base_uri', 'my_emoji_base_uri', 10, 1 );
			 * function my_emoji_base_uri( $url ) {
			 *     // your code here
			 *     return $url;
			 * }
			 * ?>
			 */
			$base_uri = apply_filters( 'um_emoji_base_uri', 'https://s.w.org/images/core/emoji/' );

			$this->emoji[':)'] = $base_uri . '72x72/1f604.png';
			$this->emoji[':smiley:'] = $base_uri . '72x72/1f603.png';
			$this->emoji[':D'] = $base_uri . '72x72/1f600.png';
			$this->emoji[':$'] = $base_uri . '72x72/1f60a.png';
			$this->emoji[':relaxed:'] = $base_uri . '72x72/263a.png';
			$this->emoji[';)'] = $base_uri . '72x72/1f609.png';
			$this->emoji[':heart_eyes:'] = $base_uri . '72x72/1f60d.png';
			$this->emoji[':kissing_heart:'] = $base_uri . '72x72/1f618.png';
			$this->emoji[':kissing_closed_eyes:'] = $base_uri . '72x72/1f61a.png';
			$this->emoji[':kissing:'] = $base_uri . '72x72/1f617.png';
			$this->emoji[':kissing_smiling_eyes:'] = $base_uri . '72x72/1f619.png';
			$this->emoji[';P'] = $base_uri . '72x72/1f61c.png';
			$this->emoji[':P'] = $base_uri . '72x72/1f61b.png';
			$this->emoji[':stuck_out_tongue_closed_eyes:'] = $base_uri . '72x72/1f61d.png';
			$this->emoji[':flushed:'] = $base_uri . '72x72/1f633.png';
			$this->emoji[':grin:'] = $base_uri . '72x72/1f601.png';
			$this->emoji[':pensive:'] = $base_uri . '72x72/1f614.png';
			$this->emoji[':relieved:'] = $base_uri . '72x72/1f60c.png';
			$this->emoji[':unamused'] = $base_uri . '72x72/1f612.png';
			$this->emoji[':('] = $base_uri . '72x72/1f61e.png';
			$this->emoji[':persevere:'] = $base_uri . '72x72/1f623.png';
			$this->emoji[":'("] = $base_uri . '72x72/1f622.png';
			$this->emoji[':joy:'] = $base_uri . '72x72/1f602.png';
			$this->emoji[':sob:'] = $base_uri . '72x72/1f62d.png';
			$this->emoji[':sleepy:'] = $base_uri . '72x72/1f62a.png';
			$this->emoji[':disappointed_relieved:'] = $base_uri . '72x72/1f625.png';
			$this->emoji[':cold_sweat:'] = $base_uri . '72x72/1f630.png';
			$this->emoji[':sweat_smile:'] = $base_uri . '72x72/1f605.png';
			$this->emoji[':sweat:'] = $base_uri . '72x72/1f613.png';
			$this->emoji[':weary:'] = $base_uri . '72x72/1f629.png';
			$this->emoji[':tired_face:'] = $base_uri . '72x72/1f62b.png';
			$this->emoji[':fearful:'] = $base_uri . '72x72/1f628.png';
			$this->emoji[':scream:'] = $base_uri . '72x72/1f631.png';
			$this->emoji[':angry:'] = $base_uri . '72x72/1f620.png';
			$this->emoji[':rage:'] = $base_uri . '72x72/1f621.png';
			$this->emoji[':triumph'] = $base_uri . '72x72/1f624.png';
			$this->emoji[':confounded:'] = $base_uri . '72x72/1f616.png';
			$this->emoji[':laughing:'] = $base_uri . '72x72/1f606.png';
			$this->emoji[':yum:'] = $base_uri . '72x72/1f60b.png';
			$this->emoji[':mask:'] = $base_uri . '72x72/1f637.png';
			$this->emoji[':cool:'] = $base_uri . '72x72/1f60e.png';
			$this->emoji[':sleeping:'] = $base_uri . '72x72/1f634.png';
			$this->emoji[':dizzy_face:'] = $base_uri . '72x72/1f635.png';
			$this->emoji[':astonished:'] = $base_uri . '72x72/1f632.png';
			$this->emoji[':worried:'] = $base_uri . '72x72/1f61f.png';
			$this->emoji[':frowning:'] = $base_uri . '72x72/1f626.png';
			$this->emoji[':anguished:'] = $base_uri . '72x72/1f627.png';
			$this->emoji[':smiling_imp:'] = $base_uri . '72x72/1f608.png';
			$this->emoji[':imp:'] = $base_uri . '72x72/1f47f.png';
			$this->emoji[':open_mouth:'] = $base_uri . '72x72/1f62e.png';
			$this->emoji[':grimacing:'] = $base_uri . '72x72/1f62c.png';
			$this->emoji[':neutral_face:'] = $base_uri . '72x72/1f610.png';
			$this->emoji[':confused:'] = $base_uri . '72x72/1f615.png';
			$this->emoji[':hushed:'] = $base_uri . '72x72/1f62f.png';
			$this->emoji[':no_mouth:'] = $base_uri . '72x72/1f636.png';
			$this->emoji[':innocent:'] = $base_uri . '72x72/1f607.png';
			$this->emoji[':smirk:'] = $base_uri . '72x72/1f60f.png';
			$this->emoji[':expressionless:'] = $base_uri . '72x72/1f611.png';
		}

		/**
		 * Conditional logout form
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		function display_logout_form( $args ) {
			if ( is_user_logged_in() && isset( $args['mode'] ) && $args['mode'] == 'login' ) {

				if ( isset( UM()->user()->preview ) && UM()->user()->preview ) {
					return $args;
				}

				if ( get_current_user_id() != um_user( 'ID' ) ) {
					um_fetch_user( get_current_user_id() );
				}

				$args['template'] = 'logout';
			}

			return $args;
		}


		/**
		 * Filter shortcode args
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		function parse_shortcode_args( $args ) {
			if ( $this->message_mode == true ) {
				if ( ! empty( $_REQUEST['um_role'] ) ) {
					$args['template'] = 'message';
					$roleID = sanitize_key( $_REQUEST['um_role'] );
					$role = UM()->roles()->role_data( $roleID );

					if ( ! empty( $role ) && ! empty( $role['status'] ) ) {
						$message_key = $role['status'] . '_message';
						$this->custom_message = ! empty( $role[ $message_key ] ) ? $this->convert_user_tags( stripslashes( $role[ $message_key ] ) ) : '';
					}
				}
			}

			foreach ( $args as $k => $v ) {
				$args[ $k ] = maybe_unserialize( $args[ $k ] );
			}

			return $args;
		}


		/**
		 * Emoji support
		 *
		 * @param $content
		 *
		 * @return mixed|string
		 */
		function emotize( $content ) {
			$content = stripslashes( $content );
			foreach ( $this->emoji as $code => $val ) {
				$regex = str_replace(array('(', ')'), array("\\" . '(', "\\" . ')'), $code);
				$content = preg_replace('/(' . $regex . ')(\s|$)/', '<img src="' . $val . '" alt="' . $code . '" title="' . $code . '" class="emoji" />$2', $content);
			}
			return $content;
		}


		/**
		 * Remove wpautop filter for post content if it's UM core page
		 */
		function is_um_page() {
			if ( is_ultimatemember() ) {
				remove_filter( 'the_content', 'wpautop' );
			}
		}

		/**
		 * Extend body classes.
		 *
		 * @param array $classes
		 *
		 * @return array
		 */
		public function body_class( $classes ) {
			$array = UM()->config()->permalinks;
			if ( ! $array ) {
				return $classes;
			}

			foreach ( $array as $slug => $info ) {
				if ( um_is_core_page( $slug ) ) {
					$classes[] = 'um-page';
					$classes[] = 'um-page-' . $slug;

					if ( is_user_logged_in() ) {
						$classes[] = 'um-page-loggedin';
					} else {
						$classes[] = 'um-page-loggedout';
					}
				}
			}

			if ( um_is_core_page( 'user' ) && um_is_user_himself() ) {
				$classes[] = 'um-own-profile';
			}

			return $classes;
		}


		/**
		 * Retrieve core login form
		 *
		 * @return int
		 */
		function core_login_form() {
			$forms = get_posts(array('post_type' => 'um_form', 'posts_per_page' => 1, 'meta_key' => '_um_core', 'meta_value' => 'login'));
			$form_id = isset( $forms[0]->ID ) ? $forms[0]->ID: 0;

			return $form_id;
		}


		/**
		 * Load a compatible template
		 *
		 * @param $tpl
		 */
		function load_template( $tpl ) {
			$loop = ( $this->loop ) ? $this->loop : array();

			if ( isset( $this->set_args ) && is_array( $this->set_args ) ) {
				$args = $this->set_args;

				unset( $args['file'], $args['theme_file'], $args['tpl'] );

				$args = apply_filters( 'um_template_load_args', $args, $tpl );

				/*
				 * This use of extract() cannot be removed. There are many possible ways that
				 * templates could depend on variables that it creates existing, and no way to
				 * detect and deprecate it.
				 *
				 * Passing the EXTR_SKIP flag is the safest option, ensuring globals and
				 * function variables cannot be overwritten.
				 */
				// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
				extract( $args, EXTR_SKIP );
			}

			$file       = UM_PATH . "templates/{$tpl}.php";
			$theme_file = get_stylesheet_directory() . "/ultimate-member/templates/{$tpl}.php";
			if ( file_exists( $theme_file ) ) {
				$file = $theme_file;
			}

			if ( file_exists( $file ) ) {
				// Avoid Directory Traversal vulnerability by the checking the realpath.
				// Templates can be situated only in the get_stylesheet_directory() or plugindir templates.
				$real_file = wp_normalize_path( realpath( $file ) );
				if ( 0 === strpos( $real_file, wp_normalize_path( UM_PATH . "templates" . DIRECTORY_SEPARATOR ) ) || 0 === strpos( $real_file, wp_normalize_path( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'ultimate-member' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR ) ) ) {
					include $file;
				}
			}
		}


		/**
		 * Add class based on shortcode
		 *
		 * @param $mode
		 * @param array $args
		 *
		 * @return mixed|string|void
		 */
		function get_class($mode, $args = array()) {

			$classes = 'um-' . $mode;

			if (is_admin()) {
				$classes .= ' um-in-admin';
			}

			if (isset(UM()->form()->errors) && UM()->form()->errors) {
				$classes .= ' um-err';
			}

			if ( true === UM()->fields()->editing ) {
				$classes .= ' um-editing';
			}

			if ( true === UM()->fields()->viewing ) {
				$classes .= ' um-viewing';
			}

			if (isset($args['template']) && $args['template'] != $args['mode']) {
				$classes .= ' um-' . $args['template'];
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_form_official_classes__hook
			 * @description Change official form classes
			 * @input_vars
			 * [{"var":"$classes","type":"string","desc":"Classes string"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_form_official_classes__hook', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_form_official_classes__hook', 'my_form_official_classes', 10, 1 );
			 * function my_form_official_classes( $classes ) {
			 *     // your code here
			 *     return $classes;
			 * }
			 * ?>
			 */
			$classes = apply_filters( 'um_form_official_classes__hook', $classes );
			return $classes;
		}

		/**
		 * Logged-in only content
		 *
		 * @param array  $args
		 * @param string $content
		 *
		 * @return string
		 */
		public function um_loggedin( $args = array(), $content = '' ) {
			$args = shortcode_atts(
				array(
					'lock_text' => __( 'This content has been restricted to logged-in users only. Please <a href="{login_referrer}">login</a> to view this content.', 'ultimate-member' ),
					'show_lock' => 'yes',
				),
				$args,
				'um_loggedin'
			);

			if ( ! is_user_logged_in() ) {
				// Hide content for not logged-in users. Maybe display locked content notice.
				if ( 'no' === $args['show_lock'] ) {
					return '';
				}

				$args['lock_text'] = $this->convert_locker_tags( $args['lock_text'] );
				return UM()->get_template( 'login-to-view.php', '', $args );
			}

			$prepared_content = wp_kses( apply_shortcodes( $this->convert_locker_tags( wpautop( $content ) ) ), UM()->get_allowed_html( 'templates' ) );

			/**
			 * Filters prepared inner content via Ultimate Member handlers in [um_loggedin] shortcode.
			 *
			 * @since 2.8.7
			 * @hook  um_loggedin_inner_content
			 *
			 * @param {string} $prepared_content Prepared inner content via Ultimate Member handlers.
			 * @param {string} $content          Original inner content.
			 *
			 * @return {string} Prepared inner content.
			 *
			 * @example <caption>Change inner content with own handlers.</caption>
			 * function my_um_loggedin_inner_content( $prepared_content, $content ) {
			 *     $prepared_content = esc_html( $content );
			 *     return $prepared_content;
			 * }
			 * add_filter( 'um_loggedin_inner_content', 'my_um_loggedin_inner_content', 10, 2 );
			 */
			return apply_filters( 'um_loggedin_inner_content', $prepared_content, $content );
		}

		/**
		 * Logged-out only content
		 *
		 * @param array  $args
		 * @param string $content
		 *
		 * @return string
		 */
		public function um_loggedout( $args = array(), $content = '' ) {
			if ( is_user_logged_in() ) {
				// Hide for logged-in users
				return '';
			}
			return apply_shortcodes( $this->convert_locker_tags( wpautop( $content ) ) );
		}

		/**
		 * Display post author's link to UM User Profile.
		 *
		 * @since 2.8.2
		 *
		 * Example 1: [um_author_profile_link] current post author User Profile URL
		 * Example 2: [um_author_profile_link title="User profile" user_id="29"]
		 * Example 3: [um_author_profile_link title="User profile" user_id="29"]Visit Author Profile[/um_author_profile_link]
		 * Example 4: [um_author_profile_link raw="1"] for result like http://localhost:8000/user/janedoe/
		 *
		 * @param array  $attr {
		 *     Attributes of the shortcode.
		 *
		 *     @type string $class   A link class.
		 *     @type string $title   A link text.
		 *     @type int    $user_id User ID. Author ID if empty.
		 *     @type bool   $raw     Get raw URL or link layout. `false` by default.
		 * }
		 * @param string $content
		 * @return string Profile link HTML or profile link URL if the link text is empty.
		 */
		public function author_profile_link( $attr = array(), $content = '' ) {
			$default_user_id = 0;
			if ( is_singular() ) {
				$default_user_id = get_post()->post_author;
			} elseif ( is_author() ) {
				$default_user_id = get_the_author_meta( 'ID' );
			}

			$defaults_atts = array(
				'class'   => 'um-link um-profile-link',
				'title'   => __( 'Go to profile', 'ultimate-member' ),
				'user_id' => $default_user_id,
				'raw'     => false,
			);

			$atts = shortcode_atts( $defaults_atts, $attr, 'um_author_profile_link' );

			if ( empty( $atts['user_id'] ) ) {
				return '';
			}

			$user_id = absint( $atts['user_id'] );
			$url     = um_user_profile_url( $user_id );
			if ( empty( $url ) ) {
				return '';
			}

			if ( ! empty( $atts['raw'] ) ) {
				return $url;
			}

			$title     = ! empty( $atts['title'] ) ? $atts['title'] : __( 'Go to profile', 'ultimate-member' );
			$link_html = empty( $content ) ? $title : $content;

			return '<a class="' . esc_attr( $atts['class'] ) . '" href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '">' . wp_kses_post( $link_html ) . '</a>';
		}

		/**
		 * @param array $args
		 *
		 * @return string
		 */
		function ultimatemember_login( $args = array() ) {
			global $wpdb;

			$args = ! empty( $args ) ? $args : array();

			$default_login = $wpdb->get_var(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND
					  pm.meta_value = 'login' AND
					  pm2.meta_value = '1'"
			);

			$args['form_id'] = $default_login;
			$shortcode_attrs = '';
			foreach ( $args as $key => $value ) {
				$shortcode_attrs .= " {$key}=\"{$value}\"";
			}

			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
			} else {
				return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
			}
		}


		/**
		 * @param array $args
		 *
		 * @return string
		 */
		function ultimatemember_register( $args = array() ) {
			global $wpdb;

			$args = ! empty( $args ) ? $args : array();

			$default_register = $wpdb->get_var(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND
					  pm.meta_value = 'register' AND
					  pm2.meta_value = '1'"
			);

			$args['form_id'] = $default_register;
			$shortcode_attrs = '';
			foreach ( $args as $key => $value ) {
				$shortcode_attrs .= " {$key}=\"{$value}\"";
			}

			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
			} else {
				return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
			}
		}


		/**
		 * @param array $args
		 *
		 * @return string
		 */
		function ultimatemember_profile( $args = array() ) {
			global $wpdb;

			$args = ! empty( $args ) ? $args : array();

			$default_profile = $wpdb->get_var(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND
					  pm.meta_value = 'profile' AND
					  pm2.meta_value = '1'"
			);

			$args['form_id'] = $default_profile;

			$shortcode_attrs = '';
			foreach ( $args as $key => $value ) {
				$shortcode_attrs .= " {$key}=\"{$value}\"";
			}

			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
			} else {
				return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
			}
		}


		/**
		 * @param array $args
		 *
		 * @return string
		 */
		function ultimatemember_directory( $args = array() ) {
			global $wpdb;

			$args = ! empty( $args ) ? $args : array();

			$default_directory = $wpdb->get_var(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND
					  pm.meta_value = 'directory' AND
					  pm2.meta_value = '1'"
			);

			$args['form_id'] = $default_directory;

			$shortcode_attrs = '';
			foreach ( $args as $key => $value ) {
				$shortcode_attrs .= " {$key}=\"{$value}\"";
			}

			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
			} else {
				return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
			}
		}

		/**
		 * Shortcode
		 *
		 * @param array $args
		 *
		 * @return string
		 */
		public function ultimatemember( $args = array() ) {
			// There is possible to use 'shortcode_atts_ultimatemember' filter for getting customized `$args`.
			$args = shortcode_atts(
				array(
					'form_id'  => '',
					'is_block' => false,
				),
				$args,
				'ultimatemember'
			);

			// Sanitize shortcode arguments.
			$args['form_id']  = ! empty( $args['form_id'] ) ? absint( $args['form_id'] ) : '';
			$args['is_block'] = (bool) $args['is_block'];

			$form_post = get_post( $args['form_id'] );
			// Invalid post ID. Maybe post doesn't exist.
			if ( empty( $form_post ) ) {
				return '';
			}

			// Invalid post type. It can be only `um_form` or `um_directory`
			$post_types = array( 'um_form' );
			if ( UM()->options()->get( 'members_page' ) ) {
				$post_types[] = 'um_directory';
			}

			if ( ! in_array( $form_post->post_type, $post_types, true ) ) {
				return '';
			}

			/**
			 * Filters variable for enable singleton shortcode loading on the same page.
			 * Note: Set it to `false` if you don't need to render the same form twice or more on the same page.
			 *
			 * @since 2.6.8
			 * @since 2.6.9 $disable argument set to `true` by default
			 *
			 * @hook  um_ultimatemember_shortcode_disable_singleton
			 *
			 * @param {bool}  $disable Disabled singleton. By default, it's `true`.
			 * @param {array} $args    Shortcode arguments.
			 *
			 * @return {bool} Disabled singleton or not.
			 *
			 * @example <caption>Turn off ability to use ultimatemember shortcode twice.</caption>
			 * add_filter( 'um_ultimatemember_shortcode_disable_singleton', '__return_false' );
			 */
			$disable_singleton_shortcode = apply_filters( 'um_ultimatemember_shortcode_disable_singleton', true, $args );
			if ( false === $disable_singleton_shortcode ) {
				if ( isset( $args['form_id'] ) ) {
					$id = $args['form_id'];
					if ( isset( $this->forms_exist[ $id ] ) && true === $this->forms_exist[ $id ] ) {
						return '';
					}
					$this->forms_exist[ $id ] = true;
				}
			}

			return $this->load( $args );
		}

		/**
		 * Load a module with global function
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function load( $args ) {
			$defaults = array();
			$args     = wp_parse_args( $args, $defaults );

			// When to not continue.
			if ( ! array_key_exists( 'form_id', $args ) ) {
				return '';
			}

			$this->form_id = $args['form_id'];
			if ( empty( $this->form_id ) ) {
				return '';
			}

			$this->form_status = get_post_status( $this->form_id );
			if ( 'publish' !== $this->form_status ) {
				return '';
			}

			UM()->fields()->set_id = absint( $this->form_id );

			// get data into one global array
			$post_data = UM()->query()->post_data( $this->form_id );
			$args      = array_merge( $args, $post_data );

			ob_start();

			/**
			 * Filters arguments for loading Ultimate Member shortcodes.
			 *
			 * @since 1.3.x
			 * @hook  um_pre_args_setup
			 *
			 * @param {array} $args Data for loading shortcode.
			 *
			 * @return {array} Data for loading shortcode.
			 *
			 * @example <caption>Change arguments on load shortcode.</caption>
			 * function my_pre_args_setup( $args ) {
			 *     // your code here
			 *     return $args;
			 * }
			 * add_filter( 'um_pre_args_setup', 'my_pre_args_setup' );
			 */
			$args = apply_filters( 'um_pre_args_setup', $args );

			if ( ! isset( $args['template'] ) ) {
				$args['template'] = '';
			}

			if ( isset( $post_data['template'] ) && $post_data['template'] !== $args['template'] ) {
				$args['template'] = $post_data['template'];
			}

			if ( ! $this->template_exists( $args['template'] ) ) {
				$args['template'] = $post_data['mode'];
			}

			if ( ! isset( $post_data['template'] ) ) {
				$post_data['template'] = $post_data['mode'];
			}

			if ( 'directory' === $args['mode'] ) {
				wp_enqueue_script( 'um_members' );
				wp_enqueue_style( 'um_members' );
			}

			if ( 'directory' !== $args['mode'] ) {
				$args = array_merge( $post_data, $args );

				if ( empty( $args['use_custom_settings'] ) ) {
					$args = array_merge( $args, $this->get_css_args( $args ) );
				} else {
					$args = array_merge( $this->get_css_args( $args ), $args );
				}
			}

			/**
			 * Filters change arguments on load shortcode.
			 *
			 * @since 1.3.x
			 * @hook  um_shortcode_args_filter
			 *
			 * @param {array} $args Shortcode arguments.
			 *
			 * @return {array} Shortcode arguments.
			 *
			 * @example <caption>Change arguments on load shortcode.</caption>
			 * function my_shortcode_args( $args ) {
			 *     // your code here
			 *     return $args;
			 * }
			 * add_filter( 'um_shortcode_args_filter', 'my_shortcode_args' );
			 */
			$args = apply_filters( 'um_shortcode_args_filter', $args );

			if ( ! array_key_exists( 'mode', $args ) || ! array_key_exists( 'template', $args ) ) {
				ob_get_clean();
				return '';
			}
			$mode = $args['mode'];

			// Not display on admin preview.
			if ( empty( $_POST['act_id'] ) || 'um_admin_preview_form' !== sanitize_key( $_POST['act_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				/**
				 * Filters the ability to show registration form for the logged-in users.
				 * Set it to true for displaying registration form for the logged-in users.
				 *
				 * @since 2.1.20
				 * @hook um_registration_for_loggedin_users
				 *
				 * @param {bool}  $show Show registration form for the logged-in users. By default, it's false
				 * @param {array} $args Shortcode arguments.
				 *
				 * @return {bool} Show registration form for the logged-in users.
				 *
				 * @example <caption>Show registration form for the logged-in users for all UM registration forms on your website.</caption>
				 * add_filter( 'um_registration_for_loggedin_users', '__return_true' );
				 */
				$enable_loggedin_registration = apply_filters( 'um_registration_for_loggedin_users', false, $args );

				if ( ! $enable_loggedin_registration && 'register' === $mode && is_user_logged_in() ) {
					ob_get_clean();
					return __( 'You are already registered.', 'ultimate-member' );
				}
			}

			if ( 'profile' === $mode && ! empty( $args['is_block'] ) && ! is_user_logged_in() ) {
				ob_get_clean();
				return '';
			}

			// For profiles only.
			if ( 'profile' === $mode && um_profile_id() ) {
				// Set requested user if it's not setup from permalinks (for not profile page in edit mode).
				if ( ! um_get_requested_user() ) {
					um_set_requested_user( um_profile_id() );
				}

				if ( ! empty( $args['use_custom_settings'] ) && ! empty( $args['role'] ) ) {
					// Option "Apply custom settings to this form". Option "Make this profile form role-specific".
					// Show the first Profile Form with role selected, don't show profile forms below the page with other role-specific setting.
					if ( empty( $this->profile_role ) ) {
						$current_user_roles = UM()->roles()->get_all_user_roles( um_profile_id() );

						if ( empty( $current_user_roles ) ) {
							ob_get_clean();
							return '';
						}
						if ( is_array( $args['role'] ) ) {
							if ( ! count( array_intersect( $args['role'], $current_user_roles ) ) ) {
								ob_get_clean();
								return '';
							}
						} elseif ( ! in_array( $args['role'], $current_user_roles, true ) ) {
							ob_get_clean();
							return '';
						}

						$this->profile_role = $args['role'];
					} elseif ( $this->profile_role !== $args['role'] ) {
						ob_get_clean();
						return '';
					}
				}
			}

			$content = apply_filters( 'um_force_shortcode_render', false, $args );
			if ( false !== $content ) {
				ob_get_clean();
				return $content;
			}

			/**
			 * Fires before loading form shortcode.
			 *
			 * Note: $mode can be 'profile', 'login', 'register', 'account'.
			 *
			 * @since 1.3.x
			 * @hook  um_pre_{$mode}_shortcode
			 *
			 * @param {array} $args Form shortcode arguments.
			 *
			 * @example <caption>Make any custom action before loading a registration form shortcode.</caption>
			 * function my_pre_register_shortcode( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_pre_register_shortcode', 'my_pre_register_shortcode' );
			 * @example <caption>Make any custom action before loading a login form shortcode.</caption>
			 * function my_pre_login_shortcode( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_pre_login_shortcode', 'my_pre_login_shortcode' );
			 * @example <caption>Make any custom action before loading a password reset form shortcode.</caption>
			 * function my_pre_password_shortcode( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_pre_password_shortcode', 'my_pre_password_shortcode' );
			 * @example <caption>Make any custom action before loading a profile form shortcode.</caption>
			 * function my_pre_profile_shortcode( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_pre_profile_shortcode', 'my_pre_profile_shortcode' );
			 * @example <caption>Make any custom action before loading an account form shortcode.</caption>
			 * function my_pre_account_shortcode( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_pre_account_shortcode', 'my_pre_account_shortcode' );
			 */
			do_action( "um_pre_{$mode}_shortcode", $args );
			/**
			 * Fires before loading form shortcode.
			 *
			 * @since 1.3.x
			 * @hook  um_before_form_is_loaded
			 *
			 * @param {array} $args Form shortcode arguments.
			 *
			 * @example <caption>Make any custom action before loading UM form shortcode.</caption>
			 * function my_pre_shortcode( $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_before_form_is_loaded', 'my_pre_shortcode', 10, 1 );
			 */
			do_action( 'um_before_form_is_loaded', $args );
			/**
			 * Fires before loading a form shortcode.
			 *
			 * @since 1.3.x
			 * @todo Deprecate since 2.7.0. Use `um_pre_{$mode}_shortcode` or `um_before_form_is_loaded` instead.
			 * @hook  um_before_{$mode}_form_is_loaded
			 *
			 * @param {array} $args Form shortcode arguments.
			 */
			do_action( "um_before_{$mode}_form_is_loaded", $args );

			$this->template_load( $args['template'], $args );

			$this->dynamic_css( $args );

			if ( 'logout' === $mode || um_get_requested_user() ) {
				um_reset_user();
			}

			/**
			 * Fires after load shortcode content.
			 *
			 * @since 2.0
			 * @hook  um_after_everything_output
			 *
			 * @param {array} $args Form shortcode arguments.
			 *
			 * @example <caption>Make any custom action after load shortcode content.</caption>
			 * function my_pre_shortcode() {
			 *     // your code here
			 * }
			 * add_action( 'um_after_everything_output', 'my_pre_shortcode', 10 );
			 */
			do_action( 'um_after_everything_output' );

			return ob_get_clean();
		}

		/**
		 * Get dynamic CSS args
		 *
		 * @param $args
		 * @return array
		 */
		public function get_css_args( $args ) {
			$arr = um_styling_defaults( $args['mode'] );
			$arr = array_merge(
				$arr,
				array(
					'form_id' => $args['form_id'],
					'mode'    => $args['mode'],
				)
			);
			return $arr;
		}

		/**
		 * Load dynamic CSS.
		 *
		 * @param array $args
		 *
		 * @return string
		 */
		public function dynamic_css( $args = array() ) {
			/**
			 * Filters for disable global dynamic CSS. It's false by default, set it to true to disable.
			 *
			 * @since 2.0
			 * @hook  um_disable_dynamic_global_css
			 *
			 * @param {bool} $disable Disable global CSS.
			 *
			 * @return {bool} Disable global CSS.
			 *
			 * @example <caption>Turn off enqueue of global dynamic CSS.</caption>
			 * add_filter( 'um_disable_dynamic_global_css', '__return_true' );
			 */
			$disable_css = apply_filters( 'um_disable_dynamic_global_css', false );
			if ( $disable_css ) {
				return '';
			}

			if ( empty( $args['form_id'] ) ) {
				return '';
			}

			include_once UM_PATH . 'assets/dynamic_css/dynamic-global.php';

			if ( array_key_exists( 'mode', $args ) && in_array( $args['mode'], array( 'profile', 'directory' ), true ) ) {
				$file = UM_PATH . 'assets/dynamic_css/dynamic-' . $args['mode'] . '.php';

				if ( file_exists( $file ) ) {
					include_once $file;
				}
			}

			return '';
		}

		/**
		 * Loads a template file
		 *
		 * @param $template
		 * @param array $args
		 */
		public function template_load( $template, $args = array() ) {
			if ( is_array( $args ) ) {
				$this->set_args = $args;
			}
			$this->load_template( $template );
		}


		/**
		 * Checks if a template file exists
		 *
		 * @param $template
		 *
		 * @return bool
		 */
		function template_exists($template) {

			$file = UM_PATH . 'templates/' . $template . '.php';
			$theme_file = get_stylesheet_directory() . '/ultimate-member/templates/' . $template . '.php';

			if (file_exists($theme_file) || file_exists($file)) {
				return true;
			}

			return false;
		}


		/**
		 * Get File Name without path and extension
		 *
		 * @param $file
		 *
		 * @return mixed|string
		 */
		function get_template_name( $file ) {
			$file = basename( $file );
			$file = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $file );
			return $file;
		}


		/**
		 * Get Templates
		 *
		 * @param null $excluded
		 *
		 * @return mixed
		 */
		function get_templates( $excluded = null ) {

			if ( $excluded ) {
				$array[ $excluded ] = __( 'Default Template', 'ultimate-member' );
			}

			$paths[] = glob( UM_PATH . 'templates/' . '*.php' );

			if ( file_exists( get_stylesheet_directory() . '/ultimate-member/templates/' ) ) {
				$paths[] = glob( get_stylesheet_directory() . '/ultimate-member/templates/' . '*.php' );
			}

			if ( isset( $paths ) && ! empty( $paths ) ) {

				foreach ( $paths as $k => $files ) {

					if ( isset( $files ) && ! empty( $files ) ) {

						foreach ( $files as $file ) {

							$clean_filename = $this->get_template_name( $file );

							if ( 0 === strpos( $clean_filename, $excluded ) ) {

								$source = file_get_contents( $file );
								$tokens = @\token_get_all( $source );
								$comment = array(
									T_COMMENT, // All comments since PHP5
									T_DOC_COMMENT, // PHPDoc comments
								);
								foreach ( $tokens as $token ) {
									if ( in_array( $token[0], $comment ) && strstr( $token[1], '/* Template:' ) && $clean_filename != $excluded ) {
										$txt = $token[1];
										$txt = str_replace( '/* Template: ', '', $txt );
										$txt = str_replace( ' */', '', $txt );
										$array[ $clean_filename ] = $txt;
									}
								}

							}

						}

					}

				}

			}

			return $array;
		}


		/**
		 * Get Shortcode for given form ID
		 *
		 * @param $post_id
		 *
		 * @return string
		 */
		function get_shortcode( $post_id ) {
			$shortcode = '[ultimatemember form_id="' . $post_id . '"]';
			return $shortcode;
		}


		/**
		 * Get Shortcode for given form ID
		 *
		 * @param $post_id
		 *
		 * @return string
		 */
		function get_default_shortcode( $post_id ) {
			$mode = UM()->query()->get_attr( 'mode', $post_id );

			switch ( $mode ) {
				case 'login':
					$shortcode = '[ultimatemember_login]';
					break;
				case 'profile':
					$shortcode = '[ultimatemember_profile]';
					break;
				case 'register':
					$shortcode = '[ultimatemember_register]';
					break;
				case 'directory':
					$shortcode = '[ultimatemember_directory]';
					break;
			}

			return $shortcode;
		}


		/**
		 * Convert access lock tags
		 *
		 * @param $str
		 *
		 * @return mixed|string
		 */
		public function convert_locker_tags( $str ) {
			add_filter( 'um_template_tags_patterns_hook', array( &$this, 'add_placeholder' ) );
			add_filter( 'um_template_tags_replaces_hook', array( &$this, 'add_replace_placeholder' ) );
			return um_convert_tags( $str, array(), false );
		}

		/**
		 * Convert user tags in a string
		 *
		 * @param string $str
		 *
		 * @return string
		 */
		public function convert_user_tags( $str ) {
			$pattern_array = array(
				'{first_name}',
				'{last_name}',
				'{display_name}',
				'{user_avatar_small}',
				'{username}',
				'{nickname}',
				'{user_email}',
			);
			/**
			 * Filters the user placeholders patterns.
			 *
			 * @since 1.3.x
			 * @hook  um_allowed_user_tags_patterns
			 *
			 * @param {array} $patterns User Placeholders.
			 *
			 * @return {array} User Placeholders.
			 *
			 * @example <caption>Add the `{user_description}` placeholder.</caption>
			 * function my_allowed_user_tags( $patterns ) {
			 *     $patterns[] = '{user_description}';
			 *     return $patterns;
			 * }
			 * add_filter( 'um_allowed_user_tags_patterns', 'my_allowed_user_tags' );
			 */
			$pattern_array = apply_filters( 'um_allowed_user_tags_patterns', $pattern_array );
			foreach ( $pattern_array as $pattern ) {
				if ( preg_match( $pattern, $str ) ) {

					$value    = '';
					$usermeta = str_replace( array( '{', '}' ), '', $pattern );
					if ( is_user_logged_in() ) {
						if ( 'user_avatar_small' === $usermeta ) {
							$value = get_avatar( um_user( 'ID' ), 40 );
						} elseif ( um_user( $usermeta ) ) {
							$value = um_user( $usermeta );
						}

						if ( 'username' === $usermeta ) {
							$value = um_user( 'user_login' );
						}

						if ( 'nickname' === $usermeta ) {
							$value = um_profile( 'nickname' );
						}

						if ( 'user_email' === $usermeta ) {
							$value = um_user( 'user_email' );
						}

						/**
						 * Filters the user placeholders value of pattern for logged-in user.
						 *
						 * @since 1.3.x
						 * @hook  um_profile_tag_hook__{$usermeta}
						 *
						 * @param {string} $value User meta field value.
						 * @param {int}    $id    User ID.
						 *
						 * @return {string} User meta field value.
						 *
						 * @example <caption>Add the replacement value for `{user_description}` placeholder.</caption>
						 * function my_user_description( $value, $user_id ) {
						 *     $value = get_user_meta( $user_id, 'user_description', true );
						 *     return $value;
						 * }
						 * add_filter( 'um_profile_tag_hook__user_description', 'my_user_description', 10, 2 );
						 */
						$value = apply_filters( "um_profile_tag_hook__{$usermeta}", $value, um_user( 'ID' ) );
					} else {
						/**
						 * Filters the user placeholders value of pattern for not logged-in user.
						 *
						 * @since 2.6.11
						 * @hook  um_profile_nopriv_tag_hook__{$usermeta}
						 *
						 * @param {string} $value User meta field value.
						 *
						 * @return {string} User meta field value.
						 *
						 * @example <caption>Add the replacement value for `{user_description}` placeholder for not logged-in user.</caption>
						 * function my_nopriv_user_description( $value ) {
						 *     $value = ! empty( $_GET['user_description'] ) ? sanitize_text_field( $_GET['user_description'] ) : '';
						 *     return $value;
						 * }
						 * add_filter( 'um_profile_nopriv_tag_hook__user_description', 'my_nopriv_user_description' );
						 */
						$value = apply_filters( "um_profile_nopriv_tag_hook__{$usermeta}", $value );
					}

					$str = preg_replace( '/' . $pattern . '/', $value, $str );
				}
			}

			return $str;
		}

		/**
		 * Shortcode: Show custom content to specific role
		 *
		 * Show content to specific roles
		 * [um_show_content roles='member'] <!-- insert content here -->  [/um_show_content]
		 * You can add multiple target roles, just use ',' e.g.  [um_show_content roles='member,candidates,pets']
		 *
		 * Hide content from specific roles
		 * [um_show_content not='contributors'] <!-- insert content here -->  [/um_show_content]
		 * You can add multiple target roles, just use ',' e.g.  [um_show_content roles='member,candidates,pets']
		 *
		 * @param  array $atts
		 * @param  string $content
		 * @return string
		 */
		public function um_shortcode_show_content_for_role( $atts = array(), $content = '' ) {
			global $user_ID;

			if ( ! is_user_logged_in() ) {
				return '';
			}

			$a = shortcode_atts(
				array(
					'roles'      => '',
					'not'        => '',
					'is_profile' => false,
				),
				$atts,
				'um_show_content'
			);

			if ( $a['is_profile'] ) {
				um_fetch_user( um_profile_id() );
			} else {
				um_fetch_user( $user_ID );
			}

			$current_user_roles = um_user( 'roles' );

			if ( ! empty( $a['not'] ) && ! empty( $a['roles'] ) ) {
				return apply_shortcodes( $this->convert_locker_tags( $content ) );
			}

			if ( ! empty( $a['not'] ) ) {
				$not_in_roles = explode( ',', $a['not'] );

				if ( is_array( $not_in_roles ) && ( empty( $current_user_roles ) || count( array_intersect( $current_user_roles, $not_in_roles ) ) <= 0 ) ) {
					return apply_shortcodes( $this->convert_locker_tags( $content ) );
				}
			} else {
				$roles = explode( ',', $a['roles'] );

				if ( ! empty( $current_user_roles ) && is_array( $roles ) && count( array_intersect( $current_user_roles, $roles ) ) > 0 ) {
					return apply_shortcodes( $this->convert_locker_tags( $content ) );
				}
			}

			return '';
		}

		/**
		 * @param array $args
		 * @param string $content
		 *
		 * @return string
		 */
		public function ultimatemember_searchform( $args = array(), $content = '' ) {
			if ( ! UM()->options()->get( 'members_page' ) ) {
				return '';
			}

			$member_directory_ids = array();

			$page_id = UM()->config()->permalinks['members'];
			if ( ! empty( $page_id ) ) {
				$member_directory_ids = UM()->member_directory()->get_member_directory_id( $page_id );
			}

			if ( empty( $member_directory_ids ) ) {
				return '';
			}

			//current user priority role
			$priority_user_role = false;
			if ( is_user_logged_in() ) {
				$priority_user_role = UM()->roles()->get_priority_user_role( get_current_user_id() );
			}

			$query = array();
			foreach ( $member_directory_ids as $directory_id ) {
				$directory_data = UM()->query()->post_data( $directory_id );

				if ( isset( $directory_data['roles_can_search'] ) ) {
					$directory_data['roles_can_search'] = maybe_unserialize( $directory_data['roles_can_search'] );
				}

				$show_search = empty( $directory_data['roles_can_search'] ) || ( ! empty( $priority_user_role ) && in_array( $priority_user_role, $directory_data['roles_can_search'] ) );
				if ( empty( $directory_data['search'] ) || ! $show_search ) {
					continue;
				}

				$hash = UM()->member_directory()->get_directory_hash( $directory_id );

				$query[ 'search_' . $hash ] = ! empty( $_GET[ 'search_' . $hash ] ) ? sanitize_text_field( $_GET[ 'search_' . $hash ] ) : '';
			}

			if ( empty( $query ) ) {
				return '';
			}

			$search_value = array_values( $query );

			$t_args = array(
				'query'        => $query,
				'search_value' => $search_value[0],
				'members_page' => um_get_core_page( 'members' ),
			);
			return UM()->get_template( 'searchform.php', '', $t_args );
		}

		/**
		 * UM Placeholders for login referrer
		 *
		 * @param array $placeholders
		 *
		 * @return array
		 */
		public function add_placeholder( $placeholders ) {
			$placeholders[] = '{login_referrer}';
			return $placeholders;
		}

		/**
		 * UM Replace Placeholders for login referrer
		 *
		 * @param array $replace_placeholders
		 *
		 * @return array
		 */
		public function add_replace_placeholder( $replace_placeholders ) {
			$replace_placeholders[] = um_dynamic_login_page_redirect();
			return $replace_placeholders;
		}
	}
}
