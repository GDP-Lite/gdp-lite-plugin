<?php
/**
 * Immutable field definition value object.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Field {
	/** @var array<string,mixed> */
	private $args;

	/**
	 * @param array<string,mixed> $args Field definition.
	 */
	public function __construct( $args ) {
		$defaults = array(
			'name'        => '',
			'label'       => '',
			'description' => '',
			'type'        => 'text',
			'default'     => '',
			'options'     => array(),
			'required'    => false,
			'searchable'  => false,
			'filterable'  => false,
			'rest'        => true,
			'export'      => true,
			'import'      => true,
			'archive'     => false,
			'single'      => true,
			'card'        => false,
			'format'      => '',
			'min'         => null,
			'max'         => null,
			'sanitize'    => null,
			'validate'    => null,
			'meta_key'    => '',
			'active'      => true,
			'tab'         => 'gameplay',
			'width'       => 50,
			'priority'    => 10,
			'step'        => null,
			'show_if'     => array(),
		);
		$args = wp_parse_args( is_array( $args ) ? $args : array(), $defaults );
		$args['name'] = sanitize_key( $args['name'] );
		$args['type'] = sanitize_key( $args['type'] );
		$args['meta_key'] = $args['meta_key'] ? sanitize_key( $args['meta_key'] ) : '_gdp_' . $args['name'];
		$args['label'] = $args['label'] ? sanitize_text_field( $args['label'] ) : ucwords( str_replace( array( '-', '_' ), ' ', $args['name'] ) );
		$args['options'] = is_array( $args['options'] ) ? $args['options'] : array();
		$args['show_if'] = is_array( $args['show_if'] ) ? $args['show_if'] : array();
		$args['tab'] = sanitize_key( $args['tab'] );
		$args['width'] = absint( $args['width'] );
		$args['priority'] = (int) $args['priority'];
		foreach ( array( 'required', 'searchable', 'filterable', 'rest', 'export', 'import', 'archive', 'single', 'card', 'active' ) as $flag ) {
			$args[ $flag ] = (bool) $args[ $flag ];
		}
		$this->args = $args;
	}

	public function get( $key, $default = null ) { return array_key_exists( $key, $this->args ) ? $this->args[ $key ] : $default; }
	public function name() { return $this->args['name']; }
	public function type() { return $this->args['type']; }
	public function meta_key() { return $this->args['meta_key']; }
	public function to_array() { return $this->args; }
}
