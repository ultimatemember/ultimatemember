<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo esc_attr( $form_id ); ?>" data-unique_id="um-<?php echo esc_attr( $form_id ) ?>">

    <?php $args['view_type'] = ! empty( $args['view_type'] ) ? $args['view_type'] : '';
    $args['view_type'] = ! empty( $_GET['view_type'] ) ? $_GET['view_type'] : $args['view_type'];
    $view_type = ( ! empty( $args['view_type'] ) && 'list' == $args['view_type'] ) ? 'list' : 'grid';

    $show_search = true;
    if ( ! empty( $args['roles_can_search'] ) && ! in_array( um_user( 'role' ), $args['roles_can_search'] ) ) {
        $show_search = false;
    }

    $show_items = true;
    if ( isset( $args['search'] ) && $args['search'] == 1 && isset( $args['must_search'] ) && $args['must_search'] == 1 && ! isset( $_REQUEST['um_search'] ) ) {
        $show_items = false;
    } ?>

	<div class="um-form">

        <div class="um-member-directory-header">
            <div class="um-clear"></div>
            <?php if ( $show_search ) { ?>
                <div class="um-member-directory-search-line">
                    <input type="text" id="um-search-line" value="" placeholder="<?php _e( 'Search', 'ultimate-member' ) ?>" />
                </div>
                <div class="um-member-directory-filters">
                    <a href="#" class="um-member-directory-filters-a">
                        <i class="um-faicon-filter"></i>
                    </a>
                </div>
            <?php } ?>
            <div class="um-member-directory-view-type">
                <a href="#" class="um-member-directory-view-type-a">
                    <i class="<?php if ( 'list' == $view_type ) { ?>um-faicon-th-list<?php } else { ?>um-faicon-th-large<?php } ?>"></i>
                </a>
                <?php
                $items = array(
                    'grid' => '<a href="javascript:void(0);" class="um_change_view_link um-dropdown-hide" data-view="grid">'.__('Grid','ultimate-member').'</a>',
                    'list' => '<a href="javascript:void(0);" class="um_change_view_link um-dropdown-hide" data-view="list">'.__('List','ultimate-member').'</a>',
                    'cancel' => '<a href="javascript:void(0);" class="um-dropdown-hide">'.__('Cancel','ultimate-member').'</a>',
                );

                UM()->menu()->new_ui( 'bc', 'div.um-member-directory-view-type', 'click', $items ); ?>
            </div>
            <div class="um-clear"></div>
        </div>

        <?php if ( $show_search ) {
            $search_filters = array();

            if ( isset( $args['search_fields'] ) ) {
                foreach( $args['search_fields'] as $k => $testfilter ) {
                    if ( $testfilter && !in_array( $testfilter, (array)$search_filters ) ) {
                        $search_filters[] = $testfilter;
                    }
                }
            }

            $search_filters = apply_filters('um_frontend_member_search_filters',$search_filters);

            if ( $args['search'] == 1 && is_array( $search_filters ) ) { // search on ?>

                <div class="um-search um-search-<?php echo count( $search_filters ) ?>">

                    <form method="get" action="" />

                        <?php if ( isset( $_REQUEST['page_id'] ) && get_option('permalink_structure') == 0 ) { ?>

                            <input type="hidden" name="page_id" id="page_id" value="<?php echo esc_attr( $_REQUEST['page_id']); ?>" />

                        <?php }

                        $i = 0;
                        foreach ( $search_filters as $filter ) {
                            $i++;

                            if ( $i % 2 == 0 ) {
                                $add_class = 'um-search-filter-2';
                            } else {
                                $add_class = '';
                            }

                            echo '<div class="um-search-filter '. $add_class .'">'; UM()->members()->show_filter( $filter ); echo '</div>';

                        } ?>

                        <div class="um-clear"></div>

                        <div class="um-search-submit">
                            <input type="hidden" name="um_search" id="um_search" value="1" />
                            <a href="#" class="um-button um-do-search"><?php _e('Search','ultimate-member'); ?></a><a href="<?php echo UM()->permalinks()->get_current_url( true ); ?>" class="um-button um-alt"><?php _e('Reset','ultimate-member'); ?></a>
                        </div>
                        <div class="um-clear"></div>

                    </form>

                </div>

                <?php
            }

            if ( isset($_REQUEST['um_search']) ) {
                $is_filtering = 1;
            } else if ( UM()->is_filtering == 1 ) {
                $is_filtering = 1;
            } else {
                $is_filtering = 0;
            }

            if ( um_members( 'header' ) && $is_filtering && um_members( 'users_per_page' ) ) { ?>

                <div class="um-members-intro">
                    <div class="um-members-total"><?php echo ( um_members('total_users') > 1 ) ? um_members( 'header' ) : um_members( 'header_single' ); ?></div>
                </div>

            <?php }

            do_action('um_members_directory_head', $args );
        }

        if ( $show_items ) { ?>

            <div class="um-members-wrapper">

                <?php if ( um_members( 'no_users' ) ) { ?>

                    <div class="um-members-none">
                        <p><?php echo $args['no_users']; ?></p>
                    </div>

                <?php }

                $args['view_type'] = ! empty( $_GET['view_type'] ) ? $_GET['view_type'] : $args['view_type'];

                $file_grid = um_path . "templates/members-grid.php";
                $theme_file = get_stylesheet_directory() . "/ultimate-member/templates/members-grid.php";

                if ( file_exists( $theme_file ) ) {
                    $file_grid = $theme_file;
                }

                $file_list = um_path . "templates/members-list.php";
                $theme_file = get_stylesheet_directory() . "/ultimate-member/templates/members-list.php";

                if ( file_exists( $theme_file ) ) {
                    $file_list = $theme_file;
                }

                include $file_grid;
                include $file_list; ?>

                <div class="um-members-overlay"><div class="um-ajax-loading"></div></div>
            </div>

            <?php if ( um_members( 'total_pages' ) > 1 ) { ?>

                <div class="um-members-pagidrop uimob340-show uimob500-show">

                    <?php _e( 'Jump to page:','ultimate-member' ); ?>

                    <?php if ( um_members('pages_to_show') && is_array( um_members('pages_to_show') ) ) { ?>
                        <select onChange="window.location.href=this.value" class="um-s2" style="width: 100px;">
                            <?php foreach( um_members('pages_to_show') as $i ) { ?>
                                <option value="<?php echo UM()->permalinks()->add_query( 'members_page', $i ); ?>" <?php selected($i, um_members('page')); ?>><?php printf(__('%s of %d','ultimate-member'), $i, um_members('total_pages') ); ?></option>
                            <?php } ?>
                        </select>
                    <?php } ?>

                </div>

                <div class="um-members-pagi uimob340-hide uimob500-hide">

                    <?php if ( um_members('page') != 1 ) { ?>
                        <a href="<?php echo UM()->permalinks()->add_query( 'members_page', 1 ); ?>" data-page="first" class="pagi pagi-arrow um-tip-n" title="<?php _e('First Page','ultimate-member'); ?>"><i class="um-faicon-angle-double-left"></i></a>
                    <?php } else { ?>
                        <span class="pagi pagi-arrow disabled" data-page="first"><i class="um-faicon-angle-double-left"></i></span>
                    <?php } ?>

                    <?php if ( um_members('page') > 1 ) { ?>
                        <a href="<?php echo UM()->permalinks()->add_query( 'members_page', um_members('page') - 1 ); ?>" data-page="prev" class="pagi pagi-arrow um-tip-n" title="<?php _e('Previous','ultimate-member'); ?>"><i class="um-faicon-angle-left"></i></a>
                    <?php } else { ?>
                        <span class="pagi pagi-arrow disabled" data-page="prev"><i class="um-faicon-angle-left"></i></span>
                    <?php } ?>

                    <?php if ( um_members('pages_to_show') && is_array( um_members('pages_to_show') ) ) { ?>
                        <?php foreach( um_members('pages_to_show') as $i ) { ?>

                            <span class="pagi <?php if ( um_members('page') == $i ) { ?>current<?php } ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></span>

                        <?php } ?>
                    <?php } ?>


                    <?php if ( um_members('page') != um_members('total_pages') ) { ?>
                        <a href="<?php echo UM()->permalinks()->add_query( 'members_page', um_members('page') + 1 ); ?>" data-page="next" class="pagi pagi-arrow um-tip-n" title="<?php _e('Next','ultimate-member'); ?>"><i class="um-faicon-angle-right"></i></a>
                    <?php } else { ?>
                        <span class="pagi pagi-arrow disabled" data-page="next"><i class="um-faicon-angle-right"></i></span>
                    <?php } ?>

                    <?php if ( um_members('page') != um_members('total_pages') ) { ?>
                        <a href="<?php echo UM()->permalinks()->add_query( 'members_page', um_members('total_pages') ); ?>" data-page="last" class="pagi pagi-arrow um-tip-n" title="<?php _e('Last Page','ultimate-member'); ?>"><i class="um-faicon-angle-double-right"></i></a>
                    <?php } else { ?>
                        <span class="pagi pagi-arrow disabled" data-page="last"><i class="um-faicon-angle-double-right"></i></span>
                    <?php } ?>

                </div>

            <?php }
        } ?>
	</div>
    <div class="um-clear"></div>
</div>