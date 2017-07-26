<?php

	/***
	***	@Does an action to user asap
	***/
	add_action('um_admin_user_action_hook', 'um_admin_user_action_hook');
	function um_admin_user_action_hook( $action ){
		switch ( $action ) {
			
			default:
				do_action("um_admin_custom_hook_{$action}", UM()->user()->id );
				break;

			case 'um_put_as_pending':
				UM()->user()->pending();
				break;
				
			case 'um_approve_membership':
			case 'um_reenable':
            UM()->user()->approve();
				break;
				
			case 'um_reject_membership':
                UM()->user()->reject();
				break;
				
			case 'um_resend_activation':
                UM()->user()->email_pending();
				break;
				
			case 'um_deactivate':
                UM()->user()->deactivate();
				break;
				
			case 'um_delete':
				if ( is_admin() )
					wp_die('This action is not allowed in backend.','ultimate-member');
                UM()->user()->delete();
				break;

		}
		
	}