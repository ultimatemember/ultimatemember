<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<script type="text/template" id="tmpl-um-members-pagination">
	<# if ( Object.keys( data.pagination.pages_to_show ).length > 0 ) { #>
		<div class="um-members-pagidrop um-responsive um-ui-xs um-ui-s">
			<?php _e( 'Jump to page:','ultimate-member' ); ?>
			<select class="um-members-pagi-dropdown" style="width: 100px;display:inline-block;">
				<# _.each( data.pagination.pages_to_show, function( page, key, list ) { #>
					<option value="{{{key}}}" <# if ( page.current ) { #>selected<# } #>>{{{page.label}}} <?php _e( 'of','ultimate-member' ) ?> {{{data.pagination.total_pages}}}</option>
				<# }); #>
			</select>
		</div>

		<div class="um-members-pagi um-responsive um-ui-m um-ui-l um-ui-xl">
			<span class="pagi pagi-arrow <# if ( data.pagination.current_page == 1 ) { #>disabled<# } #>" data-page="prev" aria-label="<?php esc_attr_e( 'Previous page', 'ultimate-member' ); ?>"><?php esc_html_e( 'Previous', 'ultimate-member' ); ?></span>

			<# _.each( data.pagination.pages_to_show, function( page, key, list ) { #>
				<span class="pagi <# if ( page.current ) { #>current<# } #>" data-page="{{{key}}}">{{{page.label}}}</span>
			<# }); #>

			<span class="pagi pagi-arrow <# if ( data.pagination.current_page == data.pagination.total_pages ) { #>disabled<# } #>" data-page="next" aria-label="<?php esc_attr_e( 'Next page', 'ultimate-member' ); ?>"><?php esc_html_e( 'Next', 'ultimate-member' ); ?></span>
		</div>
	<# } #>
</script>
