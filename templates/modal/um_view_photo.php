<?php
/**
 * Template for the modal photo
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/modal/um_view_photo.php
 *
 * @version 2.6.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div id="um_view_photo" style="display:none">

	<a href="javascript:void(0);" data-action="um_remove_modal" class="um-modal-close"
	   aria-label="<?php esc_attr_e( 'Close view photo modal', 'ultimate-member' ) ?>">
		<i class="um-faicon-times"></i>
	</a>

	<div class="um-modal-body photo">
		<div class="um-modal-photo"></div>
	</div>

</div>
