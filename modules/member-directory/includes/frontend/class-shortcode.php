<?php
namespace umm\member_directory\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Shortcode
 *
 * @package umm\member_directory\includes\frontend
 */
class Shortcode {


	/**
	 * Shortcode constructor.
	 */
	function __construct() {
		add_shortcode( 'ultimatemember_searchform', array( &$this, 'ultimatemember_searchform' ) );
		add_shortcode( 'ultimatemember_directory', array( &$this, 'ultimatemember_directory' ) );

		add_action( 'um_pre_directory_shortcode', array( &$this, 'directory_shortcode_enqueue' ) );

		add_filter( 'um_load_shortcode_maybe_skip_meta', array( &$this, 'maybe_skip_meta' ), 10, 2 );
		add_filter( 'um_get_default_shortcode', array( &$this, 'get_default_shortcode' ), 10, 2 );

		add_action( 'um_form_shortcode_dynamic_css_include', array( &$this, 'dynamic_css_include' ), 10, 1 );
	}


	/**
	 * @param $shortcode
	 * @param $mode
	 *
	 * @return string
	 */
	public function get_default_shortcode( $shortcode, $mode ) {
		if ( 'directory' === $mode ) {
			$shortcode = '[ultimatemember_directory]';
		}
		return $shortcode;
	}


	/**
	 * @param $args
	 */
	public function dynamic_css_include( $args ) {
		if ( array_key_exists( 'mode', $args ) && 'directory' === $args['mode'] ) {
			$data = UM()->modules()->get_data( 'member-directory' );

			$file = $data['path'] . 'assets/dynamic_css/dynamic_' . $args['mode'] . '.php';

			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}


	/**
	 * @param bool $skip
	 * @param array $args
	 *
	 * @return bool
	 */
	public function maybe_skip_meta( $skip, $args ) {
		if ( array_key_exists( 'mode', $args ) && 'directory' === $args['mode'] ) {
			$skip = true;
		}
		return $skip;
	}


	/**
	 * 
	 */
	public function directory_shortcode_enqueue() {
		wp_enqueue_script( 'um_members' );
		if ( is_rtl() ) {
			wp_enqueue_style( 'um_members_rtl' );
		} else {
			wp_enqueue_style( 'um_members' );
		}
	}


	/**
	 * @param array $args
	 * @param string $content
	 *
	 * @return string
	 */
	public function ultimatemember_searchform( $args = array(), $content = '' ) {
		if ( ! UM()->modules()->is_active( 'member-directory' ) ) {
			return '';
		}

		$member_directory_ids = array();

		$page_id = um_get_predefined_page_id( 'members' );
		if ( $page_id ) {
			$members_page = get_post( $page_id );
			if ( ! empty( $members_page ) && ! is_wp_error( $members_page ) ) {
				if ( ! empty( $members_page->post_content ) ) {
					preg_match_all( '/\[ultimatemember[^\]]*?form_id\=[\'"]*?(\d+)[\'"]*?/i', $members_page->post_content, $matches );
					if ( ! empty( $matches[1] ) && is_array( $matches[1] ) ) {
						$member_directory_ids = array_map( 'absint', $matches[1] );
					}
				}
			}
		}

		if ( empty( $member_directory_ids ) ) {
			return '';
		}

		//current user priority role
		$priority_user_role = false;
		if ( is_user_logged_in() ) {
			$priority_user_role = UM()->roles()->get_priority_user_role( get_current_user_id() );
		}

		$query = array();
		foreach ( $member_directory_ids as $directory_id ) {
			$directory_data = UM()->query()->post_data( $directory_id );

			if ( isset( $directory_data['roles_can_search'] ) ) {
				$directory_data['roles_can_search'] = maybe_unserialize( $directory_data['roles_can_search'] );
			}

			$show_search = empty( $directory_data['roles_can_search'] ) || ( ! empty( $priority_user_role ) && in_array( $priority_user_role, $directory_data['roles_can_search'] ) );
			if ( empty( $directory_data['search'] ) || ! $show_search ) {
				continue;
			}

			$hash = UM()->module( 'member-directory' )->get_directory_hash( $directory_id );

			$query[ 'search_' . $hash ] = ! empty( $_GET[ 'search_' . $hash ] ) ? sanitize_text_field( $_GET[ 'search_' . $hash ] ) : '';
		}

		if ( empty( $query ) ) {
			return '';
		}

		$search_value = array_values( $query );

		return um_get_template_html(
			'searchform.php',
			array(
				'query'        => $query,
				'search_value' => $search_value[0],
				'members_page' => um_get_predefined_page_url( 'members' ),
			)
		);
	}


	/**
	 * @param array $args
	 *
	 * @return string
	 */
	function ultimatemember_directory( $args = array() ) {
		global $wpdb;

		$args = ! empty( $args ) ? $args : array();

		$default_directory = $wpdb->get_var(
			"SELECT pm.post_id 
				FROM {$wpdb->postmeta} pm 
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND 
					  pm.meta_value = 'directory' AND 
					  pm2.meta_value = '1'"
		);

		$args['form_id'] = $default_directory;

		$shortcode_attrs = '';
		foreach ( $args as $key => $value ) {
			$shortcode_attrs .= " {$key}=\"{$value}\"";
		}

		if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
			return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
		} else {
			return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
		}
	}
}
