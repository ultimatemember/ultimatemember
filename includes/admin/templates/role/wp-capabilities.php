<div class="um-admin-metabox">
    <?php
    $role = $object['data'];
    $role_capabilities = ! empty( $role['wp_capabilities'] ) ? array_keys( $role['wp_capabilities'] ) : array();

    if ( ! empty( $_GET['id'] ) ) {
        $role = get_role( $_GET['id'] );
    }

    $all_caps = array();
    foreach ( get_editable_roles() as $role_info ) {
        if ( ! empty( $role_info['capabilities'] ) )
            $all_caps = array_merge( $all_caps, $role_info['capabilities'] );
    }

    $fields = array();
    foreach ( array_keys( $all_caps ) as $cap ) {
        $fields[$cap] = $cap;
    }

    UM()->admin_forms( array(
        'class'		=> 'um-role-wp-capabilities',
        'prefix_id'	=> 'role',
        'fields'    => array(
            array(
                'id'       		=> 'wp_capabilities',
                'type'     		=> 'multi_checkbox',
                'name'          => 'wp_capabilities',
                'options'       => $fields,
                'value'         => ! empty( $role_capabilities ) ? $role_capabilities : array(),
                'columns'	    => 3,
                'without_label'	=> true,
            )
        )
    ) )->render_form(); ?>
</div>