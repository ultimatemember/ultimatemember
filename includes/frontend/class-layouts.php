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
	 * Unified dropdown menu
	 *
	 * @param string $element
	 * @param string $trigger
	 * @param array  $items
	 * @param string $parent
	 */
	public static function dropdown_menu( $element, $trigger, $items = array(), $parent = '' ) {
		// !!!!Important: all links in the dropdown items must have "class" attribute
		?>
		<div class="um-dropdown-wrapper">
			<div class="um-dropdown-toggle <?php echo esc_attr( $element ); ?>"></div>
			<div class="um-dropdown" data-element=".<?php echo esc_attr( $element ); ?>" data-trigger="<?php echo esc_attr( $trigger ); ?>" data-parent="<?php echo esc_attr( $parent ); ?>">
				<ul>
					<?php
					foreach ( $items as $v ) {
						if ( is_array( $v ) ) {
							?>
							</ul>
							<?php foreach ( $v as $sub_v ) { ?>
								<li><?php echo wp_kses_post( $sub_v ); ?></li>
							<?php } ?>
							<ul>
							<?php
						} else {
							?>
							<li><?php echo wp_kses_post( $v ); ?></li>
							<?php
						}
					}
					?>
				</ul>
			</div>
		</div>

		<?php
	}

	/**
	 * @param string $type 'button|submit'
	 *
	 * @return void
	 */
	public static function button( $content, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'     => 'button',
				'primary'  => false,
				'content'  => '',
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
		?>

		<button type="<?php echo esc_attr( $args['type'] ); ?>" class="<?php echo esc_attr( $classes ); ?>" <?php disabled( $args['disabled'] ); ?>><?php echo wp_kses_post( $content ); ?></button>

		<?php
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
