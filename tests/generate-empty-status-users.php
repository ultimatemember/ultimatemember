<?php
require_once '../../../../wp-load.php';

function um_test_generate_random_string( $length = 10 ) {
	return substr( str_shuffle( str_repeat( $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil( $length / strlen( $x ) ) ) ), 1, $length );
}

for ( $i = 0; $i < 1000; $i++ ) {
	$random_user_name  = um_test_generate_random_string( 8 ); // Generate a random user name
	$random_user_email = $random_user_name . '@example.com'; // Append the user name to a dummy email domain
	$random_user_pass  = um_test_generate_random_string( 12 ); // Generate a random user password
	$random_first_name = um_test_generate_random_string( 5 ); // Generate a random first name
	$random_last_name  = um_test_generate_random_string( 8 ); // Generate a random last name

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
