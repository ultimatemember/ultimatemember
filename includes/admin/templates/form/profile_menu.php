<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id;

$profile_role_array = array();
foreach ( UM()->roles()->get_roles() as $key => $value ) {
	$_um_profile_role = UM()->query()->get_meta_value( '_um_profile_role', $key );
	if ( ! empty( $_um_profile_role ) ) {
		$profile_role_array[] = $_um_profile_role;
	}
}

$profile_menu = ! isset( $post_id ) ? true : get_post_meta( $post_id, '_um_profile_menu', true );

$profile_menu_fields = array(
	array(
		'id'    => '_um_profile_menu',
		'type'  => 'checkbox',
		'label' => __( 'Enable profile menu', 'ultimate-member' ),
		'value' => $profile_menu,
	),
);

$tabs = UM()->profile()->tabs();

$tabs_options   = array();
$tabs_condition = array();
foreach ( $tabs as $id => $tab ) {
	if ( ! empty( $tab['hidden'] ) ) {
		continue;
	}

	if ( empty( $tab['name'] ) ) {
		continue;
	}

	$profile_tab = ! isset( $post_id ) ? true : get_post_meta( $post_id, '_um_profile_tab_' . $id, true );

	$tabs_options[ $id ] = $tab['name'];
	$tabs_condition[]    = '_um_profile_tab_' . $id;

	if ( isset( $tab['default_privacy'] ) ) {
		$fields = array(
			array(
				'id'          => '_um_profile_tab_' . $id,
				'type'        => 'checkbox',
				// translators: %s: Tab title
				'label'       => sprintf( __( '%s Tab', 'ultimate-member' ), $tab['name'] ),
				'conditional' => array( '_um_profile_menu', '=', 1 ),
				'data'        => array( 'fill__um_profile_menu_default_tab' => $id ),
				'value'       => $profile_tab,
			),
		);
	} else {
		$profile_tab_privacy = ! isset( $post_id ) ? 0 : get_post_meta( $post_id, '_um_profile_tab_' . $id . '_privacy', true );
		$profile_tab_roles   = ! isset( $post_id ) ? array() : get_post_meta( $post_id, '_um_profile_tab_' . $id . '_roles', true );

		$fields = array(
			array(
				'id'          => '_um_profile_tab_' . $id,
				'type'        => 'checkbox',
				// translators: %s: Tab title
				'label'       => sprintf( __( '%s Tab', 'ultimate-member' ), $tab['name'] ),
				'conditional' => array( '_um_profile_menu', '=', 1 ),
				'data'        => array( 'fill__um_profile_menu_default_tab' => $id ),
				'value'       => $profile_tab,
			),
			array(
				'id'          => '_um_profile_tab_' . $id . '_privacy',
				'type'        => 'select',
				// translators: %s: Tab title
				'label'       => sprintf( __( 'Who can see %s Tab?', 'ultimate-member' ), $tab['name'] ),
				'description' => __( 'Select which users can view this tab.', 'ultimate-member' ),
				'options'     => UM()->profile()->tabs_privacy(),
				'conditional' => array( '_um_profile_tab_' . $id, '=', 1 ),
				'size'        => 'medium',
				'value'       => $profile_tab_privacy,
			),
			array(
				'id'          => '_um_profile_tab_' . $id . '_roles',
				'type'        => 'select',
				'multi'       => true,
				'label'       => __( 'Allowed roles', 'ultimate-member' ),
				'description' => __( 'Select the the user roles allowed to view this tab.', 'ultimate-member' ),
				'options'     => UM()->roles()->get_roles(),
				'placeholder' => __( 'Choose user roles...', 'ultimate-member' ),
				'conditional' => array( '_um_profile_tab_' . $id . '_privacy', '=', array( '4', '5' ) ),
				'size'        => 'medium',
				'value'       => $profile_tab_roles,
			),
		);
	}

	$profile_menu_fields = array_merge( $profile_menu_fields, $fields );
}

$profile_menu_fields = array_merge(
	$profile_menu_fields,
	array(
		array(
			'id'          => '_um_profile_menu_default_tab',
			'type'        => 'select',
			'label'       => __( 'Profile menu default tab', 'ultimate-member' ),
			'description' => __( 'This will be the default tab on user profile page', 'ultimate-member' ),
			'options'     => $tabs_options,
			'conditional' => array( implode( '|', $tabs_condition ), '~', 1 ),
			'size'        => 'medium',
		),
		array(
			'id'          => '_um_profile_menu_icons',
			'type'        => 'checkbox',
			'label'       => __( 'Enable menu icons in desktop view', 'ultimate-member' ),
			'conditional' => array( '_um_profile_menu', '=', 1 ),
		),
	)
);
?>


<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-form-profile-menu um-half-column',
			'prefix_id' => 'form',
			'fields'    => $profile_menu_fields,
		)
	)->render_form();
	?>
	<div class="um-admin-clear"></div>
</div>
