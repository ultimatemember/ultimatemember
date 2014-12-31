<?php

	/***
	***	@member directory search
	***/
	add_action('um_members_directory_search', 'um_members_directory_search');
	function um_members_directory_search( $args ) {
		global $ultimatemember;
		
		$search_filters = '';
		
		if ( isset($args['search_fields']) ) {
			foreach( $args['search_fields'] as $k => $testfilter ){
				if ($testfilter && !in_array( $testfilter, (array)$search_filters ) ) {
					$search_filters[] = $testfilter;
				}
			}
		}
		
		$search_filters = apply_filters('um_frontend_member_search_filters',$search_filters);
			
		if ( $args['search'] == 1 && is_array( $search_filters ) ) { // search on
		
			$count = count( $search_filters );
			
			?>
			
			<div class="um-search um-search-<?php echo $count; ?>">
			
				<form method="get" action="" />
				
					<?php

					$i = 0;
					foreach( $search_filters as $filter ) {
					$i++;
						
						if ( $i % 2 == 0 ) {
							$add_class = 'um-search-filter-2';
						} else {
							$add_class = '';
						}
						
						echo '<div class="um-search-filter '. $add_class .'">'; $ultimatemember->members->show_filter( $filter ); echo '</div>';
					
					}
					
					?>
				
					<div class="um-clear"></div>
					
					<div class="um-search-submit">
					
						<input type="hidden" name="um_search" id="um_search" value="1" />
						
						<a href="#" class="um-button um-do-search"><?php _e('Search','ultimatemember'); ?></a><a href="<?php echo $ultimatemember->permalinks->get_current_url( true ); ?>" class="um-button um-alt"><?php _e('Reset','ultimatemember'); ?></a>
						
					</div><div class="um-clear"></div>
				
				</form>
			
			</div>
			
			<?php
		
		}
	}
	
	/***
	***	@pre-display members directory
	***/
	add_action('um_pre_directory_shortcode', 'um_pre_directory_shortcode');
	function um_pre_directory_shortcode($args){
		global $ultimatemember;
		extract( $args );
		
		$ultimatemember->members->results = $ultimatemember->members->get_members( $args );

	}
	
	/***
	***	@member directory header
	***/
	add_action('um_members_directory_head', 'um_members_directory_head');
	function um_members_directory_head( $args ) {
		global $ultimatemember;
		extract( $args );
		
		if ( um_members('header') && isset($_REQUEST['um_search']) && um_members('users_per_page') ) { ?>
		
			<div class="um-members-intro">
				
				<div class="um-members-total"><?php echo um_members('header'); ?></div>
					
			</div>
			
		<?php }
		
	}
	
	/***
	***	@member directory pagination
	***/
	add_action('um_members_directory_footer', 'um_members_directory_pagination');
	function um_members_directory_pagination( $args ) {
		global $ultimatemember;
		extract( $args );

		if ( um_members('total_pages') > 1 ) { // needs pagination
		
		?>
		
		<div class="um-members-pagidrop uimob500-show">
			
			<?php _e('Jump to page:','ultimatemember'); ?>
			
			<select onChange="window.location.href=this.value" class="um-s1" style="width: 100px">
				<?php foreach( um_members('pages_to_show') as $i ) { ?>
				<option value="<?php echo $ultimatemember->permalinks->add_query( 'members_page', $i ); ?>" <?php selected($i, um_members('page')); ?>><?php printf(__('%s of %d','ultimatemember'), $i, um_members('total_pages') ); ?></option>
				<?php } ?>
			</select>
		
		</div>
		
		<div class="um-members-pagi uimob500-hide">
		
			<?php if ( um_members('page') != 1 ) { ?>
			<a href="<?php echo $ultimatemember->permalinks->add_query( 'members_page', 1 ); ?>" class="pagi pagi-arrow um-tip-n" title="First Page"><i class="um-icon-angle-double-left"></i></a>
			<?php } else { ?>
			<span class="pagi pagi-arrow disabled"><i class="um-icon-angle-double-left"></i></span>
			<?php } ?>
			
			<?php if ( um_members('page') > 1 ) { ?>
			<a href="<?php echo $ultimatemember->permalinks->add_query( 'members_page', um_members('page') - 1 ); ?>" class="pagi pagi-arrow um-tip-n" title="Previous"><i class="um-icon-angle-left"></i></a>
			<?php } else { ?>
			<span class="pagi pagi-arrow disabled"><i class="um-icon-angle-left"></i></span>
			<?php } ?>
			
			<?php foreach( um_members('pages_to_show') as $i ) { ?>
		
				<?php if ( um_members('page') == $i ) { ?>
				<span class="pagi current"><?php echo $i; ?></span>
				<?php } else { ?>
				
				<a href="<?php echo $ultimatemember->permalinks->add_query( 'members_page', $i ); ?>" class="pagi"><?php echo $i; ?></a>
				
				<?php } ?>
			
			<?php } ?>
			
			<?php if ( um_members('page') != um_members('total_pages') ) { ?>
			<a href="<?php echo $ultimatemember->permalinks->add_query( 'members_page', um_members('page') + 1 ); ?>" class="pagi pagi-arrow um-tip-n" title="Next"><i class="um-icon-angle-right"></i></a>
			<?php } else { ?>
			<span class="pagi pagi-arrow disabled"><i class="um-icon-angle-right"></i></span>
			<?php } ?>
			
			<?php if ( um_members('page') != um_members('total_pages') ) { ?>
			<a href="<?php echo $ultimatemember->permalinks->add_query( 'members_page', um_members('total_pages') ); ?>" class="pagi pagi-arrow um-tip-n" title="Last Page"><i class="um-icon-angle-double-right"></i></a>
			<?php } else { ?>
			<span class="pagi pagi-arrow disabled"><i class="um-icon-angle-double-right"></i></span>
			<?php } ?>
			
		</div>
			
		<?php
		
		}
		
	}
		
	/***
	***	@member directory display
	***/
	add_action('um_members_directory_display', 'um_members_directory_display');
	function um_members_directory_display( $args ) {
		global $ultimatemember;

		extract( $args );
		
		if ( um_members('no_users') ) {
		
		?>
		
			<div class="um-members-none">
				<p><?php echo $args['no_users']; ?></p>
			</div>
			
		<?php
		
		}
		
		if ( um_members('users_per_page') ) {
		
			$default_size = str_replace( 'px', '', um_get_option('profile_photosize') );
			
		?>
		
			<div class="um-members">
			
				<div class="um-gutter-sizer"></div>
				
				<?php $i = 0; foreach( um_members('users_per_page') as $member) { $i++; um_fetch_user( $member ); ?>
			
				<div class="um-member <?php if ($cover_photos) { echo 'with-cover'; } ?>">
				
					<?php if ($cover_photos) { ?>
					<div class="um-member-cover" data-ratio="<?php echo um_get_option('profile_cover_ratio'); ?>">
						<div class="um-member-cover-e"><?php echo um_user('cover_photo', 300); ?></div>
					</div>
					<?php } ?>
		
					<?php if ($profile_photo) { ?>
					<div class="um-member-photo"><a href="<?php echo um_user_profile_url(); ?>"><?php echo um_user('profile_photo', $default_size ); ?></a></div>
					<?php } ?>
					
					<div class="um-member-card <?php if (!$profile_photo) { echo 'no-photo'; } ?>">
						
						<?php if ( $show_name ) { ?>
						<div class="um-member-name"><a href="<?php echo um_user_profile_url(); ?>"><?php echo um_user('display_name'); ?></a></div>
						<?php } ?>
						
						<?php
						if ( $show_tagline && is_array( $tagline_fields ) ) {
							foreach( $tagline_fields as $tagline_field ) {
								if ( $tagline_field && um_user( $tagline_field ) ) {
						?>
						
						<div class="um-member-tagline"><?php echo um_user( $tagline_field ); ?></div>
						
						<?php
								}
							}
						}
						?>
						
						<?php if ( $show_userinfo && is_array($reveal_fields ) ) { ?>
						
						<div class="um-member-meta-main">
						
						<?php if ( $userinfo_animate ) { ?>
						<div class="um-member-more"><a href="#"><i class="um-icon-chevron-down-1"></i></a></div>
						<?php } ?>
						
						<div class="um-member-meta <?php if ( !$userinfo_animate ) { echo 'no-animate'; } ?>">
						
							<?php foreach( $reveal_fields as $key ) {
									if ( $key && um_user( $key ) ) {
									
										$value = um_user( $key );
										$data = $ultimatemember->builtin->get_specific_field( $key );
										$type = (isset($data['type']))?$data['type']:'';
										
										$value = apply_filters("um_profile_field_filter_hook__", $value, $data );
										$value = apply_filters("um_profile_field_filter_hook__{$key}", $value, $data );
										$value = apply_filters("um_profile_field_filter_hook__{$type}", $value, $data );
										
							?>
							
							<div class="um-member-metaline"><i class="<?php echo $ultimatemember->fields->get_field_icon( $key ); ?>"></i><span><?php echo $value; ?></span></div>
							
							<?php 
								}
							} 
							?>
							
						</div>
						
						<div class="um-member-less"><a href="#"><i class="um-icon-chevron-up-1"></i></a></div>
						
						</div>
						
						<?php } ?>
						
						<?php if ( $show_social ) { ?>
						<div class="um-member-connect">
							<a href="#" style="background: #3B5999;"><i class="um-icon-facebook-2"></i></a>
							<a href="#" style="background: #4099FF;"><i class="um-icon-twitter"></i></a>
							<a href="#" style="background: #dd4b39;"><i class="um-icon-google-plus"></i></a>
						</div>
						<?php } ?>
						
					</div>
					
					<?php if ( $show_stats ) { ?>
					<div class="um-member-stats">
						<div class="um-member-stat"><a href="#">0<span class="um-member-statname">Views</span></a></div>
						<div class="um-member-stat"><a href="#"><?php echo (int) um_user('post_count'); ?><span class="um-member-statname">Posts</span></a></div>
						<div class="um-member-stat" style="border-right:0"><a href="#"><?php echo (int) um_user('comment_count'); ?><span class="um-member-statname">Comments</span></a></div>
						<div class="um-clear"></div>
					</div>
					<?php } ?>
					
				</div>
				
				<?php 
				
					um_reset_user_clean();
				
				} // end foreach
				
				um_reset_user();
				
				?>
				
				<div class="um-clear"></div>
				
			</div>
			
		<?php
		
		}

	}