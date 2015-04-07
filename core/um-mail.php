<?php

class UM_Mail {

	function __construct() {

		add_filter('mandrill_nl2br', array(&$this, 'mandrill_nl2br') );
		
		$this->force_plain_text = '';
		
	}
	
	/***
	***	@mandrill compatibility
	***/
	function mandrill_nl2br($nl2br, $message = '') {
		
		// text emails
		if ( !um_get_option('email_html') ) {
			$nl2br = true;
		}
		
		return $nl2br;

	}
	
	/***
	***	@check If template exists
	***/
	function email_template( $template, $args = array() ) {
		if ( file_exists( get_stylesheet_directory() . '/ultimate-member/templates/email/' . $template . '.html' ) ) {
			return get_stylesheet_directory() . '/ultimate-member/templates/email/' . $template . '.html';
		}

		if ( isset( $args['path'] ) ) {
			$path = $args['path'];
		} else {
			$path = um_path . 'templates/email/';
		}
		
		if ( file_exists( $path . $template . '.html' ) ) {
			return $path . $template . '.html';
		}
		
		return false;
		
	}
	
	/***
	***	@sends an email to any user
	***/
	function send( $email, $template=null, $args = array() ) {
	
		if ( !$template ) return;
		if ( um_get_option( $template . '_on' ) != 1 ) return;
		if ( !is_email( $email ) ) return;
		
		$this->attachments = null;
		$this->headers = 'From: '. um_get_option('mail_from') .' <'. um_get_option('mail_from_addr') .'>' . "\r\n";

		$this->subject = um_get_option( $template . '_sub' );
		$this->subject = $this->convert_tags( $this->subject, $args );
		
		if ( isset( $args['admin'] ) || isset( $args['plain_text'] ) ) {
			$this->force_plain_text = 'forced';
		}

		// HTML e-mail or text
		if ( um_get_option('email_html') && $this->email_template( $template, $args ) ) {
			add_filter( 'wp_mail_content_type', array(&$this, 'set_content_type') );
			$this->message = file_get_contents( $this->email_template( $template, $args ) );
		} else {
			$this->message = um_get_option( $template );
		}
		
		// Convert tags in body
		$this->message = $this->convert_tags( $this->message, $args );

		// Send mail
		wp_mail( $email, $this->subject, $this->message, $this->headers, $this->attachments );
		remove_filter( 'wp_mail_content_type', array(&$this, 'set_content_type')  );
		
		// reset globals
		$this->force_plain_text = '';
		
	}
	
	/***
	***	@maybe sending HTML emails
	***/
	function set_content_type( $content_type ) {
		
		if ( $this->force_plain_text == 'forced' ) return 'text/plain';
		
		if ( um_get_option('email_html') ) return 'text/html';
		
		return 'text/plain';
	
	}
	
	/***
	***	@convert template tags in email template
	***/
	function convert_tags( $content, $args = array() ) {
	
		$search = array(
			'{display_name}',
			'{first_name}',
			'{last_name}',
			'{gender}',
			'{username}',
			'{email}',
			'{password}',
			'{login_url}',
			'{site_name}',
			'{site_url}',
			'{account_activation_link}',
			'{password_reset_link}',
			'{admin_email}',
			'{user_profile_link}',
			'{user_account_link}',
			'{submitted_registration}',
			'{user_avatar_url}',
		);
		
		$search = apply_filters('um_template_tags_patterns_hook', $search);
		
		$replace = array(
			um_user('display_name'),
			um_user('first_name'),
			um_user('last_name'),
			um_user('gender'),
			um_user('user_login'),
			um_user('user_email'),
			um_user('_um_cool_but_hard_to_guess_plain_pw'),
			um_get_core_page('login'),
			um_get_option('site_name'),
			get_bloginfo('url'),
			um_user('account_activation_link'),
			um_user('password_reset_link'),
			um_admin_email(),
			um_user_profile_url(),
			um_get_core_page('account'),
			um_user_submitted_registration(),
			um_get_user_avatar_url(),
		);
		
		$replace = apply_filters('um_template_tags_replaces_hook', $replace);
		
		$content = str_replace($search, $replace, $content);
		
		if ( isset( $args['tags'] ) && isset( $args['tags_replace'] ) ) {
			$content = str_replace($args['tags'], $args['tags_replace'], $content);
		}
		
		return $content;
		
	}

}