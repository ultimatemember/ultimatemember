<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div id="UM_fonticons" style="display:none">

	<div class="um-admin-modal-head">
		<h3>
			<?php
			// translators: %s: icons nubber.
			echo wp_kses( sprintf( __( 'Choose from %s available icons', 'ultimate-member' ), count( UM()->fonticons()->all ) ), UM()->get_allowed_html( 'admin_notice' ) );
			?>
		</h3>
	</div>

	<div class="um-admin-modal-body"></div>

	<div class="um-admin-modal-foot">
		<a href="javascript:void(0);" class="button-primary um-admin-modal-back" data-code=""><?php _e( 'Finish', 'ultimate-member' ) ?></a>
		<a href="javascript:void(0);" class="button um-admin-modal-back um-admin-modal-cancel" data-action="UM_remove_modal"><?php _e( 'Cancel', 'ultimate-member' ) ?></a>
	</div>

</div>
