<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id;

$_um_roles_search_value = get_post_meta( $post_id, '_um_roles_can_search', true );
$_um_roles_search_value = empty( $_um_roles_search_value ) ? array() : $_um_roles_search_value;

$_um_roles_filter_value = get_post_meta( $post_id, '_um_roles_can_filter', true );
$_um_roles_filter_value = empty( $_um_roles_filter_value ) ? array() : $_um_roles_filter_value;

$_um_search_exclude_fields = get_post_meta( $post_id, '_um_search_exclude_fields', true );
$_um_search_include_fields = get_post_meta( $post_id, '_um_search_include_fields', true );
$_um_search_fields         = get_post_meta( $post_id, '_um_search_fields', true );
$_um_search_filters        = get_post_meta( $post_id, '_um_search_filters', true ); ?>


<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-member-directory-search um-half-column',
			'prefix_id' => 'um_metadata',
			'fields'    => array(
				array(
					'id'      => '_um_search',
					'type'    => 'checkbox',
					'label'   => __( 'Enable Search feature', 'ultimate-member' ),
					'tooltip' => __( 'If turned on, users will be able to search members in this directory', 'ultimate-member' ),
					'value'   => (bool) get_post_meta( $post_id, '_um_search', true ),
				),
				array(
					'id'          => '_um_roles_can_search',
					'type'        => 'multi_checkbox',
					'label'       => __( 'User Roles that can use search', 'ultimate-member' ),
					'tooltip'     => __( 'If you want to allow specific user roles to be able to search only', 'ultimate-member' ),
					'value'       => $_um_roles_search_value,
					'options'     => UM()->roles()->get_roles(),
					'columns'     => 3,
					'conditional' => array( '_um_search', '=', 1 ),
				),
				array(
					'id'                  => '_um_search_exclude_fields',
					'type'                => 'multi_selects',
					'label'               => __( 'Exclude fields from search', 'ultimate-member' ),
					'value'               => $_um_search_exclude_fields,
					'conditional'         => array( '_um_search', '=', 1 ),
					'options'             => UM()->member_directory()->searching_fields,
					'add_text'            => __( 'Add New', 'ultimate-member' ),
					'show_default_number' => 0,
					'sorting'             => true,
					'tooltip'             => __( 'Choose fields to exclude them from search. This option will delete all included fields.', 'ultimate-member' ),
				),
				array(
					'id'                  => '_um_search_include_fields',
					'type'                => 'multi_selects',
					'label'               => __( 'Fields to search by', 'ultimate-member' ),
					'value'               => $_um_search_include_fields,
					'conditional'         => array( '_um_search', '=', 1 ),
					'options'             => UM()->member_directory()->searching_fields,
					'add_text'            => __( 'Add New', 'ultimate-member' ),
					'show_default_number' => 0,
					'sorting'             => true,
					'tooltip'             => __( 'Choose fields to only include them in the search. This option will delete all excluded fields.', 'ultimate-member' ),
				),
				array(
					'id'      => '_um_filters',
					'type'    => 'checkbox',
					'label'   => __( 'Enable Filters feature', 'ultimate-member' ),
					'tooltip' => __( 'If turned on, users will be able to filter members in this directory', 'ultimate-member' ),
					'value'   => (bool) get_post_meta( $post_id, '_um_filters', true ),
				),
				array(
					'id'          => '_um_roles_can_filter',
					'type'        => 'multi_checkbox',
					'label'       => __( 'User Roles that can use filters', 'ultimate-member' ),
					'tooltip'     => __( 'If you want to allow specific user roles to be able to filter only', 'ultimate-member' ),
					'value'       => $_um_roles_filter_value,
					'options'     => UM()->roles()->get_roles(),
					'columns'     => 3,
					'conditional' => array( '_um_filters', '=', 1 ),
				),
				array(
					'id'                  => '_um_search_fields',
					'type'                => 'multi_selects',
					'label'               => __( 'Choose filter(s) meta to enable', 'ultimate-member' ),
					'value'               => $_um_search_fields,
					'conditional'         => array( '_um_filters', '=', 1 ),
					'options'             => UM()->member_directory()->filter_fields,
					'add_text'            => __( 'Add New Custom Field', 'ultimate-member' ),
					'show_default_number' => 0,
					'sorting'             => true,
				),
				array(
					'id'          => '_um_filters_expanded',
					'type'        => 'checkbox',
					'label'       => __( 'Expand the filter bar by default', 'ultimate-member' ),
					'tooltip'     => __( 'If turned on, filters bar will be visible after a page loading', 'ultimate-member' ),
					'value'       => (bool) get_post_meta( $post_id, '_um_filters_expanded', true ),
					'conditional' => array( '_um_filters', '=', 1 ),
				),
				array(
					'id'          => '_um_filters_is_collapsible',
					'type'        => 'checkbox',
					'label'       => __( 'Can filter bar be collapsed', 'ultimate-member' ),
					'tooltip'     => __( 'If turned on, filters bar can be collapsed after a page loading', 'ultimate-member' ),
					'value'       => (bool) get_post_meta( $post_id, '_um_filters_is_collapsible', true ),
					'conditional' => array( '_um_filters_expanded', '=', 1 ),
				),
				array(
					'id'                  => '_um_search_filters',
					'type'                => 'md_default_filters',
					'label'               => __( 'Admin filtering', 'ultimate-member' ),
					'tooltip'             => __( 'Limit which users appear in the member directory e.g only display users from USA', 'ultimate-member' ),
					'value'               => $_um_search_filters,
					'options'             => UM()->member_directory()->filter_fields,
					'add_text'            => __( 'Add New Filter', 'ultimate-member' ),
					'show_default_number' => 0,
				),
			),
		)
	)->render_form();
	?>
	<div class="clear"></div>
</div>
