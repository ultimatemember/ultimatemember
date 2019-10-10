<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<style type="text/css">

	<?php if ( ! $photosize || $photosize == 'original' ) {
		//cannot be more than metadefault value
		$photosize = um_get_metadefault( 'profile_photosize' );
	}
	$photosize = filter_var( $photosize, FILTER_SANITIZE_NUMBER_INT );

	$photosize_up = ( $photosize / 2 ) + 10;
	$meta_padding = ( $photosize + 60 ) . 'px';

	if ( $area_max_width ) {
		print "
		.um-$form_id.um .um-profile-body {
			max-width: $area_max_width;
		}
		";
	}

	print "
	.um-$form_id.um .um-profile-photo a.um-profile-photo-img {
		width: ".$photosize."px;
		height: ".$photosize."px;
	}
	";

	print "
	.um-$form_id.um .um-profile-photo a.um-profile-photo-img {
		top: -".$photosize_up."px;
	}
	";

	if ( is_rtl() ) {
		print "
		.um-$form_id.um .um-profile-meta {
			padding-right: $meta_padding;
		}
		";
	} else {
		print "
		.um-$form_id.um .um-profile-meta {
			padding-left: $meta_padding;
		}
		";
	}
	?>
</style>