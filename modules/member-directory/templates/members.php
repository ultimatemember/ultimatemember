<?php if ( ! defined( 'ABSPATH' ) ) exit;

global $post;

// Get default and real arguments
$def_args = array();
foreach ( UM()->module( 'member-directory' )->config()->get( 'default_member_directory_meta' ) as $k => $v ) {
	$key = str_replace( '_um_', '', $k );
	$def_args[ $key ] = $v;
}

$unique_hash = substr( md5( $args['form_id'] ), 10, 5 );

$args = array_merge( $def_args, $args );

//current user priority role
$priority_user_role = false;
if ( is_user_logged_in() ) {
	$priority_user_role = UM()->roles()->get_priority_user_role( get_current_user_id() );
}

$args = apply_filters( 'um_member_directory_agruments_on_load', $args );

// Views
$current_view = $args['view_type'];

if ( 'grid' === $current_view ) {
	$grid_columns = $args['grid_columns'];
	if ( $grid_columns > 4 || $grid_columns < 2 ) {
		$grid_columns = 3;
	}
}

// Sorting
$default_sorting = ! empty( $args['sortby'] ) ? $args['sortby'] : 'user_registered_desc';
if ( $default_sorting == 'other' && ! empty( $args['sortby_custom'] ) ) {
	$default_sorting = $args['sortby_custom'];
}

$sort_from_url = '';
$custom_sorting_titles = array();
if ( ! empty( $args['enable_sorting'] ) ) {
	$sorting_options = empty( $args['sorting_fields'] ) ? array() : $args['sorting_fields'];

	$sorting_options_prepared = array();
	if ( ! empty( $sorting_options ) ) {
		foreach ( $sorting_options as $option ) {
			if ( is_array( $option ) ) {
				$option_keys = array_keys( $option );
				$sorting_options_prepared[] = $option_keys[0];

				$custom_sorting_titles[ $option_keys[0] ] = $option[ $option_keys[0] ];
			} else {
				$sorting_options_prepared[] = $option;
			}
		}
	}

	$all_sorting_options = UM()->module( 'member-directory' )->config()->get( 'sort_fields' );

	if ( ! in_array( $default_sorting, $sorting_options_prepared ) ) {
		$sorting_options_prepared[] = $default_sorting;

		$label = $default_sorting;
		if ( ! empty( $args['sortby_custom_label'] ) && 'other' == $args['sortby'] ) {
			$label = $args['sortby_custom_label'];
		} elseif ( ! empty( $all_sorting_options[ $default_sorting ] ) ) {
			$label = $all_sorting_options[ $default_sorting ];
		}

		$label = ( $label == 'random' ) ? __( 'Random', 'ultimate-member' ) : $label;

		$custom_sorting_titles[ $default_sorting ] = $label;
	}

	if ( ! empty( $sorting_options_prepared ) ) {
		$sorting_options = array_intersect_key( array_merge( $all_sorting_options, $custom_sorting_titles ), array_flip( $sorting_options_prepared ) );
	}

	$sorting_options = apply_filters( 'um_member_directory_pre_display_sorting', $sorting_options, $args );
	$sort_from_url = ( ! empty( $_GET[ 'sort_' . $unique_hash ] ) && in_array( sanitize_text_field( $_GET[ 'sort_' . $unique_hash ] ), array_keys( $sorting_options ) ) ) ? sanitize_text_field( $_GET[ 'sort_' . $unique_hash ] ) : $default_sorting;
}

$current_page = ( ! empty( $_GET[ 'page_' . $unique_hash ] ) && is_numeric( $_GET[ 'page_' . $unique_hash ] ) ) ? absint( $_GET[ 'page_' . $unique_hash ] ) : 1;

//Search
$search = isset( $args['search'] ) ? $args['search'] : false;
$show_search = empty( $args['roles_can_search'] ) || ( ! empty( $priority_user_role ) && in_array( $priority_user_role, $args['roles_can_search'] ) );
$search_from_url = '';
if ( $search && $show_search ) {
	$search_from_url = ! empty( $_GET[ 'search_' . $unique_hash ] ) ? stripslashes( sanitize_text_field( $_GET[ 'search_' . $unique_hash ] ) ) : '';
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
		return in_array( $item, array_keys( UM()->module( 'member-directory' )->config()->get( 'filter_fields' ) ) );
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

if ( ! empty( $args['enable_sorting'] ) && ! empty( $sorting_options ) && count( $sorting_options ) > 1 ) {
	$classes .= ' um-member-with-sorting';
}

$filters_collapsible = true;
$filters_expanded = ! empty( $args['filters_expanded'] ) ? true : false;
if ( $filters_expanded ) {
	$filters_collapsible = ! empty( $args['filters_is_collapsible'] ) ? true : false;
}

if ( ! get_option( 'show_avatars' ) ) {
	$args['profile_photo'] = false;
}

if ( ! UM()->options()->get( 'use_cover_photos' ) ) {
	$args['cover_photos'] = false;
}

//send $args variable to the templates
$args['args'] = $args;
um_get_template( 'members-' . $args['view_type'] . '.php', $args, 'member-directory' );
um_get_template( 'members-header.php', $args, 'member-directory' );
um_get_template( 'members-pagination.php', $args, 'member-directory' );

$must_search = 0;
$not_searched = false;
if ( ( ( $search && $show_search ) || ( $filters && $show_filters && count( $search_filters ) ) ) && isset( $args['must_search'] ) && $args['must_search'] == 1 ) {
	$must_search = 1;
	$not_searched = true;
	if ( $search && $show_search && ! empty( $search_from_url ) ) {
		$not_searched = false;
	} elseif ( $filters && $show_filters && count( $search_filters ) ) {
		$filter_types = UM()->module( 'member-directory' )->config()->get( 'filter_types' );
		foreach ( $search_filters as $filter ) {
			// getting value from GET line
			switch ( $filter_types[ $filter ] ) {
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

<div class="um <?php echo esc_attr( UM()->common()->shortcodes()->get_class( $mode ) ); ?> um-<?php echo esc_attr( substr( md5( $form_id ), 10, 5 ) ); ?>"
     data-hash="<?php echo esc_attr( substr( md5( $form_id ), 10, 5 ) ) ?>" data-base-post="<?php echo esc_attr( $post->ID ) ?>"
	 data-must-search="<?php echo esc_attr( $must_search ); ?>" data-searched="<?php echo $not_searched ? '0' : '1'; ?>"
	 data-page="<?php echo esc_attr( $current_page ) ?>" data-view_type="<?php echo esc_attr( $current_view ) ?>"
	 <?php if ( 'grid' === $current_view ) { ?> data-grid-columns="<?php echo esc_attr( $grid_columns ) ?>"<?php } ?>
	 data-sorting="<?php echo esc_attr( $sort_from_url ) ?>">

	<div class="um-members-overlay"><div class="um-ajax-loading"></div></div>

	<div class="um-member-directory-header um-form">

		<?php do_action( 'um_members_directory_before_head', $args, $form_id, $not_searched ); ?>

		<?php if ( ( $search && $show_search ) || ( $filters && $show_filters && count( $search_filters ) && $filters_collapsible ) ) { ?>
			<div class="um-member-directory-header-row um-member-directory-search-filters">
				<?php if ( $search && $show_search ) { ?>
					<div class="um-member-directory-search-wrapper">
						<label class="um-member-directory-search-line">
							<span><?php _e( 'Search:', 'ultimate-member' ); ?></span>
							<input type="search" class="um-search-line" placeholder="<?php esc_attr_e( 'Search', 'ultimate-member' ) ?>" value="<?php echo esc_attr( $search_from_url ) ?>" aria-label="<?php esc_attr_e( 'Search', 'ultimate-member' ) ?>" speech />
						</label>
						<input type="button" class="um-do-search um-button um-button-primary" value="<?php esc_attr_e( 'Search', 'ultimate-member' ); ?>" />
					</div>
				<?php } ?>

				<?php do_action( 'um_members_directory_between_search_filters', $args, $form_id, $not_searched ); ?>

				<?php if ( $filters && $show_filters && count( $search_filters ) && $filters_collapsible ) { ?>
					<input type="button" class="um-filters-toggle um-button" value="<?php esc_attr_e( 'Filters', 'ultimate-member' ); ?>" />
				<?php } ?>
			</div>
		<?php } ?>

		<?php if ( $filters && $show_filters && count( $search_filters ) ) {

			if ( is_array( $search_filters ) ) { ?>
				<script type="text/template" id="tmpl-um-members-filtered-line">
					<# if ( data.filters.length > 0 ) { #>
						<# _.each( data.filters, function( filter, key, list ) { #>
							<div class="um-members-filter-tag">
								<# if ( filter.type == 'slider' ) { #>
									{{{filter.value_label}}}
								<# } else { #>
									{{{filter.label}}}: {{{filter.value_label}}}
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
						<div class="um-filters-header"><?php esc_html_e( 'Filters', 'ultimate-member' ); ?></div>
						<?php $i = 0;
						$filter_types = UM()->module( 'member-directory' )->config()->get( 'filter_types' );
						foreach ( $search_filters as $filter ) {
							$filter_content = UM()->module( 'member-directory' )->frontend()->show_filter( $filter, $args );
							if ( empty( $filter_content ) ) {
								continue;
							}

							$type = $filter_types[ $filter ]; ?>

							<div class="um-search-filter um-<?php echo esc_attr( $type ) ?>-filter-type <?php echo ( $i != 0 && $i%2 !== 0 ) ? 'um-search-filter-2' : '' ?>">
								<?php echo $filter_content; ?>
							</div>

							<?php $i++;
						} ?>
					</div>
				</div>
				<div class="um-member-directory-header-row um-filtered-line">
					<div class="um-clear-filters">
						<a href="javascript:void(0);" class="um-link um-clear-filters-a" title="<?php esc_attr_e( 'Remove all filters', 'ultimate-member' ) ?>"><?php esc_html_e( 'Clear all filters', 'ultimate-member' ); ?></a>
					</div>
				</div>
				<?php
			}
		} ?>

		<div class="um-member-directory-header-row um-header-row-invisible um-member-directory-users-counter<?php if ( ! empty( $args['enable_sorting'] ) && ! empty( $sorting_options ) && count( $sorting_options ) > 1 ) { ?> um-member-directory-sorting-row<?php } ?>">
			<div class="um-member-directory-total-users">&nbsp;</div>
			<?php if ( ! empty( $args['enable_sorting'] ) && ! empty( $sorting_options ) && count( $sorting_options ) > 1 ) { ?>
				<div class="um-member-directory-sorting disabled">
					<span><?php _e( 'Sort:', 'ultimate-member' ); ?>&nbsp;</span>
					<div class="um-member-directory-sorting-a">
						<a href="javascript:void(0);" class="um-member-directory-sorting-a-text"><?php echo $sorting_options[ $sort_from_url ] ?></a>
						&nbsp;<i class="fas fa-caret-down"></i><i class="fas fa-caret-up"></i>
					</div>
				</div>

				<?php $items = array();

				foreach ( $sorting_options as $value => $title ) {
					$items[] = '<a href="javascript:void(0);" data-directory-hash="' . esc_attr( substr( md5( $form_id ), 10, 5 ) ) . '" class="um-sorting-by-' . esc_attr( $value ) . '" data-value="' . esc_attr( $value ) . '" data-selected="' . ( ( $sort_from_url == $value ) ? '1' : '0' ) . '" data-default="' . ( ( $default_sorting == $value ) ? '1' : '0' ) . '">' . $title . '</a>'; ?>
				<?php }

				UM()->frontend()->helpers()->dropdown_menu( '.um-member-directory-sorting-a', 'click', $items ); ?>
			<?php } ?>
		</div>

		<?php do_action( 'um_members_directory_head', $args ); ?>
	</div>

	<div class="um-members-wrapper um-members-<?php echo esc_attr( $current_view ) ?>"<?php if ( 'grid' === $current_view ) { ?> data-grid-columns="<?php echo esc_attr( $grid_columns ) ?>"<?php } ?>></div>

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
	do_action( 'um_members_directory_footer', $args, $form_id, $not_searched ); ?>

</div>
