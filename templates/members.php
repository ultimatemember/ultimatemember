<?php $args['view_type'] = ! empty( $args['view_type'] ) ? $args['view_type'] : '';
$args['view_type'] = ! empty( $_GET['view_type'] ) ? $_GET['view_type'] : $args['view_type'];
$view_type = ( ! empty( $args['view_type'] ) && 'list' == $args['view_type'] ) ? 'list' : 'grid';

$show_search = true;
if ( ! empty( $args['roles_can_search'] ) && ! in_array( um_user( 'role' ), $args['roles_can_search'] ) ) {
    $show_search = false;
} ?>

<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo esc_attr( $form_id ); ?>" data-unique_id="um-<?php echo esc_attr( $form_id ) ?>" data-view_type="<?php echo $view_type ?>" data-only_search="<?php echo ( $search && $show_search && ! empty( $must_search ) ) ? 1 : 0 ?>">

    <?php $sorting_options = $args['sorting_fields'];
    $all_sorting_options = UM()->members()->get_sorting_fields();

    $sorting_options = array_intersect_key( $all_sorting_options, array_flip( $sorting_options ) ); ?>

	<div class="um-form">
        <div class="um-member-directory-header">
            <div class="um-clear"></div>

            <?php if ( $search && $show_search ) { ?>
                <div class="um-member-directory-search-line">
                    <input type="text" class="um-search-line" value="" placeholder="<?php _e( 'Search', 'ultimate-member' ) ?>" />
                    <div class="uimob340-show uimob500-show">
                        <a href="javascript:void(0);" class="um-button um-do-search um-tip-n" original-title="<?php _e( 'Search', 'ultimate-member' ); ?>">
                            <i class="um-faicon-search"></i>
                        </a>
                    </div>
                    <div class="uimob340-hide uimob500-hide">
                        <a href="javascript:void(0);" class="um-button um-do-search"><?php _e( 'Search', 'ultimate-member' ); ?></a>
                    </div>
                </div>
            <?php } ?>

            <div class="um-member-directory-actions">
                <div class="um-member-directory-sorting <?php if ( ! $filters ) { ?>hidden_filter<?php } ?>">
                    <select class="um-s3 um-member-directory-sorting-options" id="um-member-directory-sorting-select-<?php echo esc_attr( $form_id ) ?>" data-placeholder="<?php _e( 'Sort By', 'ultimate-member' ); ?>">
                        <option value=""></option>
                        <?php foreach ( $sorting_options as $value => $title ) { ?>
                            <option value="<?php echo $value ?>"><?php echo $title ?></option>
                        <?php } ?>
                    </select>
                </div>
                <?php if ( $filters ) { ?>
                    <div class="um-member-directory-filters">
                        <a href="javascript:void(0);" class="um-member-directory-filters-a um-tip-n" original-title="<?php _e( 'Filters', 'ultimate-member' ); ?>">
                            <i class="um-faicon-sliders"></i>
                        </a>
                    </div>
                <?php } ?>
                <div class="um-member-directory-view-type">
                    <a href="javascript:void(0);" class="um-member-directory-view-type-a um-tip-n" original-title="<?php if ( 'list' == $view_type ) { ?>Change to Grid<?php } else { ?>Change to List<?php } ?>">
                        <i class="<?php if ( 'list' == $view_type ) { ?>um-faicon-list<?php } else { ?>um-faicon-th<?php } ?>"></i>
                    </a>
                </div>
            </div>
            <div class="um-clear"></div>
        </div>

        <?php if ( $filters ) {
            $search_filters = array();

            if ( isset( $args['search_fields'] ) ) {
                foreach( $args['search_fields'] as $k => $testfilter ) {
                    if ( $testfilter && ! in_array( $testfilter, (array) $search_filters ) ) {
                        $search_filters[] = $testfilter;
                    }
                }
            }

            $search_filters = apply_filters( 'um_frontend_member_search_filters', $search_filters );

            if ( $args['filters'] == 1 && is_array( $search_filters ) ) { // search on ?>
                <div class="um-filtered-line">
                    <div class="um-clear-filters"><a href="javascript:void(0);" class="um-clear-filters-a"><?php _e( 'Clear Filters', 'ultimate-member' ); ?></a></div>
                </div>

                <script type="text/template" id="tmpl-um-members-filtered-line">
                    <# if ( data.filters.length > 0 ) { #>
                        <# _.each( data.filters, function( filter, key, list ) { #>
                            <div class="um-members-filter-tag"><strong>{{{filter.label}}}</strong>: {{{filter.value_label}}}<div class="um-members-filter-remove" data-name="{{{filter.name}}}" data-value="{{{filter.value}}}">&times;</div></div>
                        <# }); #>
                    <# } #>
                </script>

                <div class="um-search um-search-<?php echo count( $search_filters ) ?>">

                    <form method="post" action="" class="filters_form">

                        <?php $i = 0;
                        foreach ( $search_filters as $filter ) {
                            $i++;

                            if ( $i % 2 == 0 ) {
                                $add_class = 'um-search-filter-2';
                            } else {
                                $add_class = '';
                            } ?>

                            <div class="um-search-filter <?php echo $add_class ?>"><?php echo UM()->members()->show_filter( $filter ); ?></div>

                        <?php } ?>

                        <div class="um-clear"></div>

                        <div class="um-search-submit">
                            <a href="javascript:void(0);" class="um-close-filter"><?php _e( 'Close Filters', 'ultimate-member' ); ?></a>
                        </div>
                        <div class="um-clear"></div>
                    </form>
                </div>

            <?php }

            do_action('um_members_directory_head', $args );
        } ?>

        <div class="um-members-wrapper">

            <?php if ( um_members( 'no_users' ) ) { ?>

                <div class="um-members-none">
                    <p><?php echo $args['no_users']; ?></p>
                </div>

            <?php }

            $args['view_type'] = ! empty( $_GET['view_type'] ) ? $_GET['view_type'] : $args['view_type'];

            include UM()->templates()->get_template( 'members-grid' );
            include UM()->templates()->get_template( 'members-list' ); ?>

            <div class="um-members-overlay"><div class="um-ajax-loading"></div></div>
        </div>

        <div class="um-members-pagination-box"></div>

        <script type="text/template" id="tmpl-um-members-pagination">
            <# if ( data.pagi.pages_to_show.length > 0 ) { #>
                <div class="um-members-pagidrop uimob340-show uimob500-show">
                    <?php _e( 'Jump to page:','ultimate-member' ); ?>
                    <select class="um-s2 um-members-pagi-dropdown" style="width: 100px;display:inline-block;">
                        <# _.each( data.pagi.pages_to_show, function( page, key, list ) { #>
                            <option value="{{{page}}}" <# if ( page == data.pagi.current_page ) { #>selected<# } #>>{{{page}}} <?php _e( 'of','ultimate-member' ) ?> {{{data.pagi.total_pages}}}</option>
                        <# }); #>
                    </select>
                </div>

                <div class="um-members-pagi uimob340-hide uimob500-hide">
                    <span class="pagi pagi-arrow <# if ( data.pagi.current_page == 1 ) { #>disabled<# } #>" data-page="first"><i class="um-faicon-angle-double-left"></i></span>
                    <span class="pagi pagi-arrow <# if ( data.pagi.current_page == 1 ) { #>disabled<# } #>" data-page="prev"><i class="um-faicon-angle-left"></i></span>

                    <# _.each( data.pagi.pages_to_show, function( page, key, list ) { #>
                        <span class="pagi <# if ( page == data.pagi.current_page ) { #>current<# } #>" data-page="{{{page}}}">{{{page}}}</span>
                    <# }); #>

                    <span class="pagi pagi-arrow <# if ( data.pagi.current_page == data.pagi.total_pages ) { #>disabled<# } #>" data-page="next"><i class="um-faicon-angle-right"></i></span>
                    <span class="pagi pagi-arrow <# if ( data.pagi.current_page == data.pagi.total_pages ) { #>disabled<# } #>" data-page="last"><i class="um-faicon-angle-double-right"></i></span>
                </div>
            <# } #>
        </script>

        <div class="um-clear"></div>
    </div>
</div>