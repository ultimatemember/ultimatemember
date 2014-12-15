<div class="um-admin-metabox">

	<p><label for="_um_template"><?php _e('Template','ultimatemember'); ?></label>
		<select name="_um_template" id="_um_template" class="umaf-selectjs" style="width: 100%">

			<?php foreach($ultimatemember->shortcodes->get_templates( 'members' ) as $key => $value) { ?>
			
			<option value="<?php echo $key; ?>" <?php selected($key, $ultimatemember->query->get_meta_value('_um_template' ) ); ?>><?php echo $value; ?></option>
			
			<?php } ?>
			
		</select>
	</p>
	
</div>