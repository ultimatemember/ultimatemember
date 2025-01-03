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
		add_filter( 'um_upload_edit_list_item_row', array( $this, 'field_image_edit_list_item_row' ), 10, 2 );

		add_filter( 'um_upload_item_placeholder', array( $this, 'field_file_list_item_placeholder' ), 10, 2 );
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

		$label = isset( $args['field_data']['label'] ) ? $args['field_data']['label'] : __( 'Untitled photo', 'ultimate-member' );

		ob_start();
		?>
		<div class="um-uploader-file-placeholder um-display-none">
			<div class="um-uploader-file-preview" title="<?php /* translators: %s is the field label. */ echo esc_attr( sprintf( __( 'Preview %s', 'ultimate-member' ), $label ) ); ?>"></div>
			<div class="um-uploader-file-data">
				<div class="um-file-extension">
					<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--um-gray-300, #d0d5dd)" fill="none" stroke-linecap="round" stroke-linejoin="round">
						<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
						<path d="M14 3v4a1 1 0 0 0 1 1h4" />
						<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
					</svg>
					<span class="um-file-extension-text">{{{extension}}}</span>
				</div>
				<div class="um-uploader-file-uploading-process">
					<?php echo wp_kses( UM()->frontend()::layouts()::progress_bar( array( 'label' => 'bottom' ) ), UM()->get_allowed_html( 'templates' ) ); ?>
					<div class="um-supporting-text">{{{supporting}}}</div>
				</div>
				<?php
				$name      = $args['name'] . '[path]';
				$hash_name = $args['name'] . '[hash]';
				?>
				<input type="hidden" class="um-uploaded-value" data-field="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $name ); ?>" value="" disabled />
				<input type="hidden" class="um-uploaded-value-hash" name="<?php echo esc_attr( $hash_name ); ?>" value="" disabled />
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param bool|string $value
	 * @param array       $args
	 *
	 * @return false|string
	 */
	public function field_image_edit_list_item_row( $value, $args ) {
		if ( ! isset( $args['handler'] ) || 'field-image' !== $args['handler'] ) {
			return $value;
		}

		$removed = false;
		if ( ! file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $value ) ) {
			if ( is_multisite() ) {
				//multisite fix for old customers
				$file_path = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $value );
				if ( ! file_exists( $file_path ) ) {
					$removed = true;
				}
			} else {
				$removed = true;
			}
		}

		ob_start();
		?>
		<div class="um-uploader-file">
			<?php
			if ( $removed ) {
				esc_html_e( 'This image has been removed.', 'ultimate-member' );
			} else {
				$uri   = UM()->fields()->get_download_link( UM()->fields()->set_id, $args['data']['metakey'], um_user( 'ID' ) );
				$label = isset( $args['field_data']['label'] ) ? $args['field_data']['label'] : __( 'Untitled photo', 'ultimate-member' );
				?>
				<div class="um-uploader-file-preview" title="<?php /* translators: %s is the field label. */echo esc_attr( sprintf( __( 'Preview %s', 'ultimate-member' ), $label ) ); ?>">
					<?php
					echo wp_kses(
						UM()->frontend()::layouts()::lazy_image(
							$uri,
							array(
								'width' => '100%',
								'alt'   => $label,
							)
						),
						UM()->get_allowed_html( 'templates' )
					);
					?>
				</div>
				<div class="um-uploader-file-data">
					<?php
					$name      = $args['name'] . '[path]';
					$hash_name = $args['name'] . '[hash]';
					?>
					<input type="hidden" class="um-uploaded-value" data-field="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $name ); ?>" value="" disabled />
					<input type="hidden" class="um-uploaded-value-hash" name="<?php echo esc_attr( $hash_name ); ?>" value="" disabled />
				</div>
				<?php
			}
			?>
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
		<div class="um-uploader-file-placeholder um-display-none">
			<div class="um-uploader-file-data">
				<div class="um-file-extension">
					<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--um-gray-300, #d0d5dd)" fill="none" stroke-linecap="round" stroke-linejoin="round">
						<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
						<path d="M14 3v4a1 1 0 0 0 1 1h4" />
						<path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
					</svg>
					<span class="um-file-extension-text">{{{extension}}}</span>
				</div>
				<div class="um-uploader-file-name">{{{name}}}</div>
			</div>
			<div class="um-uploader-file-uploading-process">
				<?php echo wp_kses( UM()->frontend()::layouts()::progress_bar( array( 'label' => 'bottom' ) ), UM()->get_allowed_html( 'templates' ) ); ?>
				<div class="um-supporting-text">{{{supporting}}}</div>
			</div>
			<?php
			$name      = $args['name'] . '[path]';
			$hash_name = $args['name'] . '[hash]';
			?>
			<input type="hidden" class="um-uploaded-value" data-field="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $name ); ?>" value="" disabled />
			<input type="hidden" class="um-uploaded-value-hash" name="<?php echo esc_attr( $hash_name ); ?>" value="" disabled />
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

		$file_type = wp_check_filetype( $edit_value_row );

		$removed = false;
		if ( ! file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $value ) ) {
			if ( is_multisite() ) {
				//multisite fix for old customers
				$file_path = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $value );
				if ( ! file_exists( $file_path ) ) {
					$removed = true;
				}
			} else {
				$removed = true;
			}
		}

		ob_start();
		?>
		<div class="um-uploader-file">
			<?php
			if ( $removed ) {
				esc_html_e( 'This file has been removed.', 'ultimate-member' );
			} else {
				$icon             = UM()->frontend()::layouts()::get_file_extension_icon( $file_type['ext'] );
				$file_field_value = '';
				$file_info        = um_user( $args['data']['metakey'] . '_metadata' );
				if ( ! empty( $file_info['original_name'] ) ) {
					$file_field_value = $file_info['original_name'];
				}
				?>
				<div class="um-uploader-file-data">
					<?php echo wp_kses( $icon, UM()->get_allowed_html( 'templates' ) ); ?>
					<div class="um-uploader-file-name"><?php echo esc_html( $file_field_value ); ?></div>
				</div>
				<?php
				$name      = $args['name'] . '[path]';
				$hash_name = $args['name'] . '[hash]';
				?>
				<input type="hidden" class="um-uploaded-value" data-field="<?php echo esc_attr( $args['field_id'] ); ?>" name="<?php echo esc_attr( $name ); ?>" value="" disabled />
				<input type="hidden" class="um-uploaded-value-hash" name="<?php echo esc_attr( $hash_name ); ?>" value="" disabled />
				<?php
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
