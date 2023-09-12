<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $args['max_width'] ) && ! isset( $args['align'] ) ) {
	return;
}
?>
<style>
	<?php if ( isset( $args['max_width'] ) && $args['max_width'] ) { ?>
		.um-<?php echo esc_attr( $args['form_id'] ); ?>.um {
			max-width: <?php echo esc_attr( $args['max_width'] ); ?>;
		}
	<?php } ?>
	<?php if ( isset( $args['align'] ) && in_array( $args['align'], array( 'left', 'right' ), true ) ) { ?>
		.um-<?php echo esc_attr( $args['form_id'] ); ?>.um {
			margin-<?php echo esc_attr( $args['align'] ); ?>: 0px !important;
		}
	<?php } ?>
</style>
