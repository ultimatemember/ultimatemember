<?php

$css = '';
$custom_css = UM()->options()->get( 'custom_css' );
$enable_css = UM()->options()->get( 'enable_custom_css' );

if ( ! empty( $enable_css ) && ! empty( $custom_css ) ) {
	$css .= $custom_css;
}

$forms_query = new WP_Query;
$registration_forms = $forms_query->query( array(
	'post_type' => 'um_form',
	'meta_query' => array(
		array(
			'key'   => '_um_mode',
			'value' => 'register'
		),
	),
	'posts_per_page' => -1,
	'fields' => 'ids'
) );

$forms_query = new WP_Query;
$login_forms = $forms_query->query( array(
	'post_type' => 'um_form',
	'meta_query' => array(
		array(
			'key'   => '_um_mode',
			'value' => 'login'
		)
	),
	'posts_per_page' => -1,
	'fields' => 'ids'
) );

$forms_query = new WP_Query;
$profile_forms = $forms_query->query( array(
	'post_type' => 'um_form',
	'meta_query' => array(
		array(
			'key'   => '_um_mode',
			'value' => 'profile'
		)
	),
	'posts_per_page' => -1,
	'fields' => 'ids'
) );


foreach ( $registration_forms as $form_id ) {
	$register_custom_css = get_post_meta( $form_id, '_um_register_custom_css', true );
	if ( ! empty( $register_custom_css ) ) {
		$css .= '
		/* registration form ID=' . $form_id . ' */
		' . $register_custom_css;
	}
}


foreach ( $login_forms as $form_id ) {
	$login_custom_css = get_post_meta( $form_id, '_um_login_custom_css', true );
	if ( ! empty( $login_custom_css ) ) {
		$css .= '
		/* login form ID=' . $form_id . ' */
		' . $login_custom_css;
	}
}


foreach ( $profile_forms as $form_id ) {
	$profile_custom_css = get_post_meta( $form_id, '_um_profile_custom_css', true );
	if ( ! empty( $profile_custom_css ) ) {
		$css .= '
		/* profile form ID=' . $form_id . ' */
		' . $profile_custom_css;
	}
}


if ( ! empty( $css ) ) {
	$uploads = wp_upload_dir();
	$upload_dir = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'ultimatemember' . DIRECTORY_SEPARATOR;
	if ( file_exists( $upload_dir. 'um_old_settings.css' ) ) {
		$css_doc_file = fopen( $upload_dir. 'um_old_settings.css', 'a' );
		fwrite( $css_doc_file, "\r\n" . $css );
		fclose( $css_doc_file );
	}
}