<?php
/**
 * Template for the "Verify changing account email".
 * Whether to send the user an email when changing his email needs the administrator confirmation.
 *
 * This template can be overridden by copying it to {your-theme}/ultimate-member/email/pending_change_email.php
 *
 * @version 2.8.3
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div style="max-width: 560px;padding: 20px;background: #ffffff;border-radius: 5px;margin:40px auto;font-family: Open Sans,Helvetica,Arial;font-size: 15px;color: #666;">

	<div style="color: #444444;font-weight: normal;">
		<div style="text-align: center;font-weight:600;font-size:26px;padding: 10px 0;border-bottom: solid 3px #eeeeee;">{site_name}</div>

		<div style="clear:both"></div>
	</div>

	<div style="padding: 0 30px 30px 30px;border-bottom: 3px solid #eeeeee;">

		<div style="padding: 30px 0;font-size: 24px;text-align: center;line-height: 40px;">{display_name} has just changed an account email on {site_name}.</span></div>

		<div style="padding: 10px 0 50px 0;text-align: center;">To view their profile click here: <a href="{user_profile_link}" style="color: #3ba1da;text-decoration: none;">{user_profile_link}</a></div>

	</div>

	<div style="color: #999;padding: 20px 30px">

		<div style="">Thank you!</div>
		<div style="">The <a href="{site_url}" style="color: #3ba1da;text-decoration: none;">{site_name}</a> Team</div>

	</div>

</div>
