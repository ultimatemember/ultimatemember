<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $um_settings_current_tab, $um_settings_current_subtab;
?>

<div id="um-settings-wrap" class="wrap">
	<h1><?php esc_html_e( 'Ultimate Member - Settings', 'ultimate-member' ); ?></h1>

	<?php echo UM()->admin()->settings()->generate_tabs_menu() . UM()->admin()->settings()->generate_subtabs_menu( $um_settings_current_tab ); ?>

	<div class="clear"></div>
</div>
