<?php /** GDP Template Part. Version: 1.6.0. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$post_id = isset( $post_id ) ? absint( $post_id ) : get_the_ID();
$rtp = get_post_meta( $post_id, '_gdp_rtp', true );
$vol = get_post_meta( $post_id, '_gdp_volatility', true );
$max = get_post_meta( $post_id, '_gdp_max_win', true );
if ( $rtp || $vol || $max ) : ?><div class="gdp-game-meta"><?php
if ( $rtp ) { echo '<span>RTP ' . esc_html( $rtp ) . '%</span>'; }
if ( $vol ) { echo '<span>' . esc_html( ucfirst( $vol ) ) . '</span>'; }
if ( $max ) { echo '<span>' . esc_html( $max ) . '</span>'; }
?></div><?php endif; ?>
