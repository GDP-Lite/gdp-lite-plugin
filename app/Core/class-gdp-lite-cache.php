<?php
/**
 * GDP Lite cache and invalidation service.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_Cache {
	/** @var self|null */
	private static $instance = null;

	/** @var string */
	private $group = 'gdp_lite';

	/** @var array<string,int> */
	private $runtime_stats = array( 'hits' => 0, 'misses' => 0, 'writes' => 0 );

	/** @return self */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Register invalidation hooks. */
	public function register_hooks() {
		add_action( 'save_post_gdp_game', array( $this, 'flush_on_game_save' ), 99, 3 );
		add_action( 'before_delete_post', array( $this, 'flush_on_game_delete' ) );
		add_action( 'set_object_terms', array( $this, 'flush_on_terms' ), 99, 6 );
		add_action( 'created_term', array( $this, 'flush_on_term_change' ), 99, 3 );
		add_action( 'edited_term', array( $this, 'flush_on_term_change' ), 99, 3 );
		add_action( 'delete_term', array( $this, 'flush_on_term_change' ), 99, 3 );
		add_action( 'update_option_gdp_appearance', array( $this, 'flush' ) );
		add_action( 'update_option_gdp_permalink_bases', array( $this, 'flush' ) );
	}

	/** @return int */
	public function version() {
		$version = (int) get_option( 'gdp_lite_cache_version', 1 );
		return max( 1, $version );
	}

	/** @param string $namespace Namespace. @param mixed $data Key data. @return string */
	public function key( $namespace, $data = array() ) {
		$payload = is_scalar( $data ) ? (string) $data : wp_json_encode( $this->normalize_key_data( $data ) );
		return 'v' . $this->version() . ':' . sanitize_key( $namespace ) . ':' . md5( $payload );
	}

	/** @param string $key Cache key. @param mixed $found Optional found flag. @return mixed */
	public function get( $key, &$found = null ) {
		$value = wp_cache_get( $key, $this->group, false, $found );
		if ( $found ) {
			++$this->runtime_stats['hits'];
			GDP_Lite_Hooks::action( 'cache_hit', $key, $value );
			return $value;
		}

		$transient = get_transient( 'gdp_' . md5( $key ) );
		if ( false !== $transient ) {
			$found = true;
			++$this->runtime_stats['hits'];
			wp_cache_set( $key, $transient, $this->group, $this->ttl() );
			GDP_Lite_Hooks::action( 'cache_hit', $key, $transient );
			return $transient;
		}

		$found = false;
		++$this->runtime_stats['misses'];
		GDP_Lite_Hooks::action( 'cache_miss', $key );
		return false;
	}

	/** @param string $key Key. @param mixed $value Value. @param int|null $ttl TTL. @return bool */
	public function set( $key, $value, $ttl = null ) {
		$ttl = null === $ttl ? $this->ttl() : max( 0, absint( $ttl ) );
		++$this->runtime_stats['writes'];
		wp_cache_set( $key, $value, $this->group, $ttl );
		set_transient( 'gdp_' . md5( $key ), $value, $ttl );
		GDP_Lite_Hooks::action( 'cache_set', $key, $value, $ttl );
		return true;
	}

	/** @param string $key Key. @return bool */
	public function delete( $key ) {
		wp_cache_delete( $key, $this->group );
		delete_transient( 'gdp_' . md5( $key ) );
		return true;
	}

	/** Increment namespace version to invalidate all GDP cached values. */
	public function flush() {
		$new_version = $this->version() + 1;
		update_option( 'gdp_lite_cache_version', $new_version, false );
		update_option( 'gdp_lite_cache_last_flush', time(), false );
		GDP_Lite_Hooks::action( 'cache_flushed', $new_version );
		return $new_version;
	}

	/** @return int */
	public function ttl() {
		$settings = get_option( 'gdp_lite_performance', array() );
		$ttl = isset( $settings['ttl'] ) ? absint( $settings['ttl'] ) : HOUR_IN_SECONDS;
		return (int) GDP_Lite_Hooks::filter( 'cache_ttl', max( 60, $ttl ) );
	}

	/** @return bool */
	public function enabled() {
		$settings = get_option( 'gdp_lite_performance', array() );
		return ! isset( $settings['cache_enabled'] ) || (bool) $settings['cache_enabled'];
	}

	/** @return array<string,mixed> */
	public function status() {
		global $wp_object_cache;
		return array(
			'enabled'              => $this->enabled(),
			'ttl'                  => $this->ttl(),
			'version'              => $this->version(),
			'persistent_object_cache' => wp_using_ext_object_cache(),
			'object_cache_class'   => is_object( $wp_object_cache ) ? get_class( $wp_object_cache ) : '',
			'last_flush'           => (int) get_option( 'gdp_lite_cache_last_flush', 0 ),
			'runtime'              => $this->runtime_stats,
		);
	}

	/** @param int $post_id Post ID. @param WP_Post $post Post. @param bool $update Update. */
	public function flush_on_game_save( $post_id, $post, $update ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) { return; }
		$this->flush();
	}

	/** @param int $post_id Post ID. */
	public function flush_on_game_delete( $post_id ) {
		if ( 'gdp_game' === get_post_type( $post_id ) ) { $this->flush(); }
	}

	/** @param int $object_id Object ID. @param array $terms Terms. @param array $tt_ids IDs. @param string $taxonomy Taxonomy. */
	public function flush_on_terms( $object_id, $terms, $tt_ids, $taxonomy ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( 'gdp_game' === get_post_type( $object_id ) && 0 === strpos( $taxonomy, 'gdp_' ) ) { $this->flush(); }
	}

	/** @param int $term_id Term ID. @param int $tt_id TT ID. @param string $taxonomy Taxonomy. */
	public function flush_on_term_change( $term_id, $tt_id = 0, $taxonomy = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( 0 === strpos( (string) $taxonomy, 'gdp_' ) ) { $this->flush(); }
	}

	/** @param mixed $value Value. @return mixed */
	private function normalize_key_data( $value ) {
		if ( ! is_array( $value ) ) { return $value; }
		ksort( $value );
		foreach ( $value as $key => $item ) {
			$value[ $key ] = is_array( $item ) ? $this->normalize_key_data( $item ) : $item;
		}
		return $value;
	}
}

/** @return GDP_Lite_Cache */
function gdp_cache() { return GDP_Lite_Cache::instance(); }
