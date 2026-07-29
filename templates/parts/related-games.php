<?php /** GDP Template Part. Version: 1.6.0. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$post_id = isset( $post_id ) ? absint( $post_id ) : get_the_ID();
$limit = (int) GDP_Lite_Hooks::filter( 'related_games_limit', 4, $post_id );
$q = gdp_get_related_games( $post_id, $limit );
if ( $q->have_posts() ) : GDP_Lite_Hooks::action( 'before_related_games', $post_id, $q ); ?>
<section class="gdp-related"><h2><?php esc_html_e( 'Related Games', 'gdp-lite' ); ?></h2><div class="gdp-games-grid" style="--gdp-columns:4"><?php while ( $q->have_posts() ) : $q->the_post(); GDP_Lite_Template_Loader::get_template( 'parts/game-card.php' ); endwhile; ?></div></section>
<?php GDP_Lite_Hooks::action( 'after_related_games', $post_id, $q ); endif; wp_reset_postdata();
