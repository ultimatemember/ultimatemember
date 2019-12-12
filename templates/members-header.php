<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>


<script type="text/template" id="tmpl-um-members-header">
	<div class="um-members-intro">
		<div class="um-members-total">
			<# if ( data.pagination.total_users == 1 ) { #>
				{{{data.pagination.header_single}}}
			<# } else if ( data.pagination.total_users > 1 ) { #>
				{{{data.pagination.header}}}
			<# } #>
		</div>
	</div>

	<div class="um-clear"></div>
</script>