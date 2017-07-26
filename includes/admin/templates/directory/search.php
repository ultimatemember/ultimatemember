<div class="um-admin-metabox">
	<?php
	$can_search_array = array();
	foreach ( UM()->roles()->get_roles() as $key => $value ) {
		if ( ! empty( UM()->query()->get_meta_value( '_um_roles_can_search', $key ) ) )
			$can_search_array[] = UM()->query()->get_meta_value( '_um_roles_can_search', $key );
	}

	$custom_search = apply_filters( 'um_admin_custom_search_filters', array() );
	$searchable_fields = UM()->builtin()->all_user_fields('date,time,url');
	$searchable_fields = $searchable_fields + $custom_search;
	$user_fields = array();
	foreach ( $searchable_fields as $key => $arr ) {
		$user_fields[$key] = isset( $arr['title'] ) ? $arr['title'] : '';
	}

    $post_id = get_the_ID();
    $_um_search_fields = get_post_meta( $post_id, '_um_search_fields', true );

	UM()->admin_forms( array(
		'class'		=> 'um-member-directory-search um-half-column',
		'prefix_id'	=> 'um_metadata',
		'fields' => array(
			array(
				'id'		=> '_um_search',
				'type'		=> 'checkbox',
				'name'		=> '_um_search',
				'label'		=> __( 'Enable Search feature', 'ultimate-member' ),
				'tooltip'	=> __( 'If turned on, users will be able to search members in this directory', 'ultimate-member' ),
				'value'		=> UM()->query()->get_meta_value( '_um_search' ),
			),
			array(
				'id'		=> '_um_must_search',
				'type'		=> 'checkbox',
				'name'		=> '_um_must_search',
				'label'		=> __( 'Show results only after search', 'ultimate-member' ),
				'tooltip'	=> __( 'If turned on, member results will only appear after search is performed', 'ultimate-member' ),
				'value'		=> UM()->query()->get_meta_value( '_um_must_search' ),
				'conditional'   => array( '_um_search', '=', 1 )
			),
			array(
				'id'		=> '_um_roles_can_search',
				'type'		=> 'select',
				'multi'		=> true,
				'name'		=> '_um_roles_can_search',
				'label'		=> __( 'User Roles that can use search', 'ultimate-member' ),
				'tooltip'	=> __( 'If you want to allow specific user roles to be able to search only', 'ultimate-member' ),
				'value'		=> $can_search_array,
				'options'	=> UM()->roles()->get_roles(),
				'conditional'   => array( '_um_search', '=', 1 )
			),
			array(
				'id'		=> '_um_search_fields',
				'type'		=> 'multi_selects',
				'name'		=> '_um_search_fields',
				'label'		=> __( 'Choose field(s) to enable in search', 'ultimate-member' ),
				'value'		=> $_um_search_fields,
				'conditional'   => array( '_um_search', '=', 1 ),
				'options'   => $user_fields,
				'add_text'		=> __( 'Add New Custom Field','ultimate-member' ),
				'show_default_number'	=> 1,
			),
			array(
				'id'		=> '_um_directory_header',
				'type'		=> 'text',
				'name'		=> '_um_directory_header',
				'label'		=> __( 'Results Text', 'ultimate-member' ),
				'tooltip'	=> __( 'Customize the search result text . e.g. Found 3,000 Members. Leave this blank to not show result text', 'ultimate-member' ),
				'value'		=> UM()->query()->get_meta_value('_um_directory_header', null, __('{total_users} Members','ultimate-member') ),
				'conditional'   => array( '_um_search', '=', 1 )
			),
			array(
				'id'		=> '_um_directory_header_single',
				'type'		=> 'text',
				'name'		=> '_um_directory_header_single',
				'label'		=> __( 'Single Result Text', 'ultimate-member' ),
				'tooltip'	=> __( 'Same as above but in case of 1 user found only', 'ultimate-member' ),
				'value'		=> UM()->query()->get_meta_value('_um_directory_header_single', null, __('{total_users} Member','ultimate-member') ),
				'conditional'   => array( '_um_search', '=', 1 )
			),
			array(
				'id'		=> '_um_directory_no_users',
				'type'		=> 'text',
				'name'		=> '_um_directory_no_users',
				'label'		=> __( 'Custom text if no users were found', 'ultimate-member' ),
				'tooltip'	=> __( 'This is the text that is displayed if no users are found during a search', 'ultimate-member' ),
				'value'		=> UM()->query()->get_meta_value('_um_directory_no_users', null, __('We are sorry. We cannot find any users who match your search criteria.','ultimate-member') ),
				'conditional'   => array( '_um_search', '=', 1 )
			)
		)
	) )->render_form(); ?>
	
	<div class="um-admin-clear"></div>
</div>