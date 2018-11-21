<?php
function um_upgrade_styles20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'styles.php';
	wp_send_json_success( array( 'message' => __( 'Styles was upgraded successfully', 'ultimate-member' ) ) );
}


function um_upgrade_user_roles20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();
	/**
	 * @var $response_roles_data
	 */
	include 'user_roles.php';

	wp_send_json_success( array( 'message' => __( 'User Roles was upgraded successfully', 'ultimate-member' ), 'roles' => $response_roles_data ) );
}


function um_upgrade_get_users_per_role20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	if ( ! empty( $_POST['key_in_meta'] ) ) {
		$args = array(
			'meta_query'    => array(
				array(
					'key'   => 'role',
					'value' => $_POST['key_in_meta']
				)
			),
			'number'        => '',
			'count_total'   => false,
			'fields'        => 'ids'
		);
		$users = get_users( $args );
		$count = count( $users );

		wp_send_json_success( array( 'count' => $count ) );
	} else {
		wp_send_json_error();
	}
}


function um_upgrade_update_users_per_page20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();
	if ( ! empty( $_POST['key_in_meta'] ) && ! empty( $_POST['role_key'] ) && ! empty( $_POST['page'] ) ) {
		$users_per_page = 100;

		$all_wp_roles = array_keys( get_editable_roles() );

		$args = array(
			'meta_query'    => array(
				array(
					'key'   => 'role',
					'value' => $_POST['key_in_meta']
				)
			),
			'paged'         => $_POST['page'],
			'number'        => $users_per_page,
		);
		$all_users = get_users( $args );

		//update roles for users
		foreach ( $all_users as $k => $user ) {
			$user_object = get_userdata( $user->ID );

			if ( ! in_array( $_POST['role_key'], $all_wp_roles ) ) {
				$user_object->add_role( 'um_' . $_POST['role_key'] );
			} else {
				if ( ! in_array( $_POST['role_key'], (array) $user_object->roles ) ) {
					$user_object->add_role( $_POST['role_key'] );
				}
			}
		}

		$from = ( $_POST['page'] * $users_per_page ) - $users_per_page + 1;
		$to = $_POST['page'] * $users_per_page;

		wp_send_json_success( array( 'message' => sprintf( __( 'Users from %s to %s was upgraded successfully...', 'ultimate-member' ), $from, $to ) ) );
	} else {
		wp_send_json_error();
	}
}


function um_upgrade_content_restriction20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'content_restriction.php';
	wp_send_json_success( array( 'message' => 'Content restriction settings was upgraded successfully' ) );
}



function um_upgrade_settings20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'settings.php';
	wp_send_json_success( array( 'message' => __( 'Settings was upgraded successfully', 'ultimate-member' ) ) );
}


function um_upgrade_menus20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'menus.php';
	wp_send_json_success( array( 'message' => __( 'Menus settings was upgraded successfully', 'ultimate-member' ) ) );
}


function um_upgrade_mc_lists20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'mc_lists.php';
	wp_send_json_success( array( 'message' => __( 'Mailchimp Lists was upgraded successfully', 'ultimate-member' ) ) );
}


function um_upgrade_social_login20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'social_login.php';
	wp_send_json_success( array( 'message' => __( 'Social login forms was upgraded successfully', 'ultimate-member' ) ) );
}


function um_upgrade_cpt20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'um_cpt.php';
	wp_send_json_success( array( 'message' => __( 'UM Custom Posts was upgraded successfully', 'ultimate-member' ) ) );
}


function um_upgrade_get_forums20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	remove_all_actions( 'pre_get_posts' );

	$bb_forums = get_posts( array(
		'post_type'     => 'forum',
		'numberposts'   => -1,
		'fields'        => 'ids'
	) );

	wp_send_json_success( array( 'count' => count( $bb_forums ), 'message' => __( 'Forums are ready for upgrade', 'ultimate-member' ) ) );
}


function um_upgrade_update_forum_per_page20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	if ( ! empty( $_POST['page'] ) ) {
		$posts_per_page = 100;

		$roles_associations = get_option( 'um_roles_associations' );

		/*$bb_forums = get_posts( array(
			'post_type'     => 'forum',
			'paged'         => $_POST['page'],
			'numberposts'   => $posts_per_page,
			'fields'        => 'ids'
		) );*/

		$p_query = new WP_Query;
		$bb_forums = $p_query->query( array(
			'post_type'         => 'forum',
			'paged'             => $_POST['page'],
			'posts_per_page'    => $posts_per_page,
			'fields'            => 'ids'
		) );

		foreach ( $bb_forums as $forum_id ) {
			$bbpress_can_topic = get_post_meta( $forum_id, '_um_bbpress_can_topic', true );
			$bbpress_can_topic = ! $bbpress_can_topic ? array() : $bbpress_can_topic;
			if ( ! empty( $bbpress_can_topic ) ) {
				foreach ( $bbpress_can_topic as $i => $role_k ) {
					$bbpress_can_topic[ $i ] = $roles_associations[ $role_k ];
				}

				update_post_meta( $forum_id, '_um_bbpress_can_topic', $bbpress_can_topic );
			}


			$bbpress_can_reply = get_post_meta( $forum_id, '_um_bbpress_can_reply', true );
			$bbpress_can_reply = ! $bbpress_can_reply ? array() : $bbpress_can_reply;
			if ( ! empty( $bbpress_can_reply ) ) {
				foreach ( $bbpress_can_reply as $i => $role_k ) {
					$bbpress_can_reply[ $i ] = $roles_associations[ $role_k ];
				}

				update_post_meta( $forum_id, '_um_bbpress_can_reply', $bbpress_can_reply );
			}
		}

		$from = ( $_POST['page'] * $posts_per_page ) - $posts_per_page + 1;
		$to = $_POST['page'] * $posts_per_page;

		wp_send_json_success( array( 'message' => sprintf( __( 'Forums from %s to %s was upgraded successfully...', 'ultimate-member' ), $from, $to ) ) );
	} else {
		wp_send_json_error();
	}
}


function um_upgrade_get_products20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	$wc_products = get_posts( array(
		'post_type'     => 'product',
		'numberposts'   => -1,
		'fields'        => 'ids'
	) );

	wp_send_json_success( array( 'count' => count( $wc_products ), 'message' => __( 'Woocommerce Products are ready for upgrade', 'ultimate-member' ) ) );
}


function um_upgrade_update_products_per_page20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	if ( ! empty( $_POST['page'] ) ) {
		$posts_per_page = 100;

		$roles_associations = get_option( 'um_roles_associations' );

		/*$wc_products = get_posts( array(
			'post_type'     => 'product',
			'numberposts'   => $posts_per_page,
			'paged'         => $_POST['page'],
			'fields'        => 'ids'
		) );*/

		$p_query = new WP_Query;
		$wc_products = $p_query->query( array(
			'post_type'         => 'product',
			'paged'             => $_POST['page'],
			'posts_per_page'    => $posts_per_page,
			'fields'            => 'ids'
		) );

		foreach ( $wc_products as $product_id ) {
			$woo_product_role = get_post_meta( $product_id, '_um_woo_product_role', true );

			if ( ! empty( $woo_product_role ) ) {
				$woo_product_role = $roles_associations[ $woo_product_role ];
				update_post_meta( $product_id, '_um_woo_product_role', $woo_product_role );
			}

			$woo_product_activated_role = get_post_meta( $product_id, '_um_woo_product_activated_role', true );

			if ( ! empty( $woo_product_activated_role ) ) {
				$woo_product_activated_role = $roles_associations[ $woo_product_activated_role ];
				update_post_meta( $product_id, '_um_woo_product_activated_role', $woo_product_activated_role );
			}

			$woo_product_downgrade_pending_role = get_post_meta( $product_id, '_um_woo_product_downgrade_pending_role', true );

			if ( ! empty( $woo_product_downgrade_pending_role ) ) {
				$woo_product_downgrade_pending_role = $roles_associations[ $woo_product_downgrade_pending_role ];
				update_post_meta( $product_id, '_um_woo_product_downgrade_pending_role', $woo_product_downgrade_pending_role );
			}

			$woo_product_downgrade_onhold_role = get_post_meta( $product_id, '_um_woo_product_downgrade_onhold_role', true );

			if ( ! empty( $woo_product_downgrade_onhold_role ) ) {
				$woo_product_downgrade_onhold_role = $roles_associations[ $woo_product_downgrade_onhold_role ];
				update_post_meta( $product_id, '_um_woo_product_downgrade_onhold_role', $woo_product_downgrade_onhold_role );
			}

			$woo_product_downgrade_expired_role = get_post_meta( $product_id, '_um_woo_product_downgrade_expired_role', true );

			if ( ! empty( $woo_product_downgrade_expired_role ) ) {
				$woo_product_downgrade_expired_role = $roles_associations[ $woo_product_downgrade_expired_role ];
				update_post_meta( $product_id, '_um_woo_product_downgrade_expired_role', $woo_product_downgrade_expired_role );
			}

			$woo_product_downgrade_cancelled_role = get_post_meta( $product_id, '_um_woo_product_downgrade_cancelled_role', true );

			if ( ! empty( $woo_product_downgrade_cancelled_role ) ) {
				$woo_product_downgrade_cancelled_role = $roles_associations[ $woo_product_downgrade_cancelled_role ];
				update_post_meta( $product_id, '_um_woo_product_downgrade_cancelled_role', $woo_product_downgrade_cancelled_role );
			}
		}

		$from = ( $_POST['page'] * $posts_per_page ) - $posts_per_page + 1;
		$to = $_POST['page'] * $posts_per_page;

		wp_send_json_success( array( 'message' =>  sprintf( __( 'Woocommerce Products from %s to %s was upgraded successfully...', 'ultimate-member' ), $from, $to ) ) );
	} else {
		wp_send_json_error();
	}
}



function um_upgrade_email_templates20beta1() {
	UM()->admin()->check_ajax_nonce();

	um_maybe_unset_time_limit();

	include 'email_templates.php';

	update_option( 'um_last_version_upgrade', '2.0-beta1' );
	delete_option( 'um_roles_associations' );

	wp_send_json_success( array( 'message' => __( 'Email Templates was upgraded successfully', 'ultimate-member' ) ) );
}