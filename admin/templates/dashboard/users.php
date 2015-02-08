<div class="um-admin-dash-content">

	<div class="um-admin-dash-two-col">

		<div class="um-admin-dash-col">

			<h4><?php _e('Recent Members','ultimatemember'); ?></h4>

			<?php foreach( $ultimatemember->query->get_users_by_status('approved') as $user_id ) { um_fetch_user( $user_id ); ?>

			<div class="um-admin-dash-item">

				<div class="um-admin-dash-thumb">
					<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('profile_photo', 30 ); ?></a>
				</div>

				<div class="um-admin-dash-info">
					<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('display_name'); ?></a>
				</div>

				<div class="um-admin-dash-meta"><?php echo date( "j M H:i", strtotime( um_user('user_registered') ) ); ?>

					<?php

					if ( um_user('submitted') ) {

						echo '<a href="#" class="um-admin-dash-review um-admin-tipsy-n" data-modal="UM_preview_registration" data-modal-size="smaller" data-dynamic-content="um_admin_review_registration" data-arg1="'.$user_id.'" data-arg2="edit_registration" title="Review registration info"><i class="um-icon-information-circled"></i></a>';

					}

					?>

				</div>

				<div class="um-admin-dash-more">

				</div>

			</div>

			<?php um_reset_user(); } ?>

		</div>
							
		<div class="um-admin-dash-col">
							
			<?php $users = $ultimatemember->query->get_users_by_status('awaiting_admin_review'); ?>
			<h4><?php _e('Users Awaiting Review','ultimatemember'); ?></h4>

			<?php foreach( $users as $user_id ) { um_fetch_user( $user_id ); ?>

			<div class="um-admin-dash-item">

				<div class="um-admin-dash-thumb">
					<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('profile_photo', 30 ); ?></a>
				</div>

				<div class="um-admin-dash-info">
					<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('display_name'); ?></a>
				</div>

				<div class="um-admin-dash-meta"><?php echo date( "j M H:i", strtotime( um_user('user_registered') ) ); ?>

					<?php

					if ( um_user('submitted') ) {

						echo '<a href="#" class="um-admin-dash-review um-admin-tipsy-n" data-modal="UM_preview_registration" data-modal-size="smaller" data-dynamic-content="um_admin_review_registration" data-arg1="'.$user_id.'" data-arg2="edit_registration" title="Review registration info"><i class="um-icon-information-circled"></i></a>';

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

	<div class="um-admin-dash-two-col">

		<div class="um-admin-dash-col">

			<?php $users = $ultimatemember->query->get_users_by_status('awaiting_email_confirmation'); ?>
			<h4><?php _e('Pending e-mail confirmation','ultimatemember'); ?></h4>

			<?php foreach( $users as $user_id ) { um_fetch_user( $user_id ); ?>

			<div class="um-admin-dash-item">

				<div class="um-admin-dash-thumb">
					<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('profile_photo', 30 ); ?></a>
				</div>

				<div class="um-admin-dash-info">
					<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('display_name'); ?></a>
				</div>

				<div class="um-admin-dash-meta"><?php echo date( "j M H:i", strtotime( um_user('user_registered') ) ); ?></div>

				<div class="um-admin-dash-more">
					<a href="<?php echo $ultimatemember->permalinks->admin_act_url('user_action','um_resend_activation'); ?>" class="ok">Resend Email</a>
				</div>

			</div>

			<?php um_reset_user(); } ?>

			<?php if ( !$users ) { ?>
				<div class="um-admin-dash-item"><?php _e('No users are awaiting e-mail validation yet.','ultimatemember'); ?></div>
			<?php } ?>

		</div>

		<div class="um-admin-dash-col">

			<?php $users = $ultimatemember->query->get_users_by_status('inactive'); ?>
			<h4><?php _e('Recently Deactivated','ultimatemember'); ?></h4>

			<?php foreach( $users as $user_id ) { um_fetch_user( $user_id ); ?>

			<div class="um-admin-dash-item">

				<div class="um-admin-dash-thumb">
					<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('profile_photo', 30 ); ?></a>
				</div>

				<div class="um-admin-dash-info">
					<a href="<?php echo um_user_profile_url(); ?>" target="_blank"><?php echo um_user('display_name'); ?></a>
				</div>
				
				<div class="um-admin-dash-meta"><?php echo date( "j M H:i", strtotime( um_user('user_registered') ) ); ?></div>

				<div class="um-admin-dash-more">
					<a href="<?php echo $ultimatemember->permalinks->admin_act_url('user_action','um_reenable'); ?>" class="ok">Re-activate</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="<?php echo $ultimatemember->permalinks->admin_act_url('user_action','um_delete'); ?>" class="red">Delete</a>
				</div>

			</div>

			<?php um_reset_user(); } ?>

			<?php if ( !$users ) { ?>
				<div class="um-admin-dash-item"><?php _e('No users have been deactivated recently.','ultimatemember'); ?></div>
			<?php } ?>

		</div>

	</div>

</div><div class="um-admin-clear"></div>