<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="UM_preview_form" style="display:none">
	<div class="um-admin-modal-head">
		<h3><?php esc_html_e( 'Live Form Preview', 'ultimate-member' ); ?></h3>
	</div>

	<div class="um-admin-modal-body"></div>

	<div class="um-admin-modal-foot">
		<a href="#" class="button-primary" data-action="UM_remove_modal"><?php esc_html_e( 'Continue editing', 'ultimate-member' ); ?></a>
	</div>
</div>
