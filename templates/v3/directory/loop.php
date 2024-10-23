<?php
/**
 * Template for the members directory list
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/members-list.php
 *
 * Page: "Members"
 *
 * @version 2.6.1
 *
 * @var array  $t_args
 * @var string $view_type
 * @var object $directory_obj
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Security here to avoid not registered view types.
if ( ! array_key_exists( $view_type, $directory_obj->view_types ) ) {
	return;
}

if ( ! empty( $members ) ) {
	foreach ( $members as $member ) {
		$t_args['member'] = $member;
		UM()->get_template( 'v3/directory/cell-' . $view_type . '.php', '', $t_args, true );
	}
}
