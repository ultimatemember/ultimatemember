<?php if ( ! defined( 'ABSPATH' ) ) exit;

// Get default and real arguments
$def_args = array();
foreach ( UM()->config()->core_directory_meta['members'] as $k => $v ) {
	$key = str_replace( '_um_', '', $k );
	$def_args[ $key ] = $v;
}

$unique_hash = substr( md5( $args['form_id'] ), 10, 5 );

$args = array_merge( $def_args, $args );


//current user priority role
$priority_user_role = false;
if ( is_user_logged_in() ) {
	$priority_user_role = UM()->roles()->get_priority_user_role( um_user( 'ID' ) );
}


// Views
$single_view = false;
$current_view = 'grid';

if ( ! empty( $args['view_types'] ) && is_array( $args['view_types'] ) ) {
	$args['view_types'] = array_filter( $args['view_types'], function( $item ) {
		return in_array( $item, array_keys( UM()->member_directory()->view_types ) );
	});
}

if ( empty( $args['view_types'] ) || ! is_array( $args['view_types'] ) ) {
	$args['view_types'] = array(
		'grid',
		'list'
	);
}

if ( count( $args['view_types'] ) == 1 ) {
	$single_view = true;
	$current_view = $args['view_types'][0];
} else {
	$args['default_view'] = ! empty( $args['default_view'] ) ? $args['default_view'] : $args['view_types'][0];
	$current_view = ( ! empty( $_GET[ 'view_type_' . $unique_hash ] ) && in_array( $_GET[ 'view_type_' . $unique_hash ], $args['view_types'] ) ) ? $_GET[ 'view_type_' . $unique_hash ] : $args['default_view'];
}

// Sorting
$default_sorting = ! empty( $args['sortby'] ) ? $args['sortby'] : 'user_registered_desc';

$sort_from_url = '';
if ( $args['enable_sorting'] ) {
	$sorting_options = empty( $args['sorting_fields'] ) ? array() : $args['sorting_fields'];
	if ( ! in_array( $default_sorting, $sorting_options ) ) {
		$sorting_options[] = $default_sorting;
	}

	if ( ! empty( $sorting_options ) ) {
		$all_sorting_options = UM()->member_directory()->sort_fields;
		$sorting_options = array_intersect_key( $all_sorting_options, array_flip( $sorting_options ) );
	}

	$sort_from_url = ( ! empty( $_GET[ 'sort_' . $unique_hash ] ) && in_array( $_GET[ 'sort_' . $unique_hash ], array_keys( $sorting_options ) ) ) ? $_GET[ 'sort_' . $unique_hash ] : $default_sorting;
}

$current_page = ( ! empty( $_GET[ 'page_' . $unique_hash ] ) && is_numeric( $_GET[ 'page_' . $unique_hash ] ) ) ? (int) $_GET[ 'page_' . $unique_hash ] : 1;

//Search
$search = isset( $args['search'] ) ? $args['search'] : false;
$show_search = empty( $args['roles_can_search'] ) || ( ! empty( $priority_user_role ) && in_array( $priority_user_role, $args['roles_can_search'] ) );
$search_from_url = '';
if ( $search && $show_search ) {
	$search_from_url = ! empty( $_GET[ 'search_' . $unique_hash ] ) ? $_GET[ 'search_' . $unique_hash ] : '';
}


//Filters
$filters = isset( $args['filters'] ) ? $args['filters'] : false;
$show_filters = empty( $args['roles_can_filter'] ) || ( ! empty( $priority_user_role ) && in_array( $priority_user_role, $args['roles_can_filter'] ) );
$search_filters = array();
if ( isset( $args['search_fields'] ) ) {
	$search_filters = apply_filters( 'um_frontend_member_search_filters', array_unique( array_filter( $args['search_fields'] ) ) );
}

if ( ! empty( $search_filters ) ) {
	$search_filters = array_filter( $search_filters, function( $item ) {
		return in_array( $item, array_keys( UM()->member_directory()->filter_fields ) );
	});

	$search_filters = array_values( $search_filters );
}

// Classes
$classes = '';
if ( $search && $show_search ) {
	$classes .= ' um-member-with-search';
}

if ( $filters && $show_filters && count( $search_filters ) ) {
	$classes .= ' um-member-with-filters';
}

if ( ! $single_view ) {
	$classes .= ' um-member-with-view';
}

if ( $args['enable_sorting'] && ! empty( $sorting_options ) && count( $sorting_options ) > 1 ) {
	$classes .= ' um-member-with-sorting';
}

//send $args variable to the templates
$args['args'] = $args;
foreach ( $args['view_types'] as $type ) {
	$basename = UM()->member_directory()->get_type_basename( $type );
	UM()->get_template( 'members-' . $type . '.php', $basename, $args, true );
}
UM()->get_template( 'members-pagination.php', '', $args, true ); ?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( substr( md5( $form_id ), 10, 5 ) ); ?>"
     data-hash="<?php echo esc_attr( substr( md5( $form_id ), 10, 5 ) ) ?>"
     data-view_type="<?php echo esc_attr( $current_view ) ?>" data-page="<?php echo esc_attr( $current_page ) ?>">

	<div class="um-form">

		<div class="um-member-directory-header <?php echo esc_attr( $classes ) ?>">
			<?php if ( $search && $show_search ) { ?>
				<div class="um-member-directory-search-line">
					<input type="text" class="um-search-line" placeholder="<?php esc_attr_e( 'Search', 'ultimate-member' ) ?>"  value="<?php echo esc_attr( $search_from_url ) ?>" />
					<div class="uimob340-show uimob500-show">
						<a href="javascript:void(0);" class="um-button um-do-search um-tip-n" title="<?php esc_attr_e( 'Search', 'ultimate-member' ); ?>">
							<i class="um-faicon-search"></i>
						</a>
					</div>
					<div class="uimob340-hide uimob500-hide">
						<a href="javascript:void(0);" class="um-button um-do-search"><?php _e( 'Search', 'ultimate-member' ); ?></a>
					</div>
				</div>
			<?php }

			if ( $args['enable_sorting'] && ! empty( $sorting_options ) && count( $sorting_options ) > 1 ) { ?>
				<div class="um-member-directory-sorting">
					<select class="um-s3 um-member-directory-sorting-options" id="um-member-directory-sorting-select-<?php echo esc_attr( $form_id ) ?>" data-placeholder="<?php esc_attr_e( 'Sort By', 'ultimate-member' ); ?>">
						<?php foreach ( $sorting_options as $value => $title ) { ?>
							<option value="<?php echo $value ?>" <?php selected( $sort_from_url, $value ) ?>><?php echo $title ?></option>
						<?php } ?>
					</select>
				</div>
			<?php } ?>

			<div class="um-member-directory-actions">
				<?php if ( $filters && $show_filters && count( $search_filters ) ) { ?>
					<div class="um-member-directory-filters">
						<a href="javascript:void(0);" class="um-member-directory-filters-a um-tip-n" title="<?php esc_attr_e( 'Filters', 'ultimate-member' ); ?>">
							<i class="um-faicon-sliders"></i>
						</a>
					</div>
				<?php } ?>

				<?php if ( ! $single_view ) {
					$view_types = 0;

					foreach ( UM()->member_directory()->view_types as $key => $value ) {
						if ( in_array( $key, $args['view_types'] ) ) {
							if ( empty( $view_types ) ) { ?>
								<div class="um-member-directory-view-type">
							<?php }

							$view_types++; ?>

							<a href="javascript:void(0)"
							   class="um-member-directory-view-type-a um-tip-n"
							   data-type="<?php echo $key; ?>"
							   title="<?php printf( esc_attr__( 'Change to %s', 'ultimate-member' ), $value['title'] ) ?>"
							   default-title="<?php echo esc_attr( $value['title'] ); ?>"
							   next-item="" ><i class="<?php echo $value['icon']; ?>"></i></a>
						<?php }
					}

					if ( ! empty( $view_types ) ) { ?>
						</div>
					<?php }
				} ?>
			</div>
		</div>
		<div class="um-clear"></div>

		<?php if ( $filters && $show_filters && count( $search_filters ) ) {

			if ( is_array( $search_filters ) ) { ?>
				<script type="text/template" id="tmpl-um-members-filtered-line">
					<# if ( data.filters.length > 0 ) { #>
						<# _.each( data.filters, function( filter, key, list ) { #>
							<div class="um-members-filter-tag">
								<# if ( filter.type == 'slider' ) { #>
									{{{filter.value_label}}}
								<# } else { #>
									<strong>{{{filter.label}}}</strong>: {{{filter.value_label}}}
								<# } #>
								<div class="um-members-filter-remove" data-name="{{{filter.name}}}" data-value="{{{filter.value}}}" data-range="{{{filter.range}}}" data-type="{{{filter.type}}}">&times;</div>
							</div>
						<# }); #>
					<# } #>
				</script>

				<div class="um-search um-search-<?php echo count( $search_filters ) ?>">
					<?php $i = 0;
					foreach ( $search_filters as $filter ) {
						$filter_content = UM()->member_directory()->show_filter( $filter );
						if ( empty( $filter_content ) ) {
							continue;
						} ?>

						<div class="um-search-filter <?php echo ( $i != 0 && $i%2 !== 0 ) ? 'um-search-filter-2' : '' ?>"> <?php echo $filter_content; ?> </div>

						<?php $i++;
					} ?>

					<div class="um-clear"></div>
				</div>

				<div class="um-filtered-line">
					<div class="um-clear-filters"><a href="javascript:void(0);" class="um-clear-filters-a"><?php esc_attr_e( 'Clear All Filters', 'ultimate-member' ); ?></a></div>
				</div>
				<?php
			}
		}
		do_action( 'um_members_directory_head', $args );
		?>

		<div class="um-members-wrapper">
			<div class="um-members-overlay"><div class="um-ajax-loading"></div></div>
		</div>
		<div class="um-clear"></div>

		<div class="um-members-pagination-box"></div>

		<?php
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_members_directory_footer
		 * @description Member directory display footer
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Member directory shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_members_directory_footer', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_members_directory_footer', 'my_members_directory_footer', 10, 1 );
		 * function my_members_directory_footer( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_members_directory_footer', $args ); ?>

		<div class="um-clear"></div>
	</div>
</div>