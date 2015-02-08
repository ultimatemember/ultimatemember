<div class="wrap about-wrap um-about-wrap">

	<h1>Welcome to Ultimate Member</h1>

	<div class="about-text">Thank you for installing! Ultimate Member is a powerful community and membership plugin that allows you to create beautiful community and membership sites with WordPress.</div>

	<div class="wp-badge um-badge">Version <?php echo ultimatemember_version; ?></div>

	<h2 class="nav-tab-wrapper">
	
		<?php foreach( $this->about_tabs as $k => $tab ) {
		
			if ( $k == $template ) {
				$active = 'nav-tab-active';
			} else {
				$active = '';
			}
			
		?>
		
		<a href="<?php echo admin_url('admin.php?page=ultimatemember-' . $k); ?>" class="nav-tab <?php echo $active; ?>"><?php echo $tab; ?></a>
		
		<?php } ?>
		
	</h2>