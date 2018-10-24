<?php

/**
 * Locate a template and return the path for inclusion.
 *
 * @access public
 * @param string $template_name
 * @param bool $html
 * @return string
 */
function um_upgrade20beta1_template_in_theme( $template_name, $html = false ) {
	$template_name_file = UM()->mail()->get_template_filename( $template_name );
	$ext = ! $html ? '.php' : '.html';

	$blog_id = '';
	if ( ! $html ) {
		$blog_id = UM()->mail()->get_blog_id();
	}

	// check if there is template at theme folder
	$template = locate_template( array(
		trailingslashit( 'ultimate-member/email' . $blog_id ) . $template_name_file . $ext
	) );
	// Return what we found.
	return ! $template ? false : true;
}


/**
 * Method returns expected path for template
 *
 * @access public
 * @param string $location
 * @param string $template_name
 * @param bool $html
 * @return string
 */
function um_upgrade20beta1_get_template_file( $location, $template_name, $html = false ) {
	$template_path = '';
	$template_name_file = UM()->mail()->get_template_filename( $template_name );
	$ext = ! $html ? '.php' : '.html';
	switch( $location ) {
		case 'theme':

			$blog_id = '';
			if ( ! $html ) {
				$blog_id = UM()->mail()->get_blog_id();
			}

			$template_path = trailingslashit( get_stylesheet_directory() . '/ultimate-member/email' . $blog_id ). $template_name_file . $ext;
			break;
		case 'plugin':
			$path = ! empty( UM()->mail()->path_by_slug[ $template_name ] ) ? UM()->mail()->path_by_slug[ $template_name ] : um_path . 'templates/email';
			$template_path = trailingslashit( $path ) . $template_name . $ext;
			break;
	}
	return $template_path;
}


/**
 * Ajax copy template to the theme
 *
 * @param string $template
 * @return bool
 */
function um_upgrade20beta1_copy_email_template( $template ) {
	$in_theme = um_upgrade20beta1_template_in_theme( $template );
	if ( $in_theme ) {
		return false;
	}
	$plugin_template_path = um_upgrade20beta1_get_template_file( 'plugin', $template );
	$theme_template_path = um_upgrade20beta1_get_template_file( 'theme', $template );
	$temp_path = str_replace( trailingslashit( get_stylesheet_directory() ), '', $theme_template_path );
	$temp_path = str_replace( '/', DIRECTORY_SEPARATOR, $temp_path );
	$folders = explode( DIRECTORY_SEPARATOR, $temp_path );
	$folders = array_splice( $folders, 0, count( $folders ) - 1 );
	$cur_folder = '';
	$theme_dir = trailingslashit( get_stylesheet_directory() );
	foreach ( $folders as $folder ) {
		$prev_dir = $cur_folder;
		$cur_folder .= $folder . DIRECTORY_SEPARATOR;
		if ( ! is_dir( $theme_dir . $cur_folder ) && wp_is_writable( $theme_dir . $prev_dir ) ) {
			mkdir( $theme_dir . $cur_folder, 0777 );
		}
	}
	if ( file_exists( $plugin_template_path ) && copy( $plugin_template_path, $theme_template_path ) ) {
		return true;
	} else {
		return false;
	}
}


/**
 * Insert email template content to file
 *
 * @param string $path Filepath
 * @param string $content Email template content
 */
function um_upgrade20beta1_insert_content( $path, $content ) {
	$fp = @fopen( $path, "w" );
	@fputs( $fp, $content );
	@fclose( $fp );
}


/**
 * Transferring email templates to new logic
 */
function um_upgrade20beta1_email_templates_process() {
	$templates_in_theme = 0;
	$emails = UM()->config()->email_notifications;
	foreach ( $emails as $email_key => $value ) {

		$in_theme = um_upgrade20beta1_template_in_theme( $email_key, true );
		$theme_template_path = um_upgrade20beta1_get_template_file( 'theme', $email_key );

		if ( ! $in_theme ) {
			//there isn't HTML email template's file in theme, get from option
			//this value is correct for each multisite's subsites
			$setting_value = UM()->options()->get( $email_key );

			$html_email = UM()->options()->get( 'email_html' );
			if ( $html_email ) {

				if ( ! um_upgrade20beta1_copy_email_template( $email_key ) ) {

					um_upgrade20beta1_insert_content( $theme_template_path, $setting_value );

				} else {

					$templates_in_theme++;

				}
			} else {

				um_upgrade20beta1_insert_content( $theme_template_path, $setting_value );

			}

		} else {
			//there is HTML email template in a theme's folder
			$theme_template_path_html = um_upgrade20beta1_get_template_file( 'theme', $email_key, true );

			$setting_value = preg_replace( '/<\/body>|<\/head>|<html>|<\/html>|<body.*?>|<head.*?>/' , '', file_get_contents( $theme_template_path_html ) );

			if ( file_exists( $theme_template_path_html ) ) {

				$temp_path = str_replace( trailingslashit( get_stylesheet_directory() ), '', $theme_template_path );
				$temp_path = str_replace( '/', DIRECTORY_SEPARATOR, $temp_path );
				$folders = explode( DIRECTORY_SEPARATOR, $temp_path );
				$folders = array_splice( $folders, 0, count( $folders ) - 1 );
				$cur_folder = '';
				$theme_dir = trailingslashit( get_stylesheet_directory() );
				foreach ( $folders as $folder ) {
					$prev_dir = $cur_folder;
					$cur_folder .= $folder . DIRECTORY_SEPARATOR;
					if ( ! is_dir( $theme_dir . $cur_folder ) && wp_is_writable( $theme_dir . $prev_dir ) ) {
						mkdir( $theme_dir . $cur_folder, 0777 );
					}
				}

				if ( copy( $theme_template_path_html, $theme_template_path ) ) {

					um_upgrade20beta1_insert_content( $theme_template_path, $setting_value );

					$templates_in_theme++;

				}

			}

		}
	}

	$email_html = ( $templates_in_theme > 0 ) ? true : false;
	UM()->options()->update( 'email_html', $email_html );
}


if ( is_multisite() ) {
	$start_blog_id = get_current_blog_id();

	$blog_ids = get_sites( array(
		'fields' => 'ids',
	) );

	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		um_upgrade20beta1_email_templates_process();
	}

	restore_current_blog();
} else {
	um_upgrade20beta1_email_templates_process();
}