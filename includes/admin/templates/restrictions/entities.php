<?php
/**
 * Admin View: Restriction Rule Entities
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
	$include = $object['include'];
	$exclude = $object['exclude'];

	UM()->admin_forms(
		array(
			'class'     => 'um-restriction-rule-include um-top-label',
			'prefix_id' => 'um_restriction_rules_include',
			'fields'    => array(
				array(
					'id'    => '_um_include',
					'type'  => 'entities_conditions',
					'label' => __( 'Include', 'ultimate-member' ),
					'value' => ! empty( $include ) ? $include : '',
				),
			),
		)
	)->render_form();

	UM()->admin_forms(
		array(
			'class'     => 'um-restriction-rule-exclude um-top-label',
			'prefix_id' => 'um_restriction_rules_exclude',
			'fields'    => array(
				array(
					'id'    => '_um_exclude',
					'type'  => 'entities_conditions',
					'label' => __( 'Exclude', 'ultimate-member' ),
					'value' => ! empty( $exclude ) ? $exclude : '',
				),
			),
		)
	)->render_form();
	?>
</div>
