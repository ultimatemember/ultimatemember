<?php
/**
 * Template for the members directory pagination JS template
 *
 * @version 2.9.0
 *
 * @var int  $pages
 * @var int  $page
 * @var int  $per_page
 * @var int  $pages_count
 * @var bool $previous_disabled
 * @var bool $next_disabled
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="um-pagination">
	<div class="um-pagination-view-mobile um-responsive um-ui-xs">
		<?php
		echo wp_kses(
			UM()->frontend()::layouts()::button(
				__( 'Previous', 'ultimate-member' ),
				array(
					'size'     => 's',
					'disabled' => $previous_disabled,
					'classes'  => array(
						'um-pagination-item',
						'um-pagination-arrow',
					),
					'data'     => array(
						'page' => 'prev',
					),
				)
			),
			UM()->get_allowed_html( 'templates' )
		);
		?>
		<?php // translators: %s is total pages count. ?>
		<label><?php esc_html_e( 'Page', 'ultimate-member' ); ?><input type="number" value="<?php echo esc_attr( $page ); ?>" min="1" max="<?php echo esc_attr( $pages_count ); ?>" /><?php echo esc_html( sprintf( __( 'of %s', 'ultimate-member' ), $pages_count ) ); ?></label>
		<?php
		echo wp_kses(
			UM()->frontend()::layouts()::button(
				__( 'Next', 'ultimate-member' ),
				array(
					'size'     => 's',
					'disabled' => $next_disabled,
					'classes'  => array(
						'um-pagination-item',
						'um-pagination-arrow',
					),
					'data'     => array(
						'page' => 'next',
					),
				)
			),
			UM()->get_allowed_html( 'templates' )
		);
		?>
	</div>
	<div class="um-pagination-view-list um-responsive um-ui-s um-ui-m um-ui-l um-ui-xl">
		<?php
		$prev_classes = array( 'um-pagination-item', 'um-pagination-arrow' );
		if ( $previous_disabled ) {
			$prev_classes[] = 'disabled';
		}
		?>
		<span class="<?php echo esc_attr( implode( ' ', $prev_classes ) ); ?>" data-page="prev" aria-label="<?php esc_attr_e( 'Previous page', 'ultimate-member' ); ?>"><?php esc_html_e( 'Previous', 'ultimate-member' ); ?></span>
		<?php
		foreach ( $pages as $page_index => $page_data ) {
			$page_classes = array( 'um-pagination-item' );
			if ( ! empty( $page_data['current'] ) ) {
				$page_classes[] = 'current';
			}
			?>
			<span class="<?php echo esc_attr( implode( ' ', $page_classes ) ); ?>" data-page="<?php echo esc_attr( $page_index ); ?>"><?php echo esc_html( $page_data['label'] ); ?></span>
			<?php
		}
		$next_classes = array( 'um-pagination-item', 'um-pagination-arrow' );
		if ( $next_disabled ) {
			$next_classes[] = 'disabled';
		}
		?>
		<span class="<?php echo esc_attr( implode( ' ', $next_classes ) ); ?>" data-page="next" aria-label="<?php esc_attr_e( 'Next page', 'ultimate-member' ); ?>"><?php esc_html_e( 'Next', 'ultimate-member' ); ?></span>
	</div>
</div>
