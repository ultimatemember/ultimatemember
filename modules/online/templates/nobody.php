<?php
/**
 * Template for the UM Online Users.
 * Used for "Ultimate Member - Online Users" widget.
 *
 * Caller: method Online_Shortcode->ultimatemember_online()
 * Shortcode: [ultimatemember_online]
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/um-online/nobody.php
 */

if ( ! defined( 'ABSPATH' ) ) exit; ?>

<p class="um-online-none">
	<?php _e( 'No one is online right now', 'ultimate-member' ); ?>
</p>