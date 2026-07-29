# GDP Lite Hook Framework

GDP Lite 2.0.2 exposes canonical `gdp_*` hooks. Existing `gdp_lite_*` aliases continue to run for compatibility.

## Lifecycle actions

- `gdp_loaded( GDP_Lite $app )`
- `gdp_hooks_ready( GDP_Lite $app )`

## Query filters and actions

- `gdp_query_raw_args( array $args, GDP_Lite_Query $query )`
- `gdp_query_normalized_args( array $args, GDP_Lite_Query $query )`
- `gdp_query_args( array $wp_args, array $args, GDP_Lite_Query $query )`
- `gdp_before_query( array $wp_args, array $args, GDP_Lite_Query $query )`
- `gdp_after_query( WP_Query $wp_query, array $args, GDP_Lite_Query $query )`
- `gdp_related_query_args( array $wp_args, WP_Post $game )`

## Template filters and actions

- `gdp_template_include`
- `gdp_template_path`
- `gdp_template_args`
- `gdp_before_template`
- `gdp_after_template`
- `gdp_before_archive`
- `gdp_after_archive`
- `gdp_before_archive_content`
- `gdp_after_archive_content`
- `gdp_before_archive_header`
- `gdp_after_archive_header`
- `gdp_before_single`
- `gdp_after_single`
- `gdp_before_single_content`
- `gdp_after_single_content`

## Card actions and filters

- `gdp_game_card_data`
- `gdp_game_card_class`
- `gdp_before_game_card`
- `gdp_inside_game_card_start`
- `gdp_inside_game_card_end`
- `gdp_after_game_card`

## Related games

- `gdp_related_games_limit`
- `gdp_before_related_games`
- `gdp_after_related_games`

## Shortcodes

- `gdp_shortcode_games_atts`
- `gdp_before_games_shortcode`
- `gdp_after_games_shortcode`
- `gdp_games_shortcode_html`

## REST API

- `gdp_rest_games_query_args`
- `gdp_rest_terms_query_args`
- `gdp_rest_prepare_game`
- `gdp_rest_games_response`
- `gdp_rest_game_response`

## Helpers

```php
gdp_do_action( 'before_custom_area', $game_id );
$value = gdp_apply_filters( 'custom_value', $value, $game_id );
```

## Performance and cache hooks (2.0.3)

- `gdp_cache_ttl`
- `gdp_cache_hit`
- `gdp_cache_miss`
- `gdp_cache_set`
- `gdp_cache_flushed`
- `gdp_query_cache_enabled`
- `gdp_query_cache_hit`
- `gdp_query_cache_set`

Use `gdp_cache()->flush()` after an extension changes data outside the normal WordPress game and taxonomy APIs.
