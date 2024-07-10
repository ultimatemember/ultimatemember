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
 * @var bool   $has_search
 * @var bool   $filters
 * @var bool   $show_filters
 * @var array  $search_filters
 * @var bool   $has_filters
 * @var string $classes
 * @var bool   $filters_collapsible
 * @var bool   $filters_expanded
 * @var bool   $must_search
 * @var bool   $not_searched
 * @var bool   $not_filtered
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( substr( md5( $form_id ), 10, 5 ) ); ?>"
	data-hash="<?php echo esc_attr( substr( md5( $form_id ), 10, 5 ) ); ?>" data-base-post="<?php echo esc_attr( $post_id ); ?>"
	data-must-search="<?php echo esc_attr( $must_search ); ?>" data-searched="<?php echo $not_searched ? '0' : '1'; ?>"
	data-default-layout="<?php echo esc_attr( $default_view ); ?>" data-page="<?php echo esc_attr( $current_page ); ?>"
	data-default-order="<?php echo esc_attr( $default_sorting ); ?>" data-sorting="<?php echo esc_attr( $sort_from_url ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'um_member_directory' ) ); ?>">
	<?php
	$header_classes = array( 'um-member-directory-header' );
	if ( ! $must_search ) {
		$header_classes[] = 'um-display-none';
	}
	?>
	<div class="<?php echo esc_attr( implode( ' ', $header_classes ) ); ?>">
		<?php do_action( 'um_members_directory_before_head', $args, $form_id, $not_searched ); ?>

		<?php if ( $has_search || ( $has_filters && $filters_collapsible ) ) { ?>
			<div class="um-member-directory-header-row um-member-directory-search-filters">
				<?php if ( $has_search ) { ?>
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
				if ( $has_filters && $filters_collapsible ) {
					$filter_toggle_classes = array( 'um-filters-toggle' );
					if ( $filters_expanded ) {
						$filter_toggle_classes[] = 'um-toggle-button-active';
					}
					echo wp_kses(
						UM()->frontend()::layouts()::button(
							__( 'Filters', 'ultimate-member' ),
							array(
								'size'          => 'm',
								'icon'          => '<span class="um-toggle-chevron"></span>',
								'icon_position' => 'trailing',
								'classes'       => $filter_toggle_classes,
								'data'          => array(
									'um-toggle' => '.um-member-directory-filters-bar',
								),
							)
						),
						UM()->get_allowed_html( 'templates' )
					);
				}
				?>
			</div>
		<?php } ?>

		<?php if ( $has_filters ) { ?>
			<div class="um-member-directory-header-row um-member-directory-filters-bar um-toggle-block<?php if ( ! $filters_expanded ) { ?> um-toggle-block-collapsed<?php } ?>">
				<div class="um-toggle-block-inner<?php if ( $filters_expanded ) { ?> um-visible<?php } ?>">
					<form class="um-filters-form">
						<?php foreach ( $search_filters as $filter => $filter_data ) { ?>
							<div class="um-search-filter um-<?php echo esc_attr( $filter_data['type'] ); ?>-filter-type" data-filter-name="<?php echo esc_attr( $filter ); ?>" data-filter-type="<?php echo esc_attr( $filter_data['type'] ); ?>">
								<?php echo wp_kses( $filter_data['content'], UM()->get_allowed_html( 'templates' ) ); ?>
							</div>
						<?php } ?>
						<div class="um-filters-footer">
							<?php
							$clear_classes = array( 'um-clear-filters-a' );
							if ( $not_filtered ) {
								$clear_classes[] = 'um-hidden';
							}

							echo wp_kses(
								UM()->frontend()::layouts()::button(
									__( 'Clear all', 'ultimate-member' ),
									array(
										'type'    => 'reset',
										'size'    => 'm',
										'design'  => 'link-gray',
										'title'   => __( 'Remove all filters', 'ultimate-member' ),
										'classes' => $clear_classes,
									)
								),
								UM()->get_allowed_html( 'templates' )
							);
							echo wp_kses(
								UM()->frontend()::layouts()::button(
									__( 'Apply filters', 'ultimate-member' ),
									array(
										'size'     => 'm',
										'design'   => 'primary',
										'classes'  => array( 'um-apply-filters' ),
										'disabled' => true,
									)
								),
								UM()->get_allowed_html( 'templates' )
							);
							?>
						</div>
					</form>
				</div>
			</div>
		<?php } ?>

		<?php
		$header_row_classes = array( 'um-member-directory-header-row-grid' );
		if ( $must_search ) {
			$header_row_classes[] = 'um-display-none';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $header_row_classes ) ); ?>">
			<div class="um-member-directory-header-left-cell">
				<?php
				if ( ! $single_view ) {
					$button_args = array();
					foreach ( UM()->member_directory()->view_types as $key => $value ) {
						if ( in_array( $key, $view_types, true ) ) {
							$b_classes = array( 'um-member-directory-view-type', 'um-member-directory-view-type-' . $key );
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
								'classes' => array( 'um-member-view-switcher', 'um-disabled' ), // disabled by default until first load.
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
							$items[] = '<a href="#" data-directory-hash="' . esc_attr( substr( md5( $form_id ), 10, 5 ) ) . '" class="um-members-sorting um-sorting-by-' . esc_attr( $value ) . '" data-value="' . esc_attr( $value ) . '" data-selected="' . ( ( $sort_from_url === $value ) ? '1' : '0' ) . '" data-default="' . ( ( $default_sorting === $value ) ? '1' : '0' ) . '">' . $title . '</a>';
						}
						echo wp_kses(
							UM()->frontend()::layouts()::dropdown_menu(
								'um-members-sorting-toggle',
								$items,
								array(
									'type'         => 'button',
									'button_label' => $sorting_options[ $sort_from_url ],
									'width'        => 210,
									'disabled'     => true,
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

	<?php
	if ( $must_search ) {
		$must_search_classes = array(
			'um-member-directory-must-search',
			'um-supporting-text',
		);
		if ( ! $not_searched ) {
			$must_search_classes[] = 'um-display-none';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $must_search_classes ) ); ?>">
			<?php esc_html_e( 'Please put search criteria for getting members.', 'ultimate-member' ); ?>
		</div>
	<?php } ?>
	<div class="um-member-directory-empty-search-result um-supporting-text um-display-none">
		<?php esc_html_e( 'We are sorry. We cannot find any users who match your search criteria.', 'ultimate-member' ); ?>
	</div>
	<div class="um-member-directory-empty-no-search-result um-supporting-text um-display-none">
		<?php esc_html_e( 'No users.', 'ultimate-member' ); ?>
	</div>
	<?php $wrapper_classes = array( 'um-members-wrapper', 'um-members-' . $current_view, 'um-display-none' ); ?>
	<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"></div>

	<div class="um-member-directory-loading">
		<?php echo wp_kses( UM()->frontend()::layouts()::ajax_loader( 'l' ), UM()->get_allowed_html( 'templates' ) ); ?>
	</div>

	<div class="um-members-pagination-box um-display-none"></div>

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
