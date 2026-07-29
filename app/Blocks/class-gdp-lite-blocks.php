<?php
/**
 * Gutenberg blocks.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_Blocks {
	/** @var GDP_Lite_Blocks|null */
	private static $instance = null;

	/** @return GDP_Lite_Blocks */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** @return void */
	public function register_hooks() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'register_category' ), 10, 2 );
	}

	/** @param array<int,array<string,string>> $categories Categories. @return array<int,array<string,string>> */
	public function register_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'gdp-lite' === $category['slug'] ) {
				return $categories;
			}
		}
		array_unshift(
			$categories,
			array(
				'slug'  => 'gdp-lite',
				'title' => __( 'GDP Lite', 'gdp-lite' ),
				'icon'  => 'games',
			)
		);
		return $categories;
	}

	/** @return void */
	public function register_blocks() {
		foreach ( array( 'game-grid', 'game-filters', 'featured-games', 'provider-games' ) as $block ) { $path = GDP_LITE_DIR . 'blocks/' . $block; if ( is_readable( $path . '/block.json' ) ) { register_block_type( $path, array( 'render_callback' => array( $this, 'render_' . str_replace( '-', '_', $block ) ) ) ); } }
		register_block_type( 'gdp/dynamic-field', array( 'api_version'=>3, 'title'=>__( 'GDP Dynamic Field','gdp-lite' ), 'category'=>'gdp-lite', 'attributes'=>array('field'=>array('type'=>'string'),'postId'=>array('type'=>'integer','default'=>0),'showLabel'=>array('type'=>'boolean','default'=>true)), 'render_callback'=>array($this,'render_dynamic_field') ) );
		register_block_type( 'gdp/game-card', array( 'api_version'=>3, 'title'=>__( 'GDP Game Card','gdp-lite' ), 'category'=>'gdp-lite', 'attributes'=>array('postId'=>array('type'=>'integer','default'=>0)), 'render_callback'=>array($this,'render_dynamic_card') ) );
	}

	/** @param array<string,mixed> $attributes Attributes. @return string */
	public function render_game_grid( $attributes ) {
		return $this->render_games( $attributes, false );
	}

	/** @param array<string,mixed> $attributes Attributes. @return string */
	public function render_game_filters( $attributes ) {
		return $this->render_games( $attributes, true );
	}

	/** @param array<string,mixed> $attributes Attributes. @return string */
	public function render_featured_games( $attributes ) {
		$attributes['featured'] = true;
		return $this->render_games( $attributes, false );
	}

	/** @param array<string,mixed> $attributes Attributes. @return string */
	public function render_provider_games( $attributes ) {
		$provider = isset( $attributes['provider'] ) ? sanitize_title( $attributes['provider'] ) : '';
		if ( '' === $provider ) {
			return $this->editor_notice( __( 'Select a provider in the block settings.', 'gdp-lite' ) );
		}
		$attributes['provider'] = $provider;
		return $this->render_games( $attributes, false );
	}

	public function render_dynamic_field( $attributes ) { return GDP()->renderer()->render_field( sanitize_key( $attributes['field'] ?? '' ), absint( $attributes['postId'] ?? 0 ), array( 'show_label' => ! empty( $attributes['showLabel'] ) ) ); }
	public function render_dynamic_card( $attributes ) { return GDP()->renderer()->render_card( absint( $attributes['postId'] ?? 0 ) ); }

	/** @param array<string,mixed> $attributes Attributes. @param bool $filters Show filters. @return string */
	private function render_games( $attributes, $filters ) {
		$defaults = array(
			'type'       => '',
			'provider'   => '',
			'collection' => '',
			'category'   => '',
			'volatility' => '',
			'featured'   => '',
			'limit'      => 8,
			'columns'    => 4,
			'orderby'    => 'date',
			'order'      => 'DESC',
		);
		$args = wp_parse_args( is_array( $attributes ) ? $attributes : array(), $defaults );
		$args['limit']   = max( 1, min( 48, absint( $args['limit'] ) ) );
		$args['columns'] = max( 1, min( 6, absint( $args['columns'] ) ) );
		$args['order']   = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$args['orderby'] = in_array( $args['orderby'], array( 'date', 'modified', 'title', 'rtp', 'max_win', 'popular', 'rand' ), true ) ? $args['orderby'] : 'date';
		$args['filters'] = $filters ? 'true' : 'false';

		foreach ( array( 'type', 'provider', 'collection', 'category' ) as $key ) {
			$args[ $key ] = sanitize_title( (string) $args[ $key ] );
		}
		$args['volatility'] = sanitize_key( (string) $args['volatility'] );

		return GDP_Lite_Shortcodes::instance()->games( $args );
	}

	/** @param string $message Message. @return string */
	private function editor_notice( $message ) {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return '<div class="gdp-block-notice">' . esc_html( $message ) . '</div>';
		}
		return '';
	}
}
