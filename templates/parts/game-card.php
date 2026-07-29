<?php /** GDP Template Part. Version: 1.6.0. @package GDPLite */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$post_id = get_the_ID();
$play_url = get_post_meta( $post_id, '_gdp_play_url', true );
$button_text = get_post_meta( $post_id, '_gdp_button_text', true );
$badge = get_post_meta( $post_id, '_gdp_badge', true );
$target = $play_url ? $play_url : get_permalink();
$card_data = GDP_Lite_Hooks::filter( 'game_card_data', array( 'post_id'=>$post_id, 'play_url'=>$play_url, 'button_text'=>$button_text, 'badge'=>$badge, 'target'=>$target ), get_post( $post_id ) );
$play_url = $card_data['play_url'] ?? $play_url; $button_text = $card_data['button_text'] ?? $button_text; $badge = $card_data['badge'] ?? $badge; $target = $card_data['target'] ?? $target;
GDP_Lite_Hooks::action( 'before_game_card', $post_id, $card_data );
?>
<article <?php post_class( GDP_Lite_Hooks::filter( 'game_card_class', 'gdp-game-card', $post_id, $card_data ) ); ?>>
	<?php GDP_Lite_Hooks::action( 'inside_game_card_start', $post_id, $card_data ); ?>
	<a class="gdp-game-media" href="<?php echo esc_url( $target ); ?>"<?php echo $play_url ? ' rel="nofollow"' : ''; ?>>
		<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large', array( 'loading'=>'lazy' ) ); } else { echo '<span class="gdp-game-placeholder">GDP</span>'; } ?>
		<?php if ( $badge ) : ?><span class="gdp-game-badge"><?php echo esc_html( $badge ); ?></span><?php endif; ?>
	</a>
	<div class="gdp-game-body">
		<h2 class="gdp-game-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<?php GDP_Lite_Template_Loader::get_template( 'parts/game-meta.php', array( 'post_id'=>$post_id ) ); ?>
		<a class="gdp-game-button" href="<?php echo esc_url( $target ); ?>"<?php echo $play_url ? ' rel="nofollow"' : ''; ?>><?php echo esc_html( $button_text ? $button_text : __( 'View Game', 'gdp-lite' ) ); ?></a>
	</div>
	<?php GDP_Lite_Hooks::action( 'inside_game_card_end', $post_id, $card_data ); ?>
</article>
<?php GDP_Lite_Hooks::action( 'after_game_card', $post_id, $card_data ); ?>
