<div class="um-admin-metabox">

	<div class="">
	
		<p>
			<label class="um-admin-half"><?php _e('Registration Status','ultimatemember'); ?> <?php $this->tooltip('', 'e'); ?></label>
			<span class="um-admin-half">
			
				<select name="_um_status" id="_um_status" class="umaf-selectjs um-adm-conditional" style="width: 300px" 
				data-cond1="approved" data-cond1-show="approved" 
				data-cond2="checkmail" data-cond2-show="checkmail"
				data-cond3="pending" data-cond3-show="pending">
					<option value="approved" <?php selected('approved', $ultimatemember->query->get_meta_value('_um_status') ); ?>>Auto Approve</option>
					<option value="checkmail" <?php selected('checkmail', $ultimatemember->query->get_meta_value('_um_status') ); ?>>Require Email Activation</option>
					<option value="pending" <?php selected('pending', $ultimatemember->query->get_meta_value('_um_status') ); ?>>Require Admin Review</option>
				</select>
				
			</span>
		</p><div class="um-admin-clear"></div>

		<!-- Automatic Approval Settings -->
		
		<div class="approved">
		<p>
			<label class="um-admin-half"><?php _e('Action to be taken after registration','ultimatemember'); ?> <?php $this->tooltip('', 'e'); ?></label>
			<span class="um-admin-half">
			
				<select name="_um_auto_approve_act" id="_um_auto_approve_act" class="umaf-selectjs um-adm-conditional" style="width: 300px" data-cond1="redirect_url" data-cond1-show="_um_auto_approve_act">
					<option value="redirect_profile" <?php selected('redirect_profile', $ultimatemember->query->get_meta_value('_um_auto_approve_act') ); ?>>Redirect to profile</option>
					<option value="redirect_url" <?php selected('redirect_url', $ultimatemember->query->get_meta_value('_um_auto_approve_act') ); ?>>Redirect to URL</option>
				</select>

			</span>
		</p><div class="um-admin-clear"></div>
		
		<p class="_um_auto_approve_act">
			<label class="um-admin-half" for="_um_auto_approve_url"><?php _e('Set Custom Redirect URL','ultimatemember'); ?> <?php $this->tooltip('', 'e'); ?></label>
			<span class="um-admin-half">
				
				<input type="text" value="<?php echo $ultimatemember->query->get_meta_value('_um_auto_approve_url', null, 'na'); ?>" name="_um_auto_approve_url" id="_um_auto_approve_url" />
			
			</span>
		</p><div class="um-admin-clear"></div>
		</div>
		
		<!-- Automatic Approval Settings -->
		
		<!-- Email Approval Settings -->
		
		<div class="checkmail">
		<p>
			<label class="um-admin-half"><?php _e('Action to be taken after registration','ultimatemember'); ?> <?php $this->tooltip('', 'e'); ?></label>
			<span class="um-admin-half">
			
				<select name="_um_checkmail_action" id="_um_checkmail_action" class="umaf-selectjs um-adm-conditional" style="width: 300px" 
				data-cond1="show_message" data-cond1-show="_um_checkmail_action-1"
				data-cond2="redirect_url" data-cond2-show="_um_checkmail_action-2">
					<option value="show_message" <?php selected('show_message', $ultimatemember->query->get_meta_value('_um_checkmail_action') ); ?>>Show custom message</option>
					<option value="redirect_url" <?php selected('redirect_url', $ultimatemember->query->get_meta_value('_um_checkmail_action') ); ?>>Redirect to URL</option>
				</select>

			</span>
		</p><div class="um-admin-clear"></div>
		
		<p class="_um_checkmail_action-1">
			<label class="um-admin-half"><?php _e('Personalize the custom message','ultimatemember'); ?> <?php $this->tooltip('', 'e'); ?></label>
			<span class="um-admin-half">
			
				<textarea name="_um_checkmail_message" id="_um_checkmail_message"><?php echo $ultimatemember->query->get_meta_value('_um_checkmail_message', null, 'na'); ?></textarea>
				
			</span>
		</p><div class="um-admin-clear"></div>
		
		<p class="_um_checkmail_action-2">
			<label class="um-admin-half" for="_um_checkmail_url"><?php _e('Set Custom Redirect URL','ultimatemember'); ?> <?php $this->tooltip('', 'e'); ?></label>
			<span class="um-admin-half">
				
				<input type="text" value="<?php echo $ultimatemember->query->get_meta_value('_um_checkmail_url', null, 'na'); ?>" name="_um_checkmail_url" id="_um_checkmail_url" />
			
			</span>
		</p><div class="um-admin-clear"></div>
		</div>
		
		<!-- Email Approval Settings -->
		
		<!-- Moderator Approval Settings -->
		
		<div class="pending">
		<p>
			<label class="um-admin-half"><?php _e('Action to be taken after registration','ultimatemember'); ?> <?php $this->tooltip('', 'e'); ?></label>
			<span class="um-admin-half">
			
				<select name="_um_pending_action" id="_um_pending_action" class="umaf-selectjs um-adm-conditional" style="width: 300px" 
				data-cond1="show_message" data-cond1-show="_um_pending_action-1"
				data-cond2="redirect_url" data-cond2-show="_um_pending_action-2">
					<option value="show_message" <?php selected('show_message', $ultimatemember->query->get_meta_value('_um_pending_action') ); ?>>Show custom message</option>
					<option value="redirect_url" <?php selected('redirect_url', $ultimatemember->query->get_meta_value('_um_pending_action') ); ?>>Redirect to URL</option>
				</select>

			</span>
		</p><div class="um-admin-clear"></div>
		
		<p class="_um_pending_action-1">
			<label class="um-admin-half"><?php _e('Personalize the custom message','ultimatemember'); ?> <?php $this->tooltip('', 'e'); ?></label>
			<span class="um-admin-half">
			
				<textarea name="_um_pending_message" id="_um_pending_message"><?php echo $ultimatemember->query->get_meta_value('_um_pending_message', null, 'na'); ?></textarea>
				
			</span>
		</p><div class="um-admin-clear"></div>
		
		<p class="_um_pending_action-2">
			<label class="um-admin-half" for="_um_pending_url"><?php _e('Set Custom Redirect URL','ultimatemember'); ?> <?php $this->tooltip('', 'e'); ?></label>
			<span class="um-admin-half">
				
				<input type="text" value="<?php echo $ultimatemember->query->get_meta_value('_um_pending_url', null, 'na'); ?>" name="_um_pending_url" id="_um_pending_url" />
			
			</span>
		</p><div class="um-admin-clear"></div>
		</div>
		
		<!-- Moderator Approval Settings -->
		
	</div>
	
	<div class="um-admin-clear"></div>
	
</div>