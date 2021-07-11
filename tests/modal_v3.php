<?php
namespace um\tests;

/*
 * Test JS library UM-Modal
 */

add_action( 'admin_menu', function() {
	add_submenu_page( 'ultimatemember', __( 'UM-Modal', 'ultimatemember' ), __( 'UM-Modal', 'ultimatemember' ), 'administrator', 'um-modal', '\um\tests\um_modal' );
}, 9999 );

function um_modal() {
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php _e( 'UM-Modal testing', 'ultimatemember' ); ?></h1>

	<?php
	$cover_photo_small = um_get_cover_uri( um_profile( 'cover_photo' ), 300 );
	$cover_photo_large = um_get_cover_uri( um_profile( 'cover_photo' ), 600 );
	?>

	<h2 class="title">Image popup</h2>
	<a href="#" class="um-photo-modal" data-src="<?php echo esc_url( $cover_photo_large ); ?>">
		<img src="<?php echo esc_url( $cover_photo_small ); ?>" alt="image upload" title="Image">
	</a>

	</div>

<script type="text/template" id="tmpl-um-modal-photo">
	<div class="um-modal is-photo">
		<span data-action="um_remove_modal" class="um-modal-close" aria-label="Close view photo modal"><i class="um-faicon-times"></i></span>
		<div class="um-modal-body um-photo"></div>
	</div>
</script>

<script type="text/javascript">
	jQuery(function(){
		jQuery('.um-photo-modal').umModalBtn( 'Hello!', {
			template: 'um-modal-photo'
		} );
	});
</script>

	<?php
}