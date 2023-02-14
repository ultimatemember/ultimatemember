<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$role = $object['data'];
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin()->forms(
		array(
			'class'     => 'um-role-jobboardwp um-half-column',
			'prefix_id' => 'role',
			'fields'    => array(
				array(
					'id'          => '_um_disable_jobs_tab',
					'type'        => 'checkbox',
					'label'       => __( 'Disable jobs tab?', 'ultimate-member' ),
					'description' => __( 'If you turn this off, this role will not have a jobs tab active in their profile.', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_disable_jobs_tab'] ) ? $role['_um_disable_jobs_tab'] : 0,
				),
			),
		)
	)->render_form();
	?>
</div>
