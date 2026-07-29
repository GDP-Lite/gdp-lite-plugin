# GDP Lite Archive Builder 2.2.1

The Archive Builder controls the complete `gdp_game` archive and GDP taxonomy archives.

## Admin

Open **GDP Lite → Builder → Archive Page**.

Available sections:

- Archive Header
- Search & Sort Toolbar
- Game Grid
- Pagination
- Empty Result

The grid and empty-result sections are always available so an archive cannot be saved without output.

## Settings

- 2–6 columns
- Classic, Compact, or Minimal card style
- Games per page
- Default order and order-by field
- Search, sorting, and per-page controls
- Numeric or Previous/Next pagination
- Custom empty-result title, description, and button

## Hooks

- `gdp_before_archive_builder`
- `gdp_after_archive_builder`
- `gdp_archive_before_header`
- `gdp_archive_after_header`
- `gdp_archive_before_toolbar`
- `gdp_archive_after_toolbar`
- `gdp_archive_before_grid`
- `gdp_archive_after_grid`
- `gdp_archive_before_pagination`
- `gdp_archive_after_pagination`
- `gdp_archive_before_empty`
- `gdp_archive_after_empty`
- `gdp_archive_builder_saved`
- `gdp_archive_builder_reset`
