<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="um-admin-metabox">
	<?php
	$role = $object['data'];

	UM()->admin_forms(
		array(
			'class'     => 'um-role-forumwp um-half-column',
			'prefix_id' => 'role',
			'fields'    => array(
				array(
					'id'          => '_um_disable_forumwp_tab',
					'type'        => 'checkbox',
					'label'       => __( 'Disable forums tab?', 'ultimate-member' ),
					'description' => __( 'If you turn this off, this role will not have a forums tab active in their profile.', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_disable_forumwp_tab'] ) ? $role['_um_disable_forumwp_tab'] : 0,
				),
				array(
					'id'          => '_um_disable_create_forumwp_topics',
					'type'        => 'checkbox',
					'label'       => __( 'Disable create new topics?', 'ultimate-member' ),
					'description' => __( 'Generally, decide If this role can create new topics in the forums or not.', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_disable_create_forumwp_topics'] ) ? $role['_um_disable_create_forumwp_topics'] : 0,
				),
				array(
					'id'          => '_um_lock_create_forumwp_topics_notice',
					'type'        => 'textarea',
					'label'       => __( 'Custom message to show if you force locking new topic', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_lock_create_forumwp_topics_notice'] ) ? $role['_um_lock_create_forumwp_topics_notice'] : '',
					'conditional' => array( '_um_disable_create_forumwp_topics', '=', '1' ),
				),
				array(
					'id'          => '_um_disable_create_forumwp_replies',
					'type'        => 'checkbox',
					'label'       => __( 'Disable create new replies?', 'ultimate-member' ),
					'description' => __( 'Generally, decide If this role can create new replies in the forums or not.', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_disable_create_forumwp_replies'] ) ? $role['_um_disable_create_forumwp_replies'] : 0,
				),
				array(
					'id'          => '_um_lock_create_forumwp_replies_notice',
					'type'        => 'textarea',
					'label'       => __( 'Custom message to show if you force locking new reply', 'ultimate-member' ),
					'value'       => ! empty( $role['_um_lock_create_forumwp_replies_notice'] ) ? $role['_um_lock_create_forumwp_replies_notice'] : '',
					'conditional' => array( '_um_disable_create_forumwp_replies', '=', '1' ),
				),
			)
		)
	)->render_form();
	?>
</div>
