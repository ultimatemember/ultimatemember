<?php
require_once '../../../../wp-load.php';

function um_test_generate_random_string( $length = 10 ) {
	return substr( str_shuffle( str_repeat( $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil( $length / strlen( $x ) ) ) ), 1, $length );
}

function api_call() {
	$response = wp_remote_get( 'https://randomuser.me/api/?results=1000' );

	if ( is_wp_error( $response ) ) {
		return 'Something went wrong!';
	}

	$body = wp_remote_retrieve_body( $response );

	$data = json_decode( $body );

	$users = array();
	foreach ( $data->results as $user ) {
		$users[] = array(
			'first'    => $user->name->first,
			'last'     => $user->name->last,
			'email'    => $user->email,
			'username' => $user->login->username,
		);
	}

	return $users;
}

$users = api_call();
for ( $i = 0; $i < 1000; $i++ ) {
	$random_user_name  = $users[ $i ]['username'] ?? um_test_generate_random_string( 8 );
	$random_user_email = $users[ $i ]['email'] ?? $random_user_name . '@example.com';
	$random_first_name = $users[ $i ]['first'] ?? um_test_generate_random_string( 5 );
	$random_last_name  = $users[ $i ]['last'] ?? um_test_generate_random_string( 8 );

	$userdata = array(
		'user_login' => $random_user_name,
		'user_pass'  => 'q1q2q1q2',
		'user_email' => $random_user_email,
		'first_name' => $random_first_name,
		'last_name'  => $random_last_name,
		'role'       => 'subscriber',
	);

	$user_id = wp_insert_user( $userdata );

	if ( is_wp_error( $user_id ) ) {
		// Something went wrong, handle the error
		var_dump( 'User creation failed: ' . $user_id->get_error_message() );
	} else {
		var_dump( 'User creation complete: ID:' . $user_id . ' Username:' . $userdata['user_login'] );
	}
}

exit;
