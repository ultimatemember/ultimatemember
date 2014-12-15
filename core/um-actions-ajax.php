<?php
	
	/***
	***	@Global ajax URLs
	***/
	add_action('wp_head','ultimatemember_ajax_urls');
	add_action('admin_head','ultimatemember_ajax_urls');
	function ultimatemember_ajax_urls() { ?>
		
		<script type="text/javascript">
		
		var ultimatemember_image_upload_url = '<?php echo um_url . 'core/lib/upload/um-image-upload.php'; ?>';
		var ultimatemember_file_upload_url = '<?php echo um_url . 'core/lib/upload/um-file-upload.php'; ?>';
		var ultimatemember_ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';
		
		</script>
		
	<?php
	}