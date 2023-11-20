<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_fields = array();
foreach ( UM()->builtin()->all_user_fields() as $key => $arr ) {
	$user_fields[ $key ] = isset( $arr['title'] ) ? $arr['title'] : '';
}

$post_id = get_the_ID();
$_um_search_fields = get_post_meta( $post_id, '_um_profile_metafields', true );
$_um_search_fields = empty( $_um_search_fields ) ? array() : $_um_search_fields; ?>


<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-form-profile-settings um-top-label',
			'prefix_id' => 'form',
			'fields'    => array(
				array(
					'id'                  => '_um_profile_metafields',
					'type'                => 'multi_selects',
					'label'               => __( 'Field(s) to show in user meta', 'ultimate-member' ),
					'tooltip'             => __( 'Fields selected here will appear in the profile header area below the user\'s display name', 'ultimate-member' ),
					'value'               => $_um_search_fields,
					'options'             => $user_fields,
					'add_text'            => __( 'Add New Field', 'ultimate-member' ),
					'show_default_number' => 0,
				),
			),
		)
	)->render_form();
	?>
	<div class="clear"></div>
</div>
