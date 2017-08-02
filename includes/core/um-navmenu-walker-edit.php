<?php

class UM_Menu_Item_Custom_Fields_Editor {

	protected static $fields = array();


	/**
	 * Initialize plugin
	 */
	public static function init() {
		add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, '_fields' ), 1, 4 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, '_save' ), 10, 3 );
		add_filter( 'manage_nav-menus_columns', array( __CLASS__, '_columns' ), 99 );

		self::$fields = array(
		
			'um_nav_public' => __( 'Display Mode'),
			'um_nav_roles' => __('By Role')
			
		);
	}


	public static function _save( $menu_id, $menu_item_db_id, $menu_item_args ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		foreach ( self::$fields as $_key => $label ) {
			
			if( $_key == 'um_nav_roles' ){

				$key = sprintf( 'menu-item-%s', $_key );
                
				// Sanitize
				if ( ! empty( $_POST[ $key ][ $menu_item_db_id ] ) ) {
					// Do some checks here...
					$value = $_POST[ $key ][ $menu_item_db_id ];
				}
				else {
					$value = null;
				}

			}else{
				
				$key = sprintf( 'menu-item-%s', $_key );
				
				// Sanitize
				if ( ! empty( $_POST[ $key ][ $menu_item_db_id ] ) ) {
					// Do some checks here...
					$value = $_POST[ $key ][ $menu_item_db_id ];
				}
				else {
					$value = null;
				}
			}

			// Update
			if ( ! is_null( $value ) ) {
				update_post_meta( $menu_item_db_id, $key, $value );
			}
			else {
				delete_post_meta( $menu_item_db_id, $key );
			}
		}
	}

	public static function _fields( $id, $item, $depth, $args ) {
		?>
		
		<div class="um-nav-edit">
		
			<div class="clear"></div>

			<h4 style="margin-bottom: 0.6em;"><?php _e( "Ultimate Member Menu Settings", 'ultimate-member' ) ?></h4>

		<?php foreach ( self::$fields as $_key => $label ) {
			$key = sprintf('menu-item-%s', $_key);
			$id = sprintf('edit-%s-%s', $key, $item->ID);
			$name = sprintf('%s[%s]', $key, $item->ID);
			$value = get_post_meta($item->ID, $key, true);
			$role_name = sprintf('%s[%s][]', $key, $item->ID);
			$class = sprintf('field-%s', $_key); ?>

			<?php if ( $_key == 'um_nav_public' ) { ?>

				<p class="description description-wide um-nav-mode">
					<label for="<?php echo $id ?>">
						<?php _e( "Who can see this menu link?", 'ultimate-member' ); ?><br/>
						<select id="<?php echo $id ?>" name="<?php echo $name ?>" style="width:100%;">
							<option value="0" <?php selected(!isset($value) || $value == ''); ?>>
								<?php _e( 'Everyone', 'ultimate-member' ) ?>
							</option>
							<option value="1" <?php selected(1, $value); ?>>
								<?php _e( 'Logged Out Users', 'ultimate-member' ) ?>
							</option>
							<option value="2" <?php selected(2, $value); ?>>
								<?php _e( 'Logged In Users', 'ultimate-member' ) ?>
							</option>
						</select>
					</label>
				</p>

			<?php }

			if ( $_key == 'um_nav_roles' ) { ?>

                <p class="description description-wide um-nav-roles">
                    <?php _e( "Select the member roles that can see this link", 'ultimate-member' ) ?><br />

                    <?php $options = UM()->roles()->get_roles();
                    $i = 0;
                    $html = '';
                    $columns = 2;
                    while ( $i < $columns ) {
                        $per_page = ceil( count( $options ) / $columns );
                        $section_fields_per_page = array_slice( $options, $i*$per_page, $per_page );
                        $html .= '<span class="um-form-fields-section" style="width:' . floor( 100 / $columns ) . '% !important;">';

                        foreach ( $section_fields_per_page as $k => $title ) {
                            $id_attr = ' id="' . $id . '_' . $k . '" ';
                            $for_attr = ' for="' . $id . '_' . $k . '" ';
                            $name_attr = ' name="' . $role_name . '" ';

                            $html .= "<label $for_attr>
                                <input type=\"checkbox\" " . checked( ( is_array( $value ) && in_array( $k, $value ) ) || ( isset( $value ) && $k == $value ), true, false ) . "$id_attr $name_attr value=\"" . $k . "\">
                                <span>$title</span>
                            </label>";
                        }

                        $html .= '</span>';
                        $i++;
                    }

                    echo $html; ?>
                </p>
			<?php }
		} ?>
		
		</div>
		
		<?php
	}

	public static function _columns( $columns ) {
		$columns = array_merge( $columns, self::$fields );

		return $columns;
	}
}
UM_Menu_Item_Custom_Fields_Editor::init();