<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>


<div class="um-admin-metabox">

	<script type="text/javascript">
		jQuery(document).ready( function() {
			jQuery('#um_wp_capabilities_select_all').click( function() {
				if ( jQuery(this).is(':checked') ) {
					jQuery('.um-role-wp-capabilities').find('input[type="checkbox"]').prop( 'checked', true );
				} else {
					jQuery('.um-role-wp-capabilities').find('input[type="checkbox"]').prop( 'checked', false );
				}

				um_change_check_all_label( jQuery(this) );
			});

			jQuery('.um-role-wp-capabilities input[type="checkbox"]').click( function() {
				um_check_all_trigger();
			});

			um_check_all_trigger();
		});

		function um_check_all_trigger() {
			var checkbox = jQuery('#um_wp_capabilities_select_all');

			if ( jQuery('.um-role-wp-capabilities input[type="checkbox"]:checked').length == jQuery('.um-role-wp-capabilities input[type="checkbox"]').length ) {
				checkbox.prop( 'checked', true );
			} else {
				checkbox.prop( 'checked', false );
			}

			um_change_check_all_label( checkbox );
		}

		function um_change_check_all_label( $checkbox ) {
			if ( $checkbox.is(':checked') ) {
				jQuery('#um_wp_capabilities_select_all_label').html( '<?php _e( 'Uncheck All', 'ultimate-member' ) ?>' );
			} else {
				jQuery('#um_wp_capabilities_select_all_label').html( '<?php _e( 'Check All', 'ultimate-member' ) ?>' );
			}
		}
	</script>

	<span style="padding: 10px 0 0 10px; float:left;">
		<label style="float:left;">
			<input type="checkbox" id="um_wp_capabilities_select_all" />
			<span id="um_wp_capabilities_select_all_label"><?php _e( 'Check All', 'ultimate-member' ) ?></span>
		</label>
	</span>


	<?php
	$role              = $object['data'];
	$role_capabilities = ! empty( $role['wp_capabilities'] ) ? array_keys( $role['wp_capabilities'] ) : array( 'read' );

	if ( ! empty( $_GET['id'] ) ) {
		$role = get_role( sanitize_key( $_GET['id'] ) );
	}

	$all_caps = array();
	foreach ( get_editable_roles() as $role_info ) {
		if ( ! empty( $role_info['capabilities'] ) ) {
			$all_caps = array_merge( $all_caps, $role_info['capabilities'] );
		}
	}

	//gravity forms compatibility filter
	$all_caps = apply_filters( 'members_get_capabilities', array_keys( $all_caps ) );
	$fields = array();
	foreach ( $all_caps as $cap ) {
		if ( is_numeric( $cap ) ) {
			continue;
		}
		$fields[ $cap ] = $cap;
	}

	UM()->admin_forms( array(
		'class'     => 'um-role-wp-capabilities',
		'prefix_id' => 'role',
		'fields'    => array(
			array(
				'id'            => 'wp_capabilities',
				'type'          => 'multi_checkbox',
				'options'       => $fields,
				'value'         => ! empty( $role_capabilities ) ? $role_capabilities : array(),
				'columns'       => 3,
				'without_label' => true,
			)
		)
	) )->render_form(); ?>
</div>
