# GDP Lite Builder Core

Version 2.2.0 introduces the reusable foundation for visual layouts.

## Services

```php
GDP()->builder();
GDP()->components();
```

## Render a layout

```php
echo gdp_render_layout( 'card', get_the_ID() );
```

## Register a component

```php
gdp_register_component( 'rating', array(
    'label'    => 'Rating',
    'contexts' => array( 'card', 'single' ),
    'render'   => function( $post_id ) {
        return '<span>' . esc_html( get_post_meta( $post_id, '_rating', true ) ) . '</span>';
    },
) );
```

Layouts are stored in the `gdp_builder_layouts` option. Each layout contains a context and an ordered component list. Unknown or context-incompatible components are rejected when saving.

## 2.2.1 Archive Builder

The Archive Page tab now controls the full archive shell, toolbar, query defaults, game grid, pagination, and empty state. It applies to the game post-type archive and GDP taxonomy archives.
