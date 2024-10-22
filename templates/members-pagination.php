<?php
/**
 * Template for the members directory pagination JS template
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/members-pagination.php
 *
 * Page: "Members"
 *
 * @version 2.6.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>


<script type="text/template" id="tmpl-um-members-pagination">
	<# if ( data.pagination.pages_to_show.length > 0 ) { #>
		<div class="um-members-pagidrop uimob340-show uimob500-show">
			<?php _e( 'Jump to page:','ultimate-member' ); ?>
			<select class="um-s2 um-members-pagi-dropdown" style="width: 100px;display:inline-block;">
				<# _.each( data.pagination.pages_to_show, function( page, key, list ) { #>
					<option value="{{{page}}}" <# if ( page == data.pagination.current_page ) { #>selected<# } #>>{{{page}}} <?php _e( 'of','ultimate-member' ) ?> {{{data.pagination.total_pages}}}</option>
				<# }); #>
			</select>
		</div>

		<div class="um-members-pagi uimob340-hide uimob500-hide">
			<span class="pagi pagi-arrow <# if ( data.pagination.current_page == 1 ) { #>disabled<# } #>" data-page="first" aria-label="<?php esc_attr_e( 'First page', 'ultimate-member' ); ?>"><i class="um-faicon-angle-double-left"></i></span>
			<span class="pagi pagi-arrow <# if ( data.pagination.current_page == 1 ) { #>disabled<# } #>" data-page="prev" aria-label="<?php esc_attr_e( 'Previous page', 'ultimate-member' ); ?>"><i class="um-faicon-angle-left"></i></span>

			<# _.each( data.pagination.pages_to_show, function( page, key, list ) { #>
				<span class="pagi <# if ( page == data.pagination.current_page ) { #>current<# } #>" data-page="{{{page}}}">{{{page}}}</span>
			<# }); #>

			<span class="pagi pagi-arrow <# if ( data.pagination.current_page == data.pagination.total_pages ) { #>disabled<# } #>" data-page="next" aria-label="<?php esc_attr_e( 'Next page', 'ultimate-member' ); ?>"><i class="um-faicon-angle-right"></i></span>
			<span class="pagi pagi-arrow <# if ( data.pagination.current_page == data.pagination.total_pages ) { #>disabled<# } #>" data-page="last" aria-label="<?php esc_attr_e( 'Last page', 'ultimate-member' ); ?>"><i class="um-faicon-angle-double-right"></i></span>
		</div>
	<# } #>
</script>
