<?php
/** Builder component registry. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class GDP_Lite_Component_Registry {
	private static $instance = null;
	private $components = array();
	public static function instance() { if ( null === self::$instance ) { self::$instance = new self(); } return self::$instance; }
	public function register_hooks() { add_action( 'init', array( $this, 'register_defaults' ), 30 ); }
	public function register_defaults() {
		$defaults = array(
			'image'      => array( 'label' => __( 'Featured Image', 'gdp-lite' ), 'contexts' => array( 'card', 'single', 'archive' ), 'render' => array( $this, 'render_image' ) ),
			'title'      => array( 'label' => __( 'Game Title', 'gdp-lite' ), 'contexts' => array( 'card', 'single', 'archive' ), 'render' => array( $this, 'render_title' ) ),
			'excerpt'    => array( 'label' => __( 'Excerpt', 'gdp-lite' ), 'contexts' => array( 'card', 'single', 'archive' ), 'render' => array( $this, 'render_excerpt' ) ),
			'content'    => array( 'label' => __( 'Content', 'gdp-lite' ), 'contexts' => array( 'single' ), 'render' => array( $this, 'render_content' ) ),
			'provider'   => array( 'label' => __( 'Provider', 'gdp-lite' ), 'contexts' => array( 'card', 'single', 'archive' ), 'render' => array( $this, 'render_provider' ) ),
			'fields'     => array( 'label' => __( 'Dynamic Fields', 'gdp-lite' ), 'contexts' => array( 'card', 'single', 'archive' ), 'render' => array( $this, 'render_fields' ) ),
			'button'     => array( 'label' => __( 'View Game Button', 'gdp-lite' ), 'contexts' => array( 'card', 'archive' ), 'render' => array( $this, 'render_button' ) ),
		);
		foreach ( apply_filters( 'gdp_builder_default_components', $defaults ) as $id => $definition ) { $this->register( $id, $definition ); }
		do_action( 'gdp_builder_components_ready', $this );
	}
	public function register( $id, $definition ) {
		$id = sanitize_key( $id );
		if ( ! $id || empty( $definition['label'] ) || empty( $definition['render'] ) || ! is_callable( $definition['render'] ) ) { return false; }
		$definition['id'] = $id;
		$definition['contexts'] = isset( $definition['contexts'] ) ? array_map( 'sanitize_key', (array) $definition['contexts'] ) : array();
		$this->components[ $id ] = $definition;
		return true;
	}
	public function all( $context = '' ) {
		if ( ! $context ) { return $this->components; }
		return array_filter( $this->components, function( $component ) use ( $context ) { return empty( $component['contexts'] ) || in_array( $context, $component['contexts'], true ); } );
	}
	public function get( $id ) { return isset( $this->components[ $id ] ) ? $this->components[ $id ] : null; }
	public function render( $id, $post_id, $settings = array(), $context = 'card' ) {
		$component = $this->get( $id );
		if ( ! $component || ( ! empty( $component['contexts'] ) && ! in_array( $context, $component['contexts'], true ) ) ) { return ''; }
		return (string) call_user_func( $component['render'], (int) $post_id, (array) $settings, $context );
	}
	public function render_image( $post_id, $settings ) { if ( ! has_post_thumbnail( $post_id ) ) { return ''; } $size = ! empty( $settings['size'] ) ? sanitize_key( $settings['size'] ) : 'medium_large'; return '<div class="gdp-builder-image">' . get_the_post_thumbnail( $post_id, $size, array( 'loading' => 'lazy' ) ) . '</div>'; }
	public function render_title( $post_id, $settings, $context ) { $tag = 'single' === $context ? 'h1' : 'h3'; return '<' . $tag . ' class="gdp-builder-title"><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></' . $tag . '>'; }
	public function render_excerpt( $post_id, $settings ) { $words = isset( $settings['words'] ) ? max( 5, min( 80, absint( $settings['words'] ) ) ) : 24; return '<div class="gdp-builder-excerpt">' . esc_html( wp_trim_words( get_the_excerpt( $post_id ), $words ) ) . '</div>'; }
	public function render_content( $post_id ) { return '<div class="gdp-builder-content">' . apply_filters( 'the_content', get_post_field( 'post_content', $post_id ) ) . '</div>'; }
	public function render_provider( $post_id ) { $terms = get_the_terms( $post_id, 'gdp_provider' ); if ( empty( $terms ) || is_wp_error( $terms ) ) { return ''; } return '<div class="gdp-builder-provider">' . esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) ) . '</div>'; }
	public function render_fields( $post_id, $settings, $context ) { $field_context = ! empty( $settings['context'] ) ? sanitize_key( $settings['context'] ) : $context; return GDP()->renderer()->render_group( $field_context, $post_id ); }
	public function render_button( $post_id, $settings ) { $label = ! empty( $settings['label'] ) ? sanitize_text_field( $settings['label'] ) : __( 'View Game', 'gdp-lite' ); return '<div class="gdp-builder-actions"><a class="gdp-button" href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( $label ) . '</a></div>'; }
}
