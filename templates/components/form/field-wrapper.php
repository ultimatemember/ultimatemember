<?php
/**
 * Template for field wrapper
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/components/form/field-wrapper.php
 *
 * Page: "Account"
 *
 * @version 2.7.0
 *
 * @var string $mode
 * @var int    $form_id
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field_name  = $key . $form_suffix;
$field_value = $this->field_value( $key, $default, $data );
?>

<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>
	<?php
	if ( isset( $data['label'] ) ) {
		$output .= $this->field_label( $data['label'], $key, $data );
	}

	$output .= UM()->get_template( 'components/form/' . $data['type'] . '.php', '', array(  ) );

	$output .= '<input ' . $disabled . ' autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
	if ( ! empty( $disabled ) ) {
		$output .= $this->disabled_hidden_field( $field_name, $field_value );
	}

	if ( $this->is_error( $key ) ) {
		$output .= $this->field_error( $this->show_error( $key ), $field_name );
	} elseif ( $this->is_notice( $key ) ) {
		$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
	} elseif ( ! empty( $data['help'] ) ) {
		$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
	}
	?>
</div>

