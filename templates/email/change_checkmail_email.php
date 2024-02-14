<?php
/**
 * Template for the "Changed account activation email".
 * Whether to send the user an email when changing his email needs e-mail confirmation.
 *
 * This template can be overridden by copying it to {your-theme}/ultimate-member/email/change_checkmail_email.php
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

		<div style="padding: 30px 0;font-size: 24px;text-align: center;line-height: 40px;">Please click the following link to confirm your email address.</div>

		<div style="padding: 10px 0 50px 0;text-align: center;"><a href="{account_activation_link}" style="background: #555555;color: #fff;padding: 12px 30px;text-decoration: none;border-radius: 3px;letter-spacing: 0.3px;">Confirm your email address change</a></div>

		<div style="padding: 15px;background: #eee;border-radius: 3px;text-align: center;">Need help? <a href="mailto:{admin_email}" style="color: #3ba1da;text-decoration: none;">contact  us</a> today.</div>

	</div>

	<div style="color: #999;padding: 20px 30px">

		<div style="">Thank you!</div>
		<div style="">The <a href="{site_url}" style="color: #3ba1da;text-decoration: none;">{site_name}</a> Team</div>

	</div>

</div>
