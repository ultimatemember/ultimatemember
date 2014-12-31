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
		
			<a href="#" class="active" data-rel="overview"><i class="um-icon-home-3"></i><span class="um-admin-dash-nav-title">Dashboard Overview</span></a>
			<a href="#" data-rel="analytics"><i class="um-icon-analytics-chart-graph"></i><span class="um-admin-dash-nav-title">Analytics</span></a>
			
		</div>
		
		<div class="um-admin-dash-main">
			
			<div class="um-admin-dash-content" id="overview">
			
			</div>
			
			<div class="um-admin-dash-content" id="analytics">
				
				<h3>New Registrations over last 30 days</h3>
				<?php echo $ultimatemember->chart->create('data=new_users&x_label=Day&y_label=Daily Signups'); ?>
			
			</div>
			
		</div>
		
		<div class="um-admin-clear"></div>
	
	</div>

	<div class="um-admin-dash-foot">
		<div class="um-admin-dash-share">
		
			<?php global $reduxConfig; foreach ( $reduxConfig->args['share_icons'] as $k => $arr ) { ?><a href="<?php echo $arr['url']; ?>" class="um-about-icon um-admin-tipsy-n" title="<?php echo $arr['title']; ?>" target="_blank"><i class="<?php echo $arr['icon']; ?>"></i></a><?php } ?>
			
		</div><div class="clear"></div>
	</div>
	
</div>