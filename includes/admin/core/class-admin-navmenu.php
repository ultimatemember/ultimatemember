<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_Navmenu' ) ) {

	/**
	 * Class Admin_Navmenu
	 * @package um\admin\core
	 */
	class Admin_Navmenu {

		/**
		 * @var array
		 */
		protected static $fields = array();


		/**
		 * Admin_Navmenu constructor.
		 */
		function __construct() {
			self::$fields = array(
				'um_nav_public' => __( 'Display Mode', 'ultimate-member' ),
				'um_nav_roles'  => __( 'By Role', 'ultimate-member' ),
			);

			add_action( 'customize_controls_print_footer_scripts', array( &$this, '_wp_template' ) );
			add_action( 'load-customize.php', array( &$this, 'enqueue_nav_menus_scripts' ) );

			add_action( 'wp_update_nav_menu_item', array( &$this, '_save' ), 10, 3 );

			add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'wp_nav_menu_item_custom_fields' ), 20, 5 );
		}


		/**
		 * Fires just before the move buttons of a nav menu item in the menu editor.
		 * Adds block "Ultimate Member Menu Settings"
		 *
		 * @param int       $item_id Menu item ID.
		 * @param \WP_Post  $item    Menu item data object.
		 * @param int       $depth   Depth of menu item. Used for padding.
		 * @param \stdClass $args    An object of menu item arguments.
		 * @param int       $id      Nav menu ID.
		 */
		public function wp_nav_menu_item_custom_fields( $item_id, $item, $depth, $args, $id = null ) {

			$um_nav_public = get_post_meta( $item->ID, 'menu-item-um_nav_public', true );
			$_nav_roles_meta = get_post_meta( $item->ID, 'menu-item-um_nav_roles', true );
			$um_nav_roles = array();
			if ( $_nav_roles_meta ) {
				foreach ( $_nav_roles_meta as $key => $value ) {
					if ( is_int( $key ) ) {
						$um_nav_roles[] = $value;
					}
				}
			}
			$options = UM()->roles()->get_roles( false );
			?>
			<div class="um-nav-edit">
				<div class="clear"></div>
				<h4 style="margin-bottom: 0.6em;"><?php _e( 'Ultimate Member Menu Settings', 'ultimate-member' ) ?></h4>

				<p class="description description-wide um-nav-mode">
					<label for="edit-menu-item-um_nav_public-<?php echo esc_attr( $item_id ); ?>">
						<?php _e( "Who can see this menu link?", 'ultimate-member' ); ?><br/>
						<select id="edit-menu-item-um_nav_public-<?php echo esc_attr( $item_id ); ?>" name="menu-item-um_nav_public[<?php echo esc_attr( $item_id ); ?>]" style="width:100%;">
							<option value="0" <?php selected( $um_nav_public, 0 ); ?>><?php _e( 'Everyone', 'ultimate-member' ) ?></option>
							<option value="1" <?php selected( $um_nav_public, 1 ); ?>><?php _e( 'Logged Out Users', 'ultimate-member' ) ?></option>
							<option value="2" <?php selected( $um_nav_public, 2 ); ?>><?php _e( 'Logged In Users', 'ultimate-member' ) ?></option>
						</select>
					</label>
				</p>

				<p class="description description-wide um-nav-roles" <?php echo $um_nav_public == 2 ? 'style="display: block;"' : ''; ?>><?php _e( "Select the member roles that can see this link", 'ultimate-member' ) ?><br>

					<?php
					$i = 0;
					$html = '';
					$columns = apply_filters( 'wp_nav_menu_item:um_nav_columns', 2, $item_id, $item );
					$per_page = ceil( count( $options ) / $columns );
					while ( $i < $columns ) {
						$section_fields_per_page = array_slice( $options, $i * $per_page, $per_page );
						$html .= '<span class="um-form-fields-section" style="width:' . floor( 100 / $columns ) . '% !important;">';

						foreach ( $section_fields_per_page as $k => $title ) {
							$id_attr = ' id="edit-menu-item-um_nav_roles-' . $item_id . '_' . $k . '" ';
							$for_attr = ' for="edit-menu-item-um_nav_roles-' . $item_id . '_' . $k . '" ';
							$checked_attr = checked( in_array($k,$um_nav_roles), true, false );
							$html .= "<label {$for_attr}> <input type='checkbox' {$id_attr} name='menu-item-um_nav_roles[{$item_id}][{$k}]' value='1' {$checked_attr} /> <span>{$title}</span> </label>";
						}

						$html .= '</span>';
						$i++;
					}
					echo $html;
					?>
				</p>
				<div class="clear"></div>
			</div>
			<?php
		}


		/**
		 *
		 * Backward compatibility with WP < 5.4
		 *
		 */


		/**
		 * @param int $menu_id
		 * @param int $menu_item_db_id
		 * @param array $menu_item_args
		 */
		function _save( $menu_id, $menu_item_db_id, $menu_item_args ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}

			if ( empty( $_POST['menu-item-db-id'] ) || ! in_array( $menu_item_db_id, $_POST['menu-item-db-id'] ) ) {
				return;
			}

			foreach ( self::$fields as $_key => $label ) {

				$key = sprintf( 'menu-item-%s', $_key );

				// Sanitize
				if ( ! empty( $_POST[ $key ][ $menu_item_db_id ] ) ) {
					// Do some checks here...
					$value = is_array( $_POST[ $key ][ $menu_item_db_id ] ) ?
						array_map( 'sanitize_key', array_keys( $_POST[ $key ][ $menu_item_db_id ] ) ) : (int) $_POST[ $key ][ $menu_item_db_id ];
				} else {
					$value = null;
				}

				// Update
				if ( ! is_null( $value ) ) {
					update_post_meta( $menu_item_db_id, $key, $value );
				} else {
					delete_post_meta( $menu_item_db_id, $key );
				}
			}
		}


		/**
		 *
		 */
		function enqueue_nav_menus_scripts() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
		}


		/**
		 *
		 */
		function admin_enqueue_scripts() {
			UM()->admin()->enqueue()->load_nav_manus_scripts();

			$menu_restriction_data = array();

			$menus = get_posts( 'post_type=nav_menu_item&numberposts=-1' );
			foreach ( $menus as $data ) {
				$_nav_roles_meta = get_post_meta( $data->ID, 'menu-item-um_nav_roles', true );

				$um_nav_roles = array();
				if ( $_nav_roles_meta ) {
					foreach ( $_nav_roles_meta as $key => $value ) {
						if ( is_int( $key ) ) {
							$um_nav_roles[] = $value;
						}
					}
				}

				$menu_restriction_data[ $data->ID ] = array(
					'um_nav_public' => get_post_meta( $data->ID, 'menu-item-um_nav_public', true ),
					'um_nav_roles'  => $um_nav_roles,
				);
			}

			wp_localize_script( 'um_admin_nav_manus', 'um_menu_restriction_data', $menu_restriction_data );
		}


		/**
		 *
		 */
		function _wp_template() {
			?>
			<script type="text/html" id="tmpl-um-nav-menus-fields">
				<div class="um-nav-edit">

					<div class="clear"></div>

					<h4 style="margin-bottom: 0.6em;"><?php _e( "Ultimate Member Menu Settings", 'ultimate-member' ) ?></h4>

					<p class="description description-wide um-nav-mode">
						<label for="edit-menu-item-um_nav_public-{{data.menuItemID}}">
							<?php _e( "Who can see this menu link?", 'ultimate-member' ); ?><br/>
							<select id="edit-menu-item-um_nav_public-{{data.menuItemID}}"
							        name="menu-item-um_nav_public[{{data.menuItemID}}]" style="width:100%;">
								<option value="0" <# if( data.restriction_data.um_nav_public == '0' ){ #>selected="selected"<# } #>>
								<?php _e( 'Everyone', 'ultimate-member' ) ?>
								</option>
								<option value="1" <# if( data.restriction_data.um_nav_public == '1' ){ #>selected="selected"<# } #>>
								<?php _e( 'Logged Out Users', 'ultimate-member' ) ?>
								</option>
								<option value="2" <# if( data.restriction_data.um_nav_public == '2' ){ #>selected="selected"<# } #>>
								<?php _e( 'Logged In Users', 'ultimate-member' ) ?>
								</option>
							</select>
						</label>
					</p>
					<p class="description description-wide um-nav-roles" <# if( data.restriction_data.um_nav_public == '2' ){ #>style="display: block;"<# } #>>
					<?php _e( "Select the member roles that can see this link", 'ultimate-member' ) ?><br/>

					<?php $options = UM()->roles()->get_roles( false );
					$i = 0;
					$html = '';
					$columns = 2;
					while ( $i < $columns ) {
						$per_page = ceil( count( $options ) / $columns );
						$section_fields_per_page = array_slice( $options, $i * $per_page, $per_page );
						$html .= '<span class="um-form-fields-section" style="width:' . floor( 100 / $columns ) . '% !important;">';

						foreach ( $section_fields_per_page as $k => $title ) {
							$id_attr = ' id="edit-menu-item-um_nav_roles-{{data.menuItemID}}_' . $k . '" ';
							$for_attr = ' for="edit-menu-item-um_nav_roles-{{data.menuItemID}}_' . $k . '" ';
							$html .= "<label $for_attr>
		                            <input type='checkbox' {$id_attr} name='menu-item-um_nav_roles[{{data.menuItemID}}][{$k}]' value='1' <# if( _.contains( data.restriction_data.um_nav_roles,'{$k}' ) ){ #>checked='checked'<# } #> />
		                            <span>{$title}</span>
		                        </label>";
						}

						$html .= '</span>';
						$i++;
					}

					echo $html; ?>
					</p>

					<div class="clear"></div>
				</div>
			</script>
			<?php
		}
	}
}
