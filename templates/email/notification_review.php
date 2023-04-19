<?php
/**
 * Template for the "Account Needs Review Notification".
 * Whether to receive notification when an account needs admin review.
 *
 * This template can be overridden by copying it to {your-theme}/ultimate-member/email/notification_review.php
 *
 * @version 2.6.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>


<div style="max-width: 560px;padding: 20px;background: #ffffff;border-radius: 5px;margin:40px auto;font-family: Open Sans,Helvetica,Arial;font-size: 15px;color: #666;">

	<div style="color: #444444;font-weight: normal;">
		<div style="text-align: center;font-weight:600;font-size:26px;padding: 10px 0;border-bottom: solid 3px #eeeeee;">{site_name}</div>

		<div style="clear:both"></div>
	</div>

	<div style="padding: 0 30px 30px 30px;border-bottom: 3px solid #eeeeee;">

		<div style="padding: 30px 0;font-size: 24px;text-align: center;line-height: 40px;">{display_name} has just applied for membership to {site_name} and is waiting to be reviewed.</span></div>

		<div style="padding: 10px 0 50px 0;text-align: center;">To review this member please click the following link: <a href="{user_profile_link}" style="color: #3ba1da;text-decoration: none;">{user_profile_link}</a></div>

		<div style="padding: 0 0 15px 0;">

			<div style="background: #eee;color: #444;padding: 12px 15px; border-radius: 3px;font-weight: bold;font-size: 16px;">Here is the submitted registration form:<br /><br />
				{submitted_registration}
			</div>
		</div>

	</div>

	<div style="color: #999;padding: 20px 30px">

		<div style="">Thank you!</div>
		<div style="">The <a href="{site_url}" style="color: #3ba1da;text-decoration: none;">{site_name}</a> Team</div>

	</div>

</div>
