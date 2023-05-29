<?php
/*
Plugin Name: Ultimate Member
Plugin URI: http://ultimatemember.com/
Description: The easiest way to create powerful online communities and beautiful user profiles with WordPress
Version: 3.0.0-alpha20230529-1
Author: Ultimate Member
Author URI: http://ultimatemember.com/
Text Domain: ultimate-member
*/

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugin_data = get_plugin_data( __FILE__ );

define( 'UM_URL', plugin_dir_url( __FILE__ ) );
define( 'UM_PATH', plugin_dir_path( __FILE__ ) );
define( 'UM_PLUGIN', plugin_basename( __FILE__ ) );
define( 'UM_VERSION', $plugin_data['Version'] );
define( 'UM_PLUGIN_NAME', $plugin_data['Name'] );

require_once 'includes/class-um-functions.php';
require_once 'includes/class-um.php';
