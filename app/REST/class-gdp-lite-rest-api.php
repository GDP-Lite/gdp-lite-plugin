<?php
/**
 * Read-only REST API for GDP Lite.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_REST_API {
	/** @var self|null */
	private static $instance = null;

	/** @return self */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** @return void */
	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/** @return void */
	public function register_routes() {
		register_rest_route(
			'gdp/v1',
			'/games',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_games' ),
				'permission_callback' => '__return_true',
				'args'                => $this->game_collection_args(),
			)
		);

		register_rest_route(
			'gdp/v1',
			'/games/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_game' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => static function ( $value ) { return absint( $value ) > 0; },
					),
				),
			)
		);

		foreach ( $this->taxonomy_routes() as $route => $taxonomy ) {
			register_rest_route(
				'gdp/v1',
				'/' . $route,
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => function ( WP_REST_Request $request ) use ( $taxonomy ) { return $this->get_terms( $request, $taxonomy ); },
					'permission_callback' => '__return_true',
					'args'                => $this->term_collection_args(),
				)
			);
		}
	}

	/** @return array<string,string> */
	private function taxonomy_routes() {
		return array(
			'providers'   => 'gdp_provider',
			'types'       => 'gdp_game_type',
			'collections' => 'gdp_collection',
			'categories'  => 'gdp_game_category',
		);
	}

	/** @return array<string,array<string,mixed>> */
	private function game_collection_args() {
		$list = static function ( $value ) {
			if ( is_array( $value ) ) { return implode( ',', array_map( 'sanitize_title', $value ) ); }
			return implode( ',', array_filter( array_map( 'sanitize_title', preg_split( '/\s*,\s*/', (string) $value ) ) ) );
		};

		$args = array(
			'provider'    => array( 'sanitize_callback' => $list ),
			'type'        => array( 'sanitize_callback' => $list ),
			'collection'  => array( 'sanitize_callback' => $list ),
			'category'    => array( 'sanitize_callback' => $list ),
			'volatility'  => array( 'sanitize_callback' => $list ),
			'features'    => array( 'sanitize_callback' => $list ),
			'featured'    => array( 'sanitize_callback' => 'rest_sanitize_boolean' ),
			'rtp_min'     => array( 'sanitize_callback' => array( $this, 'sanitize_number' ) ),
			'rtp_max'     => array( 'sanitize_callback' => array( $this, 'sanitize_number' ) ),
			'max_win_min' => array( 'sanitize_callback' => array( $this, 'sanitize_number' ) ),
			'search'      => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'orderby'     => array(
				'default'           => 'date',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => static function ( $value ) { return in_array( $value, array( 'date', 'modified', 'title', 'menu_order', 'rand', 'popular' ), true ) || GDP()->fields()->exists( $value ); },
			),
			'order'       => array(
				'default'           => 'desc',
				'sanitize_callback' => static function ( $value ) { return strtolower( sanitize_key( $value ) ); },
				'validate_callback' => static function ( $value ) { return in_array( strtolower( (string) $value ), array( 'asc', 'desc' ), true ); },
			),
			'page'        => array( 'default' => 1, 'sanitize_callback' => 'absint', 'validate_callback' => static function ( $value ) { return absint( $value ) >= 1; } ),
			'per_page'    => array( 'default' => 12, 'sanitize_callback' => 'absint', 'validate_callback' => static function ( $value ) { $value = absint( $value ); return $value >= 1 && $value <= 100; } ),
		);
		return array_merge( $args, $this->dynamic_field_args() );
	}

	/** @return array<string,array<string,mixed>> */
	private function dynamic_field_args() { $args = array(); foreach ( GDP()->fields()->all() as $field ) { if ( ! $field->get( 'filterable', false ) ) { continue; } $args[ $field->name() ] = array( 'sanitize_callback' => 'sanitize_text_field' ); $args[ $field->name() . '_min' ] = array( 'sanitize_callback' => array( $this, 'sanitize_number' ) ); $args[ $field->name() . '_max' ] = array( 'sanitize_callback' => array( $this, 'sanitize_number' ) ); } return $args; }

	/** @return array<string,array<string,mixed>> */
	private function term_collection_args() {
		return array(
			'hide_empty' => array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ),
			'search'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'orderby'    => array( 'default' => 'name', 'sanitize_callback' => 'sanitize_key' ),
			'order'      => array( 'default' => 'asc', 'sanitize_callback' => static function ( $value ) { return strtoupper( sanitize_key( $value ) ); } ),
			'page'       => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
			'per_page'   => array( 'default' => 100, 'sanitize_callback' => 'absint', 'validate_callback' => static function ( $value ) { $value = absint( $value ); return $value >= 1 && $value <= 100; } ),
		);
	}

	/** @param mixed $value Value. @return float|null */
	public function sanitize_number( $value ) { return is_numeric( $value ) ? (float) $value : null; }

	/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
	public function get_games( WP_REST_Request $request ) {
		$args = array(
			'provider'    => $request->get_param( 'provider' ),
			'type'        => $request->get_param( 'type' ),
			'collection'  => $request->get_param( 'collection' ),
			'category'    => $request->get_param( 'category' ),
			'volatility'  => $request->get_param( 'volatility' ),
			'features'    => $request->get_param( 'features' ),
			'featured'    => $request->has_param( 'featured' ) ? $request->get_param( 'featured' ) : null,
			'rtp_min'     => $request->get_param( 'rtp_min' ),
			'rtp_max'     => $request->get_param( 'rtp_max' ),
			'max_win_min' => $request->get_param( 'max_win_min' ),
			'search'      => $request->get_param( 'search' ),
			'orderby'     => $request->get_param( 'orderby' ),
			'order'       => strtoupper( (string) $request->get_param( 'order' ) ),
			'paged'       => max( 1, absint( $request->get_param( 'page' ) ) ),
			'limit'       => min( 100, max( 1, absint( $request->get_param( 'per_page' ) ) ) ),
		);
		$args['fields'] = array(); foreach ( GDP()->fields()->all() as $field ) { if ( ! $field->get( 'filterable', false ) ) { continue; } $name=$field->name(); if ( $request->has_param( $name ) ) { $args['fields'][$name]=array('value'=>$request->get_param($name),'compare'=>'='); } if ( $request->has_param( $name.'_min' ) ) { $args['fields'][$name]=array('value'=>$request->get_param($name.'_min'),'compare'=>'>='); } if ( $request->has_param( $name.'_max' ) ) { $args['fields'][$name]=array('value'=>$request->get_param($name.'_max'),'compare'=>'<='); } }
		$args = GDP_Lite_Hooks::filter( 'rest_games_query_args', $args, $request );

		$cache_key = gdp_cache()->key( 'rest_games', array( 'args' => $args, 'locale' => determine_locale() ) );
		$found     = false;
		$cached    = gdp_cache()->enabled() ? gdp_cache()->get( $cache_key, $found ) : false;
		if ( ! $found ) {
			$query = new GDP_Lite_Query( $args );
			$data  = array_map( array( $this, 'prepare_game' ), $query->get_posts() );
			$cached = array(
				'data'        => $data,
				'total'       => $query->found_posts(),
				'total_pages' => $query->max_num_pages(),
			);
			if ( gdp_cache()->enabled() ) { gdp_cache()->set( $cache_key, $cached ); }
		}

		$response = rest_ensure_response( $cached['data'] );
		$response->header( 'X-WP-Total', (int) $cached['total'] );
		$response->header( 'X-WP-TotalPages', (int) $cached['total_pages'] );
		$response->header( 'Cache-Control', 'public, max-age=60' );
		return GDP_Lite_Hooks::filter( 'rest_games_response', $response, $request, $cached );
	}

	/** @param WP_REST_Request $request Request. @return WP_REST_Response|WP_Error */
	public function get_game( WP_REST_Request $request ) {
		$post = get_post( absint( $request['id'] ) );
		if ( ! $post || 'gdp_game' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_Error( 'gdp_game_not_found', __( 'Game not found.', 'gdp-lite' ), array( 'status' => 404 ) );
		}
		$cache_key = gdp_cache()->key( 'rest_game', array( 'id' => $post->ID, 'modified' => $post->post_modified_gmt, 'locale' => determine_locale() ) );
		$found = false;
		$data  = gdp_cache()->enabled() ? gdp_cache()->get( $cache_key, $found ) : false;
		if ( ! $found ) {
			$data = $this->prepare_game( $post, true );
			if ( gdp_cache()->enabled() ) { gdp_cache()->set( $cache_key, $data ); }
		}
		$response = rest_ensure_response( $data );
		$response->header( 'Cache-Control', 'public, max-age=60' );
		return GDP_Lite_Hooks::filter( 'rest_game_response', $response, $request, $post );
	}

	/** @param WP_REST_Request $request Request. @param string $taxonomy Taxonomy. @return WP_REST_Response|WP_Error */
	public function get_terms( WP_REST_Request $request, $taxonomy ) {
		$page     = max( 1, absint( $request->get_param( 'page' ) ) );
		$per_page = min( 100, max( 1, absint( $request->get_param( 'per_page' ) ) ) );
		$args     = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => (bool) $request->get_param( 'hide_empty' ),
			'search'     => (string) $request->get_param( 'search' ),
			'orderby'    => sanitize_key( (string) $request->get_param( 'orderby' ) ),
			'order'      => 'DESC' === strtoupper( (string) $request->get_param( 'order' ) ) ? 'DESC' : 'ASC',
			'number'     => $per_page,
			'offset'     => ( $page - 1 ) * $per_page,
		);
		$args  = GDP_Lite_Hooks::filter( 'rest_terms_query_args', $args, $request, $taxonomy );
		$cache_key = gdp_cache()->key( 'rest_terms', array( 'taxonomy' => $taxonomy, 'args' => $args, 'locale' => determine_locale() ) );
		$found = false;
		$cached = gdp_cache()->enabled() ? gdp_cache()->get( $cache_key, $found ) : false;
		if ( ! $found ) {
			$terms = get_terms( $args );
			if ( is_wp_error( $terms ) ) { return $terms; }
			$count_args = $args; unset( $count_args['number'], $count_args['offset'] ); $count_args['fields'] = 'count';
			$total = (int) get_terms( $count_args );
			$cached = array( 'data' => array_map( array( $this, 'prepare_term' ), $terms ), 'total' => $total );
			if ( gdp_cache()->enabled() ) { gdp_cache()->set( $cache_key, $cached ); }
		}
		$total = (int) $cached['total'];
		$data  = $cached['data'];
		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', (int) ceil( $total / $per_page ) );
		return $response;
	}

	/** @param WP_Post $post Game post. @param bool $full Include content. @return array<string,mixed> */
	public function prepare_game( $post, $full = false ) {
		$post = get_post( $post );
		$image_id = get_post_thumbnail_id( $post );
		$image = array(
			'id'        => $image_id ? (int) $image_id : 0,
			'url'       => $image_id ? (string) wp_get_attachment_image_url( $image_id, 'full' ) : '',
			'thumbnail' => $image_id ? (string) wp_get_attachment_image_url( $image_id, 'medium_large' ) : '',
			'alt'       => $image_id ? (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '',
		);
		$meta = array();
		foreach ( GDP()->fields()->all() as $field ) {
			if ( ! $field->get( 'rest', true ) ) { continue; }
			$value = GDP()->fields()->get_value( $post->ID, $field->name() );
			if ( in_array( $field->type(), array( 'number', 'decimal' ), true ) && '' !== $value ) { $value = (float) $value; }
			if ( 'switch' === $field->type() ) { $value = (bool) $value; }
			$meta[ $field->name() ] = $value;
		}
		$features = array();
		foreach ( array( 'wild', 'scatter', 'free_spins', 'bonus_buy', 'jackpot', 'multiplier', 'megaways', 'cascading' ) as $feature ) {
			if ( '1' === get_post_meta( $post->ID, '_gdp_feature_' . $feature, true ) ) { $features[] = $feature; }
		}

		$data = array(
			'id'          => (int) $post->ID,
			'title'       => get_the_title( $post ),
			'slug'        => $post->post_name,
			'url'         => get_permalink( $post ),
			'excerpt'     => get_the_excerpt( $post ),
			'date'        => get_post_time( DATE_ATOM, true, $post ),
			'modified'    => get_post_modified_time( DATE_ATOM, true, $post ),
			'image'       => $image,
			'provider'    => $this->post_terms( $post->ID, 'gdp_provider' ),
			'type'        => $this->post_terms( $post->ID, 'gdp_game_type' ),
			'collection'  => $this->post_terms( $post->ID, 'gdp_collection' ),
			'category'    => $this->post_terms( $post->ID, 'gdp_game_category' ),
			'features'    => $features,
		);
		$data['fields'] = apply_filters( 'gdp_rest_prepare_fields', $meta, $post );
		$data += $meta;
		if ( $full ) { $data['content'] = apply_filters( 'the_content', $post->post_content ); }
		return GDP_Lite_Hooks::filter( 'rest_prepare_game', $data, $post, $full );
	}

	/** @param int $post_id Post ID. @param string $taxonomy Taxonomy. @return array<int,array<string,mixed>> */
	private function post_terms( $post_id, $taxonomy ) {
		$terms = wp_get_post_terms( $post_id, $taxonomy );
		if ( is_wp_error( $terms ) ) { return array(); }
		return array_map( array( $this, 'prepare_term' ), $terms );
	}

	/** @param WP_Term $term Term. @return array<string,mixed> */
	public function prepare_term( $term ) {
		return array( 'id' => (int) $term->term_id, 'name' => $term->name, 'slug' => $term->slug, 'description' => $term->description, 'count' => (int) $term->count, 'url' => get_term_link( $term ) );
	}

}
