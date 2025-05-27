<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// @todo Users list layout (e.g. likes list in User Photos modal)
// @todo Common Grid layout (e.g. albums, photos grid in User Photos gallery)

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
				'disabled'     => false,
				'place'        => '',
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
					$button_args = array(
						'type'          => 'button',
						'icon'          => '<span class="um-dropdown-chevron"></span>',
						'icon_position' => 'trailing',
						'design'        => 'secondary-gray',
						'size'          => 's',
					);
					if ( false !== $args['disabled'] ) {
						$button_args['disabled'] = true;
					}
					$type_html = self::button( $args['button_label'], $button_args );
				} elseif ( 'link' === $args['type'] ) {
					$link_args = array(
						'type'          => 'button',
						'icon'          => '<span class="um-dropdown-chevron"></span>',
						'icon_position' => 'trailing',
						'design'        => 'link-gray',
						'size'          => 's',
					);
					if ( false !== $args['disabled'] ) {
						$link_args['disabled'] = true;
					}
					$type_html = self::link( $args['button_label'], $link_args );
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
			<div class="<?php echo esc_attr( implode( ' ', $dropdown_classes ) ); ?>" data-element=".<?php echo esc_attr( $element ); ?>" data-trigger="<?php echo esc_attr( $trigger ); ?>" data-parent="<?php echo esc_attr( $parent ); ?>" data-width="<?php echo esc_attr( $args['width'] ); ?>" data-place="<?php echo esc_attr( $args['place'] ); ?>">
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
		<button <?php if ( ! empty( $args['id'] ) ) { ?>id="<?php echo esc_attr( $args['id'] ); ?>"<?php } ?> type="<?php echo esc_attr( $args['type'] ); ?>" class="<?php echo esc_attr( $classes ); ?>" title="<?php echo esc_attr( $args['title'] ); ?>" <?php disabled( $args['disabled'] ); ?> <?php echo $data_atts; ?>><?php echo wp_kses( $content, UM()->get_allowed_html( 'templates' ) ); ?></button>
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
	public static function ajax_loader( $size = 'l', $args = array() ) {
		if ( ! in_array( $size, array( 's', 'm', 'l', 'xl' ), true ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'size'    => $size,
				'id'      => '',
				'classes' => array(),
			)
		);

		$hash = md5( maybe_serialize( $args ) );

		static $content = array();

		if ( ! empty( $content[ $hash ] ) ) {
			return $content[ $hash ];
		}

		$classes = array(
			'um-ajax-spinner-svg',
			'um-ajax-spinner-' . $args['size'],
		);
		$classes = array_merge( $classes, $args['classes'] );

		ob_start();
		?>
		<span class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
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
		$content[ $hash ] = ob_get_clean();

		return $content[ $hash ];
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
						$header_classes[] = 'um-box-header-' . $args['actions_position'] . '-actions';
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
				'size'    => 'm', // s,m,l
				'type'    => 'color', // color, pill-outline, pill-color
				'color'   => 'gray', // gray, brand,error,warning,success
				'classes' => array(),
				'data'    => array(),
			)
		);

		$classes = array(
			'um-badge',
			'um-badge-' . $args['size'],
			'um-badge-' . $args['type'],
			'um-badge-color-' . $args['color'],
		);
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
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped  -- $data_atts has been already escaped above. ?>
		<span class="<?php echo esc_attr( $classes ); ?>" <?php echo $data_atts; ?>>
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
	 *     @type string   $size          Avatar size. Uses 'xs', 's', 'm', 'l', 'xl'. Default 'm'.
	 *     @type string   $type          Avatar type. Uses 'square', 'round'. Default 'round'.
	 *     @type string[] $wrapper_class Avatar wrapper additional classes.
	 *     @type bool     $ignore_caps   If `true` just display avatar without checking current user/guest caps to see `$user_id`'s avatar.
	 *     @type bool     $cache         If `false` timestamp is added to the avatar URL
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

		if ( false === get_userdata( $user_id ) ) {
			return '';
		}

		$default_args = array(
			'wrapper'       => 'div',
			'size'          => 'm',
			'type'          => 'round',
			'wrapper_class' => array(),
			'clickable'     => false,
			'url'           => um_user_profile_url( $user_id ),
			'url_title'     => __( 'Visit profile', 'ultimate-member' ),
			'ignore_caps'   => false,
			'tooltip'       => false,
			'cache'         => true,
		);
		/**
		 * Filters default arguments for displaying single avatar layout.
		 *
		 * @param {array} $default_args Default arguments.
		 * @param {int}   $user_id      User ID.
		 *
		 * @return {array} Single avatar layout default arguments.
		 *
		 * @since 3.0.0
		 * @hook um_user_single_avatar_default_args
		 *
		 * @example <caption>Extends default arguments for displaying single avatar layout.</caption>
		 * function my_user_single_avatar_default_args( $default_args, $user_id ) {
		 *     // your code here
		 *     $default_args['custom_key'] = 'custom_value';
		 *     return $default_args;
		 * }
		 * add_filter( 'um_user_single_avatar_default_args', 'my_user_single_avatar_default_args', 10, 2 );
		 */
		$default_args = apply_filters( 'um_user_single_avatar_default_args', $default_args, $user_id );

		$args = wp_parse_args( $args, $default_args );

		$user_id = absint( $user_id );

		if ( false === $args['ignore_caps'] ) {
			if ( get_current_user_id() !== $user_id ) {
				if ( ! um_can_view_profile( $user_id ) ) {
					return '';
				}

				if ( ! current_user_can( 'administrator' ) ) {
					$status = get_user_meta( $user_id, 'account_status', true );
					if ( 'approved' !== $status ) {
						return '';
					}
				}
			}
		}

		$title = '';
		if ( $args['tooltip'] ) {
			$title = um_get_display_name( $user_id );
		}

		$args['url'] = empty( $args['url'] ) ? um_user_profile_url( $user_id ) : $args['url'];

		$wrapper_classes = array(
			'um-avatar',
			'um-avatar-' . $args['size'],
			'um-avatar-' . $args['type'],
		);
		if ( ! empty( $args['wrapper_class'] ) ) {
			$wrapper_classes = array_merge( $wrapper_classes, $args['wrapper_class'] );
		}
		/**
		 * Filters wrapper classes for displaying single avatar layout.
		 *
		 * @param {array} $wrapper_classes Single avatar wrapper classes.
		 * @param {array} $args            Single avatar arguments.
		 * @param {int}   $user_id         User ID.
		 *
		 * @return {array} Single avatar wrapper classes.
		 *
		 * @since 3.0.0
		 * @hook um_user_single_avatar_wrapper_classes
		 *
		 * @example <caption>Extends wrapper classes for displaying single avatar layout when `custom_arg` isn't empty.</caption>
		 * function my_user_single_avatar_default_args( $wrapper_classes, $args, $user_id ) {
		 *     // your code here
		 *     if ( ! empty( $args['custom_arg'] ) ) {
		 *         $wrapper_classes[] = 'custom_class';
		 *     }
		 *     return $wrapper_classes;
		 * }
		 * add_filter( 'um_user_single_avatar_wrapper_classes', 'my_user_single_avatar_wrapper_classes', 10, 3 );
		 */
		$wrapper_classes = apply_filters( 'um_user_single_avatar_wrapper_classes', $wrapper_classes, $args, $user_id );
		$wrapper_classes = implode( ' ', $wrapper_classes );

		$thumb_size = 40;
		if ( 'xs' === $args['size'] || 's' === $args['size'] ) {
			$thumb_size = 32;
		} elseif ( 'l' === $args['size'] ) {
			$thumb_size = 64;
		} elseif ( 'xl' === $args['size'] ) {
			$thumb_size = 128;
		}
		/**
		 * Filters the avatar's thumb size.
		 *
		 * @since 3.0.0
		 * @hook um_single_avatar_thumbnail_size
		 *
		 * @param {int}   $thumb_size Thumb size.
		 * @param {array} $args       Avatars arguments.
		 * @param {int}   $user_id    User ID.
		 *
		 * @return {int} Thumb size in pixels.
		 *
		 * @example <caption>Add `xxl` thumb size.</caption>
		 * function um_custom_cpt_list( $thumb_size, $args, $user_id ) {
		 *     if ( 'xxl' === $args['size'] ) {
		 *         $thumb_size = 256;
		 *     }
		 *     return $thumb_size;
		 * }
		 * add_filter( 'um_single_avatar_thumbnail_size', 'um_custom_single_avatar_thumbnail_size', 10, 3 );
		 */
		$thumb_size = apply_filters( 'um_single_avatar_thumbnail_size', $thumb_size, $args, $user_id );

		$avatar_args = array( 'loading' => 'lazy' );
		if ( false === $args['cache'] ) {
			$avatar_args['um-cache'] = false;
		}
		$avatar = get_avatar( $user_id, $thumb_size, '', '', $avatar_args );

		if ( false === $avatar ) {
			return '';
		}

		ob_start();

		if ( 'div' === $args['wrapper'] ) {
			?>
			<div class="<?php echo esc_attr( $wrapper_classes ); ?>" data-user_id="<?php echo esc_attr( $user_id ); ?>" title="<?php echo esc_attr( $title ); ?>">
			<?php
		} elseif ( 'span' === $args['wrapper'] ) {
			?>
			<span class="<?php echo esc_attr( $wrapper_classes ); ?>" data-user_id="<?php echo esc_attr( $user_id ); ?>" title="<?php echo esc_attr( $title ); ?>">
			<?php
		}

		/**
		 * Fires in the single user avatar wrapper before avatar image.
		 *
		 * @param {int}   $user_id User ID.
		 * @param {array} $args    Single avatar arguments.
		 *
		 * @since 3.0.0
		 * @hook um_user_single_avatar_before
		 *
		 * @example <caption>Action before avatar image.</caption>
		 * function my_user_single_avatar_before( $user_id, $args ) {
		 *     // your code here
		 *     echo 'something';
		 * }
		 * add_action( 'um_user_single_avatar_before', 'my_user_single_avatar_before', 10, 2 );
		 */
		do_action( 'um_user_single_avatar_before', $user_id, $args );

		if ( ! empty( $args['clickable'] ) ) {
			?>
			<a href="<?php echo esc_url( $args['url'] ); ?>" title="<?php echo esc_attr( $args['url_title'] ); ?>">
			<?php
		}
		echo wp_kses( $avatar, UM()->get_allowed_html( 'templates' ) );
		if ( ! empty( $args['clickable'] ) ) {
			?>
			</a>
			<?php
		}

		/**
		 * Fires in the single user avatar wrapper after avatar image.
		 *
		 * @param {int}   $user_id User ID.
		 * @param {array} $args    Single avatar arguments.
		 *
		 * @since 3.0.0
		 * @hook um_user_single_avatar_after
		 *
		 * @example <caption>Action after avatar image.</caption>
		 * function my_user_single_avatar_after( $user_id, $args ) {
		 *     // your code here
		 *     echo 'something';
		 * }
		 * add_action( 'um_user_single_avatar_after', 'my_user_single_avatar_after', 10, 2 );
		 */
		do_action( 'um_user_single_avatar_after', $user_id, $args );

		if ( 'div' === $args['wrapper'] ) {
			?>
			</div>
			<?php
		} elseif ( 'span' === $args['wrapper'] ) {
			?>
			</span>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Avatar layout.
	 *
	 * @param int[] $user_ids User ID.
	 * @param array    $args    {
	 *     Avatar additional arguments.
	 *
	 *     @type string   $size          Avatar size. Uses 'xs', 's', 'm', 'l', 'xl'. Default 'm'.
	 *     @type string   $type          Avatar type. Uses 'square', 'round'. Default 'round'.
	 *     @type string[] $wrapper_class Avatar wrapper additional classes.
	 * }
	 *
	 * @return string
	 */
	public static function avatars_list( $user_ids, $args = array() ) {
		if ( empty( $user_ids ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'wrapper'       => 'div',
				'count'         => 0,
				'size'          => 'm',
				'type'          => 'round',
				'wrapper_class' => array(),
				'tooltip'       => true,
			)
		);

		$user_ids = array_unique( $user_ids );

		if ( 0 !== $args['count'] ) {
			array_splice( $user_ids, $args['count'] );
		}

		$wrapper_classes = array(
			'um-avatars-list',
			'um-avatars-list-' . $args['size'],
		);
		if ( $args['tooltip'] ) {
			$wrapper_classes[] = 'um-avatars-list-tooltip';
		}
		$wrapper_classes = array_merge( $wrapper_classes, $args['wrapper_class'] );

		ob_start();
		if ( 'div' === $args['wrapper'] ) {
			?>
			<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
			<?php
		} elseif ( 'span' === $args['wrapper'] ) {
			?>
			<span class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
			<?php
		}

		$counter = 0;
		foreach ( $user_ids as $user_id ) {
			if ( 0 !== $args['count'] && $args['count'] === $counter ) {
				break;
			}

			$avatar = self::single_avatar(
				$user_id,
				array(
					'size'    => $args['size'],
					'wrapper' => $args['wrapper'],
					'tooltip' => $args['tooltip'],
				)
			);

			if ( empty( $avatar ) ) {
				continue;
			}

			if ( 0 !== $args['count'] ) {
				++$counter;
			}
			echo wp_kses( $avatar, UM()->get_allowed_html( 'templates' ) );
		}

		if ( 'div' === $args['wrapper'] ) {
			?>
			</div>
			<?php
		} elseif ( 'span' === $args['wrapper'] ) {
			?>
			</span>
			<?php
		}
		return ob_get_clean();
	}

	/**
	 * Cover photo display function.
	 *
	 * @param int|bool $user_id The user ID of the user for whom the cover photo should be displayed. Default false.
	 * @param array    $args    {
	 *     Optional arguments for cover photo display.
	 *
	 *     @type string   $size    Size of the cover photo. Default null.
	 *     @type bool     $cache   Whether to cache the cover photo. Default true.
	 *     @type string[] $classes Additional classes for the cover photo container.
	 * }
	 *
	 * @return string The HTML markup for the cover photo container.
	 */
	public static function cover_photo( $user_id = false, $args = array() ) {
		if ( false === $user_id && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $user_id ) ) {
			return '';
		}

		if ( ! UM()->options()->get( 'enable_user_cover' ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'size'    => null,
				'cache'   => true,
				'classes' => array(),
			)
		);

		$url = UM()->common()->users()->get_cover_photo_url( $user_id, $args );
		if ( empty( $url ) ) {
			return '';
		}

		$srcset = empty( $args['size'] ) ? '' : UM()->common()->users()->get_cover_photo_url( $user_id, $args['size'] * 2 );
		if ( $srcset ) {
			$srcset = sprintf( ' srcset="%s 2x"', esc_attr( $srcset ) );
		}

		$display_name = um_user( 'display_name' );
		if ( absint( um_user( 'ID' ) ) !== $user_id ) {
			$temp_id = um_user( 'ID' );
			um_fetch_user( $user_id );
			$display_name = um_user( 'display_name' );
		}

		if ( ! empty( $temp_id ) ) {
			um_fetch_user( $temp_id );
		}

		// translators: %s is the user display name.
		$alt   = sprintf( __( '%s\'s cover photo', 'ultimate-member' ), $display_name );
		$ratio = UM()->options()->get( 'profile_cover_ratio' );

		$classes = array( 'um-cover-photo' );
		$classes = array_merge( $classes, $args['classes'] );

		ob_start();
		?>
		<img class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" src="<?php echo esc_url( $url ); ?>" <?php echo $srcset; ?> alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" data-ratio="<?php echo esc_attr( $ratio ); ?>" />
		<?php
		return ob_get_clean();
	}

	public static function users_list( $user_ids, $args = array() ) {
		if ( empty( $user_ids ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'count'         => 0,
				'avatar_size'   => 'm',
				'wrapper_class' => array(),
				'clickable'     => true,
				'supporting'    => '',
			)
		);

		$user_ids = array_unique( $user_ids );

		if ( 0 !== $args['count'] ) {
			array_splice( $user_ids, $args['count'] );
		}

		$wrapper_classes = array(
			'um-users-list',
		);

		$wrapper_classes = array_merge( $wrapper_classes, $args['wrapper_class'] );
		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
			<?php
			$counter = 0;
			foreach ( $user_ids as $user_id ) {
				if ( 0 !== $args['count'] && $args['count'] === $counter ) {
					break;
				}

				$data = self::small_data(
					$user_id,
					array(
						'avatar_size' => $args['avatar_size'],
						'clickable'   => $args['clickable'],
						'supporting'  => $args['supporting'],
					)
				);

				if ( empty( $data ) ) {
					continue;
				}

				if ( 0 !== $args['count'] ) {
					++$counter;
				}
				echo wp_kses( $data, UM()->get_allowed_html( 'templates' ) );
			}
			?>
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
				'header'      => '',
				'clickable'   => get_current_user_id() !== $user_id,
				'url'         => um_user_profile_url( $user_id ),
				'url_title'   => __( 'Visit profile', 'ultimate-member' ),
				'supporting'  => '',
				'ignore_caps' => false,
				'classes'     => array(),
			)
		);

		if ( false === $args['ignore_caps'] ) {
			if ( get_current_user_id() !== $user_id ) {
				if ( ! um_can_view_profile( $user_id ) ) {
					return '';
				}

				if ( ! current_user_can( 'administrator' ) ) {
					$status = get_user_meta( $user_id, 'account_status', true );
					if ( 'approved' !== $status ) {
						return '';
					}
				}
			}
		}

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

			if ( empty( $args['header'] ) ) {
				if ( ! empty( $args['clickable'] ) ) {
					?>
					<a class="um-user-display-name um-link um-header-link" href="<?php echo esc_url( $args['url'] ); ?>" title="<?php echo esc_attr( $args['url_title'] ); ?>"><?php echo esc_html( um_user( 'display_name' ) ); ?></a>
					<?php
				} else {
					?>
					<span class="um-user-display-name"><?php echo esc_html( um_user( 'display_name' ) ); ?></span>
					<?php
				}
			} else {
				?>
				<span class="um-data-header">
					<?php echo wp_kses( $args['header'], UM()->get_allowed_html( 'templates' ) ); ?>
				</span>
				<?php
			}

			um_reset_user();
			if ( ! empty( $args['supporting'] ) ) {
				?>
				<span class="um-supporting-text">
					<?php
					$supporting = $args['supporting'];
					if ( is_callable( $args['supporting'] ) ) {
						$supporting = call_user_func( $args['supporting'], $user_id, $args );
					}

					echo wp_kses(
						$supporting,
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

	public static function tabs( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'wrapper_class' => array(),
				'tabs_only'     => false,
				'orientation'   => 'vertical',
				'color'         => 'primary', // secondary, underline, underline-fill, gray-line
				'size'          => 's', // s, m
				'tabs'          => array(
					'id' => array(
						'title'         => __( 'Tab title', 'ultimate-member' ),
						'content'       => __( 'Tab content', 'ultimate-member' ),
						'url'           => '#',
						'current'       => true,
						'notifier'      => 0,
						'notifier_type' => 'gray',
						'max_notifier'  => 10,
					),
				),
			)
		);

		$wrapper_classes = array(
			'um-tabs-wrapper',
			'um-' . $args['orientation'] . '-tabs',
			'um-' . $args['color'] . '-color-tabs',
			'um-tabs-size-' . $args['size'],
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
			$tab_classes = array(
				'um-tab',
				'um-tab-' . $tab_id,
			);
			if ( array_key_exists( 'notifier', $tab_data ) ) {
				$tab_classes[] = 'um-tab-has-notifier';
			}
			if ( $current_tab === $tab_id ) {
				$tab_classes[] = 'um-tab-current';
			}
			ob_start();
			?>
			<li class="<?php echo esc_attr( implode( ' ', $tab_classes ) ); ?>">
				<a class="um-link" href="<?php echo esc_url( $tab_data['url'] ); ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>">
					<?php
					echo esc_html( $tab_data['title'] );
					if ( array_key_exists( 'notifier', $tab_data ) ) {
						$badge_args       = array(
							'size'  => 's',
							'type'  => 'pill-color',
							'color' => array_key_exists( 'notifier_type', $tab_data ) ? $tab_data['notifier_type'] : 'gray',
						);
						$notifier         = 0;
						$notifier_classes = array( 'um-' . $tab_id . '-tab-notifier' );
						if ( 0 < absint( $tab_data['notifier'] ) ) {
							$notifier = absint( $tab_data['notifier'] );
							if ( array_key_exists( 'max_notifier', $tab_data ) ) {
								$max_notifier = absint( $tab_data['max_notifier'] );

								if ( $max_notifier < $notifier ) {
									$badge_args['data']['max_notifier'] = $max_notifier;
									// translators: %d is the notifier value.
									$notifier = sprintf( __( '%d+', 'ultimate-member' ), $max_notifier );
								}
							}
						}

						if ( 0 === $notifier ) {
							$notifier_classes[] = 'um-display-none';
						}

						$badge_args['classes'] = $notifier_classes;

						echo wp_kses(
							self::badge( $notifier, $badge_args ),
							UM()->get_allowed_html( 'templates' )
						);
					}
					?>
				</a>
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

			if ( empty( $args['tabs_only'] ) ) {
				$content_classes = array(
					'um-tab-content',
					'um-tab-' . $tab_id . '-content',
				);
				if ( $current_tab === $tab_id  ) {
					$content_classes[] = 'um-tab-current';
				}
				ob_start();
				?>
				<div class="<?php echo esc_attr( implode( ' ', $content_classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_id ); ?>"><?php echo wp_kses( $tab_data['content'], UM()->get_allowed_html( 'templates' ) ); ?></div>
				<?php
				$content .= ob_get_clean();
			}
		}

		$list_html = '<ul>' . $desktop_list . '</ul><select>' . $mobile_list . '</select>';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_classes ); ?>">
			<div class="um-tabs-list"><?php echo wp_kses( $list_html, UM()->get_allowed_html( 'templates' ) ); ?></div>
			<?php if ( empty( $args['tabs_only'] ) ) { ?>
				<div class="um-tabs-content"><?php echo wp_kses( $content, UM()->get_allowed_html( 'templates' ) ); ?></div>
			<?php } ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Overlay layout.
	 *
	 * @param array $args {
	 *     Overlay additional arguments.
	 *
	 *     @type string[] $classes Additional classes for the overlay.
	 *     @type bool     $rounded Whether the overlay should have rounded corners. Default false.
	 * }
	 *
	 * @return string
	 */
	public static function overlay( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes' => array(),
				'rounded' => false,
			)
		);

		$classes = array( 'um-overlay', 'um-display-none' );
		$classes = array_merge( $args['classes'], $classes );
		if ( false !== $args['rounded'] ) {
			$classes[] = 'um-rounded';
		}
		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"></div>
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

	public static function divider( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'label'          => '',
				'label-position' => 'center',
				'color'          => 'gray',
				'classes'        => array(),
			)
		);

		$classes = array( 'um-divider' );
		if ( ! empty( $args['label'] ) ) {
			$classes[] = 'um-divider-has-label';
			$classes[] = 'um-divider-label-position-' . $args['label-position'];
		}
		$classes[] = 'um-divider-color-' . $args['color'];

		$classes = array_merge( $args['classes'], $classes );

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php
			if ( 'right' === $args['label-position'] || 'center' === $args['label-position'] ) {
				?>
				<hr />
				<?php
			}
			if ( ! empty( $args['label'] ) ) {
				echo esc_html( $args['label'] );
			}
			if ( 'left' === $args['label-position'] || 'center' === $args['label-position'] ) {
				?>
				<hr />
				<?php
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function range( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'label'       => '',
				'show_label'  => true,
				'min'         => 0,
				'max'         => 100,
				'name'        => '',
				'value'       => 0,
				'placeholder' => array(
					'single' => '',
					'plural' => '',
				),
				'classes' => array(
					'wrapper' => array(),
					'from'    => array(),
					'to'      => array(),
				),
			)
		);

		if ( empty( $args['placeholder']['single'] ) || empty( $args['placeholder']['plural'] ) ) {
			$args['placeholder'] = false;
		}

		$value = is_array( $args['value'] ) ? $args['value'] : array( $args['value'], $args['value'] );

		$args['classes'] = wp_parse_args(
			$args['classes'],
			array(
				'wrapper' => array(),
				'from'    => array(),
				'to'      => array(),
			)
		);

		$fields = array(
			'from' => array(
				'name'    => $args['name'] ? $args['name'] . '_min' : 'min',
				'min'     => $args['min'],
				'max'     => $args['max'],
				'value'   => min( $value ),
				'classes' => $args['classes']['from'],
			),
			'to'   => array(
				'name'    => $args['name'] ? $args['name'] . '_max' : 'max',
				'min'     => $args['min'],
				'max'     => $args['max'],
				'value'   => max( $value ),
				'classes' => $args['classes']['to'],
			),
		);

		ob_start();
		?>
		<div class="um-field-wrapper">
			<?php if ( $args['show_label'] ) { ?>
				<label for="<?php echo esc_attr( $fields['from']['name'] ); ?>"><?php echo esc_html( $args['label'] ); ?></label>
			<?php } ?>
			<div class="um-range-container">
				<div class="um-sliders-control">
					<?php
					foreach ( $fields as $field_k => $field ) {
						$field['classes'][] = 'um-' . $field_k . '-slider';
						$classes = implode( ' ', $field['classes'] );
						?>
						<input class="<?php echo esc_attr( $classes ); ?>" type="range" value="<?php echo esc_attr( $field['value'] ); ?>" min="<?php echo esc_attr( $field['min'] ); ?>" max="<?php echo esc_attr( $field['max'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" />
						<?php
					}
					?>
				</div>
				<?php
				if ( false !== $args['placeholder'] ) {
					if ( $fields['from']['value'] === $fields['to']['value'] ) {
						$text = str_replace( '{{{value}}}', $fields['from']['value'], $args['placeholder']['single'] );
					} else {
						$text = str_replace( array( '{{{value_from}}}', '{{{value_to}}}' ), array( $fields['from']['value'], $fields['to']['value'] ), $args['placeholder']['plural'] );
					}
					$text = str_replace( '{{{label}}}', $args['label'], $text );
					?>
					<div class="um-range-placeholder um-supporting-text" data-placeholder-s="<?php echo esc_attr( $args['placeholder']['single'] ); ?>" data-placeholder-p="<?php echo esc_attr( $args['placeholder']['plural'] ); ?>" data-label="<?php echo esc_attr( $args['label'] ); ?>"><?php echo wp_kses_post( $text ); ?></div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function date_range( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'min'        => '',
				'max'        => '',
				'id'         => '',
				'name'       => '',
				'label'      => '',
				'show_label' => true,
				'value'      => '',
				'classes'    => array(
					'wrapper' => array(),
					'from'    => array(),
					'to'      => array(),
				),
			)
		);

		if ( empty( $args['id'] ) ) {
			return '';
		}

		if ( empty( $args['name'] ) ) {
			$args['name'] = $args['id'];
		}
		if ( empty( $args['name'] ) ) {
			return '';
		}

		$args['classes'] = wp_parse_args(
			$args['classes'],
			array(
				'wrapper' => array(),
				'from'    => array(),
				'to'      => array(),
			)
		);

		$from_classes = implode( ' ', $args['classes']['from'] );
		$to_classes   = implode( ' ', $args['classes']['to'] );

		$value = is_array( $args['value'] ) ? $args['value'] : array( $args['value'], $args['value'] );

		ob_start();
		?>
		<div class="um-field-wrapper">
			<?php if ( $args['show_label'] ) { ?>
				<label for="<?php echo esc_attr( $args['id'] . '_from' ); ?>"><?php echo esc_html( $args['label'] ); ?></label>
			<?php } ?>
			<div class="um-date-range-row">
				<input type="date" class="<?php echo esc_attr( $from_classes ); ?>" id="<?php echo esc_attr( $args['id'] . '_from' ); ?>" name="<?php echo esc_attr( $args['name'] . '_from' ); ?>" data-range="from" value="<?php echo esc_attr( $value[0] ); ?>" />
				<label for="<?php echo esc_attr( $args['id'] . '_to' ); ?>">to</label>
				<input type="date" class="<?php echo esc_attr( $to_classes ); ?>" id="<?php echo esc_attr( $args['id'] . '_to' ); ?>" name="<?php echo esc_attr( $args['name'] . '_to' ); ?>" data-range="to" value="<?php echo esc_attr( $value[1] ); ?>" />
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function time_range( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'min'        => '',
				'max'        => '',
				'id'         => '',
				'name'       => '',
				'label'      => '',
				'show_label' => true,
				'value'      => '',
				'classes'    => array(
					'wrapper' => array(),
					'from'    => array(),
					'to'      => array(),
				),
			)
		);

		if ( empty( $args['id'] ) ) {
			return '';
		}

		if ( empty( $args['name'] ) ) {
			$args['name'] = $args['id'];
		}
		if ( empty( $args['name'] ) ) {
			return '';
		}

		$args['classes'] = wp_parse_args(
			$args['classes'],
			array(
				'wrapper' => array(),
				'from'    => array(),
				'to'      => array(),
			)
		);

		$value = is_array( $args['value'] ) ? $args['value'] : array( $args['value'], $args['value'] );

		$from_classes = implode( ' ', $args['classes']['from'] );
		$to_classes   = implode( ' ', $args['classes']['to'] );

		ob_start();
		?>
		<div class="um-field-wrapper">
			<?php if ( $args['show_label'] ) { ?>
				<label for="<?php echo esc_attr( $args['id'] . '_from' ); ?>"><?php echo esc_html( $args['label'] ); ?></label>
			<?php } ?>
			<div class="um-time-range-row">
				<input type="time" class="<?php echo esc_attr( $from_classes ); ?>" id="<?php echo esc_attr( $args['id'] . '_from' ); ?>" name="<?php echo esc_attr( $args['name'] . '_from' ); ?>" data-range="from" value="<?php echo esc_attr( $value[0] ); ?>" />
				<label for="<?php echo esc_attr( $args['id'] . '_to' ); ?>">to</label>
				<input type="time" class="<?php echo esc_attr( $to_classes ); ?>" id="<?php echo esc_attr( $args['id'] . '_to' ); ?>" name="<?php echo esc_attr( $args['name'] . '_to' ); ?>" data-range="to" value="<?php echo esc_attr( $value[1] ); ?>" />
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
					'classes'  => array(),
					'label'    => '',
					'data'     => array(),
					'disabled' => false,
				)
			);

			if ( empty( $button['label'] ) ) {
				continue;
			}

			$button['classes'][] = 'um-button-in-group';
			if ( false !== $button['disabled'] ) {
				$button['classes'][] = 'um-disabled';
			}

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

	public static function get_formatted_mime_types( $filter ) {
		$types         = array();
		$allowed_mimes = get_allowed_mime_types();
		foreach ( $allowed_mimes as $extensions => $mime_type ) {
			$types[] = explode( '|', $extensions );
		}
		$allowed_mimes = array_merge( ...$types );
		$all_mimes     = wp_get_ext_types();

		$mime_types = array();
		foreach ( $all_mimes as $type => $extensions ) {
			$extensions = ! empty( $filter ) ? array_intersect( $allowed_mimes, $extensions, $filter ) : array_intersect( $allowed_mimes, $extensions );
			if ( empty( $extensions ) ) {
				continue;
			}

			switch ( $type ) {
				default:
					$title_type = __( 'Other', 'ultimate-member' );
					break;
				case 'image':
					$title_type = __( 'Image', 'ultimate-member' );
					break;
				case 'audio':
					$title_type = __( 'Audio', 'ultimate-member' );
					break;
				case 'video':
					$title_type = __( 'Video', 'ultimate-member' );
					break;
				case 'document':
					$title_type = __( 'Document', 'ultimate-member' );
					break;
				case 'spreadsheet':
					$title_type = __( 'Spreadsheet', 'ultimate-member' );
					break;
				case 'interactive':
					$title_type = __( 'Interactive', 'ultimate-member' );
					break;
				case 'text':
					$title_type = __( 'Text', 'ultimate-member' );
					break;
				case 'archive':
					$title_type = __( 'Archive', 'ultimate-member' );
					break;
				case 'code':
					$title_type = __( 'Code', 'ultimate-member' );
					break;
			}

			$mime_types[] = array(
				'title'      => $title_type,
				'extensions' => implode( ',', $extensions ),
			);
		}

		return $mime_types;
	}

	public static function get_svg_by_ext( $ext ) {
		$ext_types = wp_get_ext_types();

		$icon_name = 'default';
		foreach ( $ext_types as $key => $extensions ) {
			if ( is_array( $ext, $extensions, true ) ) {
				$icon_name = $key;
				break;
			}
		}

		return trailingslashit( includes_url() ) . 'images/media/' . $icon_name . '.svg';
	}

	public static function get_file_extension_icon( $ext ) {
		ob_start();
		?>
		<div class="um-file-extension">
			<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--um-gray-300, #d0d5dd)" fill="none" stroke-linecap="round" stroke-linejoin="round">
				<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
				<path d="M14 3v4a1 1 0 0 0 1 1h4" />
				<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
			</svg>
			<span class="um-file-extension-text"><?php echo esc_html( $ext ); ?></span>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function progress_bar( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'id'    => '',
				'label' => 'none', // right, bottom
				'value' => 0, // 0 - 100% absint
			)
		);

		// translators: %d is the progress percents.
		$title = sprintf( __( '%d%%', 'ultimate-member' ), $args['value'] );

		ob_start();
		?>
		<div id="<?php echo esc_attr( $args['id'] ); ?>" class="um-progress-bar" data-value="<?php echo esc_attr( $args['value'] ); ?>" title="<?php echo esc_attr( $title ); ?>">
			<div class="um-progress-bar-inner" style="width:<?php echo esc_attr( $args['value'] ); ?>%;" title="<?php echo esc_attr( $title ); ?>"></div>
		</div>
		<?php
		$progress_bar = ob_get_clean();

		if ( 'none' === $args['label'] ) {
			$content = $progress_bar;
		} else {
			ob_start();
			?>
			<div class="um-progress-bar-wrapper um-progress-bar-label-<?php echo esc_attr( $args['label'] ); ?>">
				<?php echo wp_kses( $progress_bar, UM()->get_allowed_html( 'templates' ) ); ?>
				<div class="um-progress-bar-label"><?php echo esc_html( $title ); ?></div>
			</div>
			<?php
			$content = ob_get_clean();
		}

		return $content;
	}

	public static function upload_item_placeholder( $args ) {
		$custom_placeholder = apply_filters( 'um_upload_item_placeholder', null, $args );
		if ( ! $custom_placeholder ) {
			ob_start();
			?>
			<div class="um-uploader-file-placeholder um-display-none">
				<div class="um-file-extension">
					<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--um-gray-300, #d0d5dd)" fill="none" stroke-linecap="round" stroke-linejoin="round">
						<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
						<path d="M14 3v4a1 1 0 0 0 1 1h4" />
						<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
					</svg>
					<span class="um-file-extension-text">{{{extension}}}</span>
				</div>
				<div class="um-uploader-file-data">
					<div class="um-uploader-file-data-header">
						<div class="um-uploader-file-name">{{{name}}}</div>
						<?php
						$button_content = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
  <path d="M4 7l16 0" />
  <path d="M10 11l0 6" />
  <path d="M14 11l0 6" />
  <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
  <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
</svg>';
						$button_args    = array(
							'type'          => 'button',
							'icon_position' => 'content',
							'design'        => 'link-gray',
							'size'          => 's',
							'classes'       => array( 'um-uploader-file-remove' ),
						);
						echo wp_kses( self::button( $button_content, $button_args ), UM()->get_allowed_html( 'templates' ) );
						?>
					</div>
					<div class="um-supporting-text">{{{supporting}}}</div>
					<?php echo wp_kses( self::progress_bar( array( 'label' => 'right' ) ), UM()->get_allowed_html( 'templates' ) ); ?>
				</div>
				<?php
				if ( true !== $args['async'] ) {
					$name      = $args['multiple'] ? $args['name'] . '[{{{file_id}}}][path]' : $args['name'] . '[path]';
					$hash_name = $args['multiple'] ? $args['name'] . '[{{{file_id}}}][hash]' : $args['name'] . '[hash]';
					?>
					<input type="hidden" class="um-uploaded-value" data-field="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $name ); ?>" value="" disabled />
					<input type="hidden" class="um-uploaded-value-hash" name="<?php echo esc_attr( $hash_name ); ?>" value="" disabled />
					<?php
				}
				?>
			</div>
			<?php
			$custom_placeholder = ob_get_clean();
		}

		return $custom_placeholder;
	}

	public static function uploaded_item_edit_row( $args, $edit_value_row ) {
		$custom_placeholder = apply_filters( 'um_upload_edit_list_item_row', null, $args, $edit_value_row );
		if ( is_null( $custom_placeholder ) ) {
			ob_start();
			?>
			<div class="um-uploader-file-placeholder um-display-none">
				<div class="um-file-extension">
					<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--um-gray-300, #d0d5dd)" fill="none" stroke-linecap="round" stroke-linejoin="round">
						<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
						<path d="M14 3v4a1 1 0 0 0 1 1h4" />
						<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
					</svg>
					<span class="um-file-extension-text">{{{extension}}}</span>
				</div>
				<div class="um-uploader-file-data">
					<div class="um-uploader-file-data-header">
						<div class="um-uploader-file-name">{{{name}}}</div>
						<?php
						$button_content = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
  <path d="M4 7l16 0" />
  <path d="M10 11l0 6" />
  <path d="M14 11l0 6" />
  <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
  <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
</svg>';
						$button_args    = array(
							'type'          => 'button',
							'icon_position' => 'content',
							'design'        => 'link-gray',
							'size'          => 's',
							'classes'       => array( 'um-uploader-file-remove', 'um-tip-n' ),
						);
						echo wp_kses( self::button( $button_content, $button_args ), UM()->get_allowed_html( 'templates' ) );
						?>
					</div>
					<div class="um-supporting-text">{{{supporting}}}</div>
					<?php echo wp_kses( self::progress_bar( array( 'label' => 'right' ) ), UM()->get_allowed_html( 'templates' ) ); ?>
				</div>
				<?php
				if ( true !== $args['async'] ) {
					$name = $args['multiple'] ? $args['name'] . '[{{{file_id}}}][path]' : $args['name'] . '[path]';
					?>
					<input type="hidden" class="um-uploaded-value" data-field="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $name ); ?>" value="" />
					<?php
				}
				?>
			</div>
			<?php
			$custom_placeholder = ob_get_clean();
		}

		return $custom_placeholder;
	}

	/**
	 * Uploader layout.
	 *
	 * @return string
	 */
	public static function uploader( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'id'                => '',
				'classes'           => array(),
				'async'             => true,
				'field_id'          => '',
				'name'              => '',
				'value'             => '',
				'handler'           => '',
				'multiple'          => true,
				'nonce'             => '',
				'types'             => array(), // if not specified then get all allowed
				'button'            => array(),
				'dropzone'          => true,
				'dropzone_inner'    => '',
				'files_list'        => true,
				'sortable_files'    => false,
				'max_upload_size'   => wp_max_upload_size(),
				'max_files'         => '', // Integer value of the limit files in the files list. Empty = unlimited.
				'disable_drop_zone' => false,
				'dropzone_error'    => '',
				'data'              => array(),
			)
		);

		if ( empty( $args['handler'] ) ) {
			return esc_html__( 'Unknown handler.', 'ultimate-member' );
		}

		if ( empty( $args['nonce'] ) ) {
			$args['nonce'] = wp_create_nonce( 'um_upload_' . $args['handler'] );
		}

		if ( true !== $args['async'] && empty( $args['field_id'] ) ) {
			return esc_html__( 'Unknown field id.', 'ultimate-member' );
		}

		if ( empty( $args['id'] ) ) {
			$args['id'] = wp_unique_id( $args['id'] );
		}

		$classes = array( 'um-uploader' );
		if ( ! empty( $args['classes'] ) ) {
			$classes = array_merge( $classes, $args['classes'] );
		}
		$classes = array_unique( $classes );

		$data_atts = array();
		foreach ( $args['data'] as $data_k => $data_v ) {
			$data_atts[] = 'data-' . $data_k . '="' . esc_attr( $data_v ) . '"';
		}
		if ( ! empty( $data_atts ) ) {
			$data_atts = ' ' . implode( ' ', $data_atts );
		} else {
			$data_atts = '';
		}

		$id = $args['id'];

		$mime_types_raw = self::get_formatted_mime_types( $args['types'] );
		$mime_types     = wp_json_encode( $mime_types_raw );

		if ( empty( $mime_types ) ) {
			return '';
		}

		if ( ! empty( $args['value'] ) ) {
			$args['value'] = is_array( $args['value'] ) ? $args['value'] : array( $args['value'] );
		}

		if ( empty( $args['max_upload_size'] ) ) {
			$args['max_upload_size'] = 0;
		} else {
			// Set maximum possible from WordPress native function.
			$args['max_upload_size'] = absint( $args['max_upload_size'] ) > wp_max_upload_size() ? wp_max_upload_size() : absint( $args['max_upload_size'] );
		}

		if ( ! empty( $args['dropzone'] ) && empty( $args['dropzone_inner'] ) ) {
			// Generate default HTML for dropzone inner.
			$extra_info = array();
			if ( ! empty( $args['types'] ) ) {
				// Specify extensions if not empty.
				$extensions      = array_column( $mime_types_raw, 'extensions' );
				$extensions_info = ! empty( $extensions ) ? strtoupper( str_replace( ',', ', ', implode( ',', $extensions ) ) ) . '.' : '';

				if ( '' !== $extensions_info ) {
					$pos = strrpos( $extensions_info, ', ' );
					if ( false !== $pos ) {
						$extensions_info = substr_replace( $extensions_info, __( ' or ', 'ultimate-member' ), $pos, strlen( ', ' ) );
					}

					$extra_info[] = $extensions_info;
				}
			}

			if ( ! empty( $args['max_upload_size'] ) ) {
				// Specify file size if not unlimited.
				// translators: %s: Maximum allowed file size.
				$extra_info[] = sprintf( __( 'Maximum upload file size: %s.' ), size_format( $args['max_upload_size'] ) );
			}
			$extra_info   = implode( '<br />', $extra_info );
			$link_classes = array( 'um-upload-link', 'um-link' );
			if ( ! empty( $args['disable_drop_zone'] ) ) {
				$link_classes[] = 'um-link-disabled';
			}
			ob_start();
			?>
			<span class="um-supporting-text">
				<span><a href="#" class="<?php echo esc_attr( implode( ' ', $link_classes ) ); ?>">Click to upload</a> or drag and drop</span>
				<?php if ( ! empty( $extra_info ) ) { ?>
					<span><?php echo wp_kses_post( $extra_info ); ?></span>
				<?php } ?>
			</span>
			<?php
			$args['dropzone_inner'] = ob_get_clean();
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" id="<?php echo esc_attr( $id ); ?>"<?php echo $data_atts; ?>>
			<?php
			$button_content = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-upload" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
  <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
  <path d="M7 9l5 -5l5 5" />
  <path d="M12 4l0 12" />
</svg>';
			$button_args    = array(
				'type'          => 'button',
				'icon_position' => 'content',
				'design'        => 'secondary-color',
				'size'          => 's',
				'classes'       => array( 'um-uploader-button' ),
				'data'          => array(
					'handler'    => $args['handler'],
					'mime-types' => $mime_types,
					'nonce'      => $args['nonce'],
					'multiple'   => $args['multiple'],
					'max-size'   => $args['max_upload_size'],
					'max-files'  => $args['max_files'],
				),
			);
			if ( ! empty( $args['button'] ) && is_array( $args['button'] ) ) {
				$button_content = array_key_exists( 'content', $args['button'] ) ? $args['button']['content'] : $button_content;
				unset( $args['button']['content'] );

				$args['button']['design']  = ! empty( $args['button']['design'] ) ? $args['button']['design'] : $button_args['design'];
				$args['button']['classes'] = ! empty( $args['button']['classes'] ) ? array_merge( $args['button']['classes'], $button_args['classes'] ) : $button_args['classes'];
				$args['button']['data']    = ! empty( $args['button']['data'] ) ? array_merge( $args['button']['data'], $button_args['data'] ) : $button_args['data'];
				$button_args               = $args['button'];
			}

			if ( ! empty( $args['disable_drop_zone'] ) ) {
				$button_args['disabled'] = true;
			}

			if ( ! empty( $args['dropzone'] ) ) {
				$dropzone_classes = array( 'um-uploader-dropzone' );
				if ( ! empty( $args['disable_drop_zone'] ) ) {
					$dropzone_classes[] = 'um-dropzone-disabled';
				}
				?>
				<div id="um-<?php echo esc_attr( $id ); ?>-uploader-dropzone" class="<?php echo esc_attr( implode( ' ', $dropzone_classes ) ); ?>">
					<?php echo wp_kses( self::button( $button_content, $button_args ), UM()->get_allowed_html( 'templates' ) ); ?>
					<div>
						<?php echo wp_kses( $args['dropzone_inner'], UM()->get_allowed_html( 'templates' ) ); ?>
					</div>
				</div>
				<?php
			} else {
				echo wp_kses( self::button( $button_content, $button_args ), UM()->get_allowed_html( 'templates' ) );
			}

			if ( ! empty( $args['dropzone_error'] ) ) {
				echo wp_kses( $args['dropzone_error'], UM()->get_allowed_html( 'templates' ) );
			}

			if ( ! empty( $args['files_list'] ) ) {
				echo wp_kses( self::upload_item_placeholder( $args ), UM()->get_allowed_html( 'templates' ) );
				$filelist_classes = array(
					'um-uploader-filelist',
				);

				$list_rows = '';
				if ( ! empty( $args['value'] ) ) {
					foreach ( $args['value'] as $file_row_value ) {
						$list_rows .= self::uploaded_item_edit_row( $args, $file_row_value );
					}
				}
				if ( empty( $args['value'] ) || empty( $list_rows ) ) {
					$filelist_classes[] = 'um-display-none';
				}
				if ( ! empty( $args['sortable_files'] ) ) {
					$filelist_classes[] = 'um-uploader-filelist-sortable';
				}
				?>
				<div id="um-<?php echo esc_attr( $id ); ?>-uploader-filelist" class="<?php echo esc_attr( implode( ' ', $filelist_classes ) ); ?>">
					<?php echo wp_kses( $list_rows, UM()->get_allowed_html( 'templates' ) ); ?>
				</div>
				<?php

				do_action( 'um_uploader_layout_after_files_list', $args, $list_rows );
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function lazy_image( $src, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'alt'   => '',
				'width' => '',
				'max-width' => '',
			)
		);

		$style = '';
		if ( ! empty( $args['width'] ) ) {
			$style .= 'width: ' . $args['width'] . ';';
		}
		if ( ! empty( $args['max-width'] ) ) {
			$style .= 'max-width: ' . $args['max-width'] . ';';
		}
		ob_start();
		?>
		<div class="um-image-lazyload-wrapper" style="<?php echo esc_attr( $style ); ?>">
			<div class="um-skeleton-box"></div>
			<img class="um-image-lazyload" src="<?php echo esc_url( $src ); ?>" loading="lazy" alt="<?php echo esc_attr( $args['alt'] ); ?>" />
		</div>
		<?php
		return ob_get_clean();
	}

	public static function outline_icon( $icon ) {
		ob_start();
		?>
		<div class="um-outline-icon">
			<div class="um-outline-inner">
				<?php echo wp_kses( $icon, UM()->get_allowed_html( 'templates' ) ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function alert( $text, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'        => 'error', // error, warning, success
				'dismissible' => false,
				'supporting'  => '',
				'underline'   => true,
				'has_icon'    => true,
				'classes'     => array(),
			)
		);

		$icon = '';
		if ( ! empty( $args['has_icon'] ) ) {
			switch ( $args['type'] ) {
				case 'error':
				case 'warning':
					$icon = self::outline_icon( '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>' );
					break;
				case 'success':
					$icon = self::outline_icon( '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>' );
					break;
			}
		}

		$classes = array(
			'um-alert',
			'um-alert-' . $args['type'],
		);

		if ( ! empty( $args['dismissible'] ) ) {
			$classes[] = 'um-dismissible';
		}

		if ( ! empty( $args['underline'] ) ) {
			$classes[] = 'um-alert-underline';
		}

		if ( empty( $icon ) ) {
			$classes[] = 'um-alert-no-icon';
		}

		if ( ! empty( $args['classes'] ) ) {
			$classes = array_merge( $classes, $args['classes'] );
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<div class="um-alert-message">
				<?php if ( ! empty( $icon ) ) { ?>
					<div class="um-alert-icon"><?php echo wp_kses( $icon, UM()->get_allowed_html( 'templates' ) ); ?></div>
				<?php } ?>
				<span class="um-alert-texts">
					<span class="um-alert-text"><?php echo wp_kses( $text, UM()->get_allowed_html( 'templates' ) ); ?></span>
					<?php if ( ! empty( $args['supporting'] ) ) { ?>
						<span class="um-supporting-text"><?php echo wp_kses( $args['supporting'], UM()->get_allowed_html( 'templates' ) ); ?></span>
					<?php } ?>
				</span>
			</div>
			<?php
			if ( ! empty( $args['dismissible'] ) ) {
				$button_content = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-x"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12" /><path d="M6 6l12 12" /></svg>';
				$button_args    = array(
					'type'          => 'button',
					'icon_position' => 'content',
					'design'        => 'link-gray',
					'size'          => 's',
					'classes'       => array( 'um-alert-dismiss' ),
				);
				echo wp_kses( self::button( $button_content, $button_args ), UM()->get_allowed_html( 'templates' ) );
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function emoji_picker( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'link_classes'     => array(),
				'size'             => 's',
				'position_parent'  => '',
				'default-position' => '',
				'nonce'            => '',
			)
		);

		$args['link_classes'][] = 'um-emoji-picker-link';
		ob_start();
		?>
		<div class="um-emoji-picker">
			<?php
			$emoji_svg_html = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mood-smile" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
				<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
				<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
				<path d="M9 10l.01 0" />
				<path d="M15 10l.01 0" />
				<path d="M9.5 15a3.5 3.5 0 0 0 5 0" />
			</svg>';

			$link_args = array(
				'design'        => 'link-color',
				'type'          => 'link',
				'size'          => $args['size'],
				'title'         => __( 'Emoji', 'ultimate-member' ),
				'classes'       => $args['link_classes'],
				'icon_position' => 'content',
			);

			if ( ! empty( $args['position_parent'] ) ) {
				$link_args['data']['position_parent'] = $args['position_parent'];
			}

			if ( ! empty( $args['default-position'] ) ) {
				$link_args['data']['default-position'] = $args['default-position'];
			}

			if ( ! empty( $args['nonce'] ) ) {
				$link_args['data']['nonce'] = $args['nonce'];
			}

			echo wp_kses(
				UM()->frontend()::layouts()::link(
					$emoji_svg_html,
					$link_args
				),
				UM()->get_allowed_html( 'templates' )
			);
			?>
			<div class="um-emoji-list"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Gif picker.
	 *
	 * @param array $args {
	 *     Gif picker additional arguments.
	 *
	 * @type string[] $classes Classes for the gif picker link.
	 * @type string   $size    Gif picker size. Uses 's', 'm', 'l'. Default 's'.
	 * @type array    $data    Gif picker data.
	 * }
	 *
	 * @return string
	 */
	public static function gif_picker( $args = array() ) {
		$api_key = UM()->options()->get( 'tenor_api_key' );
		if ( empty( $api_key ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'classes' => array(),
				'size'    => 's',
				'data'    => array(),
			)
		);

		$args['classes'][] = 'um-gif-picker-link';

		$gif_svg_html = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-gif" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
			<path stroke="none" d="M0 0h24v24H0z" fill="none" />
			<path d="M8 8h-2a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2v-4h-1" />
			<path d="M12 8v8" />
			<path d="M16 12h3" />
			<path d="M20 8h-4v8" />
		</svg>';

		return UM()->frontend()::layouts()::link(
			$gif_svg_html,
			array(
				'design'        => 'link-color',
				'type'          => 'link',
				'size'          => $args['size'],
				'title'         => __( 'GIF', 'ultimate-member' ),
				'classes'       => $args['classes'],
				'icon_position' => 'content',
				'data'          => $args['data'],
			)
		);
	}

	public static function gif_list( $args = array() ) {
		$api_key = UM()->options()->get( 'tenor_api_key' );
		if ( empty( $api_key ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'classes'  => array(),
				'keyword'  => 'Hello',
				'async'    => false,
				'per_page' => 20,
			)
		);

		$classes = array( 'um-gif-list-wrapper' );

		$pagination_loader_classes = array( 'um-gif-list-pagination-loader-wrapper', 'um-display-none' );

		$pagination   = '';
		$gifs_content = '';
		if ( false === $args['async'] ) {
			$classes[] = 'um-gif-list-async';

			$url = 'https://tenor.googleapis.com/v2/search?key=' . $api_key . '&q=' . esc_attr( $args['keyword'] ) . '&limit=' . esc_attr( $args['per_page'] );

			$request_args = array(
				'headers' => array(
					'Referer' => get_site_url(),
				),
			);

			$response = wp_remote_get( $url, $request_args );
			$result   = json_decode( wp_remote_retrieve_body( $response ), true );

			$images = array();
			foreach ( $result['results'] as $image ) {
				$images[] = array(
					'preview' => $image['media_formats']['nanogif']['url'],
					'image'   => $image['media_formats']['gif']['url'],
				);
			}

			$pagination = ! empty( $result['next'] ) ? $result['next'] : '';

			foreach ( $images as $im ) {
				$gifs_content .= '<img class="um-gif-img" data-um_gif_img data-image="' . esc_attr( $im['image'] ) . '" src="' . esc_url( $im['preview'] ) . '" />';
			}
		}

		$classes = array_merge( $classes, $args['classes'] );
		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'um_get_gif_list' ) ); ?>" data-next="<?php echo esc_attr( $pagination ); ?>" data-per_page="<?php echo esc_attr( $args['per_page'] ); ?>">
			<?php
			$search_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
				<path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
				<path d="M21 21l-6 -6" />
			</svg>';
			?>
			<div class="um-gif-list-search-box">
				<span class="screen-reader-text"><?php esc_html_e( 'Search..', 'ultimate-member' ); ?></span>
				<input type="search" class="um-gif-list-search" placeholder="<?php esc_attr_e( 'Search..', 'ultimate-member' ); ?>" />
				<?php
				echo wp_kses(
					self::button(
						$search_icon,
						array(
							'design'        => 'link-color',
							'size'          => 's',
							'title'         => __( 'Search', 'ultimate-member' ),
							'classes'       => array( 'um-gif-list-search-btn' ),
							'icon_position' => 'content',
						)
					) . self::ajax_loader( 's', array( 'classes' => array( 'um-gif-list-loader', 'um-display-none' ) ) ),
					UM()->get_allowed_html( 'templates' )
				);
				?>
			</div>
			<div class="um-gif-list">
				<?php echo wp_kses( $gifs_content, UM()->get_allowed_html( 'templates' ) ); ?>
				<div class="<?php echo esc_attr( implode( ' ', $pagination_loader_classes ) ); ?>">
					<?php
					echo wp_kses(
						self::ajax_loader( 's', array( 'classes' => array( 'um-gif-list-pagination-loader' ) ) ),
						UM()->get_allowed_html( 'templates' )
					);
					?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
