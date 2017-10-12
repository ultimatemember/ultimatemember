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

	$user_fields = UM()->members()->get_filters_fields();

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
                'id'		=> '_um_filters',
                'type'		=> 'checkbox',
                'name'		=> '_um_filters',
                'label'		=> __( 'Enable Filters feature', 'ultimate-member' ),
                'tooltip'	=> __( 'If turned on, users will be able to filter members in this directory', 'ultimate-member' ),
                'value'		=> UM()->query()->get_meta_value( '_um_filters' ),
            ),
			array(
				'id'		=> '_um_search_fields',
				'type'		=> 'multi_selects',
				'name'		=> '_um_search_fields',
				'label'		=> __( 'Choose field(s) to enable in filter', 'ultimate-member' ),
				'value'		=> $_um_search_fields,
				'conditional'   => array( '_um_filters', '=', 1 ),
				'options'   => $user_fields,
				'add_text'		=> __( 'Add New Field','ultimate-member' ),
				'show_default_number'	=> 1,
			),
		)
	) )->render_form(); ?>
	
	<div class="um-admin-clear"></div>
</div>