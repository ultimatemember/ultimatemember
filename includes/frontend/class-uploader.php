<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Uploader
 *
 * @package um\frontend
 */
class Uploader {
	/**
	 * Uploader constructor.
	 */
	public function __construct() {
		add_filter( 'um_upload_item_placeholder', array( $this, 'field_image_list_item_placeholder' ), 10, 2 );
		add_filter( 'um_upload_item_placeholder', array( $this, 'field_file_list_item_placeholder' ), 10, 2 );
		add_filter( 'um_upload_edit_list_item_row', array( $this, 'field_image_edit_list_item_row' ), 10, 3 );
		add_filter( 'um_upload_edit_list_item_row', array( $this, 'field_file_edit_list_item_row' ), 10, 3 );
	}

	/**
	 * @param $value
	 * @param $args
	 *
	 * @return false|string
	 */
	public function field_image_list_item_placeholder( $value, $args ) {
		if ( ! isset( $args['handler'] ) || 'field-image' !== $args['handler'] ) {
			return $value;
		}

		ob_start();
		?>
		<div class="um-uploader-file-placeholder um-uploader-image-file-placeholder um-display-none">
			<div class="um-uploader-file-data">
				<div class="um-uploader-file-preview-error um-display-none"></div>
				<div class="um-uploader-user-photos-data-wrapper">
					<div class="um-uploader-file-preview" title="{{{name}}}"></div>
					<div class="um-uploader-file-data-header">
						<div class="um-uploader-file-data-header-info">
							<div class="um-uploader-file-name">{{{name}}}</div>
							<?php
							$button_content = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
								<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
								<path d="M4 7l16 0" />
								<path d="M10 11l0 6" />
								<path d="M14 11l0 6" />
								<path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
								<path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
							</svg>';
							$button_args    = array(
								'type'          => 'button',
								'icon_position' => 'content',
								'design'        => 'link-gray',
								'size'          => 's',
								'classes'       => array( 'um-uploader-file-remove' ),
							);
							echo wp_kses( UM()->frontend()::layouts()::button( $button_content, $button_args ), UM()->get_allowed_html( 'templates' ) );
							?>
						</div>
						<div class="um-supporting-text">{{{supporting}}}</div>
						<?php echo wp_kses( UM()->frontend()::layouts()::progress_bar( array( 'label' => 'right' ) ), UM()->get_allowed_html( 'templates' ) ); ?>
					</div>
				</div>
				<?php
				if ( true !== $args['async'] ) {
					$name          = $args['multiple'] ? $args['name'] . '[{{{file_id}}}][path]' : $args['name'] . '[path]';
					$filename_name = $args['multiple'] ? $args['name'] . '[{{{file_id}}}][filename]' : $args['name'] . '[filename]';
					$hash_name     = $args['multiple'] ? $args['name'] . '[{{{file_id}}}][hash]' : $args['name'] . '[hash]';
					?>
					<input type="hidden" class="um-uploaded-value" data-field="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $name ); ?>" value="" disabled />
					<input type="hidden" class="um-uploaded-filename" data-field="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $filename_name ); ?>" value="" disabled />
					<input type="hidden" class="um-uploaded-value-hash" name="<?php echo esc_attr( $hash_name ); ?>" value="" disabled />
					<?php
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param $value
	 * @param $args
	 *
	 * @return false|string
	 */
	public function field_file_list_item_placeholder( $value, $args ) {
		if ( ! isset( $args['handler'] ) || 'field-file' !== $args['handler'] ) {
			return $value;
		}

		ob_start();
		?>
		<div class="um-uploader-file-placeholder um-uploader-image-file-placeholder um-display-none">
			<div class="um-uploader-file-data">
				<div class="um-uploader-file-preview-error um-display-none"></div>
				<div class="um-uploader-user-photos-data-wrapper">
					<div class="um-uploader-file-preview" title="{{{name}}}"></div>
					<div class="um-uploader-file-data-header">
						<div class="um-uploader-file-data-header-info">
							<div class="um-uploader-file-name">{{{name}}}</div>
							<?php
							$button_content = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
								<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
								<path d="M4 7l16 0" />
								<path d="M10 11l0 6" />
								<path d="M14 11l0 6" />
								<path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
								<path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
							</svg>';
							$button_args    = array(
								'type'          => 'button',
								'icon_position' => 'content',
								'design'        => 'link-gray',
								'size'          => 's',
								'classes'       => array( 'um-uploader-file-remove' ),
							);
							echo wp_kses( UM()->frontend()::layouts()::button( $button_content, $button_args ), UM()->get_allowed_html( 'templates' ) );
							?>
						</div>
						<div class="um-supporting-text">{{{supporting}}}</div>
						<?php echo wp_kses( UM()->frontend()::layouts()::progress_bar( array( 'label' => 'right' ) ), UM()->get_allowed_html( 'templates' ) ); ?>
					</div>
				</div>
				<?php
				if ( true !== $args['async'] ) {
					$name          = $args['multiple'] ? $args['name'] . '[{{{file_id}}}][path]' : $args['name'] . '[path]';
					$filename_name = $args['multiple'] ? $args['name'] . '[{{{file_id}}}][filename]' : $args['name'] . '[filename]';
					$hash_name     = $args['multiple'] ? $args['name'] . '[{{{file_id}}}][hash]' : $args['name'] . '[hash]';
					?>
					<input type="hidden" class="um-uploaded-value" data-field="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $name ); ?>" value="" disabled />
					<input type="hidden" class="um-uploaded-filename" data-field="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $filename_name ); ?>" value="" disabled />
					<input type="hidden" class="um-uploaded-value-hash" name="<?php echo esc_attr( $hash_name ); ?>" value="" disabled />
					<?php
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param bool|string $value
	 * @param array       $args
	 * @param mixed       $edit_value_row
	 *
	 * @return false|string
	 */
	public function field_image_edit_list_item_row( $value, $args, $edit_value_row ) {
		if ( ! isset( $args['handler'] ) || 'field-image' !== $args['handler'] ) {
			return $value;
		}

		$photo_id        = $edit_value_row['photo_id'];
		$post_title      = $edit_value_row['title'];
		$preview_url     = $edit_value_row['preview_url'];
		$image_filename  = $edit_value_row['filename'];
		$caption         = $edit_value_row['caption'];
		$related_link    = $edit_value_row['related_link'];

		ob_start();
		?>
		<div class="um-uploader-file" id="album-photo-<?php echo esc_attr( $photo_id ); ?>">
			<div class="um-uploader-file-data">
				<div class="um-uploader-file-preview-error um-display-none"></div>
				<div class="um-uploader-user-photos-data-wrapper">
					<div class="um-uploader-file-preview" title="<?php echo esc_attr( $post_title ); ?>">
						<img src="<?php echo esc_url( $preview_url ); ?>" alt="<?php echo esc_attr( $image_filename ); ?>" />
					</div>
					<div class="um-uploader-file-data-header">
						<div class="um-uploader-file-data-header-info">
							<div class="um-uploader-file-name"><?php echo esc_html( $post_title ); ?></div>
							<?php
							$button_content = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
								<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
								<path d="M4 7l16 0" />
								<path d="M10 11l0 6" />
								<path d="M14 11l0 6" />
								<path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
								<path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
							</svg>';
							echo wp_kses(
								UM()->frontend()::layouts()::button(
									'',
									array(
										'type'          => 'button',
										'design'        => 'link-gray',
										'size'          => 's',
										'icon_position' => 'content',
										'icon'          => $button_content,
										'title'         => __( 'Delete photo', 'um-user-photos' ),
										'classes'       => array( 'um-user-photos-delete-photo' ),
										'data'          => array(
											'id'           => $photo_id,
											'delete_photo' => '#album-photo-' . esc_attr( $photo_id ),
											'wpnonce'      => wp_create_nonce( 'um_delete_photo' ),
										),
									)
								),
								UM()->get_allowed_html( 'templates' )
							);
							?>
						</div>
					</div>
				</div>
				<?php
				echo wp_kses(
					UM()->frontend()::layouts()::button(
						__( 'More info', 'um-user-photos' ),
						array(
							'size'          => 's',
							'icon'          => '<span class="um-toggle-chevron"></span>',
							'icon_position' => 'trailing',
							'data'          => array(
								'um-toggle' => '.um-image-data-' . $photo_id,
							),
							'classes'       => array(
								'um-uploader-file-more-info',
							),
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				?>
				<div class="um-image-data-<?php echo esc_attr( $photo_id ); ?> um-toggle-block um-toggle-block-collapsed">
					<div class="um-toggle-block-inner">
						<?php $name = $args['multiple'] ? $args['name'] . '[' . $photo_id . '][order]' : $args['name'] . '[order]'; ?>
						<div class="um-form-rows _um_row_1">
							<div class="um-form-row">
								<div class="um-form-cols um-form-cols-1">
									<div class="um-form-col um-form-col-1">
										<div class="um-field um-field-text um-field-type_text">
											<?php $name = $args['multiple'] ? $args['name'] . '[' . $photo_id . '][title]' : $args['name'] . '[title]'; ?>
											<label for="um-photo-title-<?php echo esc_attr( $photo_id ); ?>">
												<?php esc_html_e( 'Image title', 'um-user-photos' ); ?>
												<?php if ( UM()->options()->get( 'form_asterisk' ) ) { ?>
													<span class="um-req" title="<?php esc_attr_e( 'Required', 'um-user-photos' ); ?>">*</span>
												<?php } ?>
											</label>
											<input id="um-photo-title-<?php echo esc_attr( $photo_id ); ?>" class="um-photo-title" type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $post_title ); ?>" title="<?php esc_attr_e( 'Image title', 'um-user-photos' ); ?>" required />
										</div>
										<div class="um-field um-field-textarea um-field-type_textarea">
											<?php $name = $args['multiple'] ? $args['name'] . '[' . $photo_id . '][caption]' : $args['name'] . '[caption]'; ?>
											<label for="um-photo-caption-<?php echo esc_attr( $photo_id ); ?>"><?php esc_html_e( 'Image caption', 'um-user-photos' ); ?></label>
											<textarea id="um-photo-caption-<?php echo esc_attr( $photo_id ); ?>" class="um-photo-caption" name="<?php echo esc_attr( $name ); ?>" title="<?php esc_attr_e( 'Image caption', 'um-user-photos' ); ?>"><?php echo esc_textarea( $caption ); ?></textarea>
										</div>
										<div class="um-field um-field-url um-field-type_url">
											<?php $name = $args['multiple'] ? $args['name'] . '[' . $photo_id . '][link]' : $args['name'] . '[link]'; ?>
											<label for="um-photo-link-<?php echo esc_attr( $photo_id ); ?>"><?php esc_html_e( 'Related link', 'um-user-photos' ); ?></label>
											<input id="um-photo-link-<?php echo esc_attr( $photo_id ); ?>" class="um-photo-link" type="url" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_url( $related_link ); ?>" title="<?php esc_attr_e( 'Related link', 'um-user-photos' ); ?>" />
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param bool|string $value
	 * @param array       $args
	 * @param mixed       $edit_value_row
	 *
	 * @return false|string
	 */
	public function field_file_edit_list_item_row( $value, $args, $edit_value_row ) {
		if ( ! isset( $args['handler'] ) || 'field-file' !== $args['handler'] ) {
			return $value;
		}

		$photo_id        = $edit_value_row['photo_id'];
		$post_title      = $edit_value_row['title'];
		$preview_url     = $edit_value_row['preview_url'];
		$image_filename  = $edit_value_row['filename'];
		$caption         = $edit_value_row['caption'];
		$related_link    = $edit_value_row['related_link'];

		ob_start();
		?>
		<div class="um-uploader-file" id="album-photo-<?php echo esc_attr( $photo_id ); ?>">
			<div class="um-uploader-file-data">
				<div class="um-uploader-file-preview-error um-display-none"></div>
				<div class="um-uploader-user-photos-data-wrapper">
					<div class="um-uploader-file-preview" title="<?php echo esc_attr( $post_title ); ?>">
						<img src="<?php echo esc_url( $preview_url ); ?>" alt="<?php echo esc_attr( $image_filename ); ?>" />
					</div>
					<div class="um-uploader-file-data-header">
						<div class="um-uploader-file-data-header-info">
							<div class="um-uploader-file-name"><?php echo esc_html( $post_title ); ?></div>
							<?php
							$button_content = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
								<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
								<path d="M4 7l16 0" />
								<path d="M10 11l0 6" />
								<path d="M14 11l0 6" />
								<path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
								<path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
							</svg>';
							echo wp_kses(
								UM()->frontend()::layouts()::button(
									'',
									array(
										'type'          => 'button',
										'design'        => 'link-gray',
										'size'          => 's',
										'icon_position' => 'content',
										'icon'          => $button_content,
										'title'         => __( 'Delete photo', 'um-user-photos' ),
										'classes'       => array( 'um-user-photos-delete-photo' ),
										'data'          => array(
											'id'           => $photo_id,
											'delete_photo' => '#album-photo-' . esc_attr( $photo_id ),
											'wpnonce'      => wp_create_nonce( 'um_delete_photo' ),
										),
									)
								),
								UM()->get_allowed_html( 'templates' )
							);
							?>
						</div>
					</div>
				</div>
				<?php
				echo wp_kses(
					UM()->frontend()::layouts()::button(
						__( 'More info', 'um-user-photos' ),
						array(
							'size'          => 's',
							'icon'          => '<span class="um-toggle-chevron"></span>',
							'icon_position' => 'trailing',
							'data'          => array(
								'um-toggle' => '.um-image-data-' . $photo_id,
							),
							'classes'       => array(
								'um-uploader-file-more-info',
							),
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				?>
				<div class="um-image-data-<?php echo esc_attr( $photo_id ); ?> um-toggle-block um-toggle-block-collapsed">
					<div class="um-toggle-block-inner">
						<?php $name = $args['multiple'] ? $args['name'] . '[' . $photo_id . '][order]' : $args['name'] . '[order]'; ?>
						<div class="um-form-rows _um_row_1">
							<div class="um-form-row">
								<div class="um-form-cols um-form-cols-1">
									<div class="um-form-col um-form-col-1">
										<div class="um-field um-field-text um-field-type_text">
											<?php $name = $args['multiple'] ? $args['name'] . '[' . $photo_id . '][title]' : $args['name'] . '[title]'; ?>
											<label for="um-photo-title-<?php echo esc_attr( $photo_id ); ?>">
												<?php esc_html_e( 'Image title', 'um-user-photos' ); ?>
												<?php if ( UM()->options()->get( 'form_asterisk' ) ) { ?>
													<span class="um-req" title="<?php esc_attr_e( 'Required', 'um-user-photos' ); ?>">*</span>
												<?php } ?>
											</label>
											<input id="um-photo-title-<?php echo esc_attr( $photo_id ); ?>" class="um-photo-title" type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $post_title ); ?>" title="<?php esc_attr_e( 'Image title', 'um-user-photos' ); ?>" required />
										</div>
										<div class="um-field um-field-textarea um-field-type_textarea">
											<?php $name = $args['multiple'] ? $args['name'] . '[' . $photo_id . '][caption]' : $args['name'] . '[caption]'; ?>
											<label for="um-photo-caption-<?php echo esc_attr( $photo_id ); ?>"><?php esc_html_e( 'Image caption', 'um-user-photos' ); ?></label>
											<textarea id="um-photo-caption-<?php echo esc_attr( $photo_id ); ?>" class="um-photo-caption" name="<?php echo esc_attr( $name ); ?>" title="<?php esc_attr_e( 'Image caption', 'um-user-photos' ); ?>"><?php echo esc_textarea( $caption ); ?></textarea>
										</div>
										<div class="um-field um-field-url um-field-type_url">
											<?php $name = $args['multiple'] ? $args['name'] . '[' . $photo_id . '][link]' : $args['name'] . '[link]'; ?>
											<label for="um-photo-link-<?php echo esc_attr( $photo_id ); ?>"><?php esc_html_e( 'Related link', 'um-user-photos' ); ?></label>
											<input id="um-photo-link-<?php echo esc_attr( $photo_id ); ?>" class="um-photo-link" type="url" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_url( $related_link ); ?>" title="<?php esc_attr_e( 'Related link', 'um-user-photos' ); ?>" />
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
