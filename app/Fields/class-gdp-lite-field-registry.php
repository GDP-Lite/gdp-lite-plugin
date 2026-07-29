<?php
/**
 * Runtime registry for dynamic field definitions.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Field_Registry {
	/** @var array<string,GDP_Lite_Field> */
	private $fields = array();

	/** @param array<string,mixed>|GDP_Lite_Field $definition Field definition. */
	public function register( $definition ) {
		$field = $definition instanceof GDP_Lite_Field ? $definition : new GDP_Lite_Field( $definition );
		if ( ! $field->name() ) {
			return new WP_Error( 'gdp_invalid_field_name', __( 'A field name is required.', 'gdp-lite' ) );
		}
		$allowed = GDP_Lite_Field_Validator::supported_types();
		if ( ! in_array( $field->type(), $allowed, true ) ) {
			return new WP_Error( 'gdp_invalid_field_type', sprintf( __( 'Unsupported field type: %s', 'gdp-lite' ), $field->type() ) );
		}
		$field = apply_filters( 'gdp_register_field', $field, $field->to_array(), $this );
		if ( ! $field instanceof GDP_Lite_Field ) {
			return new WP_Error( 'gdp_invalid_field_object', __( 'The field registration filter must return a GDP_Lite_Field object.', 'gdp-lite' ) );
		}
		$this->fields[ $field->name() ] = $field;
		do_action( 'gdp_field_registered', $field, $this );
		return $field;
	}

	public function unregister( $name ) {
		$name = sanitize_key( $name );
		if ( ! isset( $this->fields[ $name ] ) ) { return false; }
		$field = $this->fields[ $name ];
		unset( $this->fields[ $name ] );
		do_action( 'gdp_field_unregistered', $field, $this );
		return true;
	}

	public function get( $name ) { $name = sanitize_key( $name ); return isset( $this->fields[ $name ] ) ? $this->fields[ $name ] : null; }
	public function exists( $name ) { return null !== $this->get( $name ); }
	public function all() { return apply_filters( 'gdp_registered_fields', $this->fields, $this ); }
	public function names() { return array_keys( $this->all() ); }
	public function clear() { $this->fields = array(); }
}
