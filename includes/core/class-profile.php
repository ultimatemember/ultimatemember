<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Profile' ) ) {


	/**
	 * Class Profile
	 * @package um\core
	 */
	class Profile {


		/**
		 * @var array
		 */
		public $arr_user_slugs = array();


		/**
		 * @var array
		 */
		public $arr_user_roles = array();


		/**
		 * @var
		 */
		var $active_tab;

		/**
		 * @var null
		 */
		public $active_subnav = null;


		/**
		 * Profile constructor.
		 */
		function __construct() {
			add_action( 'template_redirect', array( &$this, 'active_tab' ), 10002 );
			add_action( 'template_redirect', array( &$this, 'active_subnav' ), 10002 );
		}


		/**
		 * @param array $args
		 *
		 * @return string
		 */
		function get_show_bio_key( $args ) {
			$key = apply_filters( 'um_profile_bio_key', 'description', $args );
			return $key;
		}


		/**
		 * Delete profile avatar AJAX handler
		 */
		public function ajax_delete_profile_photo() {
			UM()->check_ajax_nonce();

			if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
				wp_send_json_error( __( 'Invalid data', 'ultimate-member' ) );
			}

			$user_id = absint( $_REQUEST['user_id'] );

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				die( esc_html__( 'You can not edit this user', 'ultimate-member' ) );
			}

			UM()->files()->delete_core_user_photo( $user_id, 'profile_photo' );
		}


		/**
		 * Delete cover photo AJAX handler
		 */
		public function ajax_delete_cover_photo() {
			UM()->check_ajax_nonce();

			if ( ! array_key_exists( 'user_id', $_REQUEST ) ) {
				wp_send_json_error( __( 'Invalid data', 'ultimate-member' ) );
			}

			$user_id = absint( $_REQUEST['user_id'] );

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				die( esc_html__( 'You can not edit this user', 'ultimate-member' ) );
			}

			UM()->files()->delete_core_user_photo( $user_id, 'cover_photo' );
		}


		/**
		 * Pre-defined privacy options
		 *
		 * @return array
		 */
		public function tabs_privacy() {
			$privacy = apply_filters(
				'um_profile_tabs_privacy_list',
				array(
					0 => __( 'Anyone', 'ultimate-member' ),
					1 => __( 'Guests only', 'ultimate-member' ),
					2 => __( 'Members only', 'ultimate-member' ),
					3 => __( 'Only the owner', 'ultimate-member' ),
					4 => __( 'Only specific roles', 'ultimate-member' ),
					5 => __( 'Owner and specific roles', 'ultimate-member' ),
				)
			);

			return $privacy;
		}


		/**
		 * All tab data
		 *
		 * @return array
		 */
		function tabs() {

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_profile_tabs
			 * @description Extend user profile tabs
			 * @input_vars
			 * [{"var":"$tabs","type":"array","desc":"Profile tabs"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_profile_tabs', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_profile_tabs', 'my_profile_tabs', 10, 1 );
			 * function my_profile_tabs( $tabs ) {
			 *     // your code here
			 *     return $tabs;
			 * }
			 * ?>
			 */
			$tabs = apply_filters( 'um_profile_tabs', array(
				'main' => array(
					'name' => __( 'About', 'ultimate-member' ),
					'icon' => 'um-faicon-user'
				),
				'posts' => array(
					'name' => __( 'Posts', 'ultimate-member' ),
					'icon' => 'um-faicon-pencil'
				),
				'comments' => array(
					'name' => __( 'Comments', 'ultimate-member' ),
					'icon' => 'um-faicon-comment'
				)
			) );

			// disable private tabs
			if ( ! is_admin() ) {
				if ( is_user_logged_in() ) {
					$user_id = um_user( 'ID' );
					um_fetch_user( get_current_user_id() );
				}

				foreach ( $tabs as $id => $tab ) {
					if ( ! $this->can_view_tab( $id, $tab ) ) {
						unset( $tabs[ $id ] );
					}
				}

				if ( is_user_logged_in() ) {
					um_fetch_user( $user_id );
				}
			}

			return $tabs;
		}


		/**
		 * Check if the user can view the current tab
		 *
		 * @param string $tab
		 * @param array $tab_data
		 *
		 * @return bool
		 */
		function can_view_tab( $tab, $tab_data = array() ) {
			$can_view = false;

			$target_id = (int) UM()->user()->target_id;
			if ( empty( $target_id ) ) {
				return true;
			}

			if ( isset( $tab_data['default_privacy'] ) ) {
				$privacy = $tab_data['default_privacy'];
			} else {
				$privacy = (int) UM()->options()->get( 'profile_tab_' . $tab . '_privacy' );
			}

			$privacy = apply_filters( 'um_profile_menu_tab_privacy', $privacy, $tab );
			switch ( $privacy ) {
				case 0:
					$can_view = true;
					break;

				case 1:
					$can_view = ! is_user_logged_in();
					break;

				case 2:
					$can_view = is_user_logged_in();
					break;

				case 3:
					$can_view = is_user_logged_in() && get_current_user_id() === $target_id;
					break;

				case 4:
					if ( is_user_logged_in() ) {
						if ( isset( $tab_data['default_privacy'] ) ) {
							$roles = isset( $tab_data['default_privacy_roles'] ) ? $tab_data['default_privacy_roles'] : array();
						} else {
							$roles = (array) UM()->options()->get( 'profile_tab_' . $tab . '_roles' );
						}

						$current_user_roles = um_user( 'roles' );
						if ( ! empty( $current_user_roles ) && count( array_intersect( $current_user_roles, $roles ) ) > 0 ) {
							$can_view = true;
						}
					}
					break;
				case 5:
					if ( is_user_logged_in() ) {
						// check profile owner if not - check privacy roles settings
						$can_view = get_current_user_id() === $target_id;

						if ( ! $can_view ) {
							if ( isset( $tab_data['default_privacy'] ) ) {
								$roles = isset( $tab_data['default_privacy_roles'] ) ? $tab_data['default_privacy_roles'] : array();
							} else {
								$roles = (array) UM()->options()->get( 'profile_tab_' . $tab . '_roles' );
							}

							$current_user_roles = um_user( 'roles' );
							if ( ! empty( $current_user_roles ) && count( array_intersect( $current_user_roles, $roles ) ) > 0 ) {
								$can_view = true;
							}
						}
					}
					break;

				default:
					$can_view = apply_filters( 'um_profile_menu_can_view_tab', true, $privacy, $tab, $tab_data, $target_id );
					break;
			}

			return $can_view;
		}


		/**
		 * Tabs that are active
		 *
		 * @return array
		 */
		function tabs_active() {
			$tabs = $this->tabs();

			foreach ( $tabs as $id => $info ) {
				if ( ! empty( $info['hidden'] ) ) {
					continue;
				}

				if ( ! UM()->options()->get( 'profile_tab_' . $id ) ) {
					unset( $tabs[ $id ] );
				}
			}

			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_user_profile_tabs
				 * @description Extend profile tabs
				 * @input_vars
				 * [{"var":"$tabs","type":"array","desc":"Profile Tabs"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_user_profile_tabs', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_user_profile_tabs', 'my_user_profile_tabs', 10, 1 );
				 * function my_user_profile_tabs( $tabs ) {
				 *     // your code here
				 *     return $tabs;
				 * }
				 * ?>
				 */
				$tabs = apply_filters( 'um_user_profile_tabs', $tabs );
			}

			return $tabs;
		}


		/**
		 * Get active_tab
		 *
		 * @return string
		 */
		function active_tab() {

			// get active tabs
			$tabs = UM()->profile()->tabs_active();

			if ( ! UM()->options()->get( 'profile_menu' ) ) {

				$query_arg = get_query_var( 'profiletab' );
				if ( ! empty( $query_arg ) && ! empty( $tabs[ $query_arg ]['hidden'] ) ) {
					$this->active_tab = $query_arg;
				} else {
					if ( ! empty( $tabs ) ) {
						foreach ( $tabs as $k => $tab ) {
							if ( ! empty( $tab['hidden'] ) ) {
								$this->active_tab = $k;
								break;
							}
						}
					}
				}

			} else {
				$query_arg = get_query_var( 'profiletab' );
				if ( ! empty( $query_arg ) && ! empty( $tabs[ $query_arg ] ) ) {
					$this->active_tab = $query_arg;
				} else {
					$default_tab = UM()->options()->get( 'profile_menu_default_tab' );

					if ( ! empty( $tabs[ $default_tab ] ) ) {
						$this->active_tab = $default_tab;
					} else {
						if ( ! empty( $tabs ) ) {
							foreach ( $tabs as $k => $tab ) {
								// set first tab in order
								$this->active_tab = $k;
								break;
							}
						}
					}
				}
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_profile_active_tab
			 * @description Change active profile tab
			 * @input_vars
			 * [{"var":"$tab","type":"string","desc":"Active Profile tab"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_profile_active_tab', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_profile_active_tab', 'my_profile_active_tab', 10, 1 );
			 * function my_profile_active_tab( $tab ) {
			 *     // your code here
			 *     return $tab;
			 * }
			 * ?>
			 */
			$this->active_tab = apply_filters( 'um_profile_active_tab', $this->active_tab );

			return $this->active_tab;
		}


		/**
		 * Get active active_subnav
		 *
		 * @return string|null
		 */
		function active_subnav() {

			$this->active_subnav = null;

			if ( get_query_var( 'subnav' ) ) {
				$this->active_subnav = get_query_var( 'subnav' );
			}

			return $this->active_subnav;
		}

		/**
		 * Show meta in profile
		 *
		 * @param array $array Meta Array
		 * @return string
		 */
		public function show_meta( $array, $args ) {
			$output = '';

			$fields_without_metakey = UM()->builtin()->get_fields_without_metakey();

			if ( ! empty( $array ) ) {
				foreach ( $array as $key ) {
					if ( $key ) {
						$data = array();
						if ( isset( UM()->builtin()->all_user_fields[ $key ] ) ) {
							$data = UM()->builtin()->all_user_fields[ $key ];
						}

						$data['in_profile_meta'] = true;

						$value = um_filtered_value( $key, $data );

						$description_key = UM()->profile()->get_show_bio_key( $args );
						if ( $description_key === $key ) {
							$global_setting = UM()->options()->get( 'profile_show_html_bio' );
							$bio_html       = ! empty( $global_setting );

							if ( ! empty( $args['custom_fields'][ $description_key ] ) ) {
								if ( empty( $args['custom_fields'][ $description_key ]['html'] ) ) {
									$bio_html = false;
								}
							}

							if ( $bio_html ) {
								$data['html'] = true;
								$value = um_filtered_value( $key, $data );
								$res = wp_kses_post( make_clickable( wpautop( $value ) ) );
							} else {
								$res = esc_html( $value );
							}

							$value = nl2br( $res );
						}

						if ( ! $value && ( ! array_key_exists( 'type', $data ) || ! in_array( $data['type'], $fields_without_metakey, true ) ) ) {
							continue;
						}

						if ( ! UM()->options()->get( 'profile_show_metaicon' ) ) {
							$icon = '';
						} else {
							$icon = ! empty( $data['icon'] ) ? '<i class="' . $data['icon'] . '"></i>' : '';
						}

						$items[] = apply_filters( 'um_show_meta_item_html', '<span>' . $icon . $value . '</span>', $key );
						$items[] = '<span class="b">&bull;</span>';
					}
				}
			}

			if ( isset( $items ) ) {
				array_pop( $items );
				foreach ( $items as $item ) {
					$output .= $item;
				}
			}

			return $output;
		}


		/**
		 * New menu
		 *
		 * @param string $position
		 * @param string $element
		 * @param string $trigger
		 * @param array $items
		 * @param array $args
		 */
		function new_ui( $position, $element, $trigger, $items, $args = array() ) {

			$additional_data = '';
			foreach ( $args as $key => $value ) {
				$additional_data .= " data-{$key}=\"{$value}\"";
			} ?>

			<div class="um-dropdown" data-element="<?php echo esc_attr( $element ); ?>" data-position="<?php echo esc_attr( $position ); ?>" data-trigger="<?php echo esc_attr( $trigger ); ?>"<?php echo $additional_data ?>>
				<div class="um-dropdown-b">
					<div class="um-dropdown-arr"><i class=""></i></div>
					<ul>
						<?php foreach ( $items as $k => $v ) { ?>
							<li><?php echo $v; ?></li>
						<?php } ?>
					</ul>
				</div>
			</div>

			<?php
		}


		/**
		 * UM Placeholders for user link, avatar link
		 *
		 * @param $placeholders
		 *
		 * @return array
		 */
		function add_placeholder( $placeholders ) {
			$placeholders[] = '{user_profile_link}';
			$placeholders[] = '{user_avatar_url}';
			$placeholders[] = '{password}';
			return $placeholders;
		}


		/**
		 * UM Replace Placeholders for user link, avatar link
		 *
		 * @param $replace_placeholders
		 *
		 * @return array
		 */
		function add_replace_placeholder( $replace_placeholders ) {
			$replace_placeholders[] = um_get_user_avatar_url();
			$replace_placeholders[] = um_user_profile_url();
			$replace_placeholders[] = esc_html__( 'Your set password', 'ultimate-member' );
			return $replace_placeholders;
		}

	}
}
