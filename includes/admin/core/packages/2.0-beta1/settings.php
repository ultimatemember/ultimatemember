<?php
//remove old options
UM()->options()->remove( 'active_color' );
UM()->options()->remove( 'secondary_color' );
UM()->options()->remove( 'profile_align' );
UM()->options()->remove( 'register_align' );
UM()->options()->remove( 'login_align' );
UM()->options()->remove( 'primary_btn_color' );
UM()->options()->remove( 'primary_btn_hover' );
UM()->options()->remove( 'primary_btn_text' );
UM()->options()->remove( 'secondary_btn_color' );
UM()->options()->remove( 'secondary_btn_hover' );
UM()->options()->remove( 'secondary_btn_text' );
UM()->options()->remove( 'help_tip_color' );
UM()->options()->remove( 'form_field_label' );
UM()->options()->remove( 'form_border' );
UM()->options()->remove( 'form_border_hover' );
UM()->options()->remove( 'form_bg_color' );
UM()->options()->remove( 'form_bg_color_focus' );
UM()->options()->remove( 'form_text_color' );
UM()->options()->remove( 'form_placeholder' );
UM()->options()->remove( 'form_icon_color' );
UM()->options()->remove( 'form_asterisk_color' );
UM()->options()->remove( 'profile_photocorner' );
UM()->options()->remove( 'profile_main_bg' );
UM()->options()->remove( 'profile_header_bg' );
UM()->options()->remove( 'profile_header_text' );
UM()->options()->remove( 'profile_header_link_color' );
UM()->options()->remove( 'profile_header_link_hcolor' );
UM()->options()->remove( 'profile_header_icon_color' );
UM()->options()->remove( 'profile_header_icon_hcolor' );

//remove duplicates for UM Pages settings
delete_option( 'um_core_pages' );

$roles_associations = get_option( 'um_roles_associations' );

$profile_tab_main_roles = UM()->options()->get( 'profile_tab_main_roles' );
$profile_tab_main_roles = ! $profile_tab_main_roles ? array() : $profile_tab_main_roles;
if ( ! empty( $profile_tab_main_roles ) ) {
	foreach ( $profile_tab_main_roles as $i => $role_k ) {
		$profile_tab_main_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_main_roles', $profile_tab_main_roles );
}

$profile_tab_posts_roles = UM()->options()->get( 'profile_tab_posts_roles' );
$profile_tab_posts_roles = ! $profile_tab_posts_roles ? array() : $profile_tab_posts_roles;
if ( ! empty( $profile_tab_posts_roles ) ) {
	foreach ( $profile_tab_posts_roles as $i => $role_k ) {
		$profile_tab_posts_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_posts_roles', $profile_tab_posts_roles );
}

$profile_tab_comments_roles = UM()->options()->get( 'profile_tab_comments_roles' );
$profile_tab_comments_roles = ! $profile_tab_comments_roles ? array() : $profile_tab_comments_roles;
if ( ! empty( $profile_tab_comments_roles ) ) {
	foreach ( $profile_tab_comments_roles as $i => $role_k ) {
		$profile_tab_comments_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_comments_roles', $profile_tab_comments_roles );
}

$profile_tab_activity_roles = UM()->options()->get( 'profile_tab_activity_roles' );
$profile_tab_activity_roles = ! $profile_tab_activity_roles ? array() : $profile_tab_activity_roles;
if ( ! empty( $profile_tab_activity_roles ) ) {
	foreach ( $profile_tab_activity_roles as $i => $role_k ) {
		$profile_tab_activity_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_activity_roles', $profile_tab_activity_roles );
}

$profile_tab_messages_roles = UM()->options()->get( 'profile_tab_messages_roles' );
$profile_tab_messages_roles = ! $profile_tab_messages_roles ? array() : $profile_tab_messages_roles;
if ( ! empty( $profile_tab_messages_roles ) ) {
	foreach ( $profile_tab_messages_roles as $i => $role_k ) {
		$profile_tab_messages_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_messages_roles', $profile_tab_messages_roles );
}

$profile_tab_reviews_roles = UM()->options()->get( 'profile_tab_reviews_roles' );
$profile_tab_reviews_roles = ! $profile_tab_reviews_roles ? array() : $profile_tab_reviews_roles;
if ( ! empty( $profile_tab_reviews_roles ) ) {
	foreach ( $profile_tab_reviews_roles as $i => $role_k ) {
		$profile_tab_reviews_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_reviews_roles', $profile_tab_reviews_roles );
}

$profile_tab_purchases_roles = UM()->options()->get( 'profile_tab_purchases_roles' );
$profile_tab_purchases_roles = ! $profile_tab_purchases_roles ? array() : $profile_tab_purchases_roles;
if ( ! empty( $profile_tab_purchases_roles ) ) {
	foreach ( $profile_tab_purchases_roles as $i => $role_k ) {
		$profile_tab_purchases_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_purchases_roles', $profile_tab_purchases_roles );
}

$profile_tab_product_reviews = UM()->options()->get( 'profile_tab_product-reviews_roles' );
$profile_tab_product_reviews = ! $profile_tab_product_reviews ? array() : $profile_tab_product_reviews;
if ( ! empty( $profile_tab_product_reviews ) ) {
	foreach ( $profile_tab_product_reviews as $i => $role_k ) {
		$profile_tab_product_reviews[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_product-reviews_roles', $profile_tab_product_reviews );
}


$profile_tab_forums_roles = UM()->options()->get( 'profile_tab_forums_roles' );
$profile_tab_forums_roles = ! $profile_tab_forums_roles ? array() : $profile_tab_forums_roles;
if ( ! empty( $profile_tab_forums_roles ) ) {
	foreach ( $profile_tab_forums_roles as $i => $role_k ) {
		$profile_tab_forums_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_forums_roles', $profile_tab_forums_roles );
}

$profile_tab_friends_roles = UM()->options()->get( 'profile_tab_friends_roles' );
$profile_tab_friends_roles = ! $profile_tab_friends_roles ? array() : $profile_tab_friends_roles;
if ( ! empty( $profile_tab_friends_roles ) ) {
	foreach ( $profile_tab_friends_roles as $i => $role_k ) {
		$profile_tab_friends_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_friends_roles', $profile_tab_friends_roles );
}


$register_role = UM()->options()->get( 'register_role' );
if ( ! empty( $register_role ) ) {
	$register_role = $roles_associations[ $register_role ];

	UM()->options()->update( 'register_role', $register_role );
}

$woo_oncomplete_role = UM()->options()->get( 'woo_oncomplete_role' );
if ( ! empty( $woo_oncomplete_role ) ) {
	$woo_oncomplete_role = $roles_associations[ $woo_oncomplete_role ];
	UM()->options()->update( 'woo_oncomplete_role', $woo_oncomplete_role );
}

$woo_oncomplete_except_roles = UM()->options()->get( 'woo_oncomplete_except_roles' );
$woo_oncomplete_except_roles = ! $woo_oncomplete_except_roles ? array() : $woo_oncomplete_except_roles;
if ( ! empty( $woo_oncomplete_except_roles ) ) {
	foreach ( $woo_oncomplete_except_roles as $i => $role_k ) {
		$woo_oncomplete_except_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'woo_oncomplete_except_roles', $woo_oncomplete_except_roles );
}