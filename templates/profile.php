<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_actions = true;
$avatar_url  = UM()->common()->user()->get_avatar_url( um_profile_id() );
?>

<div class="um um-profile-wrapper um-<?php echo esc_attr( $form_id ); ?> um-role-<?php echo esc_attr( um_user( 'role' ) ); ?> ">

	<div class="um-profile-photo<?php if ( $has_actions ) { ?> um-clickable<?php } ?>">
		<?php echo UM()->common()->user()->get_avatar( um_profile_id(), 'xl' ); ?>

		<?php if ( $has_actions ) { ?>
			<div class="um-profile-photo-overlay"><div class="um-ajax-loading"></div></div>

			<div class="um-new-dropdown" data-element=".um-profile-photo" data-trigger="click" data-parent=".um-profile-wrapper" data-width="190" data-place="bottom-right">
				<ul>
					<li><a href="javascript:void(0);" class="um-change-profile-photo" data-user_id="<?php echo esc_attr( um_profile_id() ); ?>"><?php esc_html_e( 'Change photo', 'ultimate-member' ); ?></a></li>
					<li style="<?php if ( false === $avatar_url ) { ?>display:none;<?php } ?>"><a href="javascript:void(0);" class="um-reset-profile-photo" data-user_id="<?php echo esc_attr( um_profile_id() ); ?>"><?php esc_html_e( 'Remove photo', 'ultimate-member' ); ?></a></li>
				</ul>
			</div>
		<?php } ?>
	</div>

</div>
