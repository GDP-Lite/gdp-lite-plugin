<?php
/** Builder layout storage and rendering. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class GDP_Lite_Layout_Manager {
	private static $instance = null;
	const OPTION = 'gdp_builder_layouts';
	public static function instance() { if ( null === self::$instance ) { self::$instance = new self(); } return self::$instance; }
	public function register_hooks() {}
	public function defaults() {
		return array(
			'card' => array( 'id' => 'card', 'label' => __( 'Game Card', 'gdp-lite' ), 'context' => 'card', 'components' => array(
				array( 'id' => 'image', 'settings' => array( 'size' => 'medium_large' ) ), array( 'id' => 'title', 'settings' => array() ), array( 'id' => 'provider', 'settings' => array() ), array( 'id' => 'fields', 'settings' => array( 'context' => 'card' ) ), array( 'id' => 'button', 'settings' => array() ),
			) ),
			'single' => array( 'id' => 'single', 'label' => __( 'Single Game', 'gdp-lite' ), 'context' => 'single', 'components' => array(
				array( 'id' => 'title', 'settings' => array() ), array( 'id' => 'image', 'settings' => array( 'size' => 'large' ) ), array( 'id' => 'provider', 'settings' => array() ), array( 'id' => 'fields', 'settings' => array( 'context' => 'single' ) ), array( 'id' => 'content', 'settings' => array() ),
			) ),
			'archive' => array( 'id' => 'archive', 'label' => __( 'Archive Item', 'gdp-lite' ), 'context' => 'archive', 'components' => array(
				array( 'id' => 'image', 'settings' => array( 'size' => 'medium_large' ) ), array( 'id' => 'title', 'settings' => array() ), array( 'id' => 'excerpt', 'settings' => array( 'words' => 20 ) ), array( 'id' => 'button', 'settings' => array() ),
			) ),
		);
	}
	public function all() { return wp_parse_args( (array) get_option( self::OPTION, array() ), $this->defaults() ); }
	public function get( $id ) { $all = $this->all(); return isset( $all[ $id ] ) ? $all[ $id ] : null; }
	public function save( $id, $layout ) {
		$id = sanitize_key( $id ); if ( ! $id ) { return new WP_Error( 'invalid_layout', __( 'Invalid layout ID.', 'gdp-lite' ) ); }
		$layout = $this->sanitize_layout( $layout, $id ); if ( is_wp_error( $layout ) ) { return $layout; }
		$all = $this->all(); $all[ $id ] = $layout; update_option( self::OPTION, $all, false );
		update_option( 'gdp_builder_version', (int) get_option( 'gdp_builder_version', 1 ) + 1, false );
		if ( GDP()->cache() ) { GDP()->cache()->flush(); }
		do_action( 'gdp_builder_layout_saved', $id, $layout ); return true;
	}
	public function reset( $id ) { $defaults = $this->defaults(); if ( ! isset( $defaults[ $id ] ) ) { return false; } $all = $this->all(); $all[ $id ] = $defaults[ $id ]; update_option( self::OPTION, $all, false ); do_action( 'gdp_builder_layout_reset', $id ); return true; }
	public function sanitize_layout( $layout, $id = '' ) {
		$context = isset( $layout['context'] ) ? sanitize_key( $layout['context'] ) : $id;
		if ( ! in_array( $context, array( 'card', 'single', 'archive' ), true ) ) { return new WP_Error( 'invalid_context', __( 'Invalid layout context.', 'gdp-lite' ) ); }
		$components = array();
		foreach ( (array) ( $layout['components'] ?? array() ) as $item ) {
			$component_id = sanitize_key( $item['id'] ?? '' ); $component = GDP()->components()->get( $component_id );
			if ( ! $component || ( ! empty( $component['contexts'] ) && ! in_array( $context, $component['contexts'], true ) ) ) { continue; }
			$components[] = array( 'id' => $component_id, 'settings' => $this->sanitize_settings( (array) ( $item['settings'] ?? array() ) ) );
		}
		return array( 'id' => sanitize_key( $id ?: ( $layout['id'] ?? $context ) ), 'label' => sanitize_text_field( $layout['label'] ?? ucfirst( $context ) ), 'context' => $context, 'components' => $components );
	}
	private function sanitize_settings( $settings ) { $clean = array(); foreach ( $settings as $key => $value ) { $key = sanitize_key( $key ); if ( is_scalar( $value ) ) { $clean[ $key ] = sanitize_text_field( (string) $value ); } } return $clean; }
	public function render( $id, $post_id = 0, $args = array() ) {
		$layout = $this->get( $id ); if ( ! $layout ) { return ''; } $post_id = $post_id ?: get_the_ID(); if ( ! $post_id ) { return ''; }
		$classes = array( 'gdp-builder-layout', 'gdp-builder-' . sanitize_html_class( $layout['context'] ), 'gdp-layout-' . sanitize_html_class( $id ) );
		$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-gdp-layout="' . esc_attr( $id ) . '">';
		do_action( 'gdp_before_render_layout', $id, $post_id, $layout );
		foreach ( $layout['components'] as $item ) { $html .= '<div class="gdp-builder-component gdp-component-' . esc_attr( $item['id'] ) . '">' . GDP()->components()->render( $item['id'], $post_id, $item['settings'], $layout['context'] ) . '</div>'; }
		do_action( 'gdp_after_render_layout', $id, $post_id, $layout );
		return apply_filters( 'gdp_builder_render_layout', $html . '</div>', $id, $post_id, $layout, $args );
	}
}
