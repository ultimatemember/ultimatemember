<?php namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Form
 *
 * @package um\frontend
 */
class Form {

	/**
	 * @var bool
	 *
	 * @since 3.0
	 */
	public $form_data;

	/**
	 * @var string
	 *
	 * @since 3.0
	 */
	public $error_class = 'um-form-error-row';

	/**
	 * @var array
	 */
	public $errors = array();

	/**
	 * @var array
	 */
	public $notices = array();

	/**
	 * @var array
	 *
	 * @since 3.0
	 */
	public $types = array(
		'text',
		'password',
		'tel',
		'date',
		'email',
		'number',
		'time',
		'url',
		'hidden',
		'select',
		'checkbox',
		'radio',
		'textarea',
		'wp_editor',
//		'media',
		'file',
		'image',
		'label',
		'divider',
		'block',
		'shortcode',
		'spacing',

//		'color',
		'month',
		'week',
//		'range',
	);

	/**
	 * @var array
	 *
	 * @since 3.0
	 */
	public $no_label_types = array(
		'hidden',
		'label',
		'divider',
		'block',
		'shortcode',
		'spacing',
	);

	/**
	 * Forms constructor.
	 *
	 * @param bool|array $form_data
	 */
	public function __construct( $form_data = false ) {
		if ( $form_data ) {
			$this->form_data = $form_data;
		}
	}

	/**
	 * Set Form Data
	 *
	 * @param array $data
	 *
	 * @return self
	 *
	 * @since 3.0
	 */
	public function set_data( $data ) {
		$this->form_data = $data;
		return $this;
	}

	/**
	 * Render form
	 *
	 *
	 * @param bool $echo
	 * @return string
	 *
	 * @since 3.0
	 */
	public function display( $echo = true ) {
		if ( empty( $this->form_data['fields'] ) && empty( $this->form_data['sections'] ) && empty( $this->form_data['hiddens'] ) ) {
			return '';
		}

		$id     = isset( $this->form_data['id'] ) ? $this->form_data['id'] : 'um-frontend-form-' . uniqid();
		$name   = isset( $this->form_data['name'] ) ? $this->form_data['name'] : $id;
		$action = isset( $this->form_data['action'] ) ? $this->form_data['action'] : '';
		$method = isset( $this->form_data['method'] ) ? $this->form_data['method'] : 'post';
		$class  = isset( $this->form_data['class'] ) ? $this->form_data['class'] : '';
		$class  = ! empty( $class ) ? 'um-form ' . $class : 'um-form';

		if ( array_key_exists( 'buttons', $this->form_data ) && 1 === count( $this->form_data['buttons'] ) ) {
			$class .= ' um-single-button';
		}

		$data_attrs = isset( $this->form_data['data'] ) ? $this->form_data['data'] : array();
		$data_attr  = '';
		foreach ( $data_attrs as $key => $val ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $val ) . '" ';
		}

		$hidden = '';
		if ( ! empty( $this->form_data['hiddens'] ) ) {
			foreach ( $this->form_data['hiddens'] as $field_id => $value ) {
				$hidden .= $this->render_form_hidden( $field_id, $value );
			}
		}

		$hidden .= '<input type="hidden" name="' . esc_attr( UM()->honeypot ) . '" id="' . esc_attr( UM()->honeypot . '_' . $id ) . '" value="' . esc_attr( UM()->honeypot . '_' . $id ) . '" size="25" autocomplete="off" />';

		$fields = '';
		if ( ! empty( $this->form_data['fields'] ) ) {
			foreach ( $this->form_data['fields'] as $data ) {
				if ( ! $this->validate_type( $data ) ) {
					continue;
				}

				$fields .= $this->render_form_row( $data );
			}
		} else {
			if ( ! empty( $this->form_data['sections'] ) ) {
				foreach ( $this->form_data['sections'] as $section_key => $section_data ) {
					$section_data['key'] = $section_key;
					$fields             .= $this->render_section( $section_data );
				}
			}
		}

		$buttons = '';
		if ( ! empty( $this->form_data['buttons'] ) ) {
			foreach ( $this->form_data['buttons'] as $field_id => $data ) {
				$buttons .= $this->render_button( $field_id, $data );
			}
		}

		ob_start();

		if ( $this->has_notices() ) {
			foreach ( $this->get_notices() as $notice ) {
				?>
				<span class="um-frontend-form-notice"><?php echo wp_kses( $notice, UM()->get_allowed_html( 'templates' ) ); ?></span>
				<?php
			}
		}

		if ( $this->has_error( 'global' ) ) {
			foreach ( $this->get_error( 'global' ) as $error ) {
				?>
				<span class="um-frontend-form-error"><?php echo wp_kses( $error, UM()->get_allowed_html( 'templates' ) ); ?></span>
				<?php
			}
		}

		/**
		 * Filters the state when Ultimate Member form opening tag <form> must be moved to the 3rd-party handler.
		 *
		 * @since 3.0
		 * @hook um_forms_move_form_tag
		 *
		 * @param {bool} $move_form_tag Whether we should move the form opening tag <form>. Defaults to false.
		 *
		 * @return {bool} If true, the form opening tag <form> must be displayed in the 3rd-party callback.
		 */
		$move_form_tag = apply_filters( 'um_forms_move_form_tag', false );

		if ( ! $move_form_tag ) {
			echo wp_kses( '<form action="' . esc_attr( $action ) . '" method="' . esc_attr( $method ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" ' . $data_attr . '>', UM()->get_allowed_html( 'templates' ) );
		}

		echo wp_kses( $fields . $hidden . '<div class="um-form-buttons-section">' . $buttons . '</div>', UM()->get_allowed_html( 'templates' ) );

		/**
		 * Fires in the form footer before closing tag in the form.
		 * This hook may be used to display custom content in the form footer.
		 *
		 * Note: For checking the form on where you need to add content - use $form_data['id']
		 *
		 * Legacy v2.x hooks: 'um_after_form_fields', 'um_change_password_form' ( 'um-resetpass' === $form_data['id'] in v3 ), 'um_reset_password_form' ( 'um-lostpassword' == $form_data['id'] in v3 )
		 *
		 * @since 3.0.0
		 * @hook um_form_footer
		 *
		 * @param {array} $form_data UM Form data.
		 */
		do_action( 'um_form_footer', $this->form_data );
		?>

		</form>

		<?php
		remove_all_filters( 'um_forms_move_form_tag' );

		if ( $echo ) {
			ob_get_flush();
			return '';
		} else {
			return ob_get_clean();
		}
	}

	/**
	 * Validate type of the field
	 *
	 * @param array $data
	 *
	 * @return bool
	 *
	 * @since 3.0
	 */
	public function validate_type( $data ) {
		$types = apply_filters( 'um_form_field_types', $this->types );
		return ( ! empty( $data['type'] ) && in_array( $data['type'], $types, true ) );
	}

	/**
	 * Get field value
	 *
	 * @param array $field_data
	 * @param string $i
	 * @return string|array
	 *
	 * @since 3.0
	 */
	public function get_field_value( $field_data, $i = '' ) {
		// phpcs:disable WordPress.Security.NonceVerification -- there is already verified
		if ( 'password' === $field_data['type'] ) {
			return '';
		}

		$default = '';
		$default = isset( $field_data[ 'default' . $i ] ) ? $field_data[ 'default' . $i ] : $default;

		if ( 'checkbox' === $field_data['type'] ) {
			$value = ( isset( $field_data[ 'value' . $i ] ) && '' !== $field_data[ 'value' . $i ] ) ? $field_data[ 'value' . $i ] : $default;
		} else {
			$value = isset( $field_data[ 'value' . $i ] ) ? $field_data[ 'value' . $i ] : $default;
		}

		$name = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
		if ( ! empty( $this->form_data['prefix_id'] ) ) {
			if ( isset( $_POST[ $this->form_data['prefix_id'] ][ $name ] ) ) {
				if ( is_array( $_POST[ $this->form_data['prefix_id'] ][ $name ] ) ) {
					$value = array_map( 'sanitize_text_field', $_POST[ $this->form_data['prefix_id'] ][ $name ] );
				} else {
					$value = sanitize_text_field( $_POST[ $this->form_data['prefix_id'] ][ $name ] );
				}
			}
		} else {
			if ( isset( $_POST[ $name ] ) ) {
				if ( is_array( $_POST[ $name ] ) ) {
					$value = array_map( 'sanitize_text_field', $_POST[ $name ] );
				} else {
					$value = sanitize_text_field( $_POST[ $name ] );
				}
			}
		}

		$value = is_string( $value ) ? stripslashes( $value ) : $value;

		if ( ! empty( $value ) ) {
			if ( ! empty( $this->form_data['prefix_id'] ) ) {
				if ( isset( $field_data['encode'] ) && ! isset( $_POST[ $this->form_data['prefix_id'] ][ $name ] ) ) {
					$value = wp_json_encode( $value, JSON_UNESCAPED_UNICODE );
				}
			} else {
				if ( isset( $field_data['encode'] ) && ! isset( $_POST[ $name ] ) ) {
					$value = wp_json_encode( $value, JSON_UNESCAPED_UNICODE );
				}
			}
		}

		return $value;
		// phpcs:enable WordPress.Security.NonceVerification -- there is already verified
	}

	/**
	 * Render form row
	 *
	 * @param array $data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_form_row( $data ) {
		if ( ! $this->validate_type( $data ) ) {
			return '';
		}

		if ( ! in_array( $data['type'], array( 'divider', 'block', 'shortcode' ), true ) && empty( $data['id'] ) ) {
			return '';
		}

		if ( method_exists( $this, 'render_' . $data['type'] ) ) {
			$field_html = call_user_func( array( &$this, 'render_' . $data['type'] ), $data );
		} else {
			$field_html = apply_filters( "um_form_field_{$data['type']}_rendered_html", '', $data, $this->form_data );
		}

		if ( empty( $field_html ) ) {
			return '';
		}

		$row_classes = array( 'um-form-row', 'um-field-' . $data['type'] . '-type' );
		if ( $this->has_error( $data['id'] ) ) {
			$row_classes[] = $this->error_class;
		}

		if ( array_key_exists( 'row', $data ) && false === $data['row'] ) {
			return wp_kses( $field_html, UM()->get_allowed_html( 'templates' ) );
		}

		ob_start();
		?>

		<div class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>">
			<?php echo wp_kses( $this->render_field_label( $data ), UM()->get_allowed_html( 'templates' ) ); ?>

			<span class="um-form-field-content">
				<?php echo wp_kses( $field_html, UM()->get_allowed_html( 'templates' ) ); ?>

				<?php if ( ! empty( $data['description'] ) ) { ?>
					<span class="um-form-field-description">
						<?php echo wp_kses( $data['description'], UM()->get_allowed_html( 'templates' ) ); ?>
					</span>
				<?php } ?>

				<?php if ( $this->has_error( $data['id'] ) ) { ?>
					<span class="um-form-field-error">
						<?php echo wp_kses( $this->get_error( $data['id'] ), UM()->get_allowed_html( 'templates' ) ); ?>
					</span>
				<?php } ?>
			</span>
		</div>

		<?php
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * Render form section
	 *
	 * @param array $data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_section( $data ) {
		$html = '';

		if ( ! empty( $data['title'] ) ) {
			$html .= '<h3 class="um-form-section-title">' . $data['title'] . '</h3>';
		}

		/**
		 * Filters the section content before its render.
		 *
		 * @since 3.0
		 * @hook um_forms_before_render_section
		 *
		 * @param {string}         $html         Default HTML before the section render start. It's <h3> title by default.
		 * @param {array}          $section_data Section data.
		 * @param {array}          $form_data    Frontend form data.
		 *
		 * @return {string} Custom HTML before the rendered section.
		 */
		$html = apply_filters( 'um_forms_before_render_section', $html, $data, $this->form_data );

		if ( ! empty( $data['wrap_fields'] ) ) {
			$strict = ! empty( $data['strict_wrap_attrs'] ) ? $data['strict_wrap_attrs'] : '';

			$html .= '<div class="um-form-section-fields-wrapper" data-key="' . esc_attr( $data['key'] ) . '"' . $strict . '>';
		}

		if ( ! empty( $data['fields'] ) ) {
			foreach ( $data['fields'] as $fields_data ) {
				if ( ! $this->validate_type( $fields_data ) ) {
					continue;
				}

				$html .= $this->render_form_row( $fields_data );
			}
		}

		if ( ! empty( $data['wrap_fields'] ) ) {
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Render field label
	 *
	 * @param array $data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_label( $data ) {
		return '<p>' . $data['label'] . '</p>';
	}

	/**
	 * Render button
	 *
	 * @param string $id
	 * @param array $data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_button( $id, $data ) {
		$type  = isset( $data['type'] ) ? $data['type'] : 'submit';
		$name  = isset( $data['name'] ) ? $data['name'] : $id;
		$label = isset( $data['label'] ) ? $data['label'] : __( 'Submit', 'ultimate-member' );
		$class = isset( $data['class'] ) ? $data['class'] : array();
		$class = is_array( $class ) ? $class : array( $class );

		$classes   = array_merge( array( 'um-button', 'um-form-button' ), $class );
		$classes[] = 'um-form-button-' . $type;

		$data = isset( $data['data'] ) ? $data['data'] : array();

		$data_attr = '';
		foreach ( $data as $key => $val ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $val ) . '" ';
		}

		ob_start();
		?>

		<label class="screen-reader-text" for="um-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>

		<?php
		echo wp_kses( '<input id="um-' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $label ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '" name="' . esc_attr( $name ) . '" ' . $data_attr . ' />', UM()->get_allowed_html( 'templates' ) );
		return ob_get_clean();
	}

	/**
	 * Render hidden field
	 *
	 * @param string $id
	 * @param string $value
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_form_hidden( $id, $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $id;
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$data = array( 'field_id' => $id );

		$data_attr = '';
		foreach ( $data as $key => $val ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $val ) . '" ';
		}

		$name      = $id;
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';

		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$html = "<input type=\"hidden\" $id_attr $name_attr $data_attr $value_attr />";

		return $html;
	}

	/**
	 * Render field label
	 *
	 * @param array $data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_field_label( $data ) {
		if ( empty( $data['label'] ) ) {
			return '';
		}

		if ( in_array( $data['type'], $this->no_label_types, true ) ) {
			return '';
		}

		// if field argument is directly used. e.g. bool checkbox
		if ( ! empty( $data['hide_label'] ) ) {
			return '';
		}

		$id       = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $data['id'];
		$for_attr = ' for="' . $id . '" ';

		$label = $data['label'];

		/**
		 * Filters the condition for disabling the "required" star in the form field label.
		 *
		 * @since 3.0
		 * @hook um_frontend_forms_required_star_disabled
		 *
		 * @param {bool} $disable_star Whether we should disable the "required" star in the form field label. Defaults to false.
		 *
		 * @return {bool} If true, the "required" star will be hidden.
		 */
		$disable_star = apply_filters( 'um_frontend_forms_required_star_disabled', false );
		if ( ! empty( $data['required'] ) && ! $disable_star ) {
			$label = $label . '<span class="um-req" title="' . esc_attr__( 'Required', 'ultimate-member' ) . '">*</span>';
		}

		$helptip = ! empty( $data['helptip'] ) ? ' ' . UM()->tooltip( $data['helptip'] ) : '';

		return "<label $for_attr class=\"um-form-row-label\">{$label}{$helptip}</label>";
	}

	/**
	 * Render media uploader field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_media( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		if ( empty( $field_data['action'] ) ) {
			return '';
		}

		$thumb_w    = get_option( 'thumbnail_size_w' );
		$thumb_h    = get_option( 'thumbnail_size_h' );
		$thumb_crop = get_option( 'thumbnail_crop', false );

		$id = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];

		$name = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
		$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;

		$img_alt      = isset( $field_data['labels']['img_alt'] ) ? $field_data['labels']['img_alt'] : __( 'Selected image', 'ultimate-member' );
		$select_label = isset( $field_data['labels']['select'] ) ? $field_data['labels']['select'] : __( 'Select file', 'ultimate-member' );
		$change_label = isset( $field_data['labels']['change'] ) ? $field_data['labels']['change'] : __( 'Change', 'ultimate-member' );
		$remove_label = isset( $field_data['labels']['remove'] ) ? $field_data['labels']['remove'] : __( 'Remove', 'ultimate-member' );
		$cancel_label = isset( $field_data['labels']['cancel'] ) ? $field_data['labels']['cancel'] : __( 'Cancel', 'ultimate-member' );

		$wrapper_classes = array( 'um-uploaded-wrapper', 'um-' . $id . '-wrapper' );
		if ( ! empty( $field_data['value'] ) ) {
			$wrapper_classes = array_merge( $wrapper_classes, array( 'um-uploaded', 'um-' . $id . '-uploaded' ) );
		}
		$wrapper_classes = implode( ' ', $wrapper_classes );

		$img_style = $thumb_crop ? 'style="object-fit: cover;"' : '';

		$uploader_classes = array( 'um-uploader', 'um-' . $id . '-uploader' );
		if ( ! empty( $field_data['value'] ) ) {
			$uploader_classes = array_merge( $uploader_classes, array( 'um-uploaded', 'um-' . $id . '-uploaded' ) );
		}
		$uploader_classes = implode( ' ', $uploader_classes );

		$value = ! empty( $field_data['value'] ) ? $field_data['value'] : '';

		ob_start();
		?>

		<span class="<?php echo esc_attr( $wrapper_classes ); ?>">
			<span class="um-uploaded-content-wrapper um-<?php echo esc_attr( $id ); ?>-image-wrapper" style="width: <?php echo esc_attr( $thumb_w ); ?>px;height: <?php echo esc_attr( $thumb_h ); ?>px;">
				<?php echo wp_kses( '<img src="' . ( ! empty( $field_data['value'] ) ? esc_url( $field_data['value'] ) : '' ) . '" alt="' . esc_attr( $img_alt ) . '" ' . $img_style . ' />', UM()->get_allowed_html( 'templates' ) ); ?>
			</span>
			<a class="um-cancel-change-media" href="#"><?php echo esc_html( $cancel_label ); ?></a>
			<a class="um-change-media" href="#"><?php echo esc_html( $change_label ); ?></a>&nbsp;&nbsp;|&nbsp;&nbsp;
			<a class="um-clear-media" href="#"><?php echo esc_html( $remove_label ); ?></a>
		</span>

		<span class="<?php echo esc_attr( $uploader_classes ); ?>">
			<span id="um_<?php echo esc_attr( $id ); ?>_filelist" class="um-uploader-dropzone">
				<span><?php esc_html_e( 'Drop file to upload', 'ultimate-member' ); ?></span>
				<span><?php esc_html_e( 'or', 'ultimate-member' ); ?></span>
				<div class="um-select-media-button-wrapper">
					<input type="button" class="um-select-media" data-action="<?php echo esc_attr( $field_data['action'] ); ?>" id="um_<?php echo esc_attr( $id ); ?>_plupload" value="<?php echo esc_attr( $select_label ); ?>" />
				</div>
			</span>

			<span id="um-<?php echo esc_attr( $id ); ?>-errorlist" class="um-uploader-errorlist"></span>
		</span>
		<input type="hidden" class="um-media-value" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
		<input type="hidden" class="um-media-value-hash" id="<?php echo esc_attr( $id ); ?>_hash" name="<?php echo esc_attr( $name ); ?>_hash" value="" />

		<?php
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * Render text field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_text( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
		$required         = ! empty( $field_data['required'] ) ? ' required' : '';

		$disabled = ! empty( $field_data['disabled'] ) ? 'disabled' : '';

		$name      = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';

		$value      = $this->get_field_value( $field_data );
		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$html = "<input type=\"text\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $required $disabled />";

		return $html;
	}

	/**
	 * Render color field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
//	public function render_color( $field_data ) {
//		if ( empty( $field_data['id'] ) ) {
//			return '';
//		}
//
//		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
//		$id_attr = ' id="' . esc_attr( $id ) . '" ';
//
//		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
//		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
//		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';
//
//		$data = array( 'field_id' => $field_data['id'] );
//
//		$data_attr = '';
//		foreach ( $data as $key => $value ) {
//			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
//		}
//
//		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
//		$required         = ! empty( $field_data['required'] ) ? ' required' : '';
//
//		$name      = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
//		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
//		$name_attr = ' name="' . esc_attr( $name ) . '" ';
//
//		$value      = $this->get_field_value( $field_data );
//		$value_attr = ' value="' . esc_attr( $value ) . '" ';
//
//		$html = "<input type=\"color\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $required />";
//
//		return $html;
//	}

	/**
	 * Render email field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_email( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$min_attr = ( array_key_exists( 'minlength', $field_data ) && is_numeric( $field_data['minlength'] ) ) ? ' minlength="' . esc_attr( $field_data['minlength'] ) . '" ' : '';
		$max_attr = ( array_key_exists( 'maxlength', $field_data ) && is_numeric( $field_data['maxlength'] ) ) ? ' maxlength="' . esc_attr( $field_data['maxlength'] ) . '" ' : '';

		$pattern_attr = ! empty( $field_data['pattern'] ) ? ' pattern="' . esc_attr( $field_data['pattern'] ) . '" ' : '';

		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
		$required         = ! empty( $field_data['required'] ) ? ' required' : '';

		$name      = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';

		$value      = $this->get_field_value( $field_data );
		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$html = "<input type=\"email\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $min_attr $max_attr $pattern_attr $required />";

		return $html;
	}

	/**
	 * Render month field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
//	public function render_month( $field_data ) {
//		if ( empty( $field_data['id'] ) ) {
//			return '';
//		}
//
//		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
//		$id_attr = ' id="' . esc_attr( $id ) . '" ';
//
//		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
//		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
//		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';
//
//		$data = array( 'field_id' => $field_data['id'] );
//
//		$data_attr = '';
//		foreach ( $data as $key => $value ) {
//			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
//		}
//
//		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
//		$required         = ! empty( $field_data['required'] ) ? ' required' : '';
//
//		$name      = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
//		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
//		$name_attr = ' name="' . esc_attr( $name ) . '" ';
//
//		$value      = $this->get_field_value( $field_data );
//		$value_attr = ' value="' . esc_attr( $value ) . '" ';
//
//		$html = "<input type=\"month\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $required />";
//
//		return $html;
//	}

	/**
	 * Render range field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
//	public function render_range( $field_data ) {
//		if ( empty( $field_data['id'] ) ) {
//			return '';
//		}
//
//		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
//		$id_attr = ' id="' . esc_attr( $id ) . '" ';
//
//		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
//		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
//		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';
//
//		$data = array( 'field_id' => $field_data['id'] );
//
//		$data_attr = '';
//		foreach ( $data as $key => $value ) {
//			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
//		}
//
//		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
//		$required         = ! empty( $field_data['required'] ) ? ' required' : '';
//
//		$name      = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
//		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
//		$name_attr = ' name="' . esc_attr( $name ) . '" ';
//
//		$value      = $this->get_field_value( $field_data );
//		$value_attr = ' value="' . esc_attr( $value ) . '" ';
//
//		$html = "<input type=\"range\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $required />";
//
//		return $html;
//	}

	/**
	 * Render week field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
//	public function render_week( $field_data ) {
//		if ( empty( $field_data['id'] ) ) {
//			return '';
//		}
//
//		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
//		$id_attr = ' id="' . esc_attr( $id ) . '" ';
//
//		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
//		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
//		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';
//
//		$data = array( 'field_id' => $field_data['id'] );
//
//		$data_attr = '';
//		foreach ( $data as $key => $value ) {
//			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
//		}
//
//		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
//		$required         = ! empty( $field_data['required'] ) ? ' required' : '';
//
//		$name      = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
//		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
//		$name_attr = ' name="' . esc_attr( $name ) . '" ';
//
//		$value      = $this->get_field_value( $field_data );
//		$value_attr = ' value="' . esc_attr( $value ) . '" ';
//
//		$html = "<input type=\"week\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $required />";
//
//		return $html;
//	}

	/**
	 * Render date field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_date( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$required = ! empty( $field_data['required'] ) ? ' required' : '';

		$min_attr = ! empty( $field_data['min'] ) ? ' min="' . esc_attr( $field_data['min'] ) . '" ' : '';
		$max_attr = ! empty( $field_data['max'] ) ? ' max="' . esc_attr( $field_data['max'] ) . '" ' : '';

		$name      = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';

		$value      = $this->get_field_value( $field_data );
		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$html = "<input type=\"date\" $id_attr $class_attr $name_attr $data_attr $value_attr $min_attr $max_attr $required />";

		return $html;
	}

	/**
	 * Render time field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_time( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$required = ! empty( $field_data['required'] ) ? ' required' : '';

		$min_attr = ! empty( $field_data['min'] ) ? ' min="' . esc_attr( $field_data['min'] ) . '" ' : '';
		$max_attr = ! empty( $field_data['max'] ) ? ' max="' . esc_attr( $field_data['max'] ) . '" ' : '';

		$step_attr = ! empty( $field_data['step'] ) ? ' step="' . esc_attr( $field_data['step'] ) . '" ' : '';

		$name      = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';

		$value      = $this->get_field_value( $field_data );
		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$html = "<input type=\"time\" $id_attr $class_attr $name_attr $data_attr $value_attr $min_attr $max_attr $step_attr $required />";

		return $html;
	}

	/**
	 * Render password field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_password( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$min_attr = ( array_key_exists( 'minlength', $field_data ) && is_numeric( $field_data['minlength'] ) ) ? ' minlength="' . esc_attr( $field_data['minlength'] ) . '" ' : '';
		$max_attr = ( array_key_exists( 'maxlength', $field_data ) && is_numeric( $field_data['maxlength'] ) ) ? ' maxlength="' . esc_attr( $field_data['maxlength'] ) . '" ' : '';

		$pattern_attr = ! empty( $field_data['pattern'] ) ? ' pattern="' . esc_attr( $field_data['pattern'] ) . '" ' : '';

		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
		$required         = ! empty( $field_data['required'] ) ? ' required' : '';

		$name      = $field_data['id'];
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';

		$value      = $this->get_field_value( $field_data );
		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$html = "<input type=\"password\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $pattern_attr $min_attr $max_attr $required />";

		return $html;
	}

	/**
	 * Render number field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_number( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$min_attr = ( array_key_exists( 'min', $field_data ) && is_numeric( $field_data['min'] ) ) ? ' min="' . esc_attr( $field_data['min'] ) . '" ' : '';
		$max_attr = ( array_key_exists( 'max', $field_data ) && is_numeric( $field_data['max'] ) ) ? ' max="' . esc_attr( $field_data['max'] ) . '" ' : '';

		$step_attr = ( array_key_exists( 'step', $field_data ) && is_numeric( $field_data['step'] ) ) ? ' step="' . esc_attr( $field_data['step'] ) . '" ' : '';

		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '"' : '';
		$required         = ! empty( $field_data['required'] ) ? ' required' : '';

		$name      = $field_data['id'];
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';

		$value      = $this->get_field_value( $field_data );
		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$html = "<input type=\"number\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $required $min_attr $max_attr $step_attr />";

		return $html;
	}

	/**
	 * Render telephone field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_tel( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$min_attr = ( array_key_exists( 'minlength', $field_data ) && is_numeric( $field_data['minlength'] ) ) ? ' minlength="' . esc_attr( $field_data['minlength'] ) . '" ' : '';
		$max_attr = ( array_key_exists( 'maxlength', $field_data ) && is_numeric( $field_data['maxlength'] ) ) ? ' maxlength="' . esc_attr( $field_data['maxlength'] ) . '" ' : '';

		$pattern_attr = ! empty( $field_data['pattern'] ) ? ' pattern="' . esc_attr( $field_data['pattern'] ) . '" ' : '';

		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
		$required         = ! empty( $field_data['required'] ) ? ' required' : '';

		$name      = $field_data['id'];
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';

		$value      = $this->get_field_value( $field_data );
		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$html = "<input type=\"tel\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $min_attr $max_attr $pattern_attr $required />";

		return $html;
	}

	/**
	 * Render telephone field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_url( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$min_attr = ( array_key_exists( 'minlength', $field_data ) && is_numeric( $field_data['minlength'] ) ) ? ' minlength="' . esc_attr( $field_data['minlength'] ) . '" ' : '';
		$max_attr = ( array_key_exists( 'maxlength', $field_data ) && is_numeric( $field_data['maxlength'] ) ) ? ' maxlength="' . esc_attr( $field_data['maxlength'] ) . '" ' : '';

		$pattern_attr = ! empty( $field_data['pattern'] ) ? ' pattern="' . esc_attr( $field_data['pattern'] ) . '" ' : '';

		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
		$required         = ! empty( $field_data['required'] ) ? ' required' : '';

		$name      = $field_data['id'];
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';

		$value      = $this->get_field_value( $field_data );
		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$html = "<input type=\"url\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $min_attr $max_attr $pattern_attr $required />";

		return $html;
	}

	/**
	 * Render hidden field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_hidden( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$data = array( 'field_id' => $id );

		$data_attr = '';
		foreach ( $data as $key => $val ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $val ) . '" ';
		}

		$name      = $id;
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';

		$value = array_key_exists( 'value', $field_data ) ? $field_data['value'] : '';
		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$html = "<input type=\"hidden\" $id_attr $name_attr $data_attr $value_attr />";

		return $html;
	}

	/**
	 * Render location autocomplete field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_location_autocomplete( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
		$class_attr = ' class="um-forms-field um-location-autocomplete ' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
		$required         = ! empty( $field_data['required'] ) ? ' required' : '';

		$name                = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
		$name                = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr           = ' name="' . esc_attr( $name ) . '" ';
		$name_loco_data_attr = ' name="' . esc_attr( $name ) . '_data" ';

		$value      = $this->get_field_value( $field_data );
		$value_attr = ' value="' . esc_attr( $value ) . '" ';

		$field_data_data           = $field_data;
		$field_data_data['name']   = $name . '_data';
		$field_data_data['value']  = $field_data['value_data'];
		$field_data_data['encode'] = true;

		$value_data = $this->get_field_value( $field_data_data );
		$value_data = esc_attr( $value_data );

		$html = "<input type=\"text\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr $required />
				 <input type=\"hidden\" $name_loco_data_attr class=\"um-location-autocomplete-data\" value=\"$value_data\" />";

		return $html;
	}

	/**
	 * Render dropdown field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_select( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		if ( empty( $field_data['ignore_predefined_options'] ) && empty( $field_data['options'] ) ) {
			return '';
		}

		$multiple = ! empty( $field_data['multi'] ) ? 'multiple' : '';

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? ' ' . $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? ' um-' . $field_data['size'] . '-field' : ' um-long-field';
		$class_attr = ' class="um-forms-field' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );
		$data = ! empty( $field_data['data'] ) ? array_merge( $data, $field_data['data'] ) : $data;

		$data['placeholder'] = ! empty( $data['placeholder'] ) ? $data['placeholder'] : __( 'Please select...', 'ultimate-member' );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$name             = $field_data['id'];
		$name             = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$hidden_name_attr = ' name="' . esc_attr( $name ) . '" ';
		$name             = $name . ( ! empty( $field_data['multi'] ) ? '[]' : '' );
		$name_attr        = ' name="' . esc_attr( $name ) . '" ';

		$value = $this->get_field_value( $field_data );
		if ( ! empty( $field_data['multi'] ) ) {
			if ( ! is_array( $value ) || empty( $value ) ) {
				$value = array();
			}

			$value = array_map( 'strval', $value );
		}

		$added_values = array();
		if ( ! empty( $field_data['ignore_predefined_options'] ) ) {
			$added_values = $value;
		}

		$options = '';
		if ( ! empty( $field_data['options'] ) ) {
			foreach ( $field_data['options'] as $key => $option ) {
				if ( ! empty( $field_data['multi'] ) ) {
					if ( in_array( (string) $key, $value, true ) ) {
						unset( $added_values[ array_search( (string) $key, $added_values, true ) ] );
					}
					$options .= '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( (string) $key, $value, true ), true, false ) . '>' . esc_html( $option ) . '</option>';
				} else {
					$options .= '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $option ) . '</option>';
				}
			}
		}

		if ( ! empty( $added_values ) ) {
			foreach ( $added_values as $option ) {
				$options .= '<option value="' . esc_attr( $option ) . '" selected>' . esc_html( $option ) . '</option>';
			}
		}

		$hidden = '';
		if ( ! empty( $multiple ) ) {
			$hidden = "<input type=\"hidden\" $hidden_name_attr value=\"\" />";
		}
		$html = "$hidden<select $multiple $id_attr $name_attr $class_attr $data_attr>$options</select>";

		return $html;
	}

	/**
	 * Render checkbox field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_checkbox( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		if ( empty( $field_data['options'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];

		$class      = ! empty( $field_data['class'] ) ? ' ' . $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? ' um-' . $field_data['size'] . '-field' : ' um-long-field';
		$class_attr = ' class="um-forms-field' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );
		$data = ! empty( $field_data['data'] ) ? array_merge( $data, $field_data['data'] ) : $data;

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$name             = $field_data['id'];
		$name             = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$hidden_name_attr = ' name="' . esc_attr( $name ) . '" ';
		if ( count( $field_data['options'] ) > 1 ) {
			$name_attr        = ' name="' . esc_attr( $name ) . '[]" ';
		} else {
			$name_attr        = ' name="' . esc_attr( $name ) . '" ';
		}


		$value = $this->get_field_value( $field_data );
		$options = '';
		if ( 1 < count( $field_data['options'] ) ) {
			if ( ! is_array( $value ) || empty( $value ) ) {
				$value = array();
			}

			foreach ( $field_data['options'] as $key => $option ) {
				$id_attr  = ' id="' . esc_attr( $id . '_' . $key ) . '" ';
				$options .= '<label><input type="checkbox" ' . $id_attr . $name_attr . $class_attr . $data_attr . ' value="' . esc_attr( $key ) . '" ' . checked( in_array( (string) $key, $value, true ), true, false ) . ' />' . esc_html( $option ) . '</label>';
			}
		} else {
			foreach ( $field_data['options'] as $key => $option ) {
				$id_attr  = ' id="' . esc_attr( $id . '_' . $key ) . '" ';
				$options .= '<label><input type="checkbox" ' . $id_attr . $name_attr . $class_attr . $data_attr . ' value="' . esc_attr( $key ) . '" ' . checked( $value, true, false ) . ' />' . esc_html( $option ) . '</label>';
			}
		}

		$columns_layout = ! empty( $field_data['columns_layout'] ) ? $field_data['columns_layout'] : 'um-col-1';

		$hidden = "<input type=\"hidden\" $hidden_name_attr value=\"\" />";
		$html = "$hidden <span class=\"" . esc_attr( $columns_layout ) . "\">$options</span>";

		return $html;
	}

	/**
	 * Render radio field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_radio( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		if ( empty( $field_data['options'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];

		$class      = ! empty( $field_data['class'] ) ? ' ' . $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? ' um-' . $field_data['size'] . '-field' : ' um-long-field';
		$class_attr = ' class="um-forms-field' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );
		$data = ! empty( $field_data['data'] ) ? array_merge( $data, $field_data['data'] ) : $data;

		$data['placeholder'] = ! empty( $data['placeholder'] ) ? $data['placeholder'] : __( 'Please select...', 'ultimate-member' );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$name             = $field_data['id'];
		$name             = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr        = ' name="' . esc_attr( $name ) . '" ';

		$value = $this->get_field_value( $field_data );

		$columns_layout = ! empty( $field_data['columns_layout'] ) ? $field_data['columns_layout'] : 'um-col-1';

		$html = '<span class="' . esc_attr( $columns_layout ) . '">';
		foreach ( $field_data['options'] as $key => $option ) {
			$id_attr = ' id="' . esc_attr( $id . '_' . $key ) . '" ';
			$html .= '<label><input type="radio" ' . $id_attr . $name_attr . $class_attr . $data_attr . ' value="' . esc_attr( $key ) . '" ' . checked( $key , $value, false ) . ' />' . esc_html( $option ) . '</label>';
		}
		$html .= '</span>';

		return $html;
	}

	/**
	 * Render textarea field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_textarea( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class     .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
		$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';
		$required         = ! empty( $field_data['required'] ) ? ' required' : '';

		$name      = isset( $field_data['name'] ) ? $field_data['name'] : $field_data['id'];
		$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name_attr = ' name="' . esc_attr( $name ) . '" ';
		$value     = $this->get_field_value( $field_data );

		$rows      = ( ! empty( $field_data['rows'] ) && is_numeric( $field_data['rows'] ) ) ? $field_data['rows'] : 5;
		$rows_attr = ' rows="' . esc_attr( $rows ) . '" ';

		$html = "<textarea $id_attr $class_attr $name_attr $data_attr $placeholder_attr $rows_attr $required>" . esc_textarea( $value ) . "</textarea>";
		return $html;
	}

	/**
	 * Render WP Editor field
	 *
	 * @param array $field_data
	 *
	 * @return string
	 *
	 * @since 3.0
	 */
	public function render_wp_editor( $field_data ) {
		if ( empty( $field_data['id'] ) ) {
			return '';
		}

		$id = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] : '' ) . '_' . $field_data['id'];

		$data = array( 'field_id' => $field_data['id'] );

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
		}

		$name = $field_data['id'];
		$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;

		$value = $this->get_field_value( $field_data );

		add_filter( 'mce_buttons', array( $this, 'filter_mce_buttons' ), 10, 2 );

		add_action(
			'after_wp_tiny_mce',
			function( $settings ) {
				if ( isset( $settings['_job_description']['plugins'] ) && false !== strpos( $settings['_job_description']['plugins'], 'wplink' ) ) {
					?>
					<script>
						jQuery("#link-selector > .howto, #link-selector > #search-panel").remove();
					</script>
					<?php
				}
			}
		);

		/**
		 * Filters the WP_Editor options.
		 *
		 * @since 3.0
		 * @hook um_content_editor_options
		 *
		 * @param {array} $editor_settings WP_Editor field's settings. See the all settings here https://developer.wordpress.org/reference/classes/_wp_editors/parse_settings/#parameters
		 * @param {array} $field_data      Frontend form's field data.
		 *
		 * @return {array} WP_Editor field's settings.
		 */
		$editor_settings = apply_filters(
			'um_content_editor_options',
			array(
				'textarea_name' => $name,
				'wpautop'       => true,
				'textarea_rows' => ( ! empty( $field_data['rows'] ) && is_numeric( $field_data['rows'] ) ) ? $field_data['rows'] : 5,
				'media_buttons' => false,
				'quicktags'     => false,
				'editor_css'    => '<style> .mce-top-part button { background-color: rgba(0,0,0,0.0) !important; } </style>',
				'tinymce'       => array(
					'init_instance_callback' => "function (editor) {
													editor.on( 'keyup paste mouseover', function (e) {
													var content = editor.getContent( { format: 'html' } ).trim();
													var textarea = jQuery( '#' + editor.id );
													textarea.val( content ).trigger( 'keyup' ).trigger( 'keypress' ).trigger( 'keydown' ).trigger( 'change' ).trigger( 'paste' ).trigger( 'mouseover' );
												});}",
				),
			),
			$field_data
		);

		ob_start();

		wp_editor( $value, $id, $editor_settings );

		$editor_contents = ob_get_clean();

		remove_filter( 'mce_buttons', array( $this, 'filter_mce_buttons' ), 10 );

		return $editor_contents;
	}

	/**
	 * Remove unusable MCE button for UM WP Editors
	 *
	 * @param array $mce_buttons
	 * @param int $editor_id
	 *
	 * @return array
	 *
	 * @since 3.0
	 */
	public function filter_mce_buttons( $mce_buttons, $editor_id ) {
		$mce_buttons = array_diff( $mce_buttons, array( 'alignright', 'alignleft', 'aligncenter', 'wp_adv', 'wp_more', 'fullscreen', 'formatselect', 'spellchecker' ) );
		/**
		 * Filters the WP_Editor MCE buttons list.
		 *
		 * @since 3.0
		 * @hook um_rich_text_editor_buttons
		 *
		 * @param {array}  $mce_buttons TinyMCE buttons. See the list of buttons here https://developer.wordpress.org/reference/hooks/mce_buttons/
		 * @param {string} $editor_id   WP_Editor ID.
		 * @param {object} $form        Frontend form class (\um\frontend\Forms) instance.
		 *
		 * @return {array} TinyMCE buttons.
		 */
		$mce_buttons = apply_filters( 'um_rich_text_editor_buttons', $mce_buttons, $editor_id, $this );

		return $mce_buttons;
	}


	public function render_divider( $field_data ) {
		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? ' ' . $field_data['class'] : '';
		$class     .= ! empty( $field_data['style'] ) ? ' um-forms-divider-' . $field_data['style'] . '-style' : '';
		$class_attr = ' class="um-forms-field' . esc_attr( $class ) . '" ';

		$style  = ! empty( $field_data['width'] ) ? 'border-top-width: ' . esc_attr( $field_data['width'] ) . 'px;' : 'border-top-width: 1px;';
		$style .= ! empty( $field_data['color'] ) ? 'border-color: ' . esc_attr( $field_data['color'] ) . ';' : 'border-color: #475467;'; // grey-600 if empty

		$style = ! empty( $style ) ? ' style="' . esc_attr( $style ) . '" ' : '';

		$data      = array( 'field_id' => $field_data['id'] );
		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		if ( ! empty( $field_data['divider_text'] ) ) {
			$html = "<span class=\"um-forms-field-text-divider\"><hr $class_attr $data_attr $style /><span>" . esc_html( $field_data['divider_text'] ) . "</span><hr $class_attr $data_attr $style /></span>";
		} else {
			$html = "<hr $id_attr $class_attr $data_attr $style />";
		}
		return $html;
	}

	public function render_block( $field_data ) {
		if ( ! array_key_exists( 'content', $field_data ) ) {
			return '';
		}

		return wp_kses( $field_data['content'], UM()->get_allowed_html( 'templates' ) );
	}

	public function render_shortcode( $field_data ) {
		if ( ! array_key_exists( 'content', $field_data ) ) {
			return '';
		}

		return apply_shortcodes( $field_data['content'] );
	}

	public function render_spacing( $field_data ) {
		$id      = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '_' : '' ) . $field_data['id'];
		$id_attr = ' id="' . esc_attr( $id ) . '" ';

		$class      = ! empty( $field_data['class'] ) ? ' ' . $field_data['class'] : '';
		$class_attr = ' class="um-forms-field' . esc_attr( $class ) . '" ';

		$size = ! empty( $field_data['size'] ) ? 'style="height: ' . esc_attr( $field_data['size'] ) . ';"' : '';

		$data      = array( 'field_id' => $field_data['id'] );
		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
		}

		$html = "<span $id_attr $class_attr $data_attr $size></span>";
		return $html;
	}

	/**
	 * Add form error
	 *
	 * @param string $field
	 * @param string $text
	 *
	 * @since 3.0
	 */
	public function add_error( $field, $text ) {
		if ( 'global' === $field ) {
			if ( ! isset( $this->errors['global'] ) ) {
				$this->errors['global'] = array();
			}
			/**
			 * Filters the frontend form global errors.
			 *
			 * @since 3.0
			 * @hook um_form_global_error
			 *
			 * @param {string} $text Global error text.
			 *
			 * @return {string} Custom singular global error.
			 */
			$this->errors['global'][] = apply_filters( 'um_form_global_error', $text );
		} else {
			if ( ! isset( $this->errors[ $field ] ) ) {
				/**
				 * Filters the frontend form error related to the field.
				 *
				 * @since 3.0
				 * @hook um_form_error
				 *
				 * @param {string} $text  Error text.
				 * @param {string} $field Field ID. E.g. 'company_name', etc.
				 *
				 * @return {string} Error text.
				 */
				$this->errors[ $field ] = apply_filters( 'um_form_error', $text, $field );
			}
		}
	}


	/**
	 * Add form notice
	 *
	 * @param string $text
	 * @param string $key
	 *
	 * @since 3.0
	 */
	public function add_notice( $text, $key ) {
		/**
		 * Filters the frontend form notices based on the notice key.
		 *
		 * @since 3.0
		 * @hook um_form_notice
		 *
		 * @param {string} $text Notice text.
		 * @param {string} $key  Notice key. E.g. 'on-moderation', etc.
		 *
		 * @return {string} Notice text.
		 */
		$this->notices[ $key ] = apply_filters( 'um_form_notice', $text, $key );
	}

	/**
	 * Remove form notice
	 *
	 * @param string $key
	 *
	 * @since 3.0
	 */
	public function remove_notice( $key ) {
		if ( array_key_exists( $key, $this->notices ) ) {
			unset( $this->notices[ $key ] );
		}
	}

	/**
	 * If a form has error by field key
	 *
	 * @param  string $field
	 * @return boolean
	 *
	 * @since 3.0
	 */
	public function has_error( $field ) {
		return ! empty( $this->errors[ $field ] ) || ! empty( $this->errors[ $field ] );
	}

	/**
	 * If a form has errors
	 *
	 * @return boolean
	 *
	 * @since 3.0
	 */
	public function has_errors() {
		return ! empty( $this->errors );
	}

	/**
	 * If a form has notices
	 *
	 * @return boolean
	 *
	 * @since 3.0
	 */
	public function has_notices() {
		return ! empty( $this->notices );
	}

	/**
	 * Flush errors
	 *
	 * @since 3.0
	 */
	public function flush_errors() {
		$this->errors = array();
	}

	/**
	 * Flush notices
	 *
	 * @since 3.0
	 */
	public function flush_notices() {
		$this->notices = array();
	}

	/**
	 * Get a form error by a field key
	 *
	 * @param string $field
	 *
	 * @return string|array
	 *
	 * @since 3.0
	 */
	public function get_error( $field ) {
		$default = 'global' === $field ? array() : '';
		return ! empty( $this->errors[ $field ] ) ? $this->errors[ $field ] : $default;
	}

	/**
	 * Get a form notices
	 *
	 * @return array
	 *
	 * @since 3.0
	 */
	public function get_notices() {
		return ! empty( $this->notices ) ? $this->notices : array();
	}
}
