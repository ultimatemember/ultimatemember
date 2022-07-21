<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$permissions['_um_forumwp_can_topic'] = get_post_meta( $object->ID, '_um_forumwp_can_topic', true );
$permissions['_um_forumwp_can_reply'] = get_post_meta( $object->ID, '_um_forumwp_can_reply', true );
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-forumwp-access um-half-column',
			'prefix_id' => '',
			'fields'    => array(
				array(
					'id'      => '_um_forumwp_can_topic',
					'type'    => 'select',
					'multi'   => true,
					'label'   => __( 'Which roles can create new topics in this forum', 'ultimate-member' ),
					'value'   => ! empty( $permissions['_um_forumwp_can_topic'] ) ? $permissions['_um_forumwp_can_topic'] : array(),
					'options' => UM()->roles()->get_roles( false, array( 'administrator' ) ),
					'size'    => 'long',
				),
				array(
					'id'      => '_um_forumwp_can_reply',
					'type'    => 'select',
					'multi'   => true,
					'label'   => __( 'Which roles can create new replies in this forum', 'ultimate-member' ),
					'value'   => ! empty( $permissions['_um_forumwp_can_reply'] ) ? $permissions['_um_forumwp_can_reply'] : array(),
					'options' => UM()->roles()->get_roles( false, array( 'administrator' ) ),
					'size'    => 'long',
				),
			),
		)
	)->render_form();
	?>
</div>
