<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_core = get_post_meta( get_the_ID(), '_um_core', true ); ?>

<div class="um-admin-boxed-links um-admin-ajaxlink<?php echo $is_core ? ' is-core-form' : ''; ?>">

	<?php if ( $is_core ) { ?>
		<p>
			<strong><?php esc_html_e( 'Note: ', 'ultimate-member' ); ?></strong><?php esc_html_e( 'Form type cannot be changed for the default forms.', 'ultimate-member' ); ?>
		</p>
	<?php } ?>

	<a href="javascript:void(0);" data-role="register">
		<?php esc_html_e( 'Registration Form', 'ultimate-member' ); ?>
	</a>

	<a href="javascript:void(0);" data-role="profile">
		<?php esc_html_e( 'Profile Form', 'ultimate-member' ); ?>
	</a>

	<a href="javascript:void(0);" data-role="login">
		<?php esc_html_e( 'Login Form', 'ultimate-member' ); ?>
	</a>

	<input type="hidden" name="form[_um_mode]" id="form__um_mode" value="<?php echo esc_attr( UM()->query()->get_meta_value( '_um_mode', null, 'register' ) ); ?>" />
</div>
<div class="clear"></div>
