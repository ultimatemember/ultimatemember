<?php

class UM_Admin_Columns {

	function __construct() {

		$this->slug = 'ultimatemember';
		
		add_filter('manage_edit-um_form_columns', array(&$this, 'manage_edit_um_form_columns') );
		add_action('manage_um_form_posts_custom_column', array(&$this, 'manage_um_form_posts_custom_column'), 10, 3);

		add_filter('manage_edit-um_directory_columns', array(&$this, 'manage_edit_um_directory_columns') );
		add_action('manage_um_directory_posts_custom_column', array(&$this, 'manage_um_directory_posts_custom_column'), 10, 3);
		
	}
	
	/***
	***	@Custom columns for Form
	***/
	function manage_edit_um_form_columns($columns) {
	
		$admin = new UM_Admin_Metabox();
		
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['id'] = __('ID');
		$new_columns['title'] = __('Title');
		$new_columns['mode'] = __('Type');
		$new_columns['shortcode'] = __('Shortcode') . $admin->_tooltip( 'Copy this shortcode to any post/page to display the relevant form' );
		$new_columns['impressions'] = __('Impressions') . $admin->_tooltip( 'The total number of times this form has been viewed' );
		$new_columns['conversions'] = __('Conversions') . $admin->_tooltip( 'The total number of conversions. e.g. Successful sign-ups' );
		$new_columns['date'] = __('Date');
		
		return $new_columns;
		
	}

	/***
	***	@Display cusom columns for Form
	***/
	function manage_um_form_posts_custom_column($column_name, $id) {
		global $wpdb, $ultimatemember;
		
		switch ($column_name) {
		
			case 'id':
				echo '<span class="um-admin-number">'.$id.'</span>';
				break;
				
			case 'shortcode':
				echo $ultimatemember->shortcodes->get_shortcode( $id );
				break;
				
			case 'mode':
				$mode = $ultimatemember->query->get_attr('mode', $id);
				echo '<span class="um-admin-tag um-admin-type-'.$mode.'">'. $ultimatemember->form->display_form_type($mode, $id) . '</span>';
				break;
				
			case 'impressions':
				$impressions = $ultimatemember->query->get_attr('impressions', $id);
				echo (!empty( $impressions )) ? $impressions : 'N/A';
				break;
				
			case 'conversions':
				$impressions = $ultimatemember->query->get_attr('impressions', $id);
				$conversions = $ultimatemember->query->get_attr('conversions', $id);
				
				if ($impressions && $conversions){
					$pct = ($conversions / $impressions) * 100;
					$pct = round($pct, 2);
				}
				
				echo (!empty( $conversions )) ? $conversions . '<span class="um-admin-txtspace"></span>' . '(' . $pct . '%)' : 'N/A';
				break;
				
		}
		
	}
	
	/***
	***	@Custom columns for Directory
	***/
	function manage_edit_um_directory_columns($columns) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['id'] = __('ID');
		$new_columns['title'] = __('Title');
		$new_columns['shortcode'] = __('Shortcode');
		$new_columns['date'] = __('Date');
		return $new_columns;
	}

	/***
	***	@Display cusom columns for Directory
	***/
	function manage_um_directory_posts_custom_column($column_name, $id) {
		global $wpdb, $ultimatemember;
		
		switch ($column_name) {
		
			case 'id':
				echo '<span class="um-admin-number">'.$id.'</span>';
				break;
				
			case 'shortcode':
				echo $ultimatemember->shortcodes->get_shortcode( $id );
				break;
				
		}
		
	}
	
}