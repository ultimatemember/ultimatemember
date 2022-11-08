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

		add_filter( 'um_load_shortcode_maybe_skip_meta', array( &$this, 'maybe_skip_meta' ), 10, 2 );

		add_filter( 'um_main_ultimatemember_shortcode_content', array( &$this, 'change_content' ), 10, 3 );

		add_action( 'um_form_shortcode_dynamic_css_include', array( &$this, 'dynamic_css_include' ), 10, 1 );
	}


	public function change_content( $content, $mode, $args ) {
		if ( 'directory' === $mode ) {
			$content = um_get_template_html( 'members.php', array( 'args' => $args, 'form_id' => $args['form_id'], 'mode' => $args['mode'] ), 'member-directory' );
		}

		return $content;
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
	 * @param array $args
	 * @param string $content
	 *
	 * @return string
	 */
	public function ultimatemember_searchform( $args = array(), $content = '' ) {
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
			),
			'member-directory'
		);
	}


	/**
	 * @param array $args
	 *
	 * @return string
	 */
	function ultimatemember_directory( $args = array() ) {
		/** There is possible to use 'shortcode_atts_ultimatemember' filter for getting customized $atts. This filter is documented in wp-includes/shortcodes.php "shortcode_atts_{$shortcode}" */
		$args = shortcode_atts(
			array(
				'id' => false,
			),
			$args,
			'ultimatemember_directory'
		);

		if ( empty( $args['id'] ) || ! is_numeric( $args['id'] ) ) {
			return '';
		}

		$directory = get_post( $args['id'] );
		if ( empty( $directory ) ) {
			return '';
		}

		if ( 'publish' !== $directory->post_status ) {
			return '';
		}

		wp_enqueue_script( 'um_members' );
		if ( is_rtl() ) {
			wp_enqueue_style( 'um_members_rtl' );
		} else {
			wp_enqueue_style( 'um_members' );
		}

		$directory_args = UM()->query()->post_data( $args['id'] );
		foreach ( $directory_args as $k => $v ) {
			$directory_args[ $k ] = maybe_unserialize( $directory_args[ $k ] );
		}

		return um_get_template_html(
			'members.php',
			array(
				'args'    => $directory_args,
				'form_id' => $args['id'],
				'mode'    => 'directory',
			),
			'member-directory'
		);
	}
}
