<?php
/**
 * Small service registry used as GDP Lite's stable module access layer.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_Registry {
	/** @var array<string,object> */
	private $services = array();

	/**
	 * Store a service.
	 *
	 * @param string $id Service ID.
	 * @param object $service Service instance.
	 * @return object
	 */
	public function set( $id, $service ) {
		$this->services[ sanitize_key( $id ) ] = $service;
		return $service;
	}

	/**
	 * Get a service or null.
	 *
	 * @param string $id Service ID.
	 * @return object|null
	 */
	public function get( $id ) {
		$id = sanitize_key( $id );
		return isset( $this->services[ $id ] ) ? $this->services[ $id ] : null;
	}

	/** @param string $id Service ID. @return bool */
	public function has( $id ) {
		return null !== $this->get( $id );
	}

	/** @return array<string,object> */
	public function all() {
		return $this->services;
	}
}
