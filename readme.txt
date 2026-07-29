=== GDP Lite ===
Contributors: gdp
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 2.2.1
License: GPLv2 or later

Lightweight gaming data manager for WordPress.

== Version 1.3.0 ==
Professional Admin UI release:
* Clean GDP menu without duplicate Games entries.
* Dashboard header, five focused statistics and consistent 10px cards.
* Quick Actions with Add Game, Import, Providers, Collections and Settings.
* Shortcode cards with one-click copy.
* Recent games table with image, provider, status and updated date.
* Improved Games list columns and taxonomy filters.
* Redesigned Game Data editor with Basic, Gameplay, Links & Display and Features tabs.
* Game Type, Provider, Category and Collection selectors inside the main editor.
* Slot feature toggles for Wild, Scatter, Free Spins, Bonus Buy, Jackpot and more.
* No Composer or vendor dependency.

== Shortcode ==
[gdp_games type="slots" limit="8" columns="4"]


== REST API ==

GDP Lite v1.8 adds a public, read-only API namespace at `/wp-json/gdp/v1/`.

* `GET /games`
* `GET /games/{id}`
* `GET /providers`
* `GET /types`
* `GET /collections`
* `GET /categories`

The games endpoint supports provider, type, collection, category, volatility, features, featured, RTP, maximum-win, search, sorting and pagination parameters. Responses include `X-WP-Total` and `X-WP-TotalPages` headers. No public create, update or delete routes are registered.

== Changelog ==

= 2.2.1 =
* Added Archive Builder with sortable page sections.
* Added archive search, sorting, per-page controls, grid columns and card styles.
* Added dynamic field ordering support for archive queries.
* Added numeric and previous/next pagination modes.
* Added customizable empty-result content.
* Applied Archive Builder to game and taxonomy archives.


= 2.2.0 =
* Added Builder Core with component registry, validated layout schemas, layout storage, rendering API, admin ordering UI and default Card/Single/Archive layouts.

= 2.1.0 =
* Added the Dynamic Fields runtime registry.
* Added field definitions, validation, sanitization and public helper APIs.
* Added default RTP, volatility, maximum win, buy feature, features and release date definitions.
* Added extension hooks for registering and handling fields.


= 2.0.5 =
* LTS release with migrations, capabilities, health checks and autoloader hardening.

= 1.9.0 =
* Added four native dynamic Gutenberg blocks: Game Grid, Game Filters, Featured Games, and Provider Games.
* Added live server-side previews and Inspector controls.
* Blocks reuse the existing Query Engine, template system, appearance settings, and AJAX filters.


= 1.8.0 =
* Added the public read-only GDP REST API.
* Added game list/detail and taxonomy endpoints.
* Added REST validation, pagination headers, cache versioning and extension filters.

= 1.3.1 =
* Added front-end corner style setting: rounded 10px or square 0px.
* Applied the selected corner style to game cards, badges, meta labels and buttons.

= 1.3.0 =
* Added English and Simplified Chinese admin language support.
* Added Auto, English and Simplified Chinese language settings.
* Fixed shortcode cards overflowing narrow dashboard columns.
* Added a first-use Getting Started workflow.
* Localized copy feedback and game status labels.

= 1.4.0 =
* Added readable permalink bases: game-db, game-type, game-provider and game-collection.
* Added permalink conflict validation and automatic rewrite refresh.
* Added Appearance page with radius, shadow, container, image ratio, button, hover and gap controls.
* Added shared front-end design tokens.


== 1.5.0 ==
* Added plugin-first front-end template engine.
* Added theme overrides under /gdp-lite/.
* Added archive, taxonomy, single-game, card, meta, pagination, empty, and related-games templates.
* Switching themes never removes game data.


== 1.6.0 ==
* Added GDP_Lite_Query as the unified game query engine.
* Added provider, type, collection, category, volatility, feature, RTP, max-win, featured and search filters.
* Added date, title, modified, random, RTP, max-win and popularity ordering.
* Added gdp_get_games(), gdp_get_game() and gdp_get_related_games() helper functions.
* Rebuilt [gdp_games] on the Query Engine.
* Added Featured Game control and normalized numeric max-win values.

= 1.7.0 =
* Added AJAX game filters with search, type, provider, volatility and sorting.
* Added AJAX pagination without page reloads.
* Added [gdp_game_filters] and filters="true" support for [gdp_games].
* Added responsive filter UI and loading/error states.

= 2.0.1 =
* Added a lightweight non-Composer autoloader.
* Introduced a modular app directory grouped by responsibility.
* Added a stable GDP() application accessor and service registry.
* Added database and plugin version constants for future migrations.
* Preserved all 2.0.0 templates, APIs, shortcodes, blocks, imports and existing data.

= 2.0.4 =
* Added the canonical GDP Hook Framework with action/filter helpers.
* Added lifecycle, query, template, card, archive, single, shortcode and REST extension points.
* Added compatibility aliases for existing gdp_lite_* hooks.
* Theme and extension developers can customize output without editing plugin core.


== 2.1.5 ==
* Added cumulative Dynamic Fields, Query, REST, Import/Export, Templates, Shortcodes and Gutenberg integration.
