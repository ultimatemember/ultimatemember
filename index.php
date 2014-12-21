<?php
/*
Plugin Name: Ultimate Member
Plugin URI: http://ultimatemember.com/
Description: Ultimate Member is a powerful community and membership plugin that allows you to create beautiful community and membership sites with WordPress
Version: 1.0.0
Author: Ultimate Member
Author URI: http://ultimatemember.com/
*/

	require_once(ABSPATH.'wp-admin/includes/plugin.php');
	$plugin_data = get_plugin_data( __FILE__ );

	define('um_url',plugin_dir_url(__FILE__ ));
	define('um_path',plugin_dir_path(__FILE__ ));
	define('ULTIMATEMEMBER_VERSION', $plugin_data['Version'] );
	define('um_plugin', plugin_basename( __FILE__ ) );
	$plugin = um_plugin;

	/* Start the plugin! */
	require_once um_path . 'um-init.php';
	
	/***
	***	@Display a welcome page
	***/
	function ultimatemember_activation_hook( $plugin ) {
	
		if( $plugin == um_plugin && get_option('um_version') != ULTIMATEMEMBER_VERSION ) {
		
			update_option('um_version', ULTIMATEMEMBER_VERSION );
			
			exit( wp_redirect( admin_url('admin.php?page=ultimatemember-about')  ) );
			
		}

	}
	add_action( 'activated_plugin', 'ultimatemember_activation_hook' );
	
	/***
	***	@Load plugin textdomain
	***/
	function ultimatemember_plugins_loaded() {
		load_plugin_textdomain( 'ultimatemember', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/' );
	}
	add_action( 'plugins_loaded', 'ultimatemember_plugins_loaded', 0 );
	
	/***
	***	@Add any custom links to plugin page
	***/
	function ultimatemember_plugin_links( $links ) {
	
		$more_links[] = '<a href="http://ultimatemember.com/docs/">' . __('Docs','ultimatemember') . '</a>';
		$more_links[] = '<a href="http://ultimatemember.com/support/">' . __('Support','ultimatemember') . '</a>';
		$more_links[] = '<a href="'.admin_url().'admin.php?page=um_options">' . __('Settings','ultimatemember') . '</a>';
		
		$links = $more_links + $links;
		
		$links[] = '<a href="'.admin_url().'?um_action=uninstall_ultimatemember" class="delete" title="'.__('Remove this plugin','ultimatemember').'">' . __( 'Uninstall','ultimatemember' ) . '</a>';

		return $links;
		
	}
	add_filter( "plugin_action_links_$plugin", 'ultimatemember_plugin_links' );