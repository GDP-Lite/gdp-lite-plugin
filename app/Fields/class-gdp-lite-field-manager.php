<?php
/**
 * Public field engine service.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Field_Manager {
	private static $instance = null;
	/** @var GDP_Lite_Field_Registry */ private $registry;
	/** @var GDP_Lite_Field_Validator */ private $validator;

	public static function instance() { if ( null === self::$instance ) { self::$instance = new self(); } return self::$instance; }
	private function __construct() { $this->registry = new GDP_Lite_Field_Registry(); $this->validator = new GDP_Lite_Field_Validator(); }
	public function register_hooks() { add_action( 'init', array( $this, 'register_default_fields' ), 5 ); add_action( 'init', array( $this, 'register_saved_fields' ), 8 ); }
	public function registry() { return $this->registry; }
	public function all() { return $this->registry->all(); }
	public function get( $name ) { return $this->registry->get( $name ); }
	public function exists( $name ) { return $this->registry->exists( $name ); }
	public function register( $definition ) { return $this->registry->register( $definition ); }
	public function unregister( $name ) { return $this->registry->unregister( $name ); }
	public function validate( $name, $value ) { $field = $this->get( $name ); return $field ? $this->validator->validate( $field, $value ) : new WP_Error( 'gdp_unknown_field', __( 'Unknown GDP field.', 'gdp-lite' ) ); }

	public function get_value( $post_id, $name, $default = null ) {
		$field = $this->get( $name );
		if ( ! $field ) { return $default; }
		$value = get_post_meta( absint( $post_id ), $field->meta_key(), true );
		if ( '' === $value && null !== $default ) { return $default; }
		if ( '' === $value ) { return $field->get( 'default' ); }
		return apply_filters( 'gdp_get_field_value', $value, $field, absint( $post_id ) );
	}

	/** @return int|bool|WP_Error */
	public function update_value( $post_id, $name, $value ) {
		$field = $this->get( $name );
		if ( ! $field ) { return new WP_Error( 'gdp_unknown_field', __( 'Unknown GDP field.', 'gdp-lite' ) ); }
		$value = apply_filters( 'gdp_before_save_field', $value, $field, absint( $post_id ) );
		$validated = $this->validator->validate( $field, $value );
		if ( is_wp_error( $validated ) ) { return $validated; }
		$sanitized = $this->validator->sanitize( $field, $validated );
		$result = update_post_meta( absint( $post_id ), $field->meta_key(), $sanitized );
		do_action( 'gdp_after_save_field', $sanitized, $field, absint( $post_id ), $result );
		return $result;
	}

	public function delete_value( $post_id, $name ) {
		$field = $this->get( $name );
		if ( ! $field ) { return false; }
		$result = delete_post_meta( absint( $post_id ), $field->meta_key() );
		do_action( 'gdp_delete_field_value', $field, absint( $post_id ), $result );
		return $result;
	}


	public function register_saved_fields() {
		$saved = get_option( 'gdp_registered_fields', array() );
		if ( ! is_array( $saved ) ) { return; }
		foreach ( $saved as $definition ) {
			if ( is_array( $definition ) && ! empty( $definition['name'] ) && ! $this->exists( $definition['name'] ) ) { $this->register( $definition ); }
		}
	}

	public function version() { return (int) get_option( 'gdp_fields_version', 1 ); }
	public function bump_version() { $version = $this->version() + 1; update_option( 'gdp_fields_version', $version, false ); if ( function_exists( 'gdp_cache' ) ) { gdp_cache()->bump_version(); } return $version; }

	public function register_default_fields() {
		$defaults = array(
			array( 'name' => 'rtp', 'label' => __( 'RTP (%)', 'gdp-lite' ), 'type' => 'decimal', 'min' => 0, 'max' => 100, 'step' => '0.01', 'tab' => 'gameplay', 'width' => 50, 'priority' => 10, 'searchable' => true, 'filterable' => true, 'card' => true, 'archive' => true ),
			array( 'name' => 'volatility', 'label' => __( 'Volatility', 'gdp-lite' ), 'type' => 'select', 'options' => array( 'low' => __( 'Low', 'gdp-lite' ), 'medium' => __( 'Medium', 'gdp-lite' ), 'high' => __( 'High', 'gdp-lite' ) ), 'tab' => 'gameplay', 'width' => 50, 'priority' => 20, 'searchable' => true, 'filterable' => true, 'card' => true, 'archive' => true ),
			array( 'name' => 'max_win', 'label' => __( 'Maximum Win', 'gdp-lite' ), 'type' => 'text', 'tab' => 'gameplay', 'width' => 50, 'priority' => 30, 'searchable' => true, 'filterable' => true, 'card' => true, 'archive' => true ),
			array( 'name' => 'buy_feature', 'label' => __( 'Buy Feature', 'gdp-lite' ), 'type' => 'switch', 'default' => 0, 'tab' => 'gameplay', 'width' => 50, 'priority' => 40, 'filterable' => true ),
			array( 'name' => 'reels', 'label' => __( 'Reels', 'gdp-lite' ), 'type' => 'number', 'min' => 0, 'tab' => 'gameplay', 'width' => 33, 'priority' => 50 ),
			array( 'name' => 'rows', 'label' => __( 'Rows', 'gdp-lite' ), 'type' => 'number', 'min' => 0, 'tab' => 'gameplay', 'width' => 33, 'priority' => 60 ),
			array( 'name' => 'paylines', 'label' => __( 'Paylines / Ways', 'gdp-lite' ), 'type' => 'text', 'tab' => 'gameplay', 'width' => 33, 'priority' => 70 ),
			array( 'name' => 'min_bet', 'label' => __( 'Minimum Bet', 'gdp-lite' ), 'type' => 'text', 'tab' => 'gameplay', 'width' => 50, 'priority' => 80 ),
			array( 'name' => 'max_bet', 'label' => __( 'Maximum Bet', 'gdp-lite' ), 'type' => 'text', 'tab' => 'gameplay', 'width' => 50, 'priority' => 90 ),
			array( 'name' => 'release_date', 'label' => __( 'Release Date', 'gdp-lite' ), 'type' => 'date', 'tab' => 'gameplay', 'width' => 50, 'priority' => 100, 'searchable' => true, 'filterable' => true ),
			array( 'name' => 'demo_url', 'label' => __( 'Demo URL', 'gdp-lite' ), 'type' => 'url', 'tab' => 'links', 'width' => 100, 'priority' => 10 ),
			array( 'name' => 'play_url', 'label' => __( 'Play URL', 'gdp-lite' ), 'type' => 'url', 'tab' => 'links', 'width' => 100, 'priority' => 20 ),
			array( 'name' => 'badge', 'label' => __( 'Badge', 'gdp-lite' ), 'type' => 'text', 'tab' => 'links', 'width' => 50, 'priority' => 30 ),
			array( 'name' => 'button_text', 'label' => __( 'Button Text', 'gdp-lite' ), 'type' => 'text', 'default' => __( 'Play Now', 'gdp-lite' ), 'tab' => 'links', 'width' => 50, 'priority' => 40 ),
			array( 'name' => 'featured', 'label' => __( 'Featured Game', 'gdp-lite' ), 'type' => 'switch', 'meta_key' => '_gdp_featured', 'tab' => 'links', 'width' => 50, 'priority' => 50, 'filterable' => true ),
			array( 'name' => 'features', 'label' => __( 'Features', 'gdp-lite' ), 'type' => 'textarea', 'tab' => 'features', 'width' => 100, 'priority' => 10 ),
			array( 'name' => 'feature_wild', 'label' => __( 'Wild', 'gdp-lite' ), 'type' => 'switch', 'meta_key' => '_gdp_feature_wild', 'tab' => 'features', 'width' => 25, 'priority' => 20 ),
			array( 'name' => 'feature_scatter', 'label' => __( 'Scatter', 'gdp-lite' ), 'type' => 'switch', 'meta_key' => '_gdp_feature_scatter', 'tab' => 'features', 'width' => 25, 'priority' => 30 ),
			array( 'name' => 'feature_free_spins', 'label' => __( 'Free Spins', 'gdp-lite' ), 'type' => 'switch', 'meta_key' => '_gdp_feature_free_spins', 'tab' => 'features', 'width' => 25, 'priority' => 40 ),
			array( 'name' => 'feature_bonus_buy', 'label' => __( 'Bonus Buy', 'gdp-lite' ), 'type' => 'switch', 'meta_key' => '_gdp_feature_bonus_buy', 'tab' => 'features', 'width' => 25, 'priority' => 50 ),
			array( 'name' => 'feature_jackpot', 'label' => __( 'Jackpot', 'gdp-lite' ), 'type' => 'switch', 'meta_key' => '_gdp_feature_jackpot', 'tab' => 'features', 'width' => 25, 'priority' => 60 ),
			array( 'name' => 'feature_multiplier', 'label' => __( 'Multiplier', 'gdp-lite' ), 'type' => 'switch', 'meta_key' => '_gdp_feature_multiplier', 'tab' => 'features', 'width' => 25, 'priority' => 70 ),
			array( 'name' => 'feature_megaways', 'label' => __( 'Megaways', 'gdp-lite' ), 'type' => 'switch', 'meta_key' => '_gdp_feature_megaways', 'tab' => 'features', 'width' => 25, 'priority' => 80 ),
			array( 'name' => 'feature_cascading', 'label' => __( 'Cascading Reels', 'gdp-lite' ), 'type' => 'switch', 'meta_key' => '_gdp_feature_cascading', 'tab' => 'features', 'width' => 25, 'priority' => 90 ),
		);
		$defaults = apply_filters( 'gdp_default_fields', $defaults, $this );
		foreach ( $defaults as $definition ) { if ( is_array( $definition ) ) { $this->register( $definition ); } }
		do_action( 'gdp_fields_ready', $this );
	}
}
