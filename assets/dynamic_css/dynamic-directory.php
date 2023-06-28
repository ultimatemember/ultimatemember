<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $args['css_profile_card_bg'] ) && empty( $args['css_card_thickness'] ) &&
	empty( $args['css_profile_card_text'] ) && empty( $args['css_card_bordercolor'] ) &&
	empty( $args['css_img_bordercolor'] ) ) {
	return;
}
?>
<style>
	<?php if ( ! empty( $args['css_profile_card_bg'] ) ) { ?>
		.um-<?php echo esc_attr( $args['form_id'] ); ?> .um-member {
			background: <?php echo esc_attr( $args['css_profile_card_bg'] ); ?>;
		}
	<?php } ?>
	<?php if ( ! empty( $args['css_card_thickness'] ) ) { ?>
		.um-<?php echo esc_attr( $args['form_id'] ); ?> .um-member {
			border-width: <?php echo esc_attr( $args['css_card_thickness'] ); ?>;
		}
	<?php } ?>
	<?php if ( ! empty( $args['css_profile_card_text'] ) ) { ?>
		.um-<?php echo esc_attr( $args['form_id'] ); ?> .um-member-card * {
			color: <?php echo esc_attr( $args['css_profile_card_text'] ); ?>;
		}
	<?php } ?>
	<?php if ( ! empty( $args['css_card_bordercolor'] ) ) { ?>
		.um-<?php echo esc_attr( $args['form_id'] ); ?> .um-member {
			border-color: <?php echo esc_attr( $args['css_card_bordercolor'] ); ?>;
		}
	<?php } ?>
	<?php if ( ! empty( $args['css_img_bordercolor'] ) ) { ?>
		.um-<?php echo esc_attr( $args['form_id'] ); ?> .um-member-photo img {
			border-color: <?php echo esc_attr( $args['css_img_bordercolor'] ); ?>;
		}
	<?php } ?>
</style>
