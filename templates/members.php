<?php if ( ! defined( 'ABSPATH' ) ) exit;

global $post;

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

$args = apply_filters( 'um_member_directory_agruments_on_load', $args );

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
	$default_view = $current_view;
} else {
	$args['default_view'] = ! empty( $args['default_view'] ) ? $args['default_view'] : $args['view_types'][0];
	$default_view = $args['default_view'];
	$current_view = ( ! empty( $_GET[ 'view_type_' . $unique_hash ] ) && in_array( $_GET[ 'view_type_' . $unique_hash ], $args['view_types'] ) ) ? $_GET[ 'view_type_' . $unique_hash ] : $args['default_view'];
}

// Sorting
$default_sorting = ! empty( $args['sortby'] ) ? $args['sortby'] : 'user_registered_desc';

$sort_from_url = '';
if ( ! empty( $args['enable_sorting'] ) ) {
	$sorting_options = empty( $args['sorting_fields'] ) ? array() : $args['sorting_fields'];
	if ( ! in_array( $default_sorting, $sorting_options ) ) {
		$sorting_options[] = $default_sorting;
	}

	if ( ! empty( $sorting_options ) ) {
		$all_sorting_options = UM()->member_directory()->sort_fields;
		$sorting_options = array_intersect_key( $all_sorting_options, array_flip( $sorting_options ) );
	}

	$sorting_options = apply_filters( 'um_member_directory_pre_display_sorting', $sorting_options, $args );

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

if ( ! empty( $args['enable_sorting'] ) && ! empty( $sorting_options ) && count( $sorting_options ) > 1 ) {
	$classes .= ' um-member-with-sorting';
}

$filters_expanded = ! empty( $args['filters_expanded'] ) ? true : false;

//send $args variable to the templates
$args['args'] = $args;
foreach ( $args['view_types'] as $type ) {
	$basename = UM()->member_directory()->get_type_basename( $type );
	UM()->get_template( 'members-' . $type . '.php', $basename, $args, true );
}
UM()->get_template( 'members-header.php', '', $args, true );
UM()->get_template( 'members-pagination.php', '', $args, true );

$must_search = 0;
$not_searched = false;
if ( ( ( $search && $show_search ) || ( $filters && $show_filters && count( $search_filters ) ) ) && isset( $args['must_search'] ) && $args['must_search'] == 1 ) {
	$must_search = 1;
	$not_searched = true;
	if ( $search && $show_search && ! empty( $search_from_url ) ) {
		$not_searched = false;
	} elseif ( $filters && $show_filters && count( $search_filters ) ) {
		foreach ( $search_filters as $filter ) {
			// getting value from GET line
			switch ( UM()->member_directory()->filter_types[ $filter ] ) {
				default: {

					$not_searched = apply_filters( 'um_member_directory_filter_value_from_url', $not_searched, $filter );

					break;
				}
				case 'select': {

					// getting value from GET line
					$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? explode( '||', sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ) : array();

					if ( ! empty( $filter_from_url ) ) {
						$not_searched = false;
					}

					break;
				}
				case 'slider': {
					// getting value from GET line
					$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) : '';
					if ( ! empty( $filter_from_url ) ) {
						$not_searched = false;
					}

					break;
				}
				case 'datepicker':
				case 'timepicker': {
					// getting value from GET line
					$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) : '';
					if ( ! empty( $filter_from_url ) ) {
						$not_searched = false;
					}

					break;
				}
			}
		}
	}
} ?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( substr( md5( $form_id ), 10, 5 ) ); ?>"
     data-hash="<?php echo esc_attr( substr( md5( $form_id ), 10, 5 ) ) ?>" data-base-post="<?php echo esc_attr( $post->ID ) ?>"
	 data-must-search="<?php echo esc_attr( $must_search ); ?>" data-searched="<?php echo $not_searched ? '0' : '1'; ?>"
	 data-view_type="<?php echo esc_attr( $current_view ) ?>" data-page="<?php echo esc_attr( $current_page ) ?>"
	 data-sorting="<?php echo esc_attr( $sort_from_url ) ?>">
	<div class="um-members-overlay"><div class="um-ajax-loading"></div></div>

	<div class="um-member-directory-header">
		<?php if ( $search && $show_search ) { ?>
			<div class="um-member-directory-header-row um-member-directory-search-row">
				<div class="um-member-directory-search-line">
					<label>
						<span><?php _e( 'Search:', 'ultimate-member' ); ?></span>
						<input type="search" class="um-search-line" placeholder="<?php esc_attr_e( 'Search', 'ultimate-member' ) ?>"  value="<?php echo esc_attr( $search_from_url ) ?>" aria-label="<?php esc_attr_e( 'Search', 'ultimate-member' ) ?>" speech />
					</label>
					<input type="button" class="um-do-search" value="<?php esc_attr_e( 'Search', 'ultimate-member' ); ?>" />
				</div>
			</div>
		<?php }

		if ( ( ! empty( $args['enable_sorting'] ) && ! empty( $sorting_options ) && count( $sorting_options ) > 1 ) ||
		     ( $filters && $show_filters && count( $search_filters ) ) ||
		     ! $single_view ) { ?>
			<div class="um-member-directory-header-row">
				<div class="um-member-directory-nav-line">
					<?php if ( ! $single_view ) {
						$view_types = 0;

						foreach ( UM()->member_directory()->view_types as $key => $value ) {
							if ( in_array( $key, $args['view_types'] ) ) {
								if ( empty( $view_types ) ) { ?>
									<span class="um-member-directory-view-type<?php if ( $not_searched ) {?> um-disabled<?php } ?>">
								<?php }

								$view_types++; ?>

								<a href="javascript:void(0)"
								   class="um-member-directory-view-type-a<?php if ( ! $not_searched ) {?> um-tip-n<?php } ?>"
								   data-type="<?php echo $key; ?>"
								   data-default="<?php echo ( $default_view == $key ) ? 1 : 0; ?>"
								   title="<?php printf( esc_attr__( 'Change to %s', 'ultimate-member' ), $value['title'] ) ?>"
								   default-title="<?php echo esc_attr( $value['title'] ); ?>"
								   next-item="" ><i class="<?php echo $value['icon']; ?>"></i></a>
							<?php }
						}

						if ( ! empty( $view_types ) ) { ?>
							</span>
						<?php }
					}

					if ( ! empty( $args['enable_sorting'] ) && ! empty( $sorting_options ) && count( $sorting_options ) > 1 ) { ?>
						<div class="um-member-directory-sorting">
							<span><?php _e( 'Sort by:', 'ultimate-member' ); ?>&nbsp;</span>
							<div class="um-member-directory-sorting-a">
								<a href="javascript:void(0);" class="um-member-directory-sorting-a-text"><?php echo $sorting_options[ $sort_from_url ] ?></a>
								&nbsp;<i class="um-faicon-caret-down"></i><i class="um-faicon-caret-up"></i>
							</div>
						</div>

						<?php $items = array();
						foreach ( $sorting_options as $value => $title ) {
							$items[] = '<a href="javascript:void(0);" data-value="' . esc_attr( $value ) . '" data-selected="' . ( ( $sort_from_url == $value ) ? '1' : '0' ) . '" data-default="' . ( ( $default_sorting == $value ) ? '1' : '0' ) . '">' . $title . '</a>'; ?>
						<?php }

						UM()->member_directory()->dropdown_menu( '.um-member-directory-sorting-a', 'click', $items ); ?>

					<?php }

					if ( $filters && $show_filters && count( $search_filters ) ) { ?>
						<span class="um-member-directory-filters">
							<span class="um-member-directory-filters-a<?php if ( $filters_expanded ) { ?> um-member-directory-filters-visible<?php } ?>">
								<a href="javascript:void(0);">
									<?php _e( 'More filters', 'ultimate-member' ); ?>
								</a>
								&nbsp;<i class="um-faicon-caret-down"></i><i class="um-faicon-caret-up"></i>
							</span>
						</span>
					<?php } ?>
				</div>
			</div>
		<?php } ?>


		<?php if ( $filters && $show_filters && count( $search_filters ) ) {

			if ( is_array( $search_filters ) ) { ?>
				<script type="text/template" id="tmpl-um-members-filtered-line">
					<# if ( data.filters.length > 0 ) { #>
						<# _.each( data.filters, function( filter, key, list ) { #>
							<div class="um-members-filter-tag">
								<# if ( filter.type == 'slider' ) { #>
									<# if ( filter.value[0] == filter.value[1] ) { #>
										<strong>{{{filter.label}}}</strong>: {{{filter.value[0]}}}
									<# } else { #>
										{{{filter.value_label}}}
									<# } #>
								<# } else { #>
									<strong>{{{filter.label}}}</strong>: {{{filter.value_label}}}
								<# } #>
								<div class="um-members-filter-remove um-tip-n" data-name="{{{filter.name}}}"
								     data-value="{{{filter.value}}}" data-range="{{{filter.range}}}"
								     data-type="{{{filter.type}}}" title="<?php esc_attr_e( 'Remove filter', 'ultimate-member' ) ?>">&times;</div>
							</div>
						<# }); #>
					<# } #>
				</script>

				<div class="um-member-directory-header-row um-member-directory-filters-bar<?php if ( ! $filters_expanded ) { ?> um-header-row-invisible<?php } ?>">
					<div class="um-search um-search-<?php echo count( $search_filters ) ?><?php if ( ! $filters_expanded ) { ?> um-search-invisible<?php } ?>">
						<?php $i = 0;
						foreach ( $search_filters as $filter ) {
							$filter_content = UM()->member_directory()->show_filter( $filter, $args );
							if ( empty( $filter_content ) ) {
								continue;
							}

							$type = UM()->member_directory()->filter_types[ $filter ]; ?>

							<div class="um-search-filter um-<?php echo esc_attr( $type ) ?>-filter-type <?php echo ( $i != 0 && $i%2 !== 0 ) ? 'um-search-filter-2' : '' ?>"> <?php echo $filter_content; ?> </div>

							<?php $i++;
						} ?>
					</div>
				</div>
				<div class="um-member-directory-header-row">
					<div class="um-filtered-line">
						<div class="um-clear-filters"><a href="javascript:void(0);" class="um-clear-filters-a" title="<?php esc_attr_e( 'Remove all filters', 'ultimate-member' ) ?>"><?php _e( 'Clear all', 'ultimate-member' ); ?></a></div>
					</div>
				</div>
				<?php
			}
		}
		do_action( 'um_members_directory_head', $args ); ?>
	</div>

	<div class="um-members-wrapper"></div>

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

</div>