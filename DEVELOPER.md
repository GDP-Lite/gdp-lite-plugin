# GDP Lite Developer Framework

GDP Lite 2.0.4 adds opt-in diagnostics under **GDP Lite → Developer**.

## Services

```php
GDP()->logger();
GDP()->cache();
GDP()->templates();
GDP()->rest();
```

## Logging

```php
gdp_logger()->log(
    'info',
    'custom',
    'Extension completed a task.',
    array( 'item_id' => 123 )
);
```

Log levels: `debug`, `info`, `warning`, `error`.
Stored entries are capped at 200 and are disabled by default.

## Diagnostics

The Developer screen reports:

- WordPress, PHP, plugin and database versions
- REST endpoint location
- object-cache status
- query/cache runtime counters
- active theme template overrides
- recent Query, REST, Template and Cache events

Developer logging should normally remain disabled on production sites.
