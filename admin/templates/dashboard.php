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
		
			<a href="#"><i class="um-icon-dashboard"></i><span class="um-admin-dash-nav-title">Dashboard Overview</span></a>
			<a href="#"><i class="um-icon-analytics-chart-graph"></i><span class="um-admin-dash-nav-title">Analytics</span></a>
			<a href="#"><i class="um-icon-user-6"></i><span class="um-admin-dash-nav-title">New Users</span><span class="um-admin-dash-count">12</span></a>
			<a href="#"><i class="um-icon-files-2"></i><span class="um-admin-dash-nav-title">User Reports</span><span class="um-admin-dash-count red">4</span></a>
			<a href="#"><i class="um-icon-denied-block"></i><span class="um-admin-dash-nav-title">Quick Delete/Ban</span></a>
			<a href="#"><i class="um-icon-download-4"></i><span class="um-admin-dash-nav-title">Updates Available</span><span class="um-admin-dash-count">1</span></a>
			
		</div>
		
		<div class="um-admin-dash-main">
			
			<h3>New Registrations over last 30 days</h3>
			<?php echo $ultimatemember->chart->create('data=new_users&x_label=Day&y_label=Daily Signups'); ?>

		</div>
		
		<div class="um-admin-clear"></div>
	
	</div>

	<div class="um-admin-dash-foot">

	</div>
	
</div>