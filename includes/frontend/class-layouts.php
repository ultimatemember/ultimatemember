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

		<div class="um-dropdown" data-element="<?php echo esc_attr( $element ); ?>" data-trigger="<?php echo esc_attr( $trigger ); ?>" data-parent="<?php echo esc_attr( $parent ); ?>">
			<ul>
				<?php foreach ( $items as $v ) { ?>
					<li><?php echo wp_kses_post( $v ); ?></li>
				<?php } ?>
			</ul>
		</div>

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
