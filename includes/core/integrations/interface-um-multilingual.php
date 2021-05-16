<?php
namespace um\core\integrations;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( interface_exists( 'um\core\integrations\UM_Multilingual' ) ) {
	return;
}

/**
 * Interface UM_Multilingual
 *
 * @package um\core\integrations
 */
interface UM_Multilingual {

	/**
	 * Class UM_Multilingual constructor.
	 */
	public function __construct();

	/**
	 * Add UM rewrite rules for the Account page and Profile page
	 *
	 * @since  2.1.7
	 *
	 * @param  array $rules
	 * @return array
	 */
	public function add_rewrite_rules( $rules );

	/**
	 * Adding endings to the "Subject Line" field, depending on the language.
	 *
	 * @since  2.1.6
	 * @exaple change 'welcome_email_sub' to 'welcome_email_sub_de_DE'
	 *
	 * @param  array  $section_fields  The email template fields
	 * @param  string $email_key       The email template slug
	 * @return array
	 */
	public function admin_settings_email_section_fields( $section_fields, $email_key );

	/**
	 *
	 * Add cell for the column 'translations' in the Email table.
	 *
	 * @since  2.1.6
	 *
	 * @param  array  $item  The email template data
	 * @return string
	 */
	public function emails_column_content( $item );

	/**
	 * Get content for the cell of the column 'translations' in the Email table.
	 *
	 * @since  2.1.6
	 *
	 * @param  string  $template  The email template slug
	 * @param  string  $code      Slug or locale of the queried language
	 * @return string
	 */
	public function emails_column_content_item( $template, $code );

	/**
	 * Add header for the column 'translations' in the Email table.
	 *
	 * @since  2.1.6
	 *
	 * @param  array   $columns   The Email table headers
	 * @return array
	 */
	public function emails_column_header( $columns );


	/**
	 * Get current locale
	 *
	 * @since  2.1.21
	 * 
	 * @return string
	 *
	 */
	public function get_current_locale();


	/**
	 * Get default and current locales.
	 *
	 * @since  2.1.6
	 *
	 * @param  string|false  $current_code  Slug of the queried language
	 * @return array
	 */
	public function get_languages_codes( $current_code = false );

	/**
	 * Replace email Subject with translated value on email send.
	 *
	 * @since  2.1.6
	 * @exaple change 'welcome_email_sub' to 'welcome_email_sub_de_DE'
	 *
	 * @param  string  $subject   Default subject
	 * @param  string  $template  The email template slug
	 * @return string
	 */
	public function localize_email_subject( $subject, $template );
}
