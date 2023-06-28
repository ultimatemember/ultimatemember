<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $args['photosize'] ) || 'original' === $args['photosize'] ) {
	$args['photosize'] = um_get_metadefault( 'profile_photosize' ); // Cannot be more than metadefault value.
}

$args['photosize'] = absint( $args['photosize'] );

$photosize_up = ( $args['photosize'] / 2 ) + 10;
$meta_padding = ( $args['photosize'] + 60 ) . 'px';
?>
<style>
	<?php if ( ! empty( $args['area_max_width'] ) ) { ?>
		.um-<?php echo esc_attr( $args['form_id'] ); ?>.um .um-profile-body {
			max-width: <?php echo esc_attr( $args['area_max_width'] ); ?>;
		}
	<?php } ?>
	.um-<?php echo esc_attr( $args['form_id'] ); ?>.um .um-profile-photo a.um-profile-photo-img {
		width: <?php echo esc_attr( $args['photosize'] ); ?>px;
		height: <?php echo esc_attr( $args['photosize'] ); ?>px;
	}
	.um-<?php echo esc_attr( $args['form_id'] ); ?>.um .um-profile-photo a.um-profile-photo-img {
		top: -<?php echo esc_attr( $photosize_up ); ?>px;
	}
	<?php if ( is_rtl() ) { ?>
		.um-<?php echo esc_attr( $args['form_id'] ); ?>.um .um-profile-meta {
			padding-right: <?php echo esc_attr( $meta_padding ); ?>;
		}
	<?php } else { ?>
		.um-<?php echo esc_attr( $args['form_id'] ); ?>.um .um-profile-meta {
			padding-left: <?php echo esc_attr( $meta_padding ); ?>;
		}
	<?php } ?>
</style>
