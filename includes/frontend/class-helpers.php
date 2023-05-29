<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\frontend\Helpers' ) ) {

	/**
	 * Class Helpers
	 *
	 * @package um\frontend
	 */
	class Helpers {

		/**
		 * Helpers constructor.
		 */
		public function __construct() {
		}

		/**
		 * New menu
		 *
		 * @param string $element
		 * @param string $trigger
		 * @param array $items
		 * @param string $parent
		 */
		public function dropdown_menu( $element, $trigger, $items = array(), $parent = '' ) {
			// !!!!Important: all links in the dropdown items must have "class" attribute
			?>

			<div class="um-new-dropdown" data-element="<?php echo esc_attr( $element ); ?>" data-trigger="<?php echo esc_attr( $trigger ); ?>" data-parent="<?php echo esc_attr( $parent ); ?>">
				<ul>
					<?php foreach ( $items as $v ) { ?>
						<li><?php echo wp_kses( $v, UM()->get_allowed_html( 'templates' ) ); ?></li>
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
		 * @param int    $width
		 * @param string $place
		 */
		public function dropdown_menu_js( $element, $trigger, $item, $additional_attributes = '', $parent = '', $width = 150, $place = '' ) {
			?>

			<div class="um-new-dropdown" data-element="<?php echo esc_attr( $element ); ?>" data-trigger="<?php echo esc_attr( $trigger ); ?>" data-parent="<?php echo esc_attr( $parent ); ?>" data-width="<?php echo esc_attr( $width ); ?>" data-place="<?php echo esc_attr( $place ); ?>">
				<ul>
					<# _.each( <?php echo $item; ?>.dropdown_actions, function( action, key, list ) { #>
					<li><a href="<# if ( typeof action.url != 'undefined' ) { #>{{{action.url}}}<# } else { #>javascript:void(0);<# }#>" class="{{{key}}}"<?php echo $additional_attributes ? " $additional_attributes" : '' ?>>{{{action.title}}}</a></li>
					<# }); #>
				</ul>
			</div>

			<?php
		}
	}
}
