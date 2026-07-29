<?php
/**
 * Dynamic field renderer for admin meta boxes and formatted output.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Field_Renderer {
	/** Render one admin control. */
	public function render_admin( GDP_Lite_Field $field, $value, $post_id = 0 ) {
		$args        = $field->to_array();
		$name        = 'gdp_fields[' . $field->name() . ']';
		$id          = 'gdp-field-' . $field->name();
		$type        = $field->type();
		$description = $field->get( 'description', '' );
		$required    = $field->get( 'required' ) ? ' required' : '';
		$min         = null !== $field->get( 'min' ) ? ' min="' . esc_attr( $field->get( 'min' ) ) . '"' : '';
		$max         = null !== $field->get( 'max' ) ? ' max="' . esc_attr( $field->get( 'max' ) ) . '"' : '';
		$step        = $field->get( 'step', 'decimal' === $type ? '0.01' : '1' );
		$attrs       = $min . $max . ( in_array( $type, array( 'number', 'decimal' ), true ) ? ' step="' . esc_attr( $step ) . '"' : '' );

		do_action( 'gdp_before_render_field', $field, $value, $post_id );
		echo '<div class="gdp-dynamic-field gdp-field-type-' . esc_attr( $type ) . '" data-field="' . esc_attr( $field->name() ) . '">';
		echo '<label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $field->get( 'label' ) ) . '</strong>' . ( $field->get( 'required' ) ? ' <span class="gdp-required">*</span>' : '' ) . '</label>';

		switch ( $type ) {
			case 'textarea':
				echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="5"' . $required . '>' . esc_textarea( $value ) . '</textarea>';
				break;
			case 'number':
			case 'decimal':
				echo '<input id="' . esc_attr( $id ) . '" type="number" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . $attrs . $required . '>';
				break;
			case 'url':
				echo '<input id="' . esc_attr( $id ) . '" type="url" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="https://"' . $required . '>';
				break;
			case 'date':
				echo '<input id="' . esc_attr( $id ) . '" type="date" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . $required . '>';
				break;
			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $required . '>';
				echo '<option value="">' . esc_html__( 'Select', 'gdp-lite' ) . '</option>';
				foreach ( $field->get( 'options', array() ) as $option_value => $option_label ) {
					echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( (string) $value, (string) $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
				}
				echo '</select>';
				break;
			case 'switch':
				echo '<label class="gdp-switch"><input id="' . esc_attr( $id ) . '" type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( ! empty( $value ), true, false ) . '><span aria-hidden="true"></span><em>' . esc_html__( 'Enabled', 'gdp-lite' ) . '</em></label>';
				break;
			case 'image':
				$attachment_id = absint( $value );
				echo '<div class="gdp-image-field">';
				echo '<input id="' . esc_attr( $id ) . '" type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $attachment_id ) . '">';
				echo '<div class="gdp-image-preview">' . ( $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail' ) : '' ) . '</div>';
				echo '<button type="button" class="button gdp-select-image">' . esc_html__( 'Select image', 'gdp-lite' ) . '</button> ';
				echo '<button type="button" class="button-link-delete gdp-remove-image"' . ( $attachment_id ? '' : ' hidden' ) . '>' . esc_html__( 'Remove', 'gdp-lite' ) . '</button>';
				echo '</div>';
				break;
			default:
				echo '<input id="' . esc_attr( $id ) . '" type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . $required . '>';
		}

		if ( $description ) { echo '<p class="description">' . esc_html( $description ) . '</p>'; }
		echo '</div>';
		do_action( 'gdp_after_render_field', $field, $value, $post_id );
	}

	/** Format front-end value. */
	public function format( GDP_Lite_Field $field, $value ) {
		if ( 'switch' === $field->type() ) { $value = $value ? __( 'Yes', 'gdp-lite' ) : __( 'No', 'gdp-lite' ); }
		if ( 'image' === $field->type() && $value ) { $value = wp_get_attachment_image( absint( $value ), 'thumbnail' ); }
		return apply_filters( 'gdp_render_field', $value, $field );
	}
}
