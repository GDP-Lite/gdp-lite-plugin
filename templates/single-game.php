<?php /** GDP Template. Version: 1.6.0. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); $id=get_the_ID(); $play=get_post_meta($id,'_gdp_play_url',true); GDP_Lite_Hooks::action( 'before_single', $id ); ?>
<main class="gdp-template-page"><article <?php post_class( 'gdp-single gdp-games-wrap ' . GDP_Lite_Template_Loader::appearance_classes() ); ?>>
	<div class="gdp-single-hero">
		<div class="gdp-single-media"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?></div>
		<div class="gdp-single-summary"><h1><?php the_title(); ?></h1><?php GDP_Lite_Template_Loader::get_template('parts/game-meta.php',array('post_id'=>$id)); ?>
		<?php foreach ( array('gdp_provider'=>'Provider','gdp_game_type'=>'Game Type','gdp_collection'=>'Collection') as $tax=>$label ) { $terms=get_the_terms($id,$tax); if($terms && !is_wp_error($terms)){ echo '<div class="gdp-single-tax"><strong>'.esc_html__($label,'gdp-lite').':</strong> '; $links=array(); foreach($terms as $term){$links[]='<a href="'.esc_url(get_term_link($term)).'">'.esc_html($term->name).'</a>';} echo wp_kses_post(implode(', ',$links)).'</div>'; } } ?>
		<?php if($play): ?><a class="gdp-game-button gdp-play-button" href="<?php echo esc_url($play); ?>" rel="nofollow"><?php esc_html_e('Play Game','gdp-lite'); ?></a><?php endif; ?></div>
	</div>
	<?php GDP_Lite_Hooks::action( 'before_single_content', $id ); ?>
	<div class="gdp-single-content"><?php the_content(); ?></div>
	<?php GDP_Lite_Hooks::action( 'after_single_content', $id ); ?>
	<?php GDP_Lite_Template_Loader::get_template('parts/related-games.php',array('post_id'=>$id)); ?>
<?php GDP_Lite_Hooks::action( 'after_single', $id ); ?>
</article></main>
<?php endwhile; get_footer();
