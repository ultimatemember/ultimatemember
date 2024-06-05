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
	 *     @type string $type   View type for dropdown. Can be 'dots' or 'button' or 'link'. It's 'dots' by default.
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
				'type'         => 'dots',
				'button_label' => '',
				'event'        => 'click',
				'header'       => '',
				'width'        => 150,
				'parent'       => '',
			)
		);

		if ( empty( $items ) ) {
			return '';
		}

		$trigger        = $args['event'];
		$parent         = $args['parent'];
		$toggle_classes = array( 'um-dropdown-toggle', 'um-dropdown-toggle-' . $args['type'], $element );

		$type_html = '';
		if ( 'dots' !== $args['type'] ) {
			if ( ! empty( $args['button_label'] ) ) {
				if ( 'button' === $args['type'] ) {
					$type_html = self::button(
						$args['button_label'],
						array(
							'type'          => 'button',
							'icon'          => '<span class="um-dropdown-chevron"></span>',
							'icon_position' => 'trailing',
							'design'        => 'secondary-gray',
							'size'          => 's',
						)
					);
				} elseif ( 'link' === $args['type'] ) {
					$type_html = self::link(
						$args['button_label'],
						array(
							'type'          => 'button',
							'icon'          => '<span class="um-dropdown-chevron"></span>',
							'icon_position' => 'trailing',
							'design'        => 'link-gray',
							'size'          => 's',
						)
					);
				}
			}
		}

		$dropdown_classes = array( 'um-dropdown' );
		if ( empty( $args['header'] ) ) {
			$dropdown_classes[] = 'um-dropdown-no-header';
		}
		ob_start();
		?>
		<div class="um-dropdown-wrapper">
			<div class="<?php echo esc_attr( implode( ' ', $toggle_classes ) ); ?>"><?php echo wp_kses( $type_html, UM()->get_allowed_html( 'templates' ) ); ?></div>
			<div class="<?php echo esc_attr( implode( ' ', $dropdown_classes ) ); ?>" data-element=".<?php echo esc_attr( $element ); ?>" data-trigger="<?php echo esc_attr( $trigger ); ?>" data-parent="<?php echo esc_attr( $parent ); ?>" data-width="<?php echo esc_attr( $args['width'] ); ?>">
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
	 *     @type string   $type     HTML button type attribute. Uses 'button','submit','link','icon-button','icon-link'. Default 'button'. button||submit||reset
	 *     @type string   $design   Button UI type. Default 'secondary-gray'. Uses 'primary','secondary-gray','secondary-color','tertiary-gray','tertiary-color','link-gray','link-color','primary-destructive','secondary-destructive','tertiary-destructive','link-destructive'.
	 *     @type string   $size     Button size. Uses 's','m','l','xl'. Default 'l'.
	 *     @type string[] $classes  Additional button's classes.
	 *     @type bool     $disabled Disabled button attribute. Default `false`.
	 *     @type string   $width    Button width. Default empty means based on content. Uses 'full' for making button 100% width.
	 * }
	 *
	 * @return string
	 */
	public static function button( $content, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'          => 'button',
				'icon_position' => null,    // leading || trailing || content
				'icon'          => null,
				'design'        => 'secondary-gray',
				'size'          => 'l',
				'width'         => 'auto',
				'id'            => '',
				'classes'       => array(),
				'data'          => array(),
				'disabled'      => false,
				'title'         => '',
			)
		);

		$classes = array(
			'um-button',
			'um-button-' . $args['design'],
			'um-button-size-' . $args['size'],
		);

		if ( 'full' === $args['width'] ) {
			$classes[] = 'um-full-width';
		}

		$has_icon = false;
		if ( ! empty( $args['icon_position'] ) ) {
			if ( ! empty( $args['icon'] ) && in_array( $args['icon_position'], array( 'leading', 'trailing' ), true ) ) {
				$icon     = '<span class="um-button-icon">' . $args['icon'] . '</span>';
				$has_icon = true;

				$content = '<span class="um-button-content">' . $content . '</span>';
				if ( 'leading' === $args['icon_position'] ) {
					$content = $icon . $content;
				} else {
					$content .= $icon;
				}
			}

			if ( ! $has_icon && 'content' === $args['icon_position'] ) {
				if ( ! empty( $args['icon'] ) && 0 === strpos( $args['icon'], '<svg' ) ) {
					$has_icon = true;
					$content  = $args['icon'];
				} elseif ( ! empty( $content ) && 0 === strpos( $content, '<svg' ) ) {
					$has_icon = true;
				}
			}
		}

		if ( $has_icon ) {
			$classes[] = 'um-button-has-icon';
			$classes[] = 'um-button-icon-' . $args['icon_position'];
		}

		if ( ! empty( $args['classes'] ) ) {
			$classes = array_merge( $classes, $args['classes'] );
		}

		$classes = implode( ' ', $classes );

		$data_atts = array();
		foreach ( $args['data'] as $data_k => $data_v ) {
			$data_atts[] = 'data-' . $data_k . '="' . esc_attr( $data_v ) . '"';
		}
		$data_atts = implode( ' ', $data_atts );

		ob_start();
		?>
		<button id="<?php echo esc_attr( $args['id'] ); ?>" type="<?php echo esc_attr( $args['type'] ); ?>" class="<?php echo esc_attr( $classes ); ?>" title="<?php echo esc_attr( $args['title'] ); ?>" <?php disabled( $args['disabled'] ); ?> <?php echo $data_atts; ?>><?php echo wp_kses( $content, UM()->get_allowed_html( 'templates' ) ); ?></button>
		<?php
		return ob_get_clean();
	}

	/**
	 * Link element.
	 *
	 * Note: Uses <button> HTML tag.
	 *
	 * @param string $content HTML inner content of the button.
	 * @param array  $args    {
	 *     Button additional arguments.
	 *
	 *     @type string   $type     HTML button type attribute. Uses 'button','submit','link','icon-button','icon-link'. Default       'button'||'link'
	 *     @type string   $design   Button UI type. Default 'secondary-gray'. Uses 'primary','secondary-gray','secondary-color','tertiary-gray','tertiary-color','link-gray','link-color','primary-destructive','secondary-destructive','tertiary-destructive','link-destructive'.
	 *     @type string   $size     Button size. Uses 's','m','l','xl'. Default 'l'.
	 *     @type string[] $classes  Additional button's classes.
	 *     @type bool     $disabled Disabled button attribute. Default `false`.
	 *     @type string   $url      Disabled button attribute. Default `#`.
	 *     @type string   $target   Disabled button attribute. Default `false`.
	 *     @type string   $width    Button width. Default empty means based on content. Uses 'full' for making button 100% width.
	 * }
	 *
	 * @return string
	 */
	public static function link( $content, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'          => 'raw',            // values: raw || button
				'icon_position' => null,             // for type="button" values: leading || trailing || content
				'icon'          => null,             // for type="button" + icon_position="leading || trailing"
				'design'        => 'secondary-gray', // for type="button"
				'size'          => 'l',              // for type="button"
				'width'         => '',               // for type="button"
				'id'            => '',
				'classes'       => array(),
				'data'          => array(),
				'disabled'      => false,
				'target'        => '_self',
				'title'         => '',
				'url'           => '#',
			)
		);

		$classes = array( 'um-link' );

		if ( false !== $args['disabled'] ) {
			$classes[] = 'um-link-disabled';
		}

		if ( 'raw' === $args['type'] ) {
			if ( ! empty( $args['classes'] ) ) {
				$classes = array_merge( $classes, $args['classes'] );
			}

			// Handles raw link
			$classes = implode( ' ', $classes );

			$data_atts = array();
			foreach ( $args['data'] as $data_k => $data_v ) {
				$data_atts[] = 'data-' . $data_k . '="' . esc_attr( $data_v ) . '"';
			}
			$data_atts = implode( ' ', $data_atts );

			ob_start();
			?>
			<a id="<?php echo esc_attr( $args['id'] ); ?>" href="<?php echo esc_url( $args['url'] ); ?>" target="<?php echo esc_attr( $args['target'] ); ?>" title="<?php echo esc_attr( $args['title'] ); ?>" class="<?php echo esc_attr( $classes ); ?>" <?php echo $data_atts; ?>><?php echo wp_kses( $content, UM()->get_allowed_html( 'templates' ) ); ?></a>
			<?php
			return ob_get_clean();
		}

		$classes[] = 'um-link-button';
		$classes[] = 'um-link-button-' . $args['design'];
		$classes[] = 'um-link-button-size-' . $args['size'];
		if ( 'full' === $args['width'] ) {
			$classes[] = 'um-full-width';
		}

		$has_icon = false;
		if ( ! empty( $args['icon_position'] ) ) {
			if ( ! empty( $args['icon'] ) && in_array( $args['icon_position'], array( 'leading', 'trailing' ), true ) ) {
				$icon     = '<span class="um-link-button-icon">' . $args['icon'] . '</span>';
				$has_icon = true;

				$content = '<span class="um-link-button-content">' . $content . '</span>';
				if ( 'leading' === $args['icon_position'] ) {
					$content = $icon . $content;
				} else {
					$content .= $icon;
				}
			}

			if ( ! $has_icon && 'content' === $args['icon_position'] ) {
				if ( ! empty( $args['icon'] ) && 0 === strpos( $args['icon'], '<svg' ) ) {
					$has_icon = true;
					$content  = $args['icon'];
				} elseif ( ! empty( $content ) && 0 === strpos( $content, '<svg' ) ) {
					$has_icon = true;
				}
			}
		}

		if ( $has_icon ) {
			$classes[] = 'um-link-button-has-icon';
			$classes[] = 'um-link-button-icon-' . $args['icon_position'];
		}

		if ( ! empty( $args['classes'] ) ) {
			$classes = array_merge( $classes, $args['classes'] );
		}

		$classes = implode( ' ', $classes );

		$data_atts = array();
		foreach ( $args['data'] as $data_k => $data_v ) {
			$data_atts[] = 'data-' . $data_k . '="' . esc_attr( $data_v ) . '"';
		}
		$data_atts = implode( ' ', $data_atts );

		if ( ! empty( $args['icon_leading'] ) || ! empty( $args['icon_trailing'] ) ) {
			$content = '<span class="um-button-content">' . $content . '</span>';
		}

		ob_start();
		?>
		<a id="<?php echo esc_attr( $args['id'] ); ?>" href="<?php echo esc_url( $args['url'] ); ?>" target="<?php echo esc_attr( $args['target'] ); ?>" title="<?php echo esc_attr( $args['title'] ); ?>" class="<?php echo esc_attr( $classes ); ?>" <?php echo $data_atts; ?>><?php echo wp_kses( $content, UM()->get_allowed_html( 'templates' ) ); ?></a>
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
				'id'               => '',
				'title'            => '',
				'classes'          => array(),
				'header'           => '',
				'footer'           => '',
				'actions'          => array(),
				'actions_position' => 'right',
			)
		);

		$classes = array(
			'um-box',
		);

		$has_header = false;
		if ( ! empty( $args['title'] ) || ! empty( $args['actions'] ) || ! empty( $args['header'] ) ) {
			$has_header = true;
		}

		if ( ! $has_header ) {
			$classes[] = 'um-box-no-header';
		}

		if ( empty( $args['footer'] ) ) {
			$classes[] = 'um-box-no-footer';
		}

		if ( ! empty( $args['classes'] ) ) {
			$classes = array_merge( $classes, $args['classes'] );
		}
		$classes = implode( ' ', $classes );

		if ( ! empty( $args['header'] ) || ! empty( $args['footer'] ) ) {
			$content = '<div class="um-box-content">' . $content . '</div>';
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $args['id'] ); ?>" class="<?php echo esc_attr( $classes ); ?>">
			<?php
			if ( $has_header ) {
				$header_classes = array(
					'um-box-header',
				);

				if ( empty( $args['header'] ) ) {
					if ( empty( $args['title'] ) ) {
						$header_classes[] = 'um-box-header-no-title';
					}
					if ( empty( $args['actions'] ) ) {
						$header_classes[] = 'um-box-header-no-actions';
					} else {
						$header_classes[] = 'um-box-header' . $args['actions_position'] . '-actions';
					}
				}
				?>
				<div class="<?php echo esc_attr( implode( ' ', $header_classes ) ); ?>">
					<?php
					if ( ! empty( $args['header'] ) ) {
						echo wp_kses( $args['header'], UM()->get_allowed_html( 'templates' ) );
					} else {
						if ( ! empty( $args['title'] ) ) {
							?>
							<span class="um-box-title"><?php echo wp_kses( $args['title'], UM()->get_allowed_html( 'templates' ) ); ?></span>
							<?php
						}
						if ( ! empty( $args['actions'] ) ) {
							echo wp_kses( self::dropdown_menu( 'um-box-dropdown-toggle', $args['actions'] ), UM()->get_allowed_html( 'templates' ) );
						}
					}
					?>
				</div>
				<?php
			}

			echo wp_kses( $content, UM()->get_allowed_html( 'templates' ) );

			if ( ! empty( $args['footer'] ) ) {
				?>
				<div class="um-box-footer">
					<?php echo wp_kses( $args['footer'], UM()->get_allowed_html( 'templates' ) ); ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function badge( $label, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'size'  => 'm', // s,m,l
				'type'  => 'color', // color, pill-outline, pill-color
				'color' => 'gray', // gray, brand,error,warning,success
				'class' => array(),
			)
		);

		$classes = array(
			'um-badge',
			'um-badge-' . $args['size'],
			'um-badge-' . $args['type'],
			'um-badge-color-' . $args['color'],
		);
		if ( ! empty( $args['class'] ) ) {
			$classes = array_merge( $classes, $args['class'] );
		}
		$classes = implode( ' ', $classes );

		ob_start();
		?>
		<span class="<?php echo esc_attr( $classes ); ?>">
			<?php echo esc_html( $label ); ?>
		</span>
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
				'clickable'     => false,
				'url'           => um_user_profile_url( $user_id ),
				'url_title'     => __( 'Visit profile', 'ultimate-member' ),
			)
		);

		$args['url'] = empty( $args['url'] ) ? um_user_profile_url( $user_id ) : $args['url'];

		$wrapper_classes = array(
			'um-avatar',
			'um-avatar-' . $args['size'],
			'um-avatar-' . $args['type'],
		);
		if ( ! empty( $args['wrapper_class'] ) ) {
			$wrapper_classes = array_merge( $wrapper_classes, $args['wrapper_class'] );
		}
		$wrapper_classes = implode( ' ', $wrapper_classes );

		$thumb_size = 40;
		if ( 's' === $args['size'] ) {
			$thumb_size = 32;
		} elseif ( 'l' === $args['size'] ) {
			$thumb_size = 64;
		} elseif ( 'xl' === $args['size'] ) {
			$thumb_size = 128;
		}

		$avatar = get_avatar( $user_id, $thumb_size, '', '', array( 'loading' => 'lazy' ) );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_classes ); ?>" data-user_id="<?php echo esc_attr( $user_id ); ?>">
			<?php if ( ! empty( $args['clickable'] ) ) { ?>
				<a href="<?php echo esc_url( $args['url'] ); ?>" title="<?php echo esc_attr( $args['url_title'] ); ?>">
			<?php } ?>
			<?php echo $avatar; ?>
			<?php if ( ! empty( $args['clickable'] ) ) { ?>
				</a>
			<?php } ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function small_data( $user_id = null, $args = array() ) {
		if ( is_null( $user_id ) && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'avatar_size' => 'l',
				'clickable'   => get_current_user_id() !== $user_id,
				'url'         => um_user_profile_url( $user_id ),
				'url_title'   => __( 'Visit profile', 'ultimate-member' ),
				'supporting'  => '',
				'classes'     => array(),
			)
		);

		$avatar_args = array(
			'size'      => $args['avatar_size'],
			'clickable' => $args['clickable'],
			'url'       => $args['url'],
			'url_title' => $args['url_title'],
		);

		$wrapper_classes = array( 'um-small-data' );
		if ( empty( $args['supporting'] ) ) {
			$wrapper_classes[] = 'um-data-no-supporting';
		}

		$wrapper_classes = array_merge( $wrapper_classes, $args['classes'] );

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
			<?php
			echo wp_kses(
				self::single_avatar(
					$user_id,
					$avatar_args
				),
				UM()->get_allowed_html( 'templates' )
			);
			um_fetch_user( $user_id );

			if ( ! empty( $args['clickable'] ) ) {
				?>
				<a class="um-user-display-name um-link um-header-link" href="<?php echo esc_url( $args['url'] ); ?>" href="<?php echo esc_attr( $args['url_title'] ); ?>"><?php echo esc_html( um_user( 'display_name' ) ); ?></a>
				<?php
			} else {
				?>
				<span class="um-user-display-name"><?php echo esc_html( um_user( 'display_name' ) ); ?></span>
				<?php
			}

			um_reset_user();
			if ( ! empty( $args['supporting'] ) ) {
				?>
				<span class="um-supporting-text">
					<?php
					echo wp_kses(
						$args['supporting'],
						UM()->get_allowed_html( 'templates' )
					);
					?>
				</span>
				<?php
			}
			?>
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

//		$items = array();
//		if ( UM()->common()->users()->has_photo( $user_id, 'profile_photo' ) ) {
//			$items[] = '<a href="#" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . esc_html__( 'Change photo', 'ultimate-member' ) . '</a>';
//			$items[] = '<a href="#" class="um-reset-profile-photo" data-user_id="' . esc_attr( $user_id ) . '" data-nonce="' . wp_create_nonce( 'um_remove_profile_photo' ) . '">' . esc_html__( 'Remove photo', 'ultimate-member' ) . '</a>';
//		} else {
//			$items[] = '<a href="#" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . esc_html__( 'Set photo', 'ultimate-member' ) . '</a>';
//		}

		/**
		 * Filters action links in dropdown menu for profile photo.
		 *
		 * @since 1.3.x
		 * @since 2.8.4 added $user_id attribute
		 * @hook um_user_photo_menu_edit
		 *
		 * @param {array} $items   Action links in dropdown for profile photo.
		 * @param {int}   $user_id User ID. Since 2.8.4.
		 *
		 * @example <caption>Make any custom action after delete cover photo.</caption>
		 * function my_custom_user_photo_menu_edit( $items, $user_id ) {
		 *     $items[] = '<a href="#" class="um-custom-action" data-user_id="' . esc_attr( $user_id ) . '">' . esc_html__( 'Custom action', 'ultimate-member' ) . '</a>';
		 *     return $items;
		 * }
		 * add_filter( 'um_user_photo_menu_edit', 'my_custom_user_photo_menu_edit', 10, 2 );
		 */
//		$items = apply_filters( 'um_user_photo_menu_edit', $items, $user_id );

		$uploader_overflow = '';
		if ( ! UM()->options()->get( 'disable_profile_photo_upload' ) ) {
			$title = __( 'Set photo', 'ultimate-member' );
			if ( UM()->common()->users()->has_photo( $user_id, 'profile_photo' ) ) {
				$title = __( 'Change photo', 'ultimate-member' );
			}
			$uploader_overflow = '<div class="um-profile-photo-uploader-overflow" title="' . esc_attr( $title ) . '" data-user_id="' . esc_attr( $user_id ) . '" data-nonce="' . wp_create_nonce( 'um_upload_profile_photo' ) . '" data-apply_nonce="' . wp_create_nonce( 'um_upload_profile_photo_apply' ) . '" data-decline_nonce="' . wp_create_nonce( 'um_upload_profile_photo_decline' ) . '">
				<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-camera" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
					<path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1a2 2 0 0 0 2 2h1a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2" />
					<path d="M9 13a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
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

	public static function tabs( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'wrapper_class' => '',
				'orientation'   => 'vertical',
				'color'         => 'primary',
				'tabs'          => array(
					'id' => array(
						'title'   => __( 'Tab title', 'ultimate-member' ),
						'content' => __( 'Tab content', 'ultimate-member' ),
						'url'     => '#',
						'current' => true,
					),
				),
			)
		);

		$wrapper_classes = array(
			'um-tabs-wrapper',
			'um-' . $args['orientation'] . '-tabs',
			'um-' . $args['color'] . '-color-tabs',
		);
		if ( ! empty( $args['wrapper_class'] ) ) {
			$wrapper_classes = array_merge( $wrapper_classes, $args['wrapper_class'] );
		}
		$wrapper_classes = implode( ' ', $wrapper_classes );

		$current_tab = array_keys( $args['tabs'] )[0];
		foreach ( $args['tabs'] as $tab_id => $tab_data ) {
			if ( ! empty( $tab_data['current'] ) ) {
				$current_tab = $tab_id;
			}
		}

		$desktop_list = '';
		$mobile_list  = '';
		$content      = '';
		foreach ( $args['tabs'] as $tab_id => $tab_data ) {
			$current_class = $current_tab === $tab_id ? 'um-current-tab' : '';
			ob_start();
			?>
			<li class="<?php echo esc_attr( $current_class ); ?>">
				<a class="um-link" href="<?php echo esc_url( $tab_data['url'] ); ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>"><?php echo esc_html( $tab_data['title'] ); ?></a>
			</li>
			<?php
			$desktop_list .= ob_get_clean();

			ob_start();
			?>
			<option value="<?php echo esc_attr( $tab_id ); ?>" <?php selected( $current_tab === $tab_id ); ?> data-href="<?php echo esc_url( $tab_data['url'] ); ?>">
				<?php echo esc_html( $tab_data['title'] ); ?>
			</option>
			<?php
			$mobile_list .= ob_get_clean();

			ob_start();
			?>
			<div class="um-tab-content <?php echo esc_attr( $current_class ); ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>"><?php echo wp_kses( $tab_data['content'], UM()->get_allowed_html( 'templates' ) ); ?></div>
			<?php
			$content .= ob_get_clean();
		}

		$list_html = '<ul>' . $desktop_list . '</ul><select>' . $mobile_list . '</select>';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_classes ); ?>">
			<div class="um-tabs-list"><?php echo wp_kses( $list_html, UM()->get_allowed_html( 'templates' ) ); ?></div>
			<div class="um-tabs-content"><?php echo wp_kses( $content, UM()->get_allowed_html( 'templates' ) ); ?></div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function form( $args = array() ) {
		ob_start();

		UM()->frontend()->form()->display();

		?>
		<form action="" class="um-form-new">
			<div class="um-form-rows">
				<div class="um-form-row">
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Disabled input label</label>
							<input id="bbb" type="text" placeholder="Disabled" disabled />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-2">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of Password</label>
							<input id="bbb" type="password" />
							<p class="um-field-hint">Hint message</p>
						</div>
						<div class="um-form-col um-form-col-2">
							<label for="aaa">Label of Email</label>
							<input id="bbb" type="email" />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-3">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of telephone</label>
							<input id="bbb" type="tel" />
							<p class="um-field-hint">Hint message</p>
						</div>
						<div class="um-form-col um-form-col-2">
							<label for="aaa">Label of number</label>
							<input id="bbb" type="number" />
							<p class="um-field-hint">Hint message</p>
						</div>
						<div class="um-form-col um-form-col-3">
							<label for="aaa">Label of text</label>
							<input id="bbb" type="text" placeholder="Placeholder" />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
				</div>

				<div class="um-form-row">
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of url</label>
							<input id="bbb" type="url" />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of textarea</label>
							<textarea placeholder="Put text..."></textarea>
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of date</label>
							<input id="bbb" type="date" />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of time</label>
							<input id="bbb" type="time" />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of month</label>
							<input id="bbb" type="month" />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of week</label>
							<input id="bbb" type="week" />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of datetime-local</label>
							<input id="bbb" type="datetime-local" />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of file</label>
							<input id="bbb" type="file" />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of сolor</label>
							<input id="bbb" type="color" />
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<div class="um-field">
								<label for="aaa">Label of native select</label>
								<select placeholder="select placeholder">
									<option value="">None</option>
									<option value="1">1</option>
									<option value="2">2</option>
									<option value="3">3</option>
									<option value="4">4</option>
									<option value="5">5</option>
									<option value="6">6</option>
									<option value="7">7</option>
									<option value="8">8</option>
									<option value="9">9</option>
									<option value="10">10</option>
								</select>
								<p class="um-field-hint">Hint message</p>
							</div>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<div class="um-field">
								<label for="aaa">Label of choices-js select</label>
								<select class="js-choice" placeholder="select placeholder">
									<option value="">None</option>
									<option value="1">1</option>
									<option value="2">2</option>
									<option value="3">3</option>
									<option value="4">4</option>
									<option value="5">5</option>
									<option value="6">6</option>
									<option value="7">7</option>
									<option value="8">8</option>
									<option value="9">9</option>
									<option value="10">10</option>
								</select>
								<p class="um-field-hint">Hint message</p>
							</div>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<div class="um-field">
								<label for="aaa">Label of native multi-select</label>
								<select multiple>
									<option value="1">1</option>
									<option value="2">2</option>
									<option value="3">3</option>
								</select>
								<p class="um-field-hint">Hint message</p>
							</div>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<div class="um-field">
								<label for="aaa">Label of choices-js multi-select</label>
								<select class="js-choice" multiple>
									<option value="1">1</option>
									<option value="2">2</option>
									<option value="3">3</option>
								</select>
								<p class="um-field-hint">Hint message</p>
							</div>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<div class="um-field">
								<label for="aaa">Label of radio sm</label>
								<div class="um-field-radio-area">
									<label class="um-radio-label um-size-sm um-supporting-text">
										<input name="bbb" type="radio" value="1" />
										<span class="um-label-content">
											<span class="um-text">Option 1</span>
											<br />
											<span class="um-supporting-text">supporting label</span>
										</span>
									</label>
									<label class="um-radio-label um-size-sm"><input name="bbb" type="radio" value="2" />Option 2</label>
									<label class="um-radio-label um-size-sm"><input name="bbb" type="radio" value="3" />Option 3</label>
								</div>
								<p class="um-field-hint">Hint message</p>
							</div>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<div class="um-field">
								<label for="aaa">Label of radio lg</label>
								<div class="um-field-radio-area">
									<label class="um-radio-label um-size-md um-supporting-text">
										<input name="bbbb" type="radio" value="1" />
										<span class="um-label-content">
											<span class="um-text">Option 1</span>
											<br />
											<span class="um-supporting-text">supporting label</span>
										</span>
									</label>
									<label class="um-radio-label um-size-md"><input name="bbbb" type="radio" value="2" />Option 2</label>
									<label class="um-radio-label um-size-md"><input name="bbbb" type="radio" value="3" />Option 3</label>
								</div>
								<p class="um-field-hint">Hint message</p>
							</div>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<div class="um-field">
								<label for="aaa">Label of checkbox sm</label>
								<div class="um-field-checkbox-area">
									<label class="um-checkbox-label um-size-sm um-supporting-text">
										<input name="ccc[]" type="checkbox" value="1" />
										<span class="um-checkbox-content">
											<span class="um-text">Option 1</span>
											<br />
											<span class="um-supporting-text">supporting label</span>
										</span>
									</label>
									<label class="um-checkbox-label um-size-sm"><input name="ccc[]" type="checkbox" value="2" />Option 2</label>
									<label class="um-checkbox-label um-size-sm"><input name="ccc[]" type="checkbox" value="3" />Option 3</label>
								</div>
								<p class="um-field-hint">Hint message</p>
							</div>
						</div>
					</div>
					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<div class="um-field">
								<label for="aaa">Label of checkbox lg</label>
								<div class="um-field-checkbox-area">
									<label class="um-checkbox-label um-size-md um-supporting-text">
										<input name="cccc[]" type="checkbox" value="1" />
										<span class="um-checkbox-content">
											<span class="um-text">Option 1</span>
											<br />
											<span class="um-supporting-text">supporting label</span>
										</span>
									</label>
									<label class="um-checkbox-label um-size-md"><input id="um-indeterminate" name="cccc[]" type="checkbox" value="2" />Option 2</label>
									<label class="um-checkbox-label um-size-md"><input name="cccc[]" type="checkbox" value="3" />Option 3</label>
								</div>
								<p class="um-field-hint">Hint message</p>
							</div>
						</div>
					</div>


					<div class="um-form-cols um-form-cols-1">
						<div class="um-form-col um-form-col-1">
							<label for="aaa">Label of range</label>
							<div class="range_container">
								<div class="sliders_control">
									<input id="fromSlider" type="range" value="10" min="0" max="100"/>
									<input id="toSlider" type="range" value="30" min="0" max="100"/>
								</div>
<!--								<div class="form_control">-->
<!--									<div class="form_control_container">-->
<!--										<div class="form_control_container__time">Min</div>-->
<!--										<input class="form_control_container__time__input" type="number" id="fromInput" value="10" min="0" max="100"/>-->
<!--									</div>-->
<!--									<div class="form_control_container">-->
<!--										<div class="form_control_container__time">Max</div>-->
<!--										<input class="form_control_container__time__input" type="number" id="toInput" value="30" min="0" max="100"/>-->
<!--									</div>-->
<!--								</div>-->
							</div>
							<p class="um-field-hint">Hint message</p>
						</div>
					</div>
				</div>
			</div>
			<div class="um-form-submit">
				<?php echo self::button( 'Submit', array( 'type' => 'submit', 'design' => 'primary', 'width' => 'full' ) ); ?>
				<?php echo self::button( 'Cancel', array( 'width' => 'full' ) ); ?>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}

	public static function input() {
		ob_start();
		?>
		<label for="aaa">Label aa</label>
		<input id="aaa" type="text" placeholder="Type here" />
		<label for="aaa">Label bb</label>
		<input id="bbb" type="text" placeholder="Disabled" disabled />
		<p class="um-field-hint">Hint message</p>
		<?php
		return ob_get_clean();
	}

	public static function divider() {
		ob_start();
		?>
		<div class="um-divider"><hr /></div>
		<?php
		return ob_get_clean();
	}

	public static function range( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'min'   => 0,
				'max'   => 100,
				'name'  => '',
				'value' => 0,
			)
		);

		$value = is_array( $args['value'] ) ? $args['value'] : array( $args['value'], $args['value'] );

		$fields = array(
			'from' => array(
				'name'  => $args['name'] ? $args['name'] . '_min' : 'min',
				'min'   => $args['min'],
				'max'   => $args['max'],
				'value' => min( $value ),
			),
			'to'   => array(
				'name'  => $args['name'] ? $args['name'] . '_max' : 'max',
				'min'   => $args['min'],
				'max'   => $args['max'],
				'value' => max( $value ),
			),
		);

		ob_start();
		?>
		<div class="range_container">
			<div class="sliders_control">
				<?php foreach ( $fields as $field_k => $field ) { ?>
					<input id="<?php echo esc_attr( $field_k ); ?>Slider" type="range" value="<?php echo esc_attr( $field['value'] ); ?>" min="<?php echo esc_attr( $field['min'] ); ?>" max="<?php echo esc_attr( $field['max'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" />
				<?php } ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function buttons_group( $buttons, $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'size'    => 'auto', // auto || equal
				'classes' => array(),
			)
		);

		$args['classes'][] = 'um-buttons-group';
		$args['classes'][] = 'um-buttons-group-' . $args['size'];
		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $args['classes'] ) ); ?>">
		<?php
		foreach ( $buttons as $button ) {
			$button = wp_parse_args(
				$button,
				array(
					'classes' => array(),
					'label'   => '',
					'data'    => array(),
				)
			);

			if ( empty( $button['label'] ) ) {
				continue;
			}

			$button['classes'][] = 'um-button-in-group';

			$data_attr = array();
			foreach ( $button['data'] as $data_k => $data_v ) {
				$data_attr[] = 'data-' . $data_k . '="' . esc_attr( $data_v ) . '"';
			}
			$data_attr = implode( ' ', $data_attr );
			?>
			<span class="<?php echo esc_attr( implode( ' ', $button['classes'] ) ); ?>" <?php echo $data_attr; ?>><?php echo esc_html( $button['label'] ); ?></span>
			<?php
		}
		?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function pagination( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'page'     => 1,
				'total'    => 0,
				'per_page' => 0,
			)
		);

		if ( empty( $args['total'] ) ) {
			return '';
		}

		$pagination_data = self::calculate_pagination( ...$args );

		if ( 1 >= $pagination_data['pages_count'] || empty( $pagination_data['pages'] ) ) {
			return '';
		}

		return UM()->get_template( 'components/pagination.php', '', $pagination_data );
	}

	/**
	 * Get data array for pagination
	 *
	 * @return array
	 */
	private static function calculate_pagination( $page, $total, $per_page = 0 ) {
		$pages       = array();
		$per_page    = empty( $per_page ) ? $total : $per_page;
		$pages_count = absint( ceil( $total / $per_page ) );

		if ( $pages_count <= 7 ) {
			$pages = array(
				1 => array(
					'label'   => '1',
					'current' => false,
				),
				2 => array(
					'label'   => '2',
					'current' => false,
				),
				3 => array(
					'label'   => '3',
					'current' => false,
				),
				4 => array(
					'label'   => '4',
					'current' => false,
				),
				5 => array(
					'label'   => '5',
					'current' => false,
				),
				6 => array(
					'label'   => '6',
					'current' => false,
				),
				7 => array(
					'label'   => '7',
					'current' => false,
				),
			);
			$pages = array_filter(
				$pages,
				function( $key ) use ( $pages_count ) {
					return $key <= $pages_count;
				},
				ARRAY_FILTER_USE_KEY
			);
		} else {
			$next_dot = true;
			for ( $i = 1; $i <= $pages_count; $i++ ) {
				if ( $i > 3 && $i <= $pages_count - 3 ) {
					if ( $i === $page ) {
						$pages[ $i ] = array(
							'label'   => (string) $i,
							'current' => $i === $page,
						);
						$next_dot    = true;
					} elseif ( $next_dot ) {
						$next_dot    = false;
						$pages[ $i ] = array(
							'label'   => __( '...', 'ultimate-member' ),
							'current' => $i === $page,
						);
					}
				} else {
					$pages[ $i ] = array(
						'label'   => (string) $i,
						'current' => $i === $page,
					);
				}
			}
		}
		$pages[ $page ]['current'] = true;

		$pagination_data = array(
			'pages'             => $pages,
			'page'              => $page,
			'per_page'          => $per_page,
			'pages_count'       => $pages_count,
			'total'             => $total,
			'previous_disabled' => 1 === $page,
			'next_disabled'     => $page === $pages_count,
		);

		return apply_filters( 'um_handle_pagination_arguments', $pagination_data );
	}
}
