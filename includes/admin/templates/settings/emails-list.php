<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ListTable = new um\admin\list_table\Emails( array(
	'singular' => __( 'Email Notification', 'ultimate-member' ),
	'plural'   => __( 'Email Notifications', 'ultimate-member' ),
	'ajax'     => false,
) );

$columns = apply_filters( 'um_email_templates_columns', array(
	'email'      => __( 'Email', 'ultimate-member' ),
	'recipients' => __( 'Recipient(s)', 'ultimate-member' ),
	'configure'  => '',
) );

$ListTable->set_columns( $columns );

$ListTable->prepare_items(); ?>

<p class="description" style="margin: 20px 0 0 0;">
	<?php printf( __( 'You may get more details about email notifications customization <a href="%s">here</a>', 'ultimate-member' ),
		'https://docs.ultimatemember.com/article/1335-email-templates'
	); ?>
</p>

<form action="" method="get" name="um-settings-emails" id="um-settings-emails">
	<input type="hidden" name="page" value="ultimatemember" />
	<input type="hidden" name="tab" value="email" />

	<?php $ListTable->display(); ?>
</form>
