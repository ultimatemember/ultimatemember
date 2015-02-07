<?php global $ultimatemember;  ?>

<div class="um-admin-dash-container">

	<div class="um-admin-dash-head">
	
		<div class="um-admin-dash-head-logo">
			<h2>Dashboard</h2>
			<span><?php echo ultimatemember_version; ?></span>
		</div><div class="um-admin-clear"></div>

	</div>
	
	<div class="um-admin-dash-body">

		<div class="um-admin-dash-main">
			
			<div class="um-admin-dash-content">
			
				<?php include_once um_path . 'admin/templates/dashboard/overview.php'; ?>

			</div>
			
		</div><div class="um-admin-clear"></div>
	
	</div>

	<div class="um-admin-dash-foot">
		<div class="um-admin-dash-share">
		
			<?php global $reduxConfig; foreach ( $reduxConfig->args['share_icons'] as $k => $arr ) { ?><a href="<?php echo $arr['url']; ?>" class="um-about-icon um-admin-tipsy-n" title="<?php echo $arr['title']; ?>" target="_blank"><i class="<?php echo $arr['icon']; ?>"></i></a><?php } ?>
			
		</div><div class="clear"></div>
	</div>
	
</div>