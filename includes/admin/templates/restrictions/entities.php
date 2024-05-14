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
			'class'     => 'um-restriction-rule-content um-top-label',
			'prefix_id' => 'um_restriction_rule_content',
			'fields'    => array(
				array(
					'id'    => '_um_include',
					'class' => 'um-protection-include',
					'type'  => 'entities_conditions',
					'label' => __( 'Include', 'ultimate-member' ),
					'value' => ! empty( $include ) ? $include : '',
				),
				array(
					'id'          => '_um_exclude',
					'class'       => 'um-protection-exclude',
					'type'        => 'entities_conditions',
					'label'       => __( 'Exclude', 'ultimate-member' ),
					'value'       => ! empty( $exclude ) ? $exclude : '',
				),
				array(
					'id'      => 'add_protection_rule',
					'type'    => 'buttons_group',
					'class'   => 'um-protection-buttons',
					'buttons' => array(
						'um_add_protection_rule' => __( 'Add protection rule', 'ultimate-member' ),
						'um_add_exclusion_rule'  => __( 'Add exclusion rule', 'ultimate-member' ),
					),
				),
			),
		)
	)->render_form();
	?>
</div>
