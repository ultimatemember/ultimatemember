<?php
/**
 * Admin View: Restriction Rule Action
 *
 * @param array $object
 *
 * @since 2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = $object['action'];
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-restriction-rule-action um-half-column',
			'prefix_id' => 'um_restriction_rules_action',
			'fields'    => array(
				array(
					'id'          => '_um_action',
					'type'        => 'select',
					'label'       => __( 'What happens when users without access try to view the post?', 'ultimate-member' ),
					'description' => __( 'Action when users without access tries to view the post', 'ultimate-member' ),
					'value'       => ! empty( $data['_um_action'] ) ? $data['_um_action'] : 0,
					'options'     => array(
						0 => __( 'Display 404', 'ultimate-member' ),
						1 => __( 'Show access restricted message', 'ultimate-member' ),
						2 => __( 'Redirect user', 'ultimate-member' ),
					),
				),
				array(
					'id'          => '_um_message_type',
					'type'        => 'select',
					'label'       => __( 'Restricted access message type', 'ultimate-member' ),
					'description' => __( 'Would you like to use the global default message or apply a custom message?', 'ultimate-member' ),
					'value'       => ! empty( $data['_um_message_type'] ) ? $data['_um_message_type'] : '0',
					'options'     => array(
						0 => __( 'Global default content message', 'ultimate-member' ),
						1 => __( 'Custom content message', 'ultimate-member' ),
					),
					'conditional' => array( '_um_action', '=', '1' ),
				),
				array(
					'id'          => '_um_custom_message',
					'type'        => 'wp_editor',
					'label'       => __( 'Custom content message', 'ultimate-member' ),
					'description' => __( 'You may replace global restricted content message here', 'ultimate-member' ),
					'value'       => ! empty( $data['_um_custom_message'] ) ? $data['_um_custom_message'] : '',
					'conditional' => array( '_um_message_type', '=', '1' ),
				),
				array(
					'id'          => '_um_redirect',
					'type'        => 'select',
					'label'       => __( 'Where should users be redirected to?', 'ultimate-member' ),
					'description' => __( 'Select redirect to page when user hasn\'t access to post', 'ultimate-member' ),
					'value'       => ! empty( $data['_um_redirect'] ) ? $data['_um_redirect'] : '0',
					'options'     => array(
						0 => __( 'Login page', 'ultimate-member' ),
						1 => __( 'Custom URL', 'ultimate-member' ),
					),
					'conditional' => array( '_um_action', '=', '2' ),
				),
				array(
					'id'          => '_um_redirect_url',
					'type'        => 'text',
					'label'       => __( 'Redirect URL', 'ultimate-member' ),
					'description' => __( 'Set full URL where do you want to redirect the user', 'ultimate-member' ),
					'value'       => ! empty( $data['_um_redirect_url'] ) ? $data['_um_redirect_url'] : '',
					'conditional' => array( '_um_redirect', '=', '1' ),
				),
			),
		)
	)->render_form();
	?>
</div>
