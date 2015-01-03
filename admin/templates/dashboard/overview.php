<div class="um-admin-dash-two-col">

	<div class="um-admin-dash-col">
	
		<h3>Latest 5 Users</h3>

		<?php foreach( $ultimatemember->query->get_recent_users() as $user_id ) { um_fetch_user( $user_id ); ?>

		<div class="um-admin-dash-item">

			<div class="um-admin-dash-thumb">
				<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('profile_photo', 30 ); ?></a>
			</div>
			
			<div class="um-admin-dash-info">
				<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('display_name'); ?></a>
			</div>
			
			<div class="um-admin-dash-meta">
				<?php echo date( "j M Y", strtotime( um_user('user_registered') ) ); ?>
			</div>
			
			<div class="um-admin-dash-more um-status-<?php echo um_user('account_status'); ?>">
				<?php echo um_user('account_status_name'); ?>
			</div>
			
		</div>

		<?php um_reset_user(); } ?>

	</div>
	
	<div class="um-admin-dash-col">
	
		<?php $users = $ultimatemember->query->get_users_by_meta('awaiting_admin_review'); ?>
		<h3>Users Awaiting Review<span class="um-admin-dash-count red count-<?php echo um_notify('awaiting_admin_review'); ?>"><?php echo um_notify('awaiting_admin_review'); ?></span></h3>

		<?php foreach( $users as $user_id ) { um_fetch_user( $user_id ); ?>

		<div class="um-admin-dash-item">

			<div class="um-admin-dash-thumb">
				<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('profile_photo', 30 ); ?></a>
			</div>
			
			<div class="um-admin-dash-info">
				<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('display_name'); ?></a>
			</div>
			
			<div class="um-admin-dash-meta">
				
				<?php
				
				if ( um_user('submitted') ) {

					echo '<a href="#" class="um-admin-tipsy-n" data-modal="UM_preview_registration" data-modal-size="smaller" data-dynamic-content="um_admin_review_registration" data-arg1="'.$user_id.'" data-arg2="edit_registration" title="Review/update registration info">Review details</a>';
				
				} else {
				
					echo '<em>No information available</em>';
				
				}
				?>
				
			</div>
			
			<div class="um-admin-dash-more">
				<a href="<?php echo $ultimatemember->permalinks->admin_act_url('user_action','um_approve_membership'); ?>" class="ok">Approve</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?php echo $ultimatemember->permalinks->admin_act_url('user_action','um_reject_membership'); ?>" class="red">Reject</a>
			</div>
			
		</div>
		
		<?php um_reset_user(); } ?>

		<?php if ( !$users ) { ?>
		<div class="um-admin-dash-item"><?php _e('No users are awaiting manual verification so far.','ultimatemember'); ?></div>
		<?php } ?>

	</div>
	
</div>