
	<?php global $ultimatemember; include_once um_path . 'admin/templates/about_header.php'; ?>

	<div class="changelog headline-feature dfw">
		<h2>Getting Started</h2>
		<div class="feature-section">

			<p>Ultimate Member has been designed to be as easy to use as possible and you shouldn’t run into any difficulties. However, the plugin contains lots of different elements so we have created the following page to help you get started with Ultimate Member.</p>
			
		</div>
	</div>
	
	<hr />

	<div class="changelog feature-list">
		
		<div class="feature-section col two-col">
			
			<div>
				<h4>Automatically installed pages</h4>
				<p>Upon activation the plugin will install 7 core pages. These pages are required for the plugin to function correctly and cannot be deleted.</p>
				<p>
					<ul>
						<li><a href="<?php echo um_get_core_page('register'); ?>" target="_blank">Register</a></li>
						<li><a href="<?php echo um_get_core_page('login'); ?>" target="_blank">Login</a></li>
						<li><a href="<?php echo um_get_core_page('user'); ?>" target="_blank">User</a></li>
						<li><a href="<?php echo um_get_core_page('members'); ?>" target="_blank">Members</a></li>
						<li><a href="<?php echo um_get_core_page('account'); ?>" target="_blank">Account</a></li>
						<li><a href="<?php echo admin_url('post.php?post=' . $ultimatemember->permalinks->core['logout'] . '&action=edit'); ?>" target="_blank">Logout</a></li>
						<li><a href="<?php echo um_get_core_page('password-reset'); ?>" target="_blank">Password Reset</a></li>
					</ul>
				</p>
			</div>

			<div class="last-feature">
				<h4>Getting started</h4>
				<p>The plugin has several different elements in the WordPress admin that allow you to customize your community/membership site:</p>
				<p>
					<ul>
						<li><a href="<?php echo admin_url('admin.php?page=ultimatemember'); ?>" target="_blank">Dashboard</a></li>
						<li><a href="<?php echo admin_url('admin.php?page=um_options'); ?>" target="_blank">Settings</a></li>
						<li><a href="<?php echo admin_url('edit.php?post_type=um_form'); ?>" target="_blank">Forms</a></li>
						<li><a href="<?php echo admin_url('edit.php?post_type=um_role'); ?>" target="_blank">Member Levels</a></li>
						<li><a href="<?php echo admin_url('edit.php?post_type=um_directory'); ?>" target="_blank">Member Directories</a></li>
					</ul>
				</p>
			</div>
			
		</div>
		
	</div>
	
	<hr />
	
	<div class="changelog headline-feature dfw">
		<h2>Need more help?</h2>
		<div class="feature-section">

			<p>If you want to learn more about Ultimate Member you’ll need to register on our website where you will be able to interact and get help from other Ultimate Member users via our community forum and also be able to access other useful resources including the plugin’s documentation.</p>
			
			<p style="text-align:center"><a href="http://ultimatemember.com/forums/" target="_blank" class="button button-primary">Join the Ultimate Member Community</a></p>
			
		</div>
	</div>
	
	<?php include_once um_path . 'admin/templates/about_footer.php'; ?>