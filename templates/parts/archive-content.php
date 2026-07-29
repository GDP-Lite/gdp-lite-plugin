<?php
/** GDP Archive Builder template part. Version: 2.2.1. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$title = isset( $title ) ? $title : '';
$description = isset( $description ) ? $description : '';
GDP()->archive_builder()->render( $title, $description );
