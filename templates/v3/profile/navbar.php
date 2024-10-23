<?php
/**
 * Template for the navigation bar of profile page
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/v3/profile/navbar.php
 *
 * Page: "Profile"
 *
 * @version 2.9.0
 *
 * @var int    $current_user_id
 * @var int    $user_profile_id
 * @var array  $wrapper_classes
 * @var array  $profile_args
 * @var string $content
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
	<?php echo wp_kses( $content, UM()->get_allowed_html( 'templates' ) ); ?>
</div>
