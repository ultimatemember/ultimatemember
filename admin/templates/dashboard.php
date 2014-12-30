<?php

global $ultimatemember; 

?>

<div class="um-admin-dash-container">

	<div class="um-admin-dash-head">
	
		<div class="um-admin-dash-head-logo">
			<h2>Dashboard</h2>
			<span><?php echo ULTIMATEMEMBER_VERSION; ?></span>
		</div>
		
		<div class="um-admin-clear"></div>
	
	</div>
	
	<div class="um-admin-dash-body">
	
		<div class="um-admin-dash-nav">
		
			<a href="#" class="active"><i class="um-icon-home-3"></i><span class="um-admin-dash-nav-title">Dashboard Overview</span></a>
			
		</div>
		
		<div class="um-admin-dash-main">
			
			<h3>New Registrations over last 30 days</h3>
			<?php echo $ultimatemember->chart->create('data=new_users&x_label=Day&y_label=Daily Signups'); ?>

		</div>
		
		<div class="um-admin-clear"></div>
	
	</div>

	<div class="um-admin-dash-foot">
		<div class="um-admin-dash-share">
		
			<?php global $reduxConfig; foreach ( $reduxConfig->args['share_icons'] as $k => $arr ) { ?><a href="<?php echo $arr['url']; ?>" class="um-about-icon um-admin-tipsy-n" title="<?php echo $arr['title']; ?>" target="_blank"><i class="<?php echo $arr['icon']; ?>"></i></a><?php } ?>
			
		</div><div class="clear"></div>
	</div>
	
</div>