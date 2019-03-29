<?php if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

$friends = $wpdb->get_results(
	"SELECT id, 
       user_id1, 
       user_id2, 
       usr_a, 
       usr_b
	FROM {$wpdb->prefix}um_friends",
ARRAY_A );

$updated_rows = array();
foreach ( $friends as $friend ) {
	$update = array();
	$update_format = array();

	if ( isset( $friend['usr_a'] ) && empty( $friend['usr_a'] ) && ! empty( $friend['user_id1'] ) ) {
		$update['usr_a'] = $friend['user_id1'];
		$update_format[] = '%d';
	}

	if ( isset( $friend['usr_b'] ) && empty( $friend['usr_b'] ) && ! empty( $friend['user_id2'] ) ) {
		$update['usr_b'] = $friend['user_id2'];
		$update_format[] = '%d';
	}

	if ( $update ) {
		$result = $wpdb->update(
			"{$wpdb->prefix}um_friends",
			$update,
			array( 'id' => $friend['id'] ),
			$update_format,
			array( '%d' )
		);
	}
}