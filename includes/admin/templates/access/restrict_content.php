<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="um-admin-metabox">
	<?php
	if ( ! empty( $object->ID ) ) {
		$data = get_post_meta( $object->ID, 'um_content_restriction', true );
	} else {
		$data = array();
	}

	$_um_access_roles_value = array();
	if ( ! empty( $data['_um_access_roles'] ) ) {
		foreach ( $data['_um_access_roles'] as $key => $value ) {
			if ( $value ) {
				$_um_access_roles_value[] = $key;
			}
		}
	}

	if ( ! empty( $object->post_type ) ) {
		$post_type_object = get_post_type_object( $object->post_type );
	} else {
		$post_type_object = array();
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_admin_access_settings_fields
	 * @description Extend Admin Access Settings Fields
	 * @input_vars
	 * [{"var":"$fields","type":"array","desc":"Access Settings Fields"},
	 * {"var":"$data","type":"array","desc":"Form Data"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_filter( 'um_admin_access_settings_fields', 'function_name', 10, 2 );
	 * @example
	 * <?php
	 * add_filter( 'um_admin_access_settings_fields', 'my_admin_access_settings_fields', 10, 2 );
	 * function my_admin_access_settings_fields( $fields, $data ) {
	 *     // your code here
	 *     return $fields;
	 * }
	 * ?>
	 */
	$fields = apply_filters( 'um_admin_access_settings_fields', array(
		array(
			'id'      => '_um_custom_access_settings',
			'type'    => 'checkbox',
			'label'   => __( 'Restrict access to this post?', 'ultimate-member' ),
			'tooltip' => __( 'Activate individual content restriction settings for this post. Then ignore all terms restriction content settings if they exists.', 'ultimate-member' ),
			'value'   => ! empty( $data['_um_custom_access_settings'] ) ? $data['_um_custom_access_settings'] : 0,
		),
		array(
			'id'          => '_um_accessible',
			'type'        => 'select',
			'label'       => __( 'Who can access this post?', 'ultimate-member' ),
			'value'       => ! empty( $data['_um_accessible'] ) ? $data['_um_accessible'] : 0,
			'options'     => array(
				'0' => __( 'Everyone', 'ultimate-member' ),
				'1' => __( 'Logged out users', 'ultimate-member' ),
				'2' => __( 'Logged in users', 'ultimate-member' ),
			),
			'conditional' => array( '_um_custom_access_settings', '=', '1' ),
		),
		array(
			'id'          => '_um_access_roles',
			'type'        => 'multi_checkbox',
			'label'       => __( 'Select which roles can access this post', 'ultimate-member' ),
			'tooltip'     => __( 'Leave empty if you want to display a post for all logged in users', 'ultimate-member' ),
			'value'       => $_um_access_roles_value,
			'options'     => UM()->roles()->get_roles( false ),
			'columns'     => 3,
			'conditional' => array( '_um_accessible', '=', '2' ),
		),
		array(
			'id'          => '_um_access_hide_from_queries',
			'type'        => 'checkbox',
			'label'       => UM()->options()->get( 'disable_restriction_pre_queries' ) ? __( 'Hide from queries', 'ultimate-member' ) : __( 'Would you like to display a 404 page for users who do not have access to this page?', 'ultimate-member' ),
			'tooltip'     => UM()->options()->get( 'disable_restriction_pre_queries' ) ? __( 'Exclude only from WP queries results', 'ultimate-member' ) : __( 'Recommended to be enabled. Restricted post will be hidden by exclusion from WP Query. The safest and most effective method that hides post and its comments from all requests, RSS feeds, etc. on your site', 'ultimate-member' ),
			'value'       => ! empty( $data['_um_access_hide_from_queries'] ) ? $data['_um_access_hide_from_queries'] : '',
			'conditional' => array( '_um_accessible', '!=', '0' ),
		),
		array(
			'id'          => '_um_noaccess_action',
			'type'        => 'select',
			'label'       => __( 'What happens when users without access try to view the post?', 'ultimate-member' ),
			'tooltip'     => __( 'Action when users without access tries to view the post', 'ultimate-member' ),
			'value'       => ! empty( $data['_um_noaccess_action'] ) ? $data['_um_noaccess_action'] : 0,
			'options'     => array(
				'0' => __( 'Show access restricted message', 'ultimate-member' ),
				'1' => __( 'Redirect user', 'ultimate-member' ),
			),
			'conditional' => UM()->options()->get( 'disable_restriction_pre_queries' ) ? array( '_um_accessible', '!=', '0' ) : array( '_um_access_hide_from_queries', '=', '0' ),
		),
		array(
			'id'          => '_um_restrict_by_custom_message',
			'type'        => 'select',
			'label'       => __( 'Restricted access message type', 'ultimate-member' ),
			'tooltip'     => __( 'Would you like to use the global default message or apply a custom message to this post?', 'ultimate-member' ),
			'value'       => ! empty( $data['_um_restrict_by_custom_message'] ) ? $data['_um_restrict_by_custom_message'] : '0',
			'options'     => array(
				'0' => __( 'Global default message', 'ultimate-member' ),
				'1' => __( 'Custom message', 'ultimate-member' ),
			),
			'conditional' => array( '_um_noaccess_action', '=', '0' ),
		),
		array(
			'id'          => '_um_restrict_custom_message',
			'type'        => 'wp_editor',
			'label'       => __( 'Custom restricted access message', 'ultimate-member' ),
			'tooltip'     => __( 'You may replace global restricted access message here', 'ultimate-member' ),
			'value'       => ! empty( $data['_um_restrict_custom_message'] ) ? $data['_um_restrict_custom_message'] : '',
			'conditional' => array( '_um_restrict_by_custom_message', '=', '1' ),
		),
		array(
			'id'          => '_um_access_redirect',
			'type'        => 'select',
			'label'       => __( 'Where should users be redirected to?', 'ultimate-member' ),
			'tooltip'     => __( 'Select redirect to page when user hasn\'t access to post', 'ultimate-member' ),
			'value'       => ! empty( $data['_um_access_redirect'] ) ? $data['_um_access_redirect'] : '0',
			'options'     => array(
				'0' => __( 'Login page', 'ultimate-member' ),
				'1' => __( 'Custom URL', 'ultimate-member' ),
			),
			'conditional' => array( '_um_noaccess_action', '=', '1' ),
		),
		array(
			'id'          => '_um_access_redirect_url',
			'type'        => 'text',
			'label'       => __( 'Redirect URL', 'ultimate-member' ),
			'tooltip'     => __( 'Set full URL where do you want to redirect the user', 'ultimate-member' ),
			'value'       => ! empty( $data['_um_access_redirect_url'] ) ? $data['_um_access_redirect_url'] : '',
			'conditional' => array( '_um_access_redirect', '=', '1' ),
		),
	), $data, $object, $post_type_object );

// Hierarchical
//	if ( $post_type_object->hierarchical ) {
//		$fields[] = array(
//			'id'            => '_um_access_hierarchical',
//			'type'          => 'checkbox',
//			'label'         => __( 'Hierarchical', 'ultimate-member' ),
//			'tooltip'       => __( 'Use parent page settings for the child pages that doesn\'t have own restriction settings.', 'ultimate-member' ),
//			'value'         => empty( $data['_um_access_hierarchical'] ) ? 0 : $data['_um_access_hierarchical'],
//			'conditional'   => array( '_um_custom_access_settings', '=', '1' )
//		);
//	}

	UM()->admin_forms(
		array(
			'class'     => 'um-restrict-content um-third-column',
			'prefix_id' => 'um_content_restriction',
			'fields'    => $fields,
		)
	)->render_form();
	?>

</div>
