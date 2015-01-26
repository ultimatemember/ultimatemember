<?php

class UM_Admin_Access {

	function __construct() {

		$this->slug = 'ultimatemember';

		add_action( 'load-post.php', array(&$this, 'add_metabox'), 9 );
		add_action( 'load-post-new.php', array(&$this, 'add_metabox'), 9 );
	
	}
	
	/***
	***	@add a helper tooltip
	***/
	function tooltip( $text, $e = false ){
	
		?>
		
		<span class="um-admin-tip">
			<?php if ($e == 'e' ) { ?>
			<span class="um-admin-tipsy-e" title="<?php echo $text; ?>"><i class="dashicons dashicons-editor-help"></i></span>
			<?php } else { ?>
			<span class="um-admin-tipsy-w" title="<?php echo $text; ?>"><i class="dashicons dashicons-editor-help"></i></span>
			<?php } ?>
		</span>
		
		<?php
	
	}

	/***
	***	@Checks core post type
	***/
	function core_post_type( $post_type ){
		
		if ( strstr($post_type, 'um_') )
			return true;
		
		if ( !class_exists('UM_bbPress_API') && in_array($post_type,array('forum','topic','reply')) )
			return true;
		
		return false;
	}
	
	/***
	***	@Init the metaboxes
	***/
	function add_metabox() {
		global $current_screen;
		
		add_action( 'add_meta_boxes', array(&$this, 'add_metabox_form'), 1 );
		add_action( 'save_post', array(&$this, 'save_metabox_form'), 10, 2 );
		
	}
	
	/***
	***	@load a form metabox
	***/
	function load_metabox_form( $object, $box ) {
		global $ultimatemember, $post;
		include_once um_path . 'admin/templates/access/settings.php';
		wp_nonce_field( basename( __FILE__ ), 'um_admin_save_metabox_access_nonce' );
	}
	
	/***
	***	@add form metabox
	***/
	function add_metabox_form() {
		
		$types = get_post_types();
		foreach($types as $post_type) {
			if ( $this->core_post_type( $post_type ) ) return;
			add_meta_box('um-admin-access-control', __('Access Control'), array(&$this, 'load_metabox_form'), $post_type, 'side', 'default');
		}
		
	}

	/***
	***	@save form metabox
	***/
	function save_metabox_form( $post_id, $post ) {
		global $wpdb;

		// validate nonce
		if ( !isset( $_POST['um_admin_save_metabox_access_nonce'] ) || !wp_verify_nonce( $_POST['um_admin_save_metabox_access_nonce'], basename( __FILE__ ) ) ) return $post_id;

		// validate user
		$post_type = get_post_type_object( $post->post_type );
		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ) return $post_id;
		
		// save
		foreach( $_POST as $k => $v ) {
			if (strstr($k, '_um_')){
				update_post_meta( $post_id, $k, $v);
			}
		}

	}
	
}