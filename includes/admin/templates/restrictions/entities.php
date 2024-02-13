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
			'class'     => 'um-restriction-rule-include um-half-column',
			'prefix_id' => 'um_restriction_rules_include',
			'fields'    => array(
				array(
					'id'    => '_um_include',
					'type'  => 'entities_conditions',
					'label' => __( 'Include', 'ultimate-member' ),
					'value' => ! empty( $include ) ? $include : '',
					'scope' => array(
						'site' => __( 'Entire website', 'ultimate-member' ),
						'post' => __( 'Post', 'ultimate-member' ),
						'page' => __( 'Page', 'ultimate-member' ),
					),
				),
			),
		)
	)->render_form();

	UM()->admin_forms(
		array(
			'class'     => 'um-restriction-rule-exclude um-half-column',
			'prefix_id' => 'um_restriction_rules_exclude',
			'fields'    => array(
				array(
					'id'    => '_um_exclude',
					'type'  => 'entities_conditions',
					'label' => __( 'Exclude', 'ultimate-member' ),
					'value' => ! empty( $exclude ) ? $exclude : '',
					'scope' => array(
						'site' => __( 'Entire website', 'ultimate-member' ),
						'post' => __( 'Post', 'ultimate-member' ),
						'page' => __( 'Page', 'ultimate-member' ),
					),
				),
			),
		)
	)->render_form();
	?>
</div>
