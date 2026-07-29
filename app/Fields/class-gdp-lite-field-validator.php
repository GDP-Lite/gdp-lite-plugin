<?php
/**
 * Dynamic field sanitization and validation.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Field_Validator {
	public static function supported_types() {
		return apply_filters( 'gdp_field_types', array( 'text', 'textarea', 'number', 'decimal', 'url', 'date', 'select', 'switch', 'image' ) );
	}

	/** @return mixed|WP_Error */
	public function validate( GDP_Lite_Field $field, $value ) {
		$value = apply_filters( 'gdp_before_validate_field', $value, $field );
		if ( $field->get( 'required' ) && ( '' === $value || null === $value || array() === $value ) ) {
			return new WP_Error( 'gdp_field_required', sprintf( __( '%s is required.', 'gdp-lite' ), $field->get( 'label' ) ) );
		}
		if ( '' === $value || null === $value ) { return $value; }

		$custom = $field->get( 'validate' );
		if ( is_callable( $custom ) ) {
			$result = call_user_func( $custom, $value, $field );
			if ( is_wp_error( $result ) ) { return $result; }
			if ( false === $result ) { return new WP_Error( 'gdp_field_invalid', sprintf( __( '%s is invalid.', 'gdp-lite' ), $field->get( 'label' ) ) ); }
		}

		switch ( $field->type() ) {
			case 'number':
				if ( ! is_numeric( $value ) ) { return new WP_Error( 'gdp_field_not_number', __( 'The value must be a number.', 'gdp-lite' ) ); }
				$value = (int) $value;
				break;
			case 'decimal':
				if ( ! is_numeric( $value ) ) { return new WP_Error( 'gdp_field_not_decimal', __( 'The value must be numeric.', 'gdp-lite' ) ); }
				$value = (float) $value;
				break;
			case 'url':
				if ( $value && ! wp_http_validate_url( $value ) ) { return new WP_Error( 'gdp_field_invalid_url', __( 'The value must be a valid URL.', 'gdp-lite' ) ); }
				break;
			case 'date':
				$date = DateTime::createFromFormat( 'Y-m-d', (string) $value );
				if ( ! $date || $date->format( 'Y-m-d' ) !== $value ) { return new WP_Error( 'gdp_field_invalid_date', __( 'The value must use YYYY-MM-DD format.', 'gdp-lite' ) ); }
				break;
			case 'select':
				$options = $field->get( 'options', array() );
				if ( $options && ! array_key_exists( (string) $value, $options ) ) { return new WP_Error( 'gdp_field_invalid_option', __( 'The selected option is invalid.', 'gdp-lite' ) ); }
				break;
			case 'switch':
				$value = empty( $value ) ? 0 : 1;
				break;
			case 'image':
				$value = absint( $value );
				if ( $value && ! wp_attachment_is_image( $value ) ) { return new WP_Error( 'gdp_field_invalid_image', __( 'The attachment must be an image.', 'gdp-lite' ) ); }
				break;
		}

		if ( is_numeric( $value ) ) {
			if ( null !== $field->get( 'min' ) && $value < $field->get( 'min' ) ) { return new WP_Error( 'gdp_field_below_min', __( 'The value is below the allowed minimum.', 'gdp-lite' ) ); }
			if ( null !== $field->get( 'max' ) && $value > $field->get( 'max' ) ) { return new WP_Error( 'gdp_field_above_max', __( 'The value is above the allowed maximum.', 'gdp-lite' ) ); }
		}
		return apply_filters( 'gdp_validate_field', $value, $field );
	}

	/** @return mixed */
	public function sanitize( GDP_Lite_Field $field, $value ) {
		$custom = $field->get( 'sanitize' );
		if ( is_callable( $custom ) ) { return call_user_func( $custom, $value, $field ); }
		switch ( $field->type() ) {
			case 'textarea': return sanitize_textarea_field( $value );
			case 'number': return (int) $value;
			case 'decimal': return (float) $value;
			case 'url': return esc_url_raw( $value );
			case 'switch': return empty( $value ) ? 0 : 1;
			case 'image': return absint( $value );
			default: return sanitize_text_field( $value );
		}
	}
}
