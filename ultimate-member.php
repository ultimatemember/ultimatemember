<?php
/**
 * Plugin Name: Ultimate Member
 * Plugin URI: http://ultimatemember.com/
 * Description: The easiest way to create powerful online communities and beautiful user profiles with WordPress
 * Version: 2.10.2
 * Author: Ultimate Member
 * Author URI: http://ultimatemember.com/
 * Text Domain: ultimate-member
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.0
 *
 * @package UM
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugin_data = get_plugin_data( __FILE__, true, false );

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName
define( 'um_url', plugin_dir_url( __FILE__ ) );
define( 'um_path', plugin_dir_path( __FILE__ ) );
define( 'um_plugin', plugin_basename( __FILE__ ) );
define( 'ultimatemember_version', $plugin_data['Version'] );
define( 'ultimatemember_plugin_name', $plugin_data['Name'] );
// phpcs:enable Generic.NamingConventions.UpperCaseConstantName

define( 'UM_URL', plugin_dir_url( __FILE__ ) );
define( 'UM_PATH', plugin_dir_path( __FILE__ ) );
define( 'UM_PLUGIN', plugin_basename( __FILE__ ) );
define( 'UM_VERSION', $plugin_data['Version'] );
define( 'UM_PLUGIN_NAME', $plugin_data['Name'] );
// define( 'UM_DEV_MODE', true );

require_once 'includes/class-functions.php';
require_once 'includes/class-init.php';
