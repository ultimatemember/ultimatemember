<?php
/**
 * Template for the search form
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/searchform.php
 *
 * Call: function ultimatemember_searchform()
 *
 * @version 2.11.2
 *
 * @var string $search_value
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="um search-form um-search-form" data-nonce="<?php echo esc_attr( wp_create_nonce( 'um_search_widget_request' ) ); ?>">
	<div class="um-form um-search-area">
		<span class="screen-reader-text"><?php echo esc_html_x( 'Search for:', 'label', 'ultimate-member' ); ?></span>
		<input type="search" class="um-search-field search-field" placeholder="<?php echo esc_attr_x( 'Search &hellip;', 'placeholder', 'ultimate-member' ); ?>" value="<?php echo esc_attr( $search_value ); ?>" name="search" title="<?php echo esc_attr_x( 'Search for:', 'label', 'ultimate-member' ); ?>" />
		<a href="#" id="um-search-button" class="um-search-icon um-faicon um-faicon-search"></a>
	</div>
</div>
