<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="um-admin-metabox">
	<p><?php echo UM()->common()->shortcodes()->get_shortcode( get_the_ID() ); ?></p>
</div>
