<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>


<div class="um-admin-metabox">
	<?php $role = $object['data'];

	UM()->admin_forms( array(
		'class'     => 'um-role-profile um-half-column',
		'prefix_id' => 'role',
		'fields'    => array(
			array(
				'id'        => '_um_can_view_all',
				'type'      => 'checkbox',
				'label'     => __( 'Can view other member profiles?', 'ultimate-member' ),
				'tooltip'   => __( 'Can this role view all member profiles?', 'ultimate-member' ),
				'value'     => ! empty( $role['_um_can_view_all'] ) ? $role['_um_can_view_all'] : 0,
			),
			array(
				'id'            => '_um_can_view_roles',
				'type'          => 'select',
				'label'         => __( 'Can view these user roles only', 'ultimate-member' ),
				'tooltip'       => __( 'Which roles that role can view, choose none to allow role to view all member roles', 'ultimate-member' ),
				'options'       => UM()->roles()->get_roles(),
				'multi'         => true,
				'value'         => ! empty( $role['_um_can_view_roles'] ) ? $role['_um_can_view_roles'] : array(),
				'conditional'   => array( '_um_can_view_all', '=', '1' )
			),
			array(
				'id'        => '_um_can_make_private_profile',
				'type'      => 'checkbox',
				'name'      => '_um_can_make_private_profile',
				'label'     => __( 'Can make their profile private?', 'ultimate-member' ),
				'tooltip'   => __( 'Can this role make their profile private?', 'ultimate-member' ),
				'value'     => ! empty( $role['_um_can_make_private_profile'] ) ? $role['_um_can_make_private_profile'] : 0,
			),
			array(
				'id'        => '_um_can_access_private_profile',
				'type'      => 'checkbox',
				'name'      => '_um_can_access_private_profile',
				'label'     => __( 'Can view/access private profiles?', 'ultimate-member' ),
				'tooltip'   => __( 'Can this role view private profiles?', 'ultimate-member' ),
				'value'     => ! empty( $role['_um_can_access_private_profile'] ) ? $role['_um_can_access_private_profile'] : 0,
			),
			array(
				'id'        => '_um_profile_noindex',
				'type'      => 'select',
				'size'      => 'medium',
				'name'      => '_um_profile_noindex',
				'label'     => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
				'tooltip'   => __( 'Hides the profile page for robots. The default value depends on the General > Users setting.', 'ultimate-member' ),
				'options'   => [
					''  => __( 'Default', 'ultimate-member' ),
					'0' => __( 'No', 'ultimate-member' ),
					'1' => __( 'Yes', 'ultimate-member' ),
				],
				'value'     => array_key_exists( '_um_profile_noindex', $role ) ? $role['_um_profile_noindex'] : '',
			)
		)
	) )->render_form(); ?>

</div>