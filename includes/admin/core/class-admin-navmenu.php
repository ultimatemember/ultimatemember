<?php
/**
 * Extend menus functionality
 *
 * @package um\admin\core
 */

namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\admin\core\Admin_Navmenu' ) ) {


	/**
	 * Class Admin_Navmenu
	 */
	class Admin_Navmenu {


		/**
		 * Class constructor
		 */
		public function __construct() {
			global $wp_version;

			if ( $wp_version < '5.4' ) {
				add_action( 'admin_footer-nav-menus.php', array( &$this, 'render_nav_menu_item_settings' ) );
				add_action( 'load-nav-menus.php', array( &$this, 'enqueue_nav_menus_scripts' ) );
			} else {
				add_action( 'load-customize.php', array( &$this, 'enqueue_nav_menus_scripts' ) );
			}

			add_action( 'wp_update_nav_menu_item', array( &$this, 'update_nav_menu_item' ), 10, 3 );

			add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'wp_nav_menu_item_custom_fields' ), 20, 5 );

			// waiting wp.org answer.
			/* add_action( 'wp_nav_menu_item_custom_fields_customize_template', array( $this, 'wp_nav_menu_item_custom_fields_customize_template' ), 20 ); */
		}


		/**
		 * Add and localize script um_admin_nav_manus
		 *
		 * @hook  admin_enqueue_scripts
		 * @see   Admin_Navmenu::enqueue_nav_menus_scripts()
		 */
		public function admin_enqueue_scripts() {
			UM()->admin_enqueue()->load_nav_manus_scripts();

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
		 * Helper method to add and localize script um_admin_nav_manus
		 *
		 * @hook  load-nav-menus.php
		 * @hook  load-customize.php
		 */
		public function enqueue_nav_menus_scripts() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
		}


		/**
		 * Prints scripts or data after the default footer scripts.
		 * Backward compatibility with WP < 5.4
		 *
		 * @hook  admin_footer-nav-menus.php
		 */
		public function render_nav_menu_item_settings() {
			$columns  = 2;
			$options  = UM()->roles()->get_roles( false );
			$per_page = ceil( count( $options ) / $columns );
			?>

			<script type="text/html" id="tmpl-um-nav-menus-fields">
				<div class="um-nav-edit">
					<div class="clear"></div>

					<h4 style="margin-bottom: 0.6em;"><?php esc_html_e( 'Ultimate Member Menu Settings', 'ultimate-member' ); ?></h4>

					<p class="description description-wide um-nav-mode">
						<label for="edit-menu-item-um_nav_public-{{data.menuItemID}}">
							<?php esc_html_e( 'Who can see this menu link?', 'ultimate-member' ); ?><br/>
							<select id="edit-menu-item-um_nav_public-{{data.menuItemID}}" name="menu-item-um_nav_public[{{data.menuItemID}}]" style="width:100%;">
								<option value="0" <# if( data.restriction_data.um_nav_public == '0' ){ #>selected="selected"<# } #>><?php esc_html_e( 'Everyone', 'ultimate-member' ); ?></option>
								<option value="1" <# if( data.restriction_data.um_nav_public == '1' ){ #>selected="selected"<# } #>><?php esc_html_e( 'Logged Out Users', 'ultimate-member' ); ?></option>
								<option value="2" <# if( data.restriction_data.um_nav_public == '2' ){ #>selected="selected"<# } #>><?php esc_html_e( 'Logged In Users', 'ultimate-member' ); ?></option>
							</select>
						</label>
					</p>

					<p class="description description-wide um-nav-roles" <# if( data.restriction_data.um_nav_public == '2' ){ #>style="display: block;"<# } #>>
						<?php esc_html_e( 'Select the member roles that can see this link', 'ultimate-member' ); ?>
						<br/>

						<?php
						$i    = 0;
						$html = '';
						while ( $i < $columns ) {
							$section_fields_per_page = array_slice( $options, $i * $per_page, $per_page );

							$html .= '<span class="um-form-fields-section" style="width:' . floor( 100 / $columns ) . '% !important;">';

							foreach ( $section_fields_per_page as $k => $title ) {
								$id_attr  = ' id="edit-menu-item-um_nav_roles-{{data.menuItemID}}_' . sanitize_key( $k ) . '" ';
								$for_attr = ' for="edit-menu-item-um_nav_roles-{{data.menuItemID}}_' . sanitize_key( $k ) . '" ';

								$html .= '<label ' . $for_attr . '>'
									. '<input type="checkbox" name="menu-item-um_nav_roles[{{data.menuItemID}}][' . sanitize_key( $k ) . ']" value="1" ' . $id_attr . ' <# if( _.contains( data.restriction_data.um_nav_roles, "' . sanitize_key( $k ) . '" ) ){ #>checked="checked"<# } #> />'
									. '<span>' . esc_html( $title ) . '</span>'
									. '</label>';
							}

							$html .= '</span>';
							$i++;
						}
						echo $html;
						?>
					</p>

					<div class="clear"></div>
				</div>
			</script>

			<?php
		}


		/**
		 * Fires after a navigation menu item has been updated.
		 * Backward compatibility with WP < 5.4
		 *
		 * @hook  wp_update_nav_menu_item
		 *
		 * @param int   $menu_id          ID of the updated menu.
		 * @param int   $menu_item_db_id  ID of the updated menu item.
		 * @param array $menu_item_args   An array of arguments used to update a menu item.
		 */
		public function update_nav_menu_item( $menu_id, $menu_item_db_id, $menu_item_args ) {
			check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}

			if ( empty( $_POST['menu-item-db-id'] ) || ! in_array( (string) $menu_item_db_id, $_POST['menu-item-db-id'], true ) ) {
				return;
			}

			$fields = array(
				'um_nav_public' => __( 'Display Mode', 'ultimate-member' ),
				'um_nav_roles'  => __( 'By Role', 'ultimate-member' ),
			);

			foreach ( $fields as $_key => $label ) {

				$key = sprintf( 'menu-item-%s', $_key );
				if ( empty( $_POST[ $key ] ) ) {
					continue;
				}

				// Sanitize.
				$data = is_array( $_POST[ $key ] ) ? map_deep( wp_unslash( $_POST[ $key ] ), 'sanitize_key' ) : array();
				if ( ! empty( $data[ $menu_item_db_id ] ) ) {
					$value = is_array( $data[ $menu_item_db_id ] ) ? array_keys( $data[ $menu_item_db_id ] ) : sanitize_key( $data[ $menu_item_db_id ] );
				} else {
					$value = null;
				}

				// Update.
				if ( ! is_null( $value ) ) {
					update_post_meta( $menu_item_db_id, $key, $value );
				} else {
					delete_post_meta( $menu_item_db_id, $key );
				}
			}
		}


		/**
		 * Render a block "Ultimate Member Menu Settings".
		 * Fires just before the move buttons of a nav menu item in the menu editor.
		 *
		 * @since WP 5.4.0
		 * @hook  wp_nav_menu_item_custom_fields
		 *
		 * @param int      $item_id  Menu item ID.
		 * @param WP_Post  $item     Menu item data object.
		 * @param int      $depth    Depth of menu item. Used for padding.
		 * @param stdClass $args     An object of menu item arguments.
		 * @param int      $id       Nav menu ID.
		 */
		public function wp_nav_menu_item_custom_fields( $item_id, $item, $depth, $args, $id = null ) {

			$um_nav_public   = absint( get_post_meta( $item->ID, 'menu-item-um_nav_public', true ) );
			$_nav_roles_meta = get_post_meta( $item->ID, 'menu-item-um_nav_roles', true );

			$um_nav_roles = array();
			if ( is_array( $_nav_roles_meta ) ) {
				foreach ( $_nav_roles_meta as $key => $value ) {
					if ( is_int( $key ) ) {
						$um_nav_roles[] = $value;
					}
				}
			}

			$columns  = apply_filters( 'wp_nav_menu_item_um_nav_columns', 2, $item_id, $item );
			$options  = UM()->roles()->get_roles( false );
			$per_page = ceil( count( $options ) / $columns );
			?>

			<div class="um-nav-edit">
				<div class="clear"></div>

				<h4 style="margin-bottom:0.6em;"><?php esc_html_e( 'Ultimate Member Menu Settings', 'ultimate-member' ); ?></h4>

				<p class="description description-wide um-nav-mode">
					<label for="edit-menu-item-um_nav_public-<?php echo absint( $item_id ); ?>">
						<?php esc_html_e( 'Who can see this menu link?', 'ultimate-member' ); ?>
						<br/>
						<select id="edit-menu-item-um_nav_public-<?php echo absint( $item_id ); ?>" name="menu-item-um_nav_public[<?php echo absint( $item_id ); ?>]" style="width:100%;">
							<option value="0" <?php selected( $um_nav_public, 0 ); ?>><?php esc_html_e( 'Everyone', 'ultimate-member' ); ?></option>
							<option value="1" <?php selected( $um_nav_public, 1 ); ?>><?php esc_html_e( 'Logged Out Users', 'ultimate-member' ); ?></option>
							<option value="2" <?php selected( $um_nav_public, 2 ); ?>><?php esc_html_e( 'Logged In Users', 'ultimate-member' ); ?></option>
						</select>
					</label>
				</p>

				<p class="description description-wide um-nav-roles" style="<?php echo esc_attr( 2 === $um_nav_public ? 'display: block;' : '' ); ?>">
					<?php esc_html_e( 'Select the member roles that can see this link', 'ultimate-member' ); ?>
					<br>

					<?php
					$i    = 0;
					$html = '';
					while ( $i < $columns ) {
						$section_fields_per_page = array_slice( $options, $i * $per_page, $per_page );

						$html .= '<span class="um-form-fields-section" style="width:' . floor( 100 / $columns ) . '% !important;">';

						foreach ( $section_fields_per_page as $k => $title ) {
							$id_attr      = ' id="edit-menu-item-um_nav_roles-' . absint( $item_id ) . '_' . sanitize_key( $k ) . '" ';
							$for_attr     = ' for="edit-menu-item-um_nav_roles-' . absint( $item_id ) . '_' . sanitize_key( $k ) . '" ';
							$checked_attr = checked( in_array( $k, $um_nav_roles, true ), true, false );

							$html .= '<label ' . $for_attr . '>'
								. '<input type="checkbox" name="menu-item-um_nav_roles[' . absint( $item_id ) . '][' . sanitize_key( $k ) . ']" value="1" ' . $id_attr . $checked_attr . '/>'
								. '<span>' . esc_html( $title ) . '</span>'
								. '</label>';
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
		 * Render a wrapper for the "Ultimate Member Menu Settings" conditional fields.
		 * Fires at the end of the form field template for nav menu items in the customizer.
		 *
		 * @hook  wp_nav_menu_item_custom_fields_customize_template
		 */
		public function wp_nav_menu_item_custom_fields_customize_template() {
			?>
			<div class="um-nav-edit">
				<div class="clear"></div>
				<h4 style="margin-bottom: 0.6em;"><?php esc_html_e( 'Ultimate Member Menu Settings', 'ultimate-member' ); ?></h4>

				<# console.log( data ); #>

				<div class="clear"></div>
			</div>
			<?php
		}

	}
}
