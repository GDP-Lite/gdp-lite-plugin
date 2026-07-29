# GDP Lite Dynamic Meta Box

Version 2.1.2 replaces the hard-coded game data controls with controls generated from the Field Registry.

## Field layout arguments

```php
gdp_register_field( array(
    'name'     => 'hit_rate',
    'label'    => 'Hit Rate',
    'type'     => 'decimal',
    'tab'      => 'gameplay',
    'width'    => 50,
    'priority' => 110,
    'min'      => 0,
    'max'      => 100,
) );
```

Supported widths: `25`, `33`, `50`, and `100`.

## Conditional display

```php
gdp_register_field( array(
    'name'    => 'buy_price',
    'label'   => 'Buy Price',
    'type'    => 'decimal',
    'show_if' => array( 'buy_feature' => 1 ),
) );
```

Conditional display affects the editor interface only. Server-side validation and permissions remain authoritative.

## Hooks

- `gdp_before_render_meta_box`
- `gdp_after_render_meta_box`
- `gdp_before_render_field`
- `gdp_after_render_field`
- `gdp_before_save_meta_box`
- `gdp_after_save_meta_box`
