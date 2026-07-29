<?php
/**
 * GDP Lite developer logger.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_Logger {
	/** @var self|null */
	private static $instance = null;

	/** @var int */
	private $limit = 200;

	/** @return self */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Register opt-in diagnostics listeners. */
	public function register_hooks() {
		add_action( 'gdp_cache_hit', array( $this, 'cache_hit' ), 10, 2 );
		add_action( 'gdp_cache_miss', array( $this, 'cache_miss' ), 10, 1 );
		add_action( 'gdp_cache_flushed', array( $this, 'cache_flushed' ), 10, 1 );
		add_action( 'gdp_before_query', array( $this, 'before_query' ), 10, 2 );
		add_action( 'gdp_after_query', array( $this, 'after_query' ), 10, 2 );
		add_action( 'gdp_before_template', array( $this, 'before_template' ), 10, 2 );
		add_action( 'rest_request_after_callbacks', array( $this, 'rest_request' ), 10, 3 );
	}

	/** @return array<string,mixed> */
	public function settings() {
		return wp_parse_args(
			get_option( 'gdp_lite_developer', array() ),
			array(
				'enabled'  => 0,
				'query'    => 0,
				'rest'     => 0,
				'template' => 0,
				'cache'    => 0,
				'log_level'=> 'info',
			)
		);
	}

	/** @param string $channel Channel. @return bool */
	public function channel_enabled( $channel ) {
		$settings = $this->settings();
		return ! empty( $settings['enabled'] ) && ! empty( $settings[ $channel ] );
	}

	/** @param string $level Level. @param string $channel Channel. @param string $message Message. @param array<string,mixed> $context Context. */
	public function log( $level, $channel, $message, $context = array() ) {
		$settings = $this->settings();
		if ( empty( $settings['enabled'] ) ) {
			return;
		}
		$allowed = array( 'debug' => 10, 'info' => 20, 'warning' => 30, 'error' => 40 );
		$minimum = isset( $allowed[ $settings['log_level'] ] ) ? $allowed[ $settings['log_level'] ] : 20;
		$current = isset( $allowed[ $level ] ) ? $allowed[ $level ] : 20;
		if ( $current < $minimum ) {
			return;
		}
		$logs = get_option( 'gdp_lite_logs', array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}
		array_unshift(
			$logs,
			array(
				'time'    => time(),
				'level'   => sanitize_key( $level ),
				'channel' => sanitize_key( $channel ),
				'message' => sanitize_text_field( $message ),
				'context' => $this->sanitize_context( $context ),
			)
		);
		$logs = array_slice( $logs, 0, $this->limit );
		update_option( 'gdp_lite_logs', $logs, false );
	}

	/** @return array<int,array<string,mixed>> */
	public function get_logs() {
		$logs = get_option( 'gdp_lite_logs', array() );
		return is_array( $logs ) ? $logs : array();
	}

	/** Clear stored logs. */
	public function clear() {
		delete_option( 'gdp_lite_logs' );
	}

	public function cache_hit( $key, $value = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( $this->channel_enabled( 'cache' ) ) { $this->log( 'debug', 'cache', 'Cache hit.', array( 'key' => $key ) ); }
	}
	public function cache_miss( $key ) {
		if ( $this->channel_enabled( 'cache' ) ) { $this->log( 'debug', 'cache', 'Cache miss.', array( 'key' => $key ) ); }
	}
	public function cache_flushed( $version ) {
		if ( $this->channel_enabled( 'cache' ) ) { $this->log( 'info', 'cache', 'GDP cache flushed.', array( 'version' => (int) $version ) ); }
	}
	public function before_query( $wp_args, $normalized ) {
		if ( $this->channel_enabled( 'query' ) ) { $this->log( 'debug', 'query', 'Game query started.', array( 'normalized' => $normalized, 'wp_args' => $wp_args ) ); }
	}
	public function after_query( $query, $normalized ) {
		if ( $this->channel_enabled( 'query' ) ) { $this->log( 'info', 'query', 'Game query completed.', array( 'found_posts' => isset( $query->found_posts ) ? (int) $query->found_posts : 0, 'args' => $normalized ) ); }
	}
	public function before_template( $name, $file ) {
		if ( $this->channel_enabled( 'template' ) ) { $this->log( 'info', 'template', 'Template loaded.', array( 'name' => $name, 'file' => $file ) ); }
	}
	public function rest_request( $response, $handler, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! $this->channel_enabled( 'rest' ) || ! $request instanceof WP_REST_Request || 0 !== strpos( $request->get_route(), '/gdp/' ) ) { return $response; }
		$status = $response instanceof WP_REST_Response ? $response->get_status() : 200;
		$this->log( 'info', 'rest', 'GDP REST request completed.', array( 'method' => $request->get_method(), 'route' => $request->get_route(), 'status' => $status ) );
		return $response;
	}

	/** @param mixed $value Value. @return mixed */
	private function sanitize_context( $value ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( array_slice( $value, 0, 30, true ) as $key => $item ) {
				$out[ sanitize_key( (string) $key ) ] = $this->sanitize_context( $item );
			}
			return $out;
		}
		if ( is_object( $value ) ) { return get_class( $value ); }
		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) { return $value; }
		return mb_substr( sanitize_text_field( (string) $value ), 0, 500 );
	}
}

/** @return GDP_Lite_Logger */
function gdp_logger() { return GDP_Lite_Logger::instance(); }
