<?php
/**
 * Front-end template loader.
 *
 * @package GDPLite
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class GDP_Lite_Template_Loader {
	private static $instance = null;
	public static function instance() {
		if ( null === self::$instance ) { self::$instance = new self(); }
		return self::$instance;
	}
	public function register_hooks() {
		add_filter( 'template_include', array( $this, 'template_include' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}
	public function enqueue_assets() {
		if ( is_singular( 'gdp_game' ) || is_post_type_archive( 'gdp_game' ) || is_tax( array( 'gdp_game_type', 'gdp_provider', 'gdp_collection', 'gdp_game_category' ) ) ) {
			wp_enqueue_style( 'gdp-lite-public', GDP_LITE_URL . 'assets/css/public.css', array(), GDP_LITE_VERSION );
		}
	}
	public function template_include( $template ) {
		$name = '';
		if ( is_singular( 'gdp_game' ) ) { $name = 'single-game.php'; }
		elseif ( is_post_type_archive( 'gdp_game' ) ) { $name = 'archive-game.php'; }
		elseif ( is_tax( 'gdp_game_type' ) ) { $name = 'taxonomy-game-type.php'; }
		elseif ( is_tax( 'gdp_provider' ) ) { $name = 'taxonomy-game-provider.php'; }
		elseif ( is_tax( 'gdp_collection' ) ) { $name = 'taxonomy-game-collection.php'; }
		elseif ( is_tax( 'gdp_game_category' ) ) { $name = 'taxonomy-game-category.php'; }
		if ( ! $name ) { return $template; }
		$found = $this->locate_template( $name );
		$selected = $found ? $found : $template;
		return GDP_Lite_Hooks::filter( 'template_include', $selected, $name, $template );
	}
	public function locate_template( $name ) {
		$name = ltrim( str_replace( '..', '', $name ), '/\\' );
		$theme = locate_template( array( 'gdp-lite/' . $name ) );
		if ( $theme ) { return GDP_Lite_Hooks::filter( 'template_path', $theme, $name, 'theme' ); }
		$file = GDP_LITE_DIR . 'templates/' . $name;
		$file = file_exists( $file ) ? $file : '';
		return GDP_Lite_Hooks::filter( 'template_path', $file, $name, 'plugin' );
	}
	public static function get_template( $name, $args = array() ) {
		$file = self::instance()->locate_template( $name );
		if ( ! $file ) { return; }
		$args = GDP_Lite_Hooks::filter( 'template_args', is_array( $args ) ? $args : array(), $name, $file );
		GDP_Lite_Hooks::action( 'before_template', $name, $file, $args );
		if ( is_array( $args ) ) { extract( $args, EXTR_SKIP ); }
		include $file;
		GDP_Lite_Hooks::action( 'after_template', $name, $file, $args );
	}
	public static function appearance_classes() {
		$a = wp_parse_args( get_option( 'gdp_appearance', array() ), array( 'button_style'=>'filled', 'hover'=>'lift' ) );
		return 'gdp-button-' . sanitize_html_class( $a['button_style'] ) . ' gdp-hover-' . sanitize_html_class( $a['hover'] );
	}
}
