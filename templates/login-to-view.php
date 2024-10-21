<?php
/**
 * Template for the login only content, locked message
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/login-to-view.php
 *
 * Call: function um_loggedin()
 *
 * @version 2.8.7
 *
 * @var string $lock_text
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="um-locked-content">
	<div class="um-locked-content-msg"><?php echo wp_kses( $lock_text, UM()->get_allowed_html( 'templates' ) ); ?></div>
</div>
