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
	$rule = $object['data'];

		UM()->admin_forms(
			array(
				'class'     => 'um-restriction-rule-include um-half-column',
				'prefix_id' => 'um_restriction_rules_include',
				'fields'    => array(
					array(
						'id'    => '_um_include',
						'type'  => 'entities_conditions',
						'label' => __( 'Include', 'ultimate-member' ),
						'value' => ! empty( $rule['include'] ) ? $rule['include'] : '',
						'scope' => array(
							'post' => __( 'Post', 'ultimate-member' ),
							'page' => __( 'Page', 'ultimate-member' ),
						),
					),
				),
			)
		)->render_form();
		?>
</div>
