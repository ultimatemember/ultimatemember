<?php

class UM_ADDON_multi_language {

	function __construct() {
		
		add_action('admin_menu', array(&$this, 'admin_menu'), 1001);
		add_action('admin_init', array(&$this, 'admin_init'), 1);
		
		add_filter('locale', array(&$this, 'locale'), 10 );
		add_filter('um_pre_args_setup', array(&$this, 'um_pre_args_setup'), 99 );
		
		add_action('um_after_form_fields', array(&$this, 'um_after_form_fields'), 1);
		add_action('um_after_everything_output', array(&$this, 'um_after_everything_output'), 1);

	}
	
	function um_after_form_fields( $args ) {
		if ( !isset( $args['locale'] ) ) return; ?>
		
		<input type="hidden" name="lang" id="lang" value="<?php echo $args['locale']; ?>" />
		
		<?php
	}
	
	function um_after_everything_output() {
		remove_filter('locale', array(&$this, 'locale'), 10 );
	}
	
	function locale( $locale ) {
		if ( isset($_GET['lang']) && !empty($_GET['lang']) ) {
			$locale = $_GET['lang'];
		} else {
			$browser_loc = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$browser_loc = explode(',', $browser_loc);
			$locale = $browser_loc[0];
			$locale = str_replace( '-', '_', $locale );
		}
		return $locale;
	}
	
	function um_pre_args_setup( $args ) {

		$assigned = get_option('um_multi_language');
		$locale = get_locale();

		$mode = ( isset( $args['mode'] ) ) ? $args['mode'] : 'undefined';
		
		if ( isset( $assigned[$mode][$locale] ) ) {
			$args['locale'] = $locale;
			$args['form_id'] = $assigned[$mode][$locale];
		}
		
		return $args;
		
	}

	function admin_menu() {
		
		global $ultimatemember;
		$this->addon = $ultimatemember->addons['multi_language'];
		add_submenu_page('ultimatemember', $this->addon[0], $this->addon[0], 'manage_options', 'multi_language', array(&$this, 'content') );
		
	}

	function admin_init() {
		
		if ( isset( $_POST['multi-language'] ) && current_user_can('manage_options') ) {
			
			$sync = '';
			$lang = $_POST['lang'];
			$form = $_POST['form'];
			
			$array = array('register','login','profile');
			
			foreach( $array as $arrays ) {
			foreach( $lang[$arrays] as $k => $v ) {
				if ( $v ) {
					$sync[$arrays][$v] = $form[$arrays][$k];
				}
			}
			}

			if ( isset( $sync ) ) {
				update_option('um_multi_language', $sync );
			}
		}
		
	}

	function content() {
		global $ultimatemember;
		
		$this->process_link = add_query_arg('um-addon-hook','multi_language');
		$sync = get_option('um_multi_language');

		?>
		
		<div class="wrap">
		
			<h2>Ultimate Member <sup style="font-size:15px"><?php echo ultimatemember_version; ?></sup></h2>
			
			<h3><?php echo $this->addon[0]; ?></h3>
			
			<form method="post" action="">
			
			<p><?php _e('To make a form appear in a specific language, enter the locale then select a form to assign to. Clear the locale and save changes to delete an assigned translation.','ultimatemember'); ?></p>

			<?php $array = array(
				'register' => __('Register Forms','ultimatemember'),
				'login' => __('Login Forms','ultimatemember'),
				'profile' => __('Profile Forms','ultimatemember')
			); ?>
			
			<?php foreach( $array as $slug => $label ) { ?>
			
			<h2><?php echo $label; ?></h2>
			<table class="form-table">
				
				<?php if ( isset( $sync[$slug] ) && !empty( $sync[$slug] ) ) { ?>

				<?php foreach( $sync[$slug] as $locale => $form_id ) { ?>
				<tr>
				<th scope="row"><input name="lang[<?php echo $slug; ?>][]" type="text" id="lang" value="<?php echo $locale; ?>" class="regular-text" /></th>
				<td>
					<select name="form[<?php echo $slug; ?>][]" id="form">
						<?php foreach( $ultimatemember->query->forms() as $id => $title ) { ?>
						<option value="<?php echo $id; ?>" <?php selected( $id, $form_id ); ?> ><?php echo $title; ?></option>
						<?php } ?>
					</select>
				</td>
				</tr>
				<?php } ?>
				
				<?php } ?>
				
				<tr>
				<th scope="row"><input name="lang[<?php echo $slug; ?>][]" type="text" id="lang" value="" class="regular-text" /></th>
				<td>
					<select name="form[<?php echo $slug; ?>][]" id="form">
						<?php foreach( $ultimatemember->query->forms() as $id => $title ) { ?>
						<option value="<?php echo $id; ?>"><?php echo $title; ?></option>
						<?php } ?>
					</select>
				</td>
				</tr>
				
			</table>
			
			<?php } ?>
			
			<p class="submit"><input type="submit" name="multi-language" id="multi-language" class="button button-primary" value="<?php _e('Save Changes','ultimatemember'); ?>" /></p>

			</form>

		</div><div class="clear"></div>
		
		<?php
		
	}

}

$UM_ADDON_multi_language = new UM_ADDON_multi_language();