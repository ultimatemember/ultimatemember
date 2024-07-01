<?php
/**
 * Template for the profile page
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/profile.php
 *
 * Page: "Profile"
 *
 * @version 2.6.9
 *
 * @var array  $roles
 * @var int    $form_id
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wrapper_classes = array(
	'um',
	'um-profile',
	"um-{$form_id}",
);

$roles_classes = $roles;
foreach ( $roles_classes as &$value ) {
	$value = 'um-user-role-' . $value;
}
unset( $value );

$wrapper_classes = array_merge( $wrapper_classes, $roles_classes );
$wrapper_classes = implode( ' ', $wrapper_classes );

$avatar           = '';
$header_row_class = 'um-profile-header';
if ( get_option( 'show_avatars' ) ) {
	$avatar            = get_avatar( um_user( 'ID' ), 50, 'mystery' );
	$header_row_class .= ' has-avatar';
}
?>

<div class="<?php echo esc_attr( $wrapper_classes ); ?>">
	<form method="post" action="">
		<div class="um-cover-photo-wrapper"></div>
		<div class="<?php echo esc_attr( $header_row_class ); ?>">
			<?php if ( ! empty( $avatar ) ) { ?>
				<div class="um-profile-photo">
					<?php echo $avatar; ?>
				</div>
			<?php } ?>
			<div class="um-profile-header-content">
				<div class="um-profile-header-content">
					<div class="um-profile-title">Profile</div>
					<div class="um-profile-description">Update your photo and personal details.</div>
				</div>
				<div class="um-profile-header-content">
					<a href="#" class="um-button">Cancel</a>
					<input type="submit" class="um-button um-button-alt" value="Save" />
				</div>
			</div>
		</div>
	</form>
</div>
