<?php
$meta = get_post_custom( get_the_ID() );
foreach( $meta as $k => $v ) {
	if ( strstr( $k, '_um_' ) && !is_array( $v[0] ) ) {
		//print "'$k' => '" . $v[0] . "',<br />";
	}
}

$roles_array = array();

foreach ( UM()->roles()->get_roles() as $key => $value ) {
	if ( ! empty( UM()->query()->get_meta_value( '_um_roles', $key ) ) )
		$roles_array[] = UM()->query()->get_meta_value( '_um_roles', $key );
}

$show_these_users = get_post_meta( get_the_ID(), '_um_show_these_users', true );
if ( $show_these_users ) {
	$show_these_users = implode( "\n", str_replace( "\r", "", $show_these_users ) );
} ?>

<div class="um-admin-metabox">

	<?php $fields = array(
		array(
			'id'		=> '_um_mode',
			'type'		=> 'hidden',
			'name'		=> '_um_mode',
			'value'		=> 'directory',
		),
		array(
			'id'		=> '_um_roles',
			'type'		=> 'select',
			'name'		=> '_um_roles',
			'label'		=> __( 'User Roles to Display', 'ultimate-member' ),
			'tooltip'	=> __( 'If you do not want to show all members, select only user roles to appear in this directory', 'ultimate-member' ),
			'options'	=> UM()->roles()->get_roles(),
			'multi'		=> true,
			'value'		=> $roles_array,
		),
		array(
			'id'		=> '_um_has_profile_photo',
			'type'		=> 'checkbox',
			'name'		=> '_um_has_profile_photo',
			'label'		=> __( 'Only show members who have uploaded a profile photo', 'ultimate-member' ),
			'tooltip'	=> __( 'If \'Use Gravatars\' as profile photo is enabled, this option is ignored', 'ultimate-member' ),
			'value'		=> UM()->query()->get_meta_value( '_um_has_profile_photo' ),
		),
		array(
			'id'		=> '_um_has_cover_photo',
			'type'		=> 'checkbox',
			'name'		=> '_um_has_cover_photo',
			'label'		=> __( 'Only show members who have uploaded a cover photo', 'ultimate-member' ),
			'value'		=> UM()->query()->get_meta_value( '_um_has_cover_photo' ),
		),
		array(
			'id'		=> '_um_sortby',
			'type'		=> 'select',
			'name'		=> '_um_sortby',
			'label'		=> __( 'Sort users by', 'ultimate-member' ),
			'tooltip'	=> __( 'Sort users by a specific parameter in the directory', 'ultimate-member' ),
			'options'	=> apply_filters( 'um_admin_directory_sort_users_select', array(
				'user_registered_desc'	=> __( 'New users first', 'ultimate-member' ),
				'user_registered_asc'	=> __( 'Old users first', 'ultimate-member' ),
				'last_login'			=> __( 'Last login', 'ultimate-member' ),
				'display_name'			=> __( 'Display Name', 'ultimate-member' ),
				'first_name'			=> __( 'First Name', 'ultimate-member' ),
				'last_name'				=> __( 'Last Name', 'ultimate-member' ),
				'random'				=> __( 'Random', 'ultimate-member' ),
				'other'					=> __( 'Other (custom field)', 'ultimate-member' ),
			) ),
			'value'		=> UM()->query()->get_meta_value( '_um_sortby' ),
		),
		array(
			'id'		    => '_um_sortby_custom',
			'type'		    => 'text',
			'name'		    => '_um_sortby_custom',
			'label'		    => __( 'Meta key', 'ultimate-member' ),
			'tooltip'	    => __( 'To sort by a custom field, enter the meta key of field here', 'ultimate-member' ),
			'value'		    => UM()->query()->get_meta_value( '_um_sortby_custom', null, 'na' ),
			'conditional'   => array( '_um_sortby', '=', 'other' )
		),
		array(
			'id'		    => '_um_show_these_users',
			'type'		    => 'textarea',
			'name'		    => '_um_show_these_users',
			'label'		    => __( 'Only show specific users (Enter one username per line)', 'ultimate-member' ),
			'value'		    => $show_these_users,
		)
	);

	$fields = apply_filters( 'um_admin_extend_directory_options_general', $fields );

	UM()->admin_forms( array(
		'class'		=> 'um-member-directory-general um-half-column',
		'prefix_id'	=> 'um_metadata',
		'fields' 	=> $fields
	) )->render_form(); ?>

	<div class="um-admin-clear"></div>

</div>