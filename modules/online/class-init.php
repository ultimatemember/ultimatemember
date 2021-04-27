<?php
namespace umm\online;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\online
 */
final class Init {


	/**
	 * @var string
	 */
	private $slug = 'online';


	/**
	 * @var
	 */
	private static $instance;


	/**
	 * @return Init
	 */
	static public function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Init constructor.
	 */
	function __construct() {
		$this->common();
		if ( UM()->is_request( 'frontend' ) ) {
			$this->shortcode();
		}

		$this->member_directory();

		add_action( 'widgets_init', [ &$this, 'widgets_init' ] );
	}


	/**
	 * Init Online users widget
	 */
	function widgets_init() {
		register_widget( 'umm\online\includes\widgets\Online_List' );
	}


	/**
	 * @return includes\Common()
	 */
	function common() {
		if ( empty( UM()->classes['umm\online\includes\common'] ) ) {
			UM()->classes['umm\online\includes\common'] = new includes\Common();
		}
		return UM()->classes['umm\online\includes\common'];
	}


	/**
	 * @return includes\Member_Directory()
	 */
	function member_directory() {
		if ( empty( UM()->classes['umm\online\includes\member_directory'] ) ) {
			UM()->classes['umm\online\includes\member_directory'] = new includes\Member_Directory();
		}
		return UM()->classes['umm\online\includes\member_directory'];
	}


	/**
	 * @return includes\Shortcode()
	 */
	function shortcode() {
		if ( empty( UM()->classes['umm\online\includes\shortcode'] ) ) {
			UM()->classes['umm\online\includes\shortcode'] = new includes\Shortcode();
		}
		return UM()->classes['umm\online\includes\shortcode'];
	}


}