<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>


<div class="um-admin-metabox">
	<?php $role = $object['data'];

	UM()->admin_forms( array(
		'class'     => 'um-role-admin um-half-column',
		'prefix_id' => 'role',
		'fields'    => array(
			array(
				'id'        => '_um_can_access_wpadmin',
				'type'      => 'checkbox',
				'label'     => __( 'Can access wp-admin?', 'ultimate-member' ),
				'tooltip'   => __( 'The core admin role must always have access to wp-admin / WordPress backend', 'ultimate-member' ),
				'value'     => ! empty( $role['_um_can_access_wpadmin'] ) ? $role['_um_can_access_wpadmin'] : 0,
			),
			array(
				'id'        => '_um_can_not_see_adminbar',
				'type'      => 'checkbox',
				'label'     => __( 'Force hiding adminbar in frontend?', 'ultimate-member' ),
				'tooltip'   => __( 'Mark this option if you need to hide the adminbar on frontend for this role', 'ultimate-member' ),
				'value'     => isset( $role['_um_can_not_see_adminbar'] ) ? $role['_um_can_not_see_adminbar'] : 1,
			),
			array(
				'id'        => '_um_can_edit_everyone',
				'type'      => 'checkbox',
				'label'     => __( 'Can edit other member accounts?', 'ultimate-member' ),
				'tooltip'   => __( 'Allow this role to edit accounts of other members', 'ultimate-member' ),
				'value'     => ! empty( $role['_um_can_edit_everyone'] ) ? $role['_um_can_edit_everyone'] : 0,
			),
			array(
				'id'            => '_um_can_edit_roles',
				'type'          => 'select',
				'label'         => __( 'Can edit these user roles only', 'ultimate-member' ),
				'tooltip'       => __( 'Which roles that role can edit, choose none to allow role to edit all member roles', 'ultimate-member' ),
				'options'       => UM()->roles()->get_roles(),
				'multi'         => true,
				'value'         => ! empty( $role['_um_can_edit_roles'] ) ? $role['_um_can_edit_roles'] : array(),
				'conditional'   => array( '_um_can_edit_everyone', '=', '1' )
			),
			array(
				'id'        => '_um_can_delete_everyone',
				'type'      => 'checkbox',
				'label'     => __( 'Can delete other member accounts?', 'ultimate-member' ),
				'tooltip'   => __( 'Allow this role to edit accounts of other members', 'ultimate-member' ),
				'value'     => ! empty( $role['_um_can_delete_everyone'] ) ? $role['_um_can_delete_everyone'] : 0,
			),
			array(
				'id'            => '_um_can_delete_roles',
				'type'          => 'select',
				'label'         => __( 'Can delete these user roles only', 'ultimate-member' ),
				'tooltip'       => __( 'Which roles that role can edit, choose none to allow role to edit all member roles', 'ultimate-member' ),
				'options'       => UM()->roles()->get_roles(),
				'multi'         => true,
				'value'         => ! empty( $role['_um_can_delete_roles'] ) ? $role['_um_can_delete_roles'] : array(),
				'conditional'   => array( '_um_can_delete_everyone', '=', '1' )
			)
		)
	) )->render_form(); ?>
</div>