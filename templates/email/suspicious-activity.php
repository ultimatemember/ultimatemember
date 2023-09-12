<?php
/**
 * Template for the "Secure: Suspicious Account Activity".
 * Whether to receive notification when suspicious account activity is detected.
 *
 * This template can be overridden by copying it to {your-theme}/ultimate-member/email/suspicious-activity.php
 *
 * @version 2.6.8
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

		<div style="padding: 30px 0;font-size: 14px;">This is to inform you that there are suspicious activities with the following account(s):</div>
		<div style="padding: 30px 0;font-size: 14px;">{banned_profile_links}</div>
		<div style="padding: 30px 0;font-size: 14px;">Due to that we have set each account(s) status to rejected or deactivated, revoked roles & destroyed the login session.</div>

	</div>

	<div style="color: #999;padding: 20px 30px">

		<div style="">- Sent via Ultimate Member plugin.</div>

	</div>

</div>
