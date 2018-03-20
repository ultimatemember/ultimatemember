<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Member Directory Search
 *
 * @param $args
 */
function um_members_directory_search( $args ) {
	$search_filters = array();
		
	if ( isset($args['search_fields']) ) {
		foreach( $args['search_fields'] as $k => $testfilter ){
			if ($testfilter && !in_array( $testfilter, (array)$search_filters ) ) {
				$search_filters[] = $testfilter;
			}
		}
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_frontend_member_search_filters
	 * @description Extend Member Directory Search filter
	 * @input_vars
	 * [{"var":"$search_filters","type":"array","desc":"Search Filters"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_frontend_member_search_filters', 'function_name', 10, 1 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_frontend_member_search_filters', 'my_frontend_member_search_filters', 10, 1 );
	 * function my_frontend_member_search_filters( $search_filters ) {
	 *     // your code here
	 *     return $search_filters;
	 * }
	 * ?>
	 */
	$search_filters = apply_filters( 'um_frontend_member_search_filters', $search_filters );

	if ( $args['search'] == 1 && is_array( $search_filters ) ) { // search on

		$current_user_roles = um_user( 'roles' );
		if ( ! empty( $args['roles_can_search'] ) && ( empty( $current_user_roles ) || count( array_intersect( $current_user_roles, $args['roles_can_search'] ) ) <= 0 ) ) {
			return;
		}

		$count = count( $search_filters ); ?>

		<div class="um-search um-search-<?php echo $count; ?>">
			
			<form method="get" action="" />
				
			<?php if ( isset( $_REQUEST['page_id'] ) && get_option('permalink_structure') == 0 ) { ?>
					
				<input type="hidden" name="page_id" id="page_id" value="<?php echo esc_attr( $_REQUEST['page_id']); ?>" />
					
			<?php }

			$i = 0;
			foreach( $search_filters as $filter ) {
				$i++;
						
				if ( $i % 2 == 0 ) {
					$add_class = 'um-search-filter-2';
				} else {
					$add_class = '';
				}
						
				echo '<div class="um-search-filter '. $add_class .'">'; UM()->members()->show_filter( $filter ); echo '</div>';
					
			}
					
			?>
				
			<div class="um-clear"></div>
					
			<div class="um-search-submit">

				<input type="hidden" name="um_search" id="um_search" value="1" />
						
				<a href="#" class="um-button um-do-search"><?php _e('Search','ultimate-member'); ?></a><a href="<?php echo UM()->permalinks()->get_current_url( true ); ?>" class="um-button um-alt"><?php _e('Reset','ultimate-member'); ?></a>
						
			</div><div class="um-clear"></div>
				
			</form>
			
		</div>
			
		<?php
		
	}
}
add_action( 'um_members_directory_search', 'um_members_directory_search' );


/**
 * Pre-display Member Directory
 *
 * @param $args
 */
function um_pre_directory_shortcode( $args ) {
	extract( $args );
	UM()->members()->results = UM()->members()->get_members( $args );
}
add_action( 'um_pre_directory_shortcode', 'um_pre_directory_shortcode' );


/**
 * Member Directory Header
 *
 * @param $args
 */
function um_members_directory_head( $args ) {
	extract( $args );
		
	if ( isset($_REQUEST['um_search']) ) {
		$is_filtering = 1;
	} else if ( UM()->is_filtering == 1 ) {
		$is_filtering = 1;
	} else {
		$is_filtering = 0;
	}
		
	if ( um_members('header') && $is_filtering && um_members('users_per_page') ) { ?>
		
		<div class="um-members-intro">
				
			<div class="um-members-total"><?php echo ( um_members('total_users') > 1 ) ? um_members('header') : um_members('header_single'); ?></div>
					
		</div>
			
	<?php }
}
add_action( 'um_members_directory_head', 'um_members_directory_head' );


/**
 * Member Directory Pagination
 *
 * @param $args
 */
function um_members_directory_pagination( $args ) {
	extract( $args );


	if ( isset( $args['search'] ) && $args['search'] == 1 && isset( $args['must_search'] ) && $args['must_search'] == 1 && !isset( $_REQUEST['um_search'] ) )
		return;
		
	if ( um_members('total_pages') > 1 ) { // needs pagination
		
		?>
		
		<div class="um-members-pagidrop uimob340-show uimob500-show">
			
			<?php _e('Jump to page:','ultimate-member'); ?>
			
			<?php if ( um_members('pages_to_show') && is_array( um_members('pages_to_show') ) ) { ?>
				<select onChange="window.location.href=this.value" class="um-s2" style="width: 100px">
					<?php foreach( um_members('pages_to_show') as $i ) { ?>
						<option value="<?php echo UM()->permalinks()->add_query( 'members_page', $i ); ?>" <?php selected($i, um_members('page')); ?>><?php printf(__('%s of %d','ultimate-member'), $i, um_members('total_pages') ); ?></option>
					<?php } ?>
				</select>
			<?php } ?>
		
		</div>
		
		<div class="um-members-pagi uimob340-hide uimob500-hide">
		
			<?php if ( um_members('page') != 1 ) { ?>
				<a href="<?php echo UM()->permalinks()->add_query( 'members_page', 1 ); ?>" class="pagi pagi-arrow um-tip-n" title="<?php _e('First Page','ultimate-member'); ?>"><i class="um-faicon-angle-double-left"></i></a>
			<?php } else { ?>
				<span class="pagi pagi-arrow disabled"><i class="um-faicon-angle-double-left"></i></span>
			<?php } ?>
			
			<?php if ( um_members('page') > 1 ) { ?>
				<a href="<?php echo UM()->permalinks()->add_query( 'members_page', um_members('page') - 1 ); ?>" class="pagi pagi-arrow um-tip-n" title="<?php _e('Previous','ultimate-member'); ?>"><i class="um-faicon-angle-left"></i></a>
			<?php } else { ?>
				<span class="pagi pagi-arrow disabled"><i class="um-faicon-angle-left"></i></span>
			<?php } ?>
			
			<?php if ( um_members('pages_to_show') && is_array( um_members('pages_to_show') ) ) { ?>
				<?php foreach( um_members('pages_to_show') as $i ) { ?>
		
					<?php if ( um_members('page') == $i ) { ?>
						<span class="pagi current"><?php echo $i; ?></span>
					<?php } else { ?>
				
						<a href="<?php echo UM()->permalinks()->add_query( 'members_page', $i ); ?>" class="pagi"><?php echo $i; ?></a>
				
					<?php } ?>
			
				<?php } ?>
			<?php } ?>
			
			<?php if ( um_members('page') != um_members('total_pages') ) { ?>
				<a href="<?php echo UM()->permalinks()->add_query( 'members_page', um_members('page') + 1 ); ?>" class="pagi pagi-arrow um-tip-n" title="<?php _e('Next','ultimate-member'); ?>"><i class="um-faicon-angle-right"></i></a>
			<?php } else { ?>
				<span class="pagi pagi-arrow disabled"><i class="um-faicon-angle-right"></i></span>
			<?php } ?>
			
			<?php if ( um_members('page') != um_members('total_pages') ) { ?>
				<a href="<?php echo UM()->permalinks()->add_query( 'members_page', um_members('total_pages') ); ?>" class="pagi pagi-arrow um-tip-n" title="<?php _e('Last Page','ultimate-member'); ?>"><i class="um-faicon-angle-double-right"></i></a>
			<?php } else { ?>
				<span class="pagi pagi-arrow disabled"><i class="um-faicon-angle-double-right"></i></span>
			<?php } ?>
			
		</div>
			
		<?php
		
	}
		
}
add_action( 'um_members_directory_footer', 'um_members_directory_pagination' );


/**
 * Member Directory Display
 *
 * @param $args
 */
function um_members_directory_display( $args ) {
	extract( $args );
		
	if ( isset( $args['search'] ) && $args['search'] == 1 && isset( $args['must_search'] ) && $args['must_search'] == 1 && !isset( $_REQUEST['um_search'] ) )
		return;
		
		if ( um_members('no_users') ) {
		
		?>
		
		<div class="um-members-none">
			<p><?php echo $args['no_users']; ?></p>
		</div>
			
		<?php

		}
		
		$file = um_path . 'templates/members-grid.php';
		$theme_file = get_stylesheet_directory() . '/ultimate-member/templates/members-grid.php';
		
		if ( file_exists( $theme_file )  ){
			$file = $theme_file;
		}

		include $file;

	}
add_action( 'um_members_directory_display', 'um_members_directory_display' );