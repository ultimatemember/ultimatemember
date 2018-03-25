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
		 * Profile constructor.
		 */
		function __construct() {
			add_action( 'template_redirect', array( &$this, 'active_tab' ), 10002 );
			add_action( 'template_redirect', array( &$this, 'active_subnav' ), 10002 );
		}


		/**
		 * Delete profile avatar AJAX handler
		 */
		function ajax_delete_profile_photo() {
			/**
			 * @var $user_id
			 */
			extract( $_REQUEST );

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) )
				die( __( 'You can not edit this user' ) );

			UM()->files()->delete_core_user_photo( $user_id, 'profile_photo' );
		}


		/**
		 * Delete cover photo AJAX handler
		 */
		function ajax_delete_cover_photo() {
			/**
			 * @var $user_id
			 */
			extract( $_REQUEST );

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) )
				die( __( 'You can not edit this user' ) );

			UM()->files()->delete_core_user_photo( $user_id, 'cover_photo' );
		}


		/**
		 * All tab data
		 *
		 * @return mixed|void
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
				foreach ( $tabs as $id => $tab ) {
					if ( ! $this->can_view_tab( $id ) ) {
						unset( $tabs[$id] );
					}
				}
			}

			return $tabs;
		}


		/**
		 * Tabs that are active
		 *
		 * @return mixed|void
		 */
		function tabs_active() {
			$tabs = $this->tabs();
			foreach( $tabs as $id => $info ) {
				if ( ! UM()->options()->get('profile_tab_'.$id) && !isset( $info['_builtin'] ) && !isset( $info['custom'] ) )
					unset( $tabs[ $id ] );
			}
			return $tabs;
		}


		/**
		 * Primary tabs only
		 *
		 * @return array
		 */
		function tabs_primary(){
			$tabs = $this->tabs();
			$primary = array();
			foreach ( $tabs as $id => $info ) {
				if ( isset( $info['name'] ) ) {
					$primary[$id] = $info['name'];
				}
			}
			return $primary;
		}


		/**
		 * Activated tabs in backend
		 *
		 * @return string
		 */
		function tabs_enabled(){
			$tabs = $this->tabs();
			foreach( $tabs as $id => $info ){
				if ( isset( $info['name'] ) ) {
					if ( UM()->options()->get('profile_tab_'.$id) || isset( $info['_builtin'] ) ) {
						$primary[$id] = $info['name'];
					}
				}
			}
			return ( isset( $primary ) ) ? $primary : '';
		}


		/**
		 * Privacy options
		 *
		 * @return array
		 */
		function tabs_privacy() {
			$privacy = array(
				0 => 'Anyone',
				1 => 'Guests only',
				2 => 'Members only',
				3 => 'Only the owner',
				4 => 'Specific roles'
			);

			return $privacy;
		}


		/**
		 * Check if the user can view the current tab
		 *
		 * @param $tab
		 *
		 * @return bool
		 */
		function can_view_tab( $tab ) {
			$privacy  = intval( UM()->options()->get( 'profile_tab_' . $tab . '_privacy' ) );
			$can_view = false;

			switch( $privacy ) {
				case 1:
					$can_view = is_user_logged_in() ? false : true;
					break;

				case 2:
					$can_view = is_user_logged_in() ? true : false;
					break;

				case 3:
					$can_view = get_current_user_id() == um_user( 'ID' ) ? true : false;
					break;

				case 4:
					$can_view = false;
					if( is_user_logged_in() ) {
						$roles = UM()->options()->get( 'profile_tab_' . $tab . '_roles' );
						if( is_array( $roles )
						    && in_array( UM()->user()->get_role(), $roles ) ) {
							$can_view = true;
						}
					}
					break;

				default:
					$can_view = true;
					break;
			}

			return $can_view;
		}


		/**
		 * Get active_tab
		 *
		 * @return mixed|void
		 */
		function active_tab() {

			$this->active_tab = UM()->options()->get('profile_menu_default_tab');

			if ( get_query_var('profiletab') ) {
				$this->active_tab = get_query_var('profiletab');
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
		 * @return mixed|null
		 */
		function active_subnav() {

			$this->active_subnav = null;

			if ( get_query_var('subnav') ) {
				$this->active_subnav = get_query_var('subnav');
			}

			return $this->active_subnav;
		}


		/**
		 * Show meta in profile
		 *
		 * @param array $array Meta Array
		 * @return string
		 */
		function show_meta( $array ) {
			$output = '';

			if ( ! empty( $array ) ) {
				foreach ( $array as $key ) {
					if ( $key ) {
						$data = array();
						if ( isset( UM()->builtin()->all_user_fields[ $key ] ) ){
							$data = UM()->builtin()->all_user_fields[ $key ];
						}

						$data['in_profile_meta'] = true;

						$value = um_filtered_value( $key, $data );
						if ( ! $value )
							continue;

						if ( ! UM()->options()->get( 'profile_show_metaicon' ) ) {
							$icon = '';
						} else {
							$icon = ! empty( $data['icon'] ) ? '<i class="' . $data['icon'] . '"></i>' : '';
						}

						$items[] = '<span>' . $icon . $value . '</span>';
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
		 */
		function new_ui( $position, $element, $trigger, $items ) {
			?>

			<div class="um-dropdown" data-element="<?php echo $element; ?>" data-position="<?php echo $position; ?>" data-trigger="<?php echo $trigger; ?>">
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
	}
}