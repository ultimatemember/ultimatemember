<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Layouts.
 *
 * @package um\frontend
 */
class Layouts {

	/**
	 * Dropdown menu.
	 *
	 * Note: !!!!Important: all links in the dropdown items must have "class" attribute.
	 *
	 * @param string           $element Additional class for `um-dropdown-toggle` element to make the dropdown unique. Unique event callback is assigned to this class.
	 * @param array[]|string[] $items   Dropdown Menu Items.
	 * @param array            $args    {
	 *     Dropdown Menu additional arguments.
	 *
	 *     @type string $event  Event in JS that will be used for trigger displaying menu. Uses jQuery events, 'click' by default.
	 *     @type string $header HTML that would be used as the dropdown menu header.
	 *     @type int    $width  Dropdown menu predefined width.
	 *     @type string $parent Parent element for rendering dropdown menu after show event. If empty then <body>.
	 * }
	 *
	 * @return string
	 */
	public static function dropdown_menu( $element, $items, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'event'  => 'click',
				'header' => '',
				'width'  => 150,
				'parent' => '',
			)
		);

		if ( empty( $items ) ) {
			return '';
		}

		$trigger = $args['event'];
		$parent  = $args['parent'];

		ob_start();
		?>
		<div class="um-dropdown-wrapper">
			<div class="um-dropdown-toggle <?php echo esc_attr( $element ); ?>"></div>
			<div class="um-dropdown<?php if ( empty( $args['header'] ) ) { ?> um-dropdown-no-header<?php } ?>" data-element=".<?php echo esc_attr( $element ); ?>" data-trigger="<?php echo esc_attr( $trigger ); ?>" data-parent="<?php echo esc_attr( $parent ); ?>" data-width="<?php echo esc_attr( $args['width'] ); ?>">
				<?php if ( ! empty( $args['header'] ) ) { ?>
					<div class="um-dropdown-header">
						<?php echo wp_kses( $args['header'], UM()->get_allowed_html( 'templates' ) ); ?>
					</div>
				<?php } ?>
				<ul>
					<?php
					$i = 0;
					foreach ( $items as $v ) {
						if ( is_array( $v ) ) {
							foreach ( $v as $sub_v ) {
								?>
								<li><?php echo wp_kses_post( $sub_v ); ?></li>
								<?php
							}
							if ( count( $items ) - 1 !== $i ) {
								?>
								</ul>
								<ul>
								<?php
							}
						} else {
							?>
							<li><?php echo wp_kses_post( $v ); ?></li>
							<?php
						}
						$i++;
					}
					?>
				</ul>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Button element.
	 *
	 * Note: Uses <button> HTML tag.
	 *
	 * @param string $content HTML inner content of the button.
	 * @param array  $args    {
	 *     Button additional arguments.
	 *
	 *     @type string   $type     HTML button type attribute. Uses 'button', 'submit'. Default 'button'.
	 *     @type bool     $primary  Marker for using primary or secondary UI. Default `false`.
	 *     @type string   $size     Button size. Uses 'm', 'l', 'xl'. Default 'l'.
	 *     @type string[] $classes  Additional button's classes.
	 *     @type bool     $disabled Disabled button attribute. Default `false`.
	 * }
	 *
	 * @return string
	 */
	public static function button( $content, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'     => 'button',
				'primary'  => false,
				'size'     => 'l',
				'classes'  => array(),
				'disabled' => false,
			)
		);

		$classes = array(
			'um-button',
			'um-button-size-' . $args['size'],
		);
		if ( false !== $args['primary'] ) {
			$classes[] = 'um-button-primary';
		}
		if ( ! empty( $args['classes'] ) ) {
			$classes = array_merge( $classes, $args['classes'] );
		}
		$classes = implode( ' ', $classes );
		ob_start();
		?>
		<button type="<?php echo esc_attr( $args['type'] ); ?>" class="<?php echo esc_attr( $classes ); ?>" <?php disabled( $args['disabled'] ); ?>><?php echo wp_kses_post( $content ); ?></button>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX loader element.
	 *
	 * @param string $size Spinner size. Uses 'm', 'l', 'xl'. Default 'l'.
	 *
	 * @return string
	 */
	public static function ajax_loader( $size = 'l' ) {
		if ( ! in_array( $size, array( 's', 'm', 'l', 'xl' ), true ) ) {
			return '';
		}

		static $content = array();

		if ( ! empty( $content[ $size ] ) ) {
			return $content[ $size ];
		}

		ob_start();
		?>
		<span class="um-ajax-spinner-svg um-ajax-spinner-<?php echo esc_attr( $size ); ?>">
			<?php
			if ( 'm' === $size ) {
				?>
				<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="none">
					<path d="M30 16C30 17.8385 29.6379 19.659 28.9343 21.3576C28.2308 23.0561 27.1995 24.5995 25.8995 25.8995C24.5995 27.1995 23.0561 28.2307 21.3576 28.9343C19.659 29.6379 17.8385 30 16 30C14.1615 30 12.341 29.6379 10.6424 28.9343C8.94387 28.2307 7.40052 27.1995 6.1005 25.8995C4.80048 24.5995 3.76925 23.0561 3.06569 21.3576C2.36212 19.659 2 17.8385 2 16C2 14.1615 2.36212 12.341 3.06569 10.6424C3.76926 8.94387 4.80049 7.40052 6.10051 6.1005C7.40053 4.80048 8.94388 3.76925 10.6424 3.06568C12.341 2.36212 14.1615 2 16 2C17.8385 2 19.659 2.36212 21.3576 3.06569C23.0561 3.76926 24.5995 4.80049 25.8995 6.10051C27.1995 7.40053 28.2308 8.94388 28.9343 10.6424C29.6379 12.341 30 14.1615 30 16L30 16Z" stroke="var(--um-gray-100,#f2f4f7)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M16 2C17.8385 2 19.659 2.36212 21.3576 3.06569C23.0561 3.76925 24.5995 4.80049 25.8995 6.10051C27.1995 7.40053 28.2308 8.94388 28.9343 10.6424C29.6379 12.341 30 14.1615 30 16" stroke="var(--um-primary-600-bg,#7f56d9)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<?php
			} elseif ( 's' === $size || 'l' === $size ) {
				?>
				<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none">
					<path d="M45 24C45 26.7578 44.4568 29.4885 43.4015 32.0364C42.3461 34.5842 40.7993 36.8992 38.8492 38.8492C36.8992 40.7993 34.5842 42.3461 32.0364 43.4015C29.4885 44.4568 26.7578 45 24 45C21.2422 45 18.5115 44.4568 15.9636 43.4015C13.4158 42.3461 11.1008 40.7993 9.15075 38.8492C7.20072 36.8992 5.65388 34.5842 4.59853 32.0363C3.54318 29.4885 3 26.7578 3 24C3 21.2422 3.54318 18.5115 4.59853 15.9636C5.65388 13.4158 7.20073 11.1008 9.15076 9.15075C11.1008 7.20072 13.4158 5.65387 15.9637 4.59853C18.5115 3.54318 21.2423 3 24 3C26.7578 3 29.4885 3.54318 32.0364 4.59853C34.5842 5.65388 36.8992 7.20073 38.8493 9.15077C40.7993 11.1008 42.3461 13.4158 43.4015 15.9637C44.4568 18.5115 45 21.2423 45 24L45 24Z" stroke="var(--um-gray-100,#f2f4f7)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M24 3C26.7578 3 29.4885 3.54318 32.0364 4.59853C34.5842 5.65388 36.8992 7.20073 38.8492 9.15076C40.7993 11.1008 42.3461 13.4158 43.4015 15.9637C44.4568 18.5115 45 21.2422 45 24" stroke="var(--um-primary-600-bg,#7f56d9)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<?php
			} elseif ( 'xl' === $size ) {
				?>
				<svg xmlns="http://www.w3.org/2000/svg" width="66" height="66" viewBox="0 0 66 66" fill="none">
					<path d="M62 33C62 36.8083 61.2499 40.5794 59.7925 44.0978C58.3351 47.6163 56.199 50.8132 53.5061 53.5061C50.8132 56.199 47.6163 58.3351 44.0978 59.7925C40.5794 61.2499 36.8083 62 33 62C29.1917 62 25.4206 61.2499 21.9022 59.7925C18.3837 58.3351 15.1868 56.199 12.4939 53.5061C9.801 50.8132 7.66488 47.6163 6.20749 44.0978C4.7501 40.5794 4 36.8083 4 33C4 29.1917 4.75011 25.4206 6.2075 21.9022C7.66489 18.3837 9.80101 15.1868 12.4939 12.4939C15.1868 9.801 18.3837 7.66487 21.9022 6.20749C25.4206 4.7501 29.1917 4 33 4C36.8083 4 40.5794 4.75011 44.0978 6.2075C47.6163 7.66489 50.8132 9.80101 53.5061 12.4939C56.199 15.1868 58.3351 18.3838 59.7925 21.9022C61.2499 25.4206 62 29.1917 62 33L62 33Z" stroke="var(--um-gray-100,#f2f4f7)" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M33 4C36.8083 4 40.5794 4.75011 44.0978 6.20749C47.6163 7.66488 50.8132 9.80101 53.5061 12.4939C56.199 15.1868 58.3351 18.3837 59.7925 21.9022C61.2499 25.4206 62 29.1917 62 33" stroke="var(--um-primary-600-bg,#7f56d9)" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<?php
			}
			?>
		</span>
		<?php
		$content[ $size ] = ob_get_clean();

		return $content[ $size ];
	}

	/**
	 * Box element.
	 *
	 * @param string $content Box HTML content.
	 * @param array  $args    {
	 *     Box additional arguments.
	 *
	 *     @type string           $title   Box title. Displayed in header if not empty.
	 *     @type string[]         $classes Additional box classes.
	 *     @type string           $footer  Box footer content. Displayed in if not empty.
	 *     @type array[]|string[] $actions Dropdown menu actions. Displayed in header if not empty.
	 * }
	 *
	 * @return string
	 */
	public static function box( $content, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'title'   => '',
				'classes' => array(),
				'footer'  => '',
				'actions' => array(),
			)
		);

		$classes = array(
			'um-box',
		);
		if ( empty( $args['footer'] ) ) {
			$classes[] = 'um-box-no-footer';
		}

		if ( ! empty( $args['classes'] ) ) {
			$classes = array_merge( $classes, $args['classes'] );
		}
		$classes = implode( ' ', $classes );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>">
			<div class="um-box-header<?php if ( empty( $args['title'] ) ) { ?> um-box-no-title<?php } ?><?php if ( empty( $args['actions'] ) ) { ?> um-box-no-actions<?php } ?>">
				<?php if ( ! empty( $args['title'] ) ) { ?>
					<span class="um-box-title">
						<?php echo esc_html( $args['title'] ); ?>
					</span>
				<?php } ?>

				<?php
				if ( ! empty( $args['actions'] ) ) {
					echo self::dropdown_menu( 'um-box-dropdown-toggle', $args['actions'] );
				}
				?>
			</div>
			<div class="um-box-content">
				<?php echo wp_kses( $content, UM()->get_allowed_html( 'templates' ) ); ?>
			</div>
			<?php if ( ! empty( $args['footer'] ) ) { ?>
				<div class="um-box-footer">
					<?php echo wp_kses( $args['footer'], UM()->get_allowed_html( 'templates' ) ); ?>
				</div>
			<?php } ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Avatar layout.
	 *
	 * @param bool|int $user_id User ID.
	 * @param array    $args    {
	 *     Avatar additional arguments.
	 *
	 *     @type string   $size          Avatar size. Uses 's', 'm', 'l', 'xl'. Default 'm'.
	 *     @type string   $type          Avatar type. Uses 'square', 'round'. Default 'round'.
	 *     @type string[] $wrapper_class Avatar wrapper additional classes.
	 * }
	 *
	 * @return string
	 */
	public static function single_avatar( $user_id = false, $args = array() ) {
		if ( false === $user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'size'          => 'm',
				'type'          => 'round',
				'wrapper_class' => array(),
			)
		);

		$wrapper_classes = array(
			'um-avatar',
			'um-avatar-' . $args['size'],
			'um-avatar-' . $args['type'],
		);
		if ( ! empty( $args['wrapper_class'] ) ) {
			$wrapper_classes = array_merge( $wrapper_classes, $args['wrapper_class'] );
		}
		$wrapper_classes = implode( ' ', $wrapper_classes );

		$thumb_size = 32;
		if ( 's' === $args['size'] ) {
			$thumb_size = 24;
		} elseif ( 'l' === $args['size'] ) {
			$thumb_size = 64;
		} elseif ( 'xl' === $args['size'] ) {
			$thumb_size = 128;
		}

		$avatar = get_avatar( $user_id, $thumb_size, '', '', array( 'loading' => 'lazy' ) );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_classes ); ?>" data-user_id="<?php echo esc_attr( $user_id ); ?>">
			<?php echo $avatar; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Avatar uploader layout.
	 *
	 * @param bool|int $user_id User ID.
	 *
	 * @return string
	 */
	public static function avatar_uploader( $user_id = false ) {
		if ( false === $user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return '';
		}

		$items = array();
		if ( UM()->common()->users()->has_photo( $user_id, 'profile_photo' ) ) {
			$items[] = '<a href="#" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . esc_html__( 'Change photo', 'ultimate-member' ) . '</a>';
			$items[] = '<a href="#" class="um-reset-profile-photo" data-user_id="' . esc_attr( $user_id ) . '" data-nonce="' . wp_create_nonce( 'um_remove_profile_photo' ) . '">' . esc_html__( 'Remove photo', 'ultimate-member' ) . '</a>';
		} else {
			$items[] = '<a href="#" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . esc_html__( 'Set photo', 'ultimate-member' ) . '</a>';
		}

		/**
		 * Filters action links in dropdown menu for profile photo.
		 *
		 * @since 1.3.x
		 * @since 2.8.3 added $user_id attribute
		 * @hook um_user_photo_menu_edit
		 *
		 * @param {array} $items   Action links in dropdown for profile photo.
		 * @param {int}   $user_id User ID. Since 2.8.3.
		 *
		 * @example <caption>Make any custom action after delete cover photo.</caption>
		 * function my_custom_user_photo_menu_edit( $items, $user_id ) {
		 *     $items[] = '<a href="#" class="um-custom-action" data-user_id="' . esc_attr( $user_id ) . '">' . esc_html__( 'Custom action', 'ultimate-member' ) . '</a>';
		 *     return $items;
		 * }
		 * add_filter( 'um_user_photo_menu_edit', 'my_custom_user_photo_menu_edit', 10, 2 );
		 */
		$items = apply_filters( 'um_user_photo_menu_edit', $items, $user_id );

		$uploader_overflow = '';
		if ( ! UM()->options()->get( 'disable_profile_photo_upload' ) ) {
			$title = __( 'Set photo', 'ultimate-member' );
			if ( UM()->common()->users()->has_photo( $user_id, 'profile_photo' ) ) {
				$title = __( 'Change photo', 'ultimate-member' );
			}
			$uploader_overflow = '<div class="um-profile-photo-uploader-overflow" title="' . esc_attr( $title ) . '" data-user_id="' . esc_attr( $user_id ) . '" data-nonce="' . wp_create_nonce( 'um_upload_profile_photo' ) . '" data-apply_nonce="' . wp_create_nonce( 'um_upload_profile_photo_apply' ) . '" data-decline_nonce="' . wp_create_nonce( 'um_upload_profile_photo_decline' ) . '">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M2 8.37722C2 8.0269 2 7.85174 2.01462 7.70421C2.1556 6.28127 3.28127 5.1556 4.70421 5.01462C4.85174 5 5.03636 5 5.40558 5C5.54785 5 5.61899 5 5.67939 4.99634C6.45061 4.94963 7.12595 4.46288 7.41414 3.746C7.43671 3.68986 7.45781 3.62657 7.5 3.5C7.54219 3.37343 7.56329 3.31014 7.58586 3.254C7.87405 2.53712 8.54939 2.05037 9.32061 2.00366C9.38101 2 9.44772 2 9.58114 2H14.4189C14.5523 2 14.619 2 14.6794 2.00366C15.4506 2.05037 16.126 2.53712 16.4141 3.254C16.4367 3.31014 16.4578 3.37343 16.5 3.5C16.5422 3.62657 16.5633 3.68986 16.5859 3.746C16.874 4.46288 17.5494 4.94963 18.3206 4.99634C18.381 5 18.4521 5 18.5944 5C18.9636 5 19.1483 5 19.2958 5.01462C20.7187 5.1556 21.8444 6.28127 21.9854 7.70421C22 7.85174 22 8.0269 22 8.37722V16.2C22 17.8802 22 18.7202 21.673 19.362C21.3854 19.9265 20.9265 20.3854 20.362 20.673C19.7202 21 18.8802 21 17.2 21H6.8C5.11984 21 4.27976 21 3.63803 20.673C3.07354 20.3854 2.6146 19.9265 2.32698 19.362C2 18.7202 2 17.8802 2 16.2V8.37722Z" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M12 16.5C14.2091 16.5 16 14.7091 16 12.5C16 10.2909 14.2091 8.5 12 8.5C9.79086 8.5 8 10.2909 8 12.5C8 14.7091 9.79086 16.5 12 16.5Z" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>';
		}

		ob_start();
		?>
		<div class="um-profile-photo-uploader">
			<div class="um-profile-photo-wrapper">
				<?php echo self::single_avatar( $user_id, array( 'size' => 'xl', 'wrapper_class' => array( 'um-profile-photo' ) ) ); ?>
				<?php echo $uploader_overflow; ?>
				<div class="um-profile-photo-overflow">
					<?php echo self::ajax_loader(); ?>
				</div>
			</div>
			<?php /*echo self::dropdown_menu( 'um-avatar-dropdown-toggle', $items );*/ ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * New menu JS
	 *
	 * @param string $element
	 * @param string $trigger
	 * @param string $item
	 * @param string $additional_attributes
	 * @param string $parent
	 */
	public static function dropdown_menu_js( $element, $trigger, $item, $additional_attributes = '', $parent = '' ) {
		?>

		<div class="um-new-dropdown" data-element="<?php echo $element; ?>" data-trigger="<?php echo $trigger; ?>" data-parent="<?php echo $parent; ?>">
			<ul>
				<# _.each( <?php echo $item; ?>.dropdown_actions, function( action, key, list ) { #>
				<li><a href="<# if ( typeof action.url != 'undefined' ) { #>{{{action.url}}}<# } else { #>javascript:void(0);<# }#>" class="{{{key}}}"<?php echo $additional_attributes ? " $additional_attributes" : '' ?>>{{{action.title}}}</a></li>
				<# }); #>
			</ul>
		</div>

		<?php
	}
}
