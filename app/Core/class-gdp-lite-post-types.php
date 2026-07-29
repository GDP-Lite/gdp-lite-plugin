<?php
/**
 * Post types and taxonomies.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_Post_Types {
	/** @var self|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register_hooks() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public static function permalink_defaults() {
		return array(
			'game'       => 'game-db',
			'type'       => 'game-type',
			'provider'   => 'game-provider',
			'collection' => 'game-collection',
		);
	}

	public static function permalink_bases() {
		$defaults = self::permalink_defaults();
		$saved    = get_option( 'gdp_permalink_bases', array() );
		$saved    = is_array( $saved ) ? $saved : array();
		return wp_parse_args( $saved, $defaults );
	}

	public function register() {
		$bases = self::permalink_bases();

		register_post_type(
			'gdp_game',
			array(
				'labels' => array(
					'name'          => __( 'Games', 'gdp-lite' ),
					'singular_name' => __( 'Game', 'gdp-lite' ),
					'add_new_item'  => __( 'Add New Game', 'gdp-lite' ),
					'edit_item'     => __( 'Edit Game', 'gdp-lite' ),
					'menu_name'     => __( 'Games', 'gdp-lite' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'show_in_menu' => false,
				'menu_icon'    => 'dashicons-games',
				'capability_type' => array( 'game', 'games' ),
				'map_meta_cap'    => true,
				'capabilities'    => array(
					'edit_posts' => 'edit_games',
					'delete_posts' => 'delete_games',
					'publish_posts' => 'edit_games',
					'read_private_posts' => 'edit_games',
				),
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'has_archive'  => $bases['game'],
				'rewrite'      => array( 'slug' => $bases['game'], 'with_front' => false ),
			)
		);

		$this->register_taxonomy( 'gdp_game_type', __( 'Game Types', 'gdp-lite' ), __( 'Game Type', 'gdp-lite' ), true, $bases['type'] );
		$this->register_taxonomy( 'gdp_provider', __( 'Providers', 'gdp-lite' ), __( 'Provider', 'gdp-lite' ), false, $bases['provider'] );
		$this->register_taxonomy( 'gdp_collection', __( 'Collections', 'gdp-lite' ), __( 'Collection', 'gdp-lite' ), true, $bases['collection'] );
		$this->register_taxonomy( 'gdp_game_category', __( 'Categories', 'gdp-lite' ), __( 'Category', 'gdp-lite' ), true, 'game-category' );

		if ( ! term_exists( 'Slots', 'gdp_game_type' ) ) {
			wp_insert_term( 'Slots', 'gdp_game_type', array( 'slug' => 'slots' ) );
		}
	}

	private function register_taxonomy( $taxonomy, $plural, $singular, $hierarchical, $rewrite_slug ) {
		register_taxonomy(
			$taxonomy,
			array( 'gdp_game' ),
			array(
				'labels' => array(
					'name'          => $plural,
					'singular_name' => $singular,
					'menu_name'     => $plural,
				),
				'public'            => true,
				'hierarchical'      => $hierarchical,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'show_in_menu'      => false,
				'rewrite'           => array( 'slug' => $rewrite_slug, 'with_front' => false ),
			)
		);
	}
}
