<?php

	/***
	***	@Does an action to user asap
	***/
	add_action('um_admin_user_action_hook', 'um_admin_user_action_hook');
	function um_admin_user_action_hook( $action ){
		global $ultimatemember;

		switch ( $action ) {

			case 'um_put_as_pending':
				$ultimatemember->user->pending();
				break;

			case 'um_approve_membership':
			case 'um_reenable':
				$ultimatemember->user->approve();
				break;

			case 'um_reject_membership':
				$ultimatemember->user->reject();
				break;

			case 'um_resend_activation':
				$ultimatemember->user->email_pending();
				break;

			case 'um_deactivate':
				$ultimatemember->user->deactivate();
				break;

			case 'um_delete':
				$ultimatemember->user->delete();
				break;

		}

	}
