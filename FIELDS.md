# GDP Lite Dynamic Fields — v2.1.0

This release introduces the runtime Field Registry foundation. It does not yet replace the existing game edit screen; that integration follows in v2.1.2.

## Register a field

```php
add_action( 'init', function () {
    gdp_register_field( array(
        'name'       => 'hit_rate',
        'label'      => 'Hit Rate',
        'type'       => 'decimal',
        'min'        => 0,
        'max'        => 100,
        'rest'       => true,
        'export'     => true,
        'searchable' => true,
        'filterable' => true,
    ) );
}, 10 );
```

## Access fields

```php
GDP()->fields()->all();
GDP()->fields()->get( 'rtp' );
GDP()->fields()->exists( 'rtp' );

gdp_get_field( $post_id, 'rtp' );
gdp_update_field( $post_id, 'rtp', 96.5 );
gdp_has_field( $post_id, 'rtp' );
gdp_delete_field( $post_id, 'rtp' );
```

## Supported types

`text`, `textarea`, `number`, `decimal`, `url`, `date`, `select`, `switch`, `image`.

## Hooks

- `gdp_field_types`
- `gdp_default_fields`
- `gdp_register_field`
- `gdp_field_registered`
- `gdp_field_unregistered`
- `gdp_fields_ready`
- `gdp_before_validate_field`
- `gdp_validate_field`
- `gdp_before_save_field`
- `gdp_after_save_field`
- `gdp_get_field_value`
- `gdp_render_field`
