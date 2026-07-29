# GDP Lite Application Structure

GDP Lite 2.0.1 introduces a WordPress-native modular application structure without Composer.

- `Core/` bootstrap, autoloader, registry, post types, translations
- `Admin/` admin screens and game editing
- `Frontend/` templates, shortcodes and AJAX filters
- `Query/` reusable game query engine
- `REST/` public read-only API
- `Blocks/` Gutenberg blocks
- `Import/` JSON/CSV import and export controller
- `Export/`, `Fields/`, `Templates/`, `Helpers/` reserved extension boundaries

Use `GDP()` as the stable application entry point and `GDP()->service( 'id' )` to access a registered module.
