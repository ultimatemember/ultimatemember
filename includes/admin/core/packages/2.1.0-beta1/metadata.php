<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$wpdb->update(
	$wpdb->usermeta,
	array(
		'meta_value' => serialize( array() ),
	),
	array(
		'meta_key' => 'um_account_secure_fields',
	),
	array(
		'%s',
	),
	array(
		'%s',
	)
);
