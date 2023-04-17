<?php
/**
 * Template for the login only content, locked message
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/login-to-view.php
 *
 * Call: function um_loggedin()
 *
 * @version 2.6.1
 *
 * @var string $lock_text
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="um-locked-content">

	<div class="um-locked-content-msg"><?php echo htmlspecialchars_decode( $lock_text ); ?></div>

</div>
