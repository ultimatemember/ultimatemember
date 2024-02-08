<?php
/**
 * Admin View: Restriction Rule Publish
 *
 * @param array $object
 *
 * @since 2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rule = $object['data']; ?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-restriction-rule-publish um-top-label',
			'prefix_id' => 'um_restriction_rules',
			'fields'    => array(
				array(
					'id'      => 'priority',
					'type'    => 'text',
					'label'   => __( 'Rule Priority', 'ultimate-member' ),
					'tooltip' => __( 'The higher the number, the higher the priority', 'ultimate-member' ),
					'value'   => ! empty( $rule['priority'] ) ? $rule['priority'] : '',
				),
				array(
					'id'      => 'status',
					'type'    => 'select',
					'options' => array(
						'active'   => __( 'Active', 'ultimate-member' ),
						'inactive' => __( 'Inactive', 'ultimate-member' ),
					),
					'label'   => __( 'Status', 'ultimate-member' ),
					'tooltip' => __( 'The higher the number, the higher the priority', 'ultimate-member' ),
					'value'   => ! empty( $rule['status'] ) ? $rule['status'] : 'active',
				),
			),
		)
	)->render_form();
	?>
</div>

<div class="submitbox" id="submitpost">
	<div id="major-publishing-actions">
		<input type="submit" value="<?php echo ! empty( $_GET['id'] ) ? esc_attr__( 'Update Rule', 'ultimate-member' ) : esc_attr__( 'Create Rule', 'ultimate-member' ); ?>" class="button-primary" id="create_restriction_rule" name="create_restriction_rule" />
		<input type="button" class="cancel_popup button" value="<?php esc_attr_e( 'Cancel', 'ultimate-member' ); ?>" onclick="window.location = '<?php echo esc_attr( add_query_arg( array( 'page' => 'um_restriction_rules' ), admin_url( 'admin.php' ) ) ); ?>';" />
		<div class="clear"></div>
	</div>
</div>
