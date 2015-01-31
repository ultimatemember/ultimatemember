<?php

class UM_Mail {

	function __construct() {

		add_filter('mandrill_nl2br', array(&$this, 'mandrill_nl2br') );
		
	}
	
	/***
	***	@mandrill compatibility
	***/
	function mandrill_nl2br($nl2br, $message) {
		
		// text emails
		$nl2br = true;
		
		return $nl2br;

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
		$this->subject = $this->convert_tags( $this->subject );
		
		$this->message = um_get_option( $template );
		$this->message = $this->convert_tags( $this->message );

		wp_mail( $email, $this->subject, $this->message, $this->headers, $this->attachments );

	}
	
	/***
	***	@convert template tags in email template
	***/
	function convert_tags( $content ) {
	
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
			'{account_activation_link}',
			'{password_reset_link}',
			'{admin_email}',
			'{user_profile_link}',
			'{submitted_registration}',
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
			um_user('account_activation_link'),
			um_user('password_reset_link'),
			um_admin_email(),
			um_user_profile_url(),
			um_user_submitted_registration(),
		);
		
		$replace = apply_filters('um_template_tags_replaces_hook', $replace);
		
		$content = str_replace($search, $replace, $content);
		return $content;
		
	}

}