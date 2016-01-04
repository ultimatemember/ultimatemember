<?php

	/***
	***	@Get all bulk actions
	***/
	add_filter('um_admin_bulk_user_actions_hook', 'um_admin_bulk_user_actions_hook', 1);
	function um_admin_bulk_user_actions_hook( $actions ){

		$actions = null;

		$actions['um_approve_membership'] = array( 'label' => __('Approve Membership','ultimatemember') );
		$actions['um_reject_membership'] = array( 'label' => __('Reject Membership','ultimatemember') );
		$actions['um_put_as_pending'] = array( 'label' => __('Put as Pending Review','ultimatemember') );
		$actions['um_resend_activation'] = array( 'label' => __('Resend Activation E-mail','ultimatemember') );
		$actions['um_deactivate'] = array( 'label' => __('Deactivate','ultimatemember') );
		$actions['um_reenable'] = array( 'label' => __('Reactivate','ultimatemember') );
		//$actions['um_delete'] = array( 'label' => __('Delete','ultimatemember') );
		
		return $actions;
	}
	
	/***
	***	@Main admin user actions
	***/
	add_filter('um_admin_user_actions_hook', 'um_admin_user_actions_hook', 1);
	function um_admin_user_actions_hook( $actions ){

		$actions = null;
		
		if ( !um_user('super_admin') ) {
		
			if ( um_user('account_status') == 'awaiting_admin_review' ){
				$actions['um_approve_membership'] = array( 'label' => __('Approve Membership','ultimatemember') );
				$actions['um_reject_membership'] = array( 'label' => __('Reject Membership','ultimatemember') );
			}
			
			if ( um_user('account_status') == 'rejected' ) {
				$actions['um_approve_membership'] = array( 'label' => __('Approve Membership','ultimatemember') );
			}
			
			if ( um_user('account_status') == 'approved' ) {
				$actions['um_put_as_pending'] = array( 'label' => __('Put as Pending Review','ultimatemember') );
			}
			
			if ( um_user('account_status') == 'awaiting_email_confirmation' ) {
				$actions['um_resend_activation'] = array( 'label' => __('Resend Activation E-mail','ultimatemember') );
			}
			
			if (  um_user('account_status') != 'inactive' ) {
				$actions['um_deactivate'] = array( 'label' => __('Deactivate this account','ultimatemember') );
			}
			
			if (  um_user('account_status') == 'inactive' ) {
				$actions['um_reenable'] = array( 'label' => __('Reactivate this account','ultimatemember') );
			}
			
			if ( um_current_user_can( 'delete', um_profile_id() ) ) {
				$actions['um_delete'] = array( 'label' => __('Delete this user','ultimatemember') );
			}
			
		}
		
		if ( current_user_can('delete_users') ) {
			$actions['um_switch_user'] = array( 'label' => __('Login as this user','ultimatemember') );
		}
		
		um_fetch_user( um_profile_id() );
		
		return $actions;
	}