<?php
/**
 * Transferring email templates to new logic
 */
$templates_in_theme = 0;
$emails = UM()->config()->email_notifications;
foreach ( $emails as $email_key => $value ) {

	$in_theme = UM()->mail()->template_in_theme( $email_key, true );
	$theme_template_path = UM()->mail()->get_template_file( 'theme', $email_key );

	if ( ! $in_theme ) {
		$html_email = UM()->options()->get( 'email_html' );

		if ( $html_email ) {
			if ( ! UM()->mail()->copy_email_template( $email_key ) ) {
				$setting_value = UM()->options()->get( $email_key );

				$fp = fopen( $theme_template_path, "w" );
				$result = fputs( $fp, $setting_value );
				fclose( $fp );
			} else {
				$templates_in_theme++;
			}
		} else {
			$setting_value = UM()->options()->get( $email_key );

			$fp = fopen( $theme_template_path, "w" );
			$result = fputs( $fp, $setting_value );
			fclose( $fp );
		}
	} else {
		$theme_template_path_html = UM()->mail()->get_template_file( 'theme', $email_key, true );

		$setting_value = preg_replace( '/<\/body>|<\/head>|<html>|<\/html>|<body.*?>|<head.*?>/' , '', file_get_contents( $theme_template_path_html ) );

		if ( file_exists( $theme_template_path_html ) ) {
			if ( copy( $theme_template_path_html, $theme_template_path ) ) {
				$fp = fopen( $theme_template_path, "w" );
				$result = fputs( $fp, $setting_value );
				fclose( $fp );

				$templates_in_theme++;
			}
		}
	}
}

if ( $templates_in_theme > 0 ) {
	UM()->options()->update( 'email_html', true );
} else {
	UM()->options()->update( 'email_html', false );
}