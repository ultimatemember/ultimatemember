<?php

	$premium['profile-completeness'] = array(
		'url' => 'https://ultimatemember.com/extensions/profile-completeness/',
		'image' => 'https://ultimatemember.com/wp-content/uploads/2015/04/pc3-01-copy.png',
		'name' => 'Profile Completeness',
		'desc' => 'Encourage users to complete their profiles or force them to fill specific profile fields with the Profile Completeness extension.',
	);
	
	$premium['real-time-notifications'] = array(
		'url' => 'https://ultimatemember.com/extensions/real-time-notifications/',
		'image' => 'https://ultimatemember.com/wp-content/uploads/2015/04/notifications-01-copy.png',
		'name' => 'Real-time Notifications',
		'desc' => 'Add a real-time notification system to your site so users can receive updates and notifications directly on your website as they happen.',
	);
	
	$premium['user-reviews'] = array(
		'url' => 'https://ultimatemember.com/extensions/user-reviews/',
		'image' => 'https://ultimatemember.com/wp-content/uploads/2015/03/userrating800x300.png',
		'name' => 'User Reviews',
		'desc' => 'With our user reviews extension, you can add a 5 star user rating and review system to your site so users can rate/review each other.',
	);

	$premium['social-login'] = array(
		'url' => 'https://ultimatemember.com/extensions/social-login/',
		'image' => 'https://ultimatemember.com/wp-content/uploads/2015/02/socialloginv2-011.png',
		'name' => 'Social Login',
		'desc' => 'This extension allows users to register and login to your site using their social network accounts (Facebook, Twitter, Google+, LinkedIn, Instagram, VK)',
	);
	
	$premium['bbpress'] = array(
		'url' => 'https://ultimatemember.com/extensions/bbpress/',
		'image' => 'https://ultimatemember.com/wp-content/uploads/2015/02/bbpress-copy.png',
		'name' => 'bbPress',
		'desc' => 'Integrates the popular forums plugin bbPress with Ultimate Member.',
	);
	
	$premium['mailchimp'] = array(
		'url' => 'https://ultimatemember.com/extensions/mailchimp/',
		'image' => 'https://ultimatemember.com/wp-content/uploads/2015/02/mailchimp-01-copy.png',
		'name' => 'MailChimp',
		'desc' => 'This extension integrates MailChimp with Ultimate Member and allows users to subscribe to your mailing lists when they register on your site.',
	);
	
	$premium['mycred'] = array(
		'url' => 'https://ultimatemember.com/extensions/mycred/',
		'image' => 'https://ultimatemember.com/wp-content/uploads/2015/02/mycred1.png',
		'name' => 'myCRED',
		'desc' => 'With our myCRED extension, reward or charge your users for using Ultimate Member features and doing profile updates and show their rank and badges beautifully in their user profile.',
	);
	
	$premium['notices'] = array(
		'url' => 'https://ultimatemember.com/extensions/notices/',
		'image' => 'https://ultimatemember.com/wp-content/uploads/2015/02/notices.png',
		'name' => 'Notices',
		'desc' => 'Alert users to important information or let them know about promotions or new features using conditional notices.',
	);
	
	$free['online-users'] = array(
		'url' => 'https://ultimatemember.com/extensions/online-users/',
		'image' => 'https://ultimatemember.com/wp-content/uploads/2015/04/onlineuser1-01-copy.png',
		'name' => 'Online Users',
		'desc' => 'Adds online users widget to your site and allow you to show the online users anywhere with a simple shortcode, and also see user online status.'
	);
	
	$free['google-recaptcha'] = array(
		'url' => 'https://ultimatemember.com/extensions/google-recaptcha/',
		'image' => 'https://ultimatemember.com/wp-content/uploads/2015/02/recaptcha-01-copy.png',
		'name' => 'Google reCAPTCHA',
		'desc' => 'This free Google reCAPTCHA extension helps you stop spam registrations on your WordPress site.',
	);
	
?>

<div id="um-extensions-wrap" class="wrap">
		
	<h2>Ultimate Member - Extensions</h2>
	
	<div class="wp-filter">
		<ul class="filter-links">
			<li><a href='?page=ultimatemember-extensions&filter=premium' class='<?php if ( !isset($_REQUEST['filter']) || isset( $_REQUEST['filter'] ) && $_REQUEST['filter'] == 'premium' ) { echo 'current'; } ?>'>Premium</a></li>
			<li><a href='?page=ultimatemember-extensions&filter=free' class='<?php if ( isset( $_REQUEST['filter'] ) && $_REQUEST['filter'] == 'free' ) { echo 'current'; } ?>'>Free</a></li>
		</ul>
	</div>

	<div class="wp-list-table widefat plugin-install">
		<div id="the-list">
		
			<?php if ( !isset($_REQUEST['filter']) || isset( $_REQUEST['filter'] ) && $_REQUEST['filter'] == 'premium' ) { ?>
			
			<?php foreach( $premium as $key => $info ) { ?>
			
			<div class="plugin-card">
				<a href="<?php echo $info['url']; ?>" class="plugin-image"><img src="<?php echo $info['image']; ?>" /></a>
				<div class="plugin-card-top">
					<div class="name column-name">
						<h4><a href="<?php echo $info['url']; ?>"><?php echo $info['name']; ?></a></h4>
					</div>
					<div class="action-links">
						<ul class="plugin-action-buttons"><li><a class="install-now button" href="<?php echo $info['url']; ?>">Get this Add on</a></li>
						<li><a href="<?php echo $info['url']; ?>">More Details</a></li></ul>
					</div>
					<div class="desc column-description">
						<p><?php echo $info['desc']; ?></p>
					</div>
				</div>
			</div>
			
			<?php } 
			
			} ?>
			
			<?php if ( isset( $_REQUEST['filter'] ) && $_REQUEST['filter'] == 'free' ) { ?>
			
			<?php foreach( $free as $key => $info ) { ?>
			
			<div class="plugin-card">
				<a href="<?php echo $info['url']; ?>" class="plugin-image"><img src="<?php echo $info['image']; ?>" /></a>
				<div class="plugin-card-top">
					<div class="name column-name">
						<h4><a href="<?php echo $info['url']; ?>"><?php echo $info['name']; ?></a></h4>
					</div>
					<div class="action-links">
						<ul class="plugin-action-buttons"><li><a class="install-now button" href="<?php echo $info['url']; ?>">Get this Add on</a></li>
						<li><a href="<?php echo $info['url']; ?>">More Details</a></li></ul>
					</div>
					<div class="desc column-description">
						<p><?php echo $info['desc']; ?></p>
					</div>
				</div>
			</div>
			
			<?php } 
			
			} ?>

		</div>
	</div>

</div><div class="um-admin-clear"></div>