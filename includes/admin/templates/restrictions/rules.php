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
	$rules = $object['rules'];

	UM()->admin_forms(
		array(
			'class'     => 'um-restriction-rule-users um-top-label',
			'prefix_id' => 'um_restriction_rules_users',
			'fields'    => array(
				array(
					'id'      => '_um_authentification',
					'type'    => 'select',
					'value'   => ! empty( $rules['_um_authentification'] ) ? $rules['_um_authentification'] : 'loggedin',
					'default' => 'loggedin',
					'options' => array(
						'loggedin'  => __( 'Logged in users', 'ultimate-member' ),
						'loggedout' => __( 'Logged out users', 'ultimate-member' ),
					),
				),
				array(
					'id'          => '_um_users',
					'type'        => 'users_conditions',
					'conditional' => array( '_um_authentification', '!=', 'loggedout' ),
					'label'       => __( 'Rules', 'ultimate-member' ),
					'value'       => ! empty( $rules ) ? $rules : array(),
				),
			),
		)
	)->render_form();
	?>
</div>
