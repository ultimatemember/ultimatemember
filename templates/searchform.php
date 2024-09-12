<?php
/**
 * Template for the search form
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/searchform.php
 *
 * Call: function ultimatemember_searchform()
 *
 * @version 2.6.1
 *
 * @var string $members_page
 * @var string $search_value
 * @var array  $query
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="search-form um-search-form" data-members_page="<?php echo esc_url( $members_page ); ?>">
	<?php foreach ( array_keys( $query ) as $key ) { ?>
		<input type="hidden" name="um-search-keys[]" value="<?php echo esc_attr( $key ) ?>" />
	<?php } ?>
	<div class="um-search-area">
		<span class="screen-reader-text"><?php echo _x( 'Search for:', 'label' ); ?></span>
		<input type="search" class="um-search-field search-field" placeholder="<?php echo esc_attr_x( 'Search &hellip;', 'placeholder' ); ?>" value="<?php echo esc_attr( $search_value ); ?>" name="search" title="<?php echo esc_attr_x( 'Search for:', 'label' ); ?>" />
		<a href="javascript:void(0);" id="um-search-button" class="um-search-icon um-faicon um-faicon-search"></a>
	</div>
</div>
