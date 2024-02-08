<?php
/**
 * Admin View: Restriction Rule General
 *
 * @param array $object
 *
 * @since 2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>


<div class="um-admin-metabox">
	<?php
	$rule = $object['data'];

//	UM()->admin_forms(
//		array(
//			'class'     => 'um-restriction-rule-admin um-half-column',
//			'prefix_id' => 'um_restriction_rules',
//			'fields'    => array(
//				array(
//					'id'    => '_um_description',
//					'type'  => 'textarea',
//					'label' => __( 'Rule Description', 'ultimate-member' ),
//					'value' => ! empty( $rule['_um_description'] ) ? $rule['_um_description'] : '',
//				),
//			),
//		)
//	)->render_form();
	?>
</div>
