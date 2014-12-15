<div class="um <?php echo $this->get_class( $mode ); ?> um-<?php echo $form_id; ?>">

	<div class="um-form">
	
		<form method="post" action="">
			
			<?php do_action('um_account_page_hidden_fields', $args ); ?>
			
			<div class="um-account-side">
			
				<?php do_action('um_account_user_photo_hook', $args ); ?>
				
				<?php do_action('um_account_display_tabs_hook', $args ); ?>

			</div>
			
			<div class="um-account-main" data-current_tab="<?php echo $ultimatemember->account->current_tab; ?>">
			
				<?php
				
				foreach( $ultimatemember->account->tabs as $k => $arr ) {
				
					foreach( $arr as $id => $info ) { extract( $info );
					
						if ( um_get_option('account_tab_'.$id ) == 1 || $id == 'general' ) {
					
							echo '<div class="um-account-tab um-account-tab-'.$id.'" data-tab="'.$id.'">';
							do_action("um_account_tab__{$id}", $info );
							echo '</div>';
						
						}
						
					}
					
				}
				
				?>
				
			</div><div class="um-clear"></div>
			
		</form>
	
	</div>
	
</div>