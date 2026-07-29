<?php
/**
 * Public hook framework and compatibility bridge.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_Hooks {
	/** @var self|null */
	private static $instance = null;

	/** @return self */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Register framework lifecycle hooks. */
	public function register_hooks() {
		add_action( 'init', array( $this, 'announce_ready' ), 100 );
	}

	/** Announce that all first-party hooks are registered. */
	public function announce_ready() {
		do_action( 'gdp_hooks_ready', GDP() );
	}

	/**
	 * Fire a canonical action and its legacy-prefixed alias.
	 *
	 * @param string $hook Canonical hook without prefix.
	 * @param mixed  ...$args Hook arguments.
	 */
	public static function action( $hook, ...$args ) {
		$hook = sanitize_key( $hook );
		do_action_ref_array( 'gdp_' . $hook, $args );
		do_action_ref_array( 'gdp_lite_' . $hook, $args );
	}

	/**
	 * Apply a canonical filter and its legacy-prefixed alias.
	 *
	 * @param string $hook Canonical hook without prefix.
	 * @param mixed  $value Filtered value.
	 * @param mixed  ...$args Extra arguments.
	 * @return mixed
	 */
	public static function filter( $hook, $value, ...$args ) {
		$hook  = sanitize_key( $hook );
		$value = apply_filters_ref_array( 'gdp_' . $hook, array_merge( array( $value ), $args ) );
		return apply_filters_ref_array( 'gdp_lite_' . $hook, array_merge( array( $value ), $args ) );
	}
}

/** Public action helper. */
function gdp_do_action( $hook, ...$args ) {
	GDP_Lite_Hooks::action( $hook, ...$args );
}

/** Public filter helper. */
function gdp_apply_filters( $hook, $value, ...$args ) {
	return GDP_Lite_Hooks::filter( $hook, $value, ...$args );
}
