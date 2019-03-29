<?php if ( ! defined( 'ABSPATH' ) ) exit;


$forms_query = new WP_Query;
$profile_forms = $forms_query->query( array(
	'post_type'         => 'um_form',
	'meta_query'        => array(
		array(
			'key'   => '_um_mode',
			'value' => 'profile'
		),
	),
	'posts_per_page'    => -1,
	'fields'            => 'ids'
) );

foreach ( $profile_forms as $form_id ) {
	$profile_forms_fields = get_post_meta( $form_id, '_um_custom_fields', true );

	foreach ( $profile_forms_fields as $key => $field ) {

		if ( isset( $field['metakey'] ) ) {
			$metakey = $field['metakey'];
			if ( $key != $metakey ) {
				$profile_forms_fields[ $metakey ] = $profile_forms_fields[ $key ];
				unset( $profile_forms_fields[ $key ] );
			}
		}

	}

	update_post_meta( $form_id, '_um_custom_fields', $profile_forms_fields );
}