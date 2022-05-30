<?php
namespace umm\member_directory\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Forms
 * @package umm\member_directory\includes\admin
 */
class Forms {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_filter( 'um_render_field_type_md_default_filters', array( &$this, 'render_md_default_filters' ), 10, 4 );
	}


	/**
	 * @param string $content
	 * @param array $field_data
	 * @param array $form_data
	 * @param \um\admin\core\Admin_Forms $admin_forms
	 *
	 * @return string
	 */
	function render_md_default_filters( $content, $field_data, $form_data, $admin_forms ) {
		if ( empty( $field_data['id'] ) ) {
			return $content;
		}
		global $post;

		$id = ( ! empty( $form_data['prefix_id'] ) ? $form_data['prefix_id'] : '' ) . '_' . $field_data['id'];

		$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
		$class .= ! empty( $field_data['size'] ) ? $field_data['size'] : 'um-long-field';
		$class_attr = ' class="um-forms-field ' . $class . '" ';

		$data = array(
			'field_id'         => $field_data['id'],
			'id_attr'          => $id,
			'member_directory' => $post->ID,
		);

		$data_attr = '';
		foreach ( $data as $key => $value ) {
			$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
		}

		$name = $field_data['id'];
		$name = ! empty( $form_data['prefix_id'] ) ? $form_data['prefix_id'] . '[' . $name . ']' : $name;
		$name = "{$name}[]";
		$name_attr = ' name="' . $name . '" ';

		$values = $admin_forms->get_field_value( $field_data );
		if ( is_array( $values ) ) {
			$filters = array_keys( $values );
		}

		$options = '';
		foreach ( $field_data['options'] as $key => $option ) {
			$options .= '<option value="' . $key . '">' . $option . '</option>';
		}

		$html = "<input type=\"hidden\" name=\"um-gmt-offset\" /><select class=\"um-hidden-md-default-filters\" $data_attr>$options</select>";
		$html .= "<ul class=\"um-md-default-filters-list\" $data_attr>";

		if ( ! empty( $filters ) && is_array( $filters ) ) {
			foreach ( $filters as $k => $value ) {

				if ( ! in_array( $value, array_keys( $field_data['options'] ) ) ) {
					continue ;
				}

				$id_attr = ' id="' . esc_attr( $id . '-' . $k ) . '" ';

				$options = '';
				foreach ( $field_data['options'] as $key => $option ) {
					$options .= '<option value="' . $key . '" ' . selected( $key == $value, true, false ) . '>' . $option . '</option>';
				}

				$html .= "<li class=\"um-md-default-filters-option-line\"><span class=\"um-field-wrapper\">
						<select $id_attr $name_attr $class_attr $data_attr>$options</select></span>
						<span class=\"um-field-control\"><a href=\"javascript:void(0);\" class=\"um-select-delete\">" . __( 'Remove', 'ultimate-member' ) . "</a></span><span class=\"um-field-wrapper2 um\">" . UM()->module( 'member-directory' )->frontend()->show_filter( $value, array( 'form_id' => $post->ID ), $values[ $value ], true ) . "</span></li>";
			}
		} elseif ( ! empty( $field_data['show_default_number'] ) && is_numeric( $field_data['show_default_number'] ) && $field_data['show_default_number'] > 0 ) {
			$i = 0;
			while ( $i < $field_data['show_default_number'] ) {
				$id_attr = ' id="' . $id . '-' . $i . '" ';

				$options = '';
				foreach ( $field_data['options'] as $key => $option ) {
					$options .= '<option value="' . $key . '">' . $option . '</option>';
				}

				$html .= "<li class=\"um-md-default-filters-option-line\"><span class=\"um-field-wrapper\">
						<select $id_attr $name_attr $class_attr $data_attr>$options</select></span>
						<span class=\"um-field-control\"><a href=\"javascript:void(0);\" class=\"um-select-delete\">" . __( 'Remove', 'ultimate-member' ) . "</a></span></li>";

				$i++;
			}
		}

		$html .= "</ul><a href=\"javascript:void(0);\" class=\"button button-primary um-md-default-filters-add-option\" data-name=\"$name\">{$field_data['add_text']}</a>";

		return $html;
	}
}
