<?php
/**
 * Template for the members directory
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/members.php
 *
 * Page: "Members"
 *
 * @version 2.9.0
 *
 * @var array  $args
 * @var array  $t_args
 * @var array  $view_types
 * @var string $mode
 * @var string $unique_hash
 * @var string $current_view
 * @var bool   $single_view
 * @var string $default_view
 * @var int    $post_id
 * @var int    $form_id
 * @var int    $current_page
 * @var string $sort_from_url
 * @var string $default_sorting
 * @var array  $sorting_options
 * @var string $search
 * @var bool   $show_search
 * @var string $search_from_url
 * @var bool   $filters
 * @var bool   $show_filters
 * @var array  $search_filters
 * @var string $classes
 * @var bool   $filters_collapsible
 * @var bool   $filters_expanded
 * @var bool   $must_search
 * @var bool   $not_searched
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//foreach ( $view_types as $view_type ) {
//	$basename = UM()->member_directory()->get_type_basename( $view_type );
//	UM()->get_template( 'v3/members-' . $view_type . '.php', $basename, $args, true );
//}
?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( substr( md5( $form_id ), 10, 5 ) ); ?>"
	data-hash="<?php echo esc_attr( substr( md5( $form_id ), 10, 5 ) ); ?>" data-base-post="<?php echo esc_attr( $post_id ); ?>"
	data-must-search="<?php echo esc_attr( $must_search ); ?>" data-searched="<?php echo $not_searched ? '0' : '1'; ?>"
	data-view_type="<?php echo esc_attr( $current_view ); ?>" data-page="<?php echo esc_attr( $current_page ); ?>"
	data-sorting="<?php echo esc_attr( $sort_from_url ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'um_member_directory' ) ); ?>">

	<div class="um-members-overlay"><div class="um-ajax-loading"></div></div>

	<div class="um-member-directory-header um-form">
		<?php
		do_action( 'um_members_directory_before_head', $args, $form_id, $not_searched );

		if ( $filters && $show_filters && count( $search_filters ) ) {
			?>
			<div class="um-member-directory-header-row">
				<div class="um-member-directory-nav-line">
					<?php
					if ( $filters && $show_filters && count( $search_filters ) && $filters_collapsible ) { ?>
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
									{{{filter.value_label}}}
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

							<div class="um-search-filter um-<?php echo esc_attr( $type ) ?>-filter-type <?php echo ( $i != 0 && $i%2 !== 0 ) ? 'um-search-filter-2' : '' ?>">
								<?php echo $filter_content; ?>
							</div>

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
		do_action( 'um_members_directory_head', $args, $form_id, $not_searched );
		?>
	</div>

	<div class="um-member-directory-header">
		<?php if ( ( $search && $show_search ) || ( $filters && $show_filters && count( $search_filters ) && $filters_collapsible ) ) { ?>
			<div class="um-member-directory-header-row um-member-directory-search-filters">
				<?php if ( $search && $show_search ) { ?>
					<div class="um-member-directory-search-wrapper">
						<label class="um-member-directory-search-line">
							<span><?php esc_html_e( 'Search:', 'ultimate-member' ); ?></span>
							<input type="search" class="um-search-line" placeholder="<?php esc_attr_e( 'Search', 'ultimate-member' ); ?>" value="<?php echo esc_attr( $search_from_url ); ?>" aria-label="<?php esc_attr_e( 'Search', 'ultimate-member' ); ?>" speech />
						</label>
						<?php
						echo wp_kses(
							UM()->frontend()::layouts()::button(
								__( 'Search', 'ultimate-member' ),
								array(
									'design'  => 'primary',
									'size'    => 'm',
									'classes' => array( 'um-do-search' ),
								)
							),
							UM()->get_allowed_html( 'templates' )
						);
						?>
					</div>
				<?php } ?>

				<?php do_action( 'um_members_directory_between_search_filters', $args, $form_id, $not_searched ); ?>

				<?php
				if ( $filters && $show_filters && $filters_collapsible && count( $search_filters ) ) {
					echo wp_kses(
						UM()->frontend()::layouts()::button(
							__( 'Filters', 'ultimate-member' ),
							array(
								'size'    => 'm',
								'classes' => array( 'um-filters-toggle' ),
							)
						),
						UM()->get_allowed_html( 'templates' )
					);
				}
				?>
			</div>
		<?php } ?>

		<div class="um-member-directory-header-row-grid">
			<div class="um-member-directory-header-left-cell">
				<?php
				if ( ! $single_view ) {
					$button_args = array();
					foreach ( UM()->member_directory()->view_types as $key => $value ) {
						if ( in_array( $key, $view_types, true ) ) {
							$b_classes = array( 'um-member-directory-view-type-' . $key );
							if ( $current_view === $key ) {
								$b_classes[] = 'current';
							}
							$button_args[] = array(
								'label'   => $value['title'],
								'classes' => $b_classes,
								'data'    => array(
									'type'    => $key,
									'default' => $default_view === $key,
								),
							);
						}
					}

					echo wp_kses(
						UM()->frontend()::layouts()::buttons_group(
							$button_args,
							array(
								'size'    => 'equal',
								'classes' => array( 'um-member-view-switcher' ),
							)
						),
						UM()->get_allowed_html( 'templates' )
					);
				}
				?>
				<div class="um-members-counter"></div>
			</div>
			<div class="um-member-directory-header-right-cell">
				<?php if ( ! empty( $enable_sorting ) && ! empty( $sorting_options ) && count( $sorting_options ) > 1 ) { ?>
					<div class="um-member-directory-sorting">
						<span><?php esc_html_e( 'Sort by:', 'ultimate-member' ); ?></span>
						<?php
						$items = array();
						foreach ( $sorting_options as $value => $title ) {
							$items[] = '<a href="#" data-directory-hash="' . esc_attr( substr( md5( $form_id ), 10, 5 ) ) . '" class="um-members-sorting um-sorting-by-' . esc_attr( $value ) . '" data-value="' . esc_attr( $value ) . '" data-selected="' . ( ( $sort_from_url == $value ) ? '1' : '0' ) . '" data-default="' . ( ( $default_sorting == $value ) ? '1' : '0' ) . '">' . $title . '</a>';
						}
						echo wp_kses(
							UM()->frontend()::layouts()::dropdown_menu(
								'um-members-sorting-toggle',
								$items,
								array(
									'type'          => 'button',
									'button_label'  => $sorting_options[ $sort_from_url ],
									'width'         => 210,
								)
							),
							UM()->get_allowed_html( 'templates' )
						);
						?>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>

	<?php $wrapper_classes = array( 'um-members-wrapper', 'um-members-' . $current_view ); ?>
	<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"></div>

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
	do_action( 'um_members_directory_footer', $args, $form_id, $not_searched );
	?>
</div>
