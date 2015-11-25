<?php
/*
Plugin Name: Ultimate Member
Plugin URI: http://ultimatemember.com/
Description: The easiest way to create powerful online communities and beautiful user profiles with WordPress
Version: 1.3.29
Author: Ultimate Member
Author URI: http://ultimatemember.com/
*/

	require_once(ABSPATH.'wp-admin/includes/plugin.php');
	
	$plugin_data = get_plugin_data( __FILE__ );

	define('UM_URL',plugin_dir_url(__FILE__ ));
	define('UM_PATH',plugin_dir_path(__FILE__ ));
	define('UM_PLUGIN', plugin_basename( __FILE__ ) );
	
	define('ultimatemember_version', $plugin_data['Version'] );
	
	$plugin = UM_PLUGIN;

	/***
	***	@Init
	***/
	require_once UM_PATH . 'um-init.php';
	
	/***
	***	@Display a welcome page
	***/
	function ultimatemember_activation_hook( $plugin ) {

		if( $plugin == UM_PLUGIN && get_option('um_version') != ultimatemember_version ) {
		
			update_option('um_version', ultimatemember_version );
			
			exit( wp_redirect( admin_url('admin.php?page=ultimatemember-about')  ) );
			
		}

	}
	add_action( 'activated_plugin', 'ultimatemember_activation_hook' );

	/***
	***	@Add any custom links to plugin page
	***/
	function ultimatemember_plugin_links( $links ) {
	
		$more_links[] = '<a href="http://ultimatemember.com/docs/">' . __('Docs','ultimatemember') . '</a>';
		$more_links[] = '<a href="http://ultimatemember.com/forums/">' . __('Support','ultimatemember') . '</a>';
		$more_links[] = '<a href="'.admin_url().'admin.php?page=um_options">' . __('Settings','ultimatemember') . '</a>';
		
		$links = $more_links + $links;
		
		$links[] = '<a href="'.admin_url().'?um_adm_action=uninstall_ultimatemember" class="delete" title="'.__('Remove this plugin','ultimatemember').'">' . __( 'Uninstall','ultimatemember' ) . '</a>';

		return $links;
		
	}
	$prefix = is_network_admin() ? 'network_admin_' : '';
	add_filter( "{$prefix}plugin_action_links_$plugin", 'ultimatemember_plugin_links' );