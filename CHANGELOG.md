# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2026-07-05

### Added

- Event replacement: a "Replace" action on shelter-generated events (TEC list
  row action and the Generate page) cancels the original and creates a draft
  replacement, pre-populated with the original's date, time, venue, and
  organizer, that opens in the block editor.
- Global blackout dates (Events → Generate Events) and per-program blackout
  dates (program metabox); dates in either list are skipped during generation,
  and pre-existing events on blackout dates are flagged in the admin UI.
- Variable-pricing flag on programs — displays "Varies" across all The Events
  Calendar cost surfaces via the `tribe_get_cost` filter.
- `POST /shelter-events/v1/replace` REST endpoint and `shelter-events/replace-event`
  ability (WP 6.9+).
- `uninstall.php` with preserve-by-default data retention: cron and transients
  are always cleaned up; programs, generated events, and options are removed
  only when the "Delete all data when this plugin is deleted" opt-in is
  enabled.
- WordPress.org packaging: `readme.txt`, `LICENSE`, `.distignore`, and a
  Plugin Check CI job.

### Changed

- Public identity renamed to `shelter-events-wrapper` (plugin folder, main
  file, and text domain now match, as required by WordPress.org). Internal
  identifiers (`Shelter_Events` namespace, `shelter_events_*` prefixes, REST
  namespace, block name) are unchanged.
- The Events Calendar dependency is now declared via the `Requires Plugins`
  header, and activation-time seeding/cron scheduling is guarded on its
  presence.

### Fixed

- Block editor script rewritten without JSX so it actually executes in the
  editor (it previously shipped untranspiled), and registered with its script
  dependencies via an asset manifest.
- Seeded programs are imported with their organizer attached (the seed data
  referenced an organizer key that did not exist).
- Events with missing or malformed date meta are skipped instead of fataling
  in the admin Upcoming table and the event list block.
- Hardened input handling (`wp_unslash` + sanitization on all superglobal
  reads) and output escaping (`wp_die`, admin notices, Help-page link URLs);
  user-facing Abilities messages are now translatable.

## [2.1.0] - 2025-04-02

### Added

- Event sync on program save — all future events are updated automatically
  when a program changes; staff can opt in to updating past events per save.
- Website/Booking URL and Facebook URL fields on programs; the website URL is
  written to TEC's native Event Website field.
- In-dashboard staff guide at Events → Help, rendered from README.md.
- `shelter_events_event_synced` action hook.

### Fixed

- Duplicate venue/organizer creation on repeated generation (exact-title
  lookup now uses a direct query; `get_posts()` silently ignores title args).
- New venues/organizers are created published; existing drafts are promoted.

## [2.0.0] - 2025-03-19

### Changed

- Programs are now a custom post type (`shelter_program`) managed in the
  WordPress admin, replacing JSON config as the source of truth; bundled seed
  programs are imported once on activation.

### Added

- "Event Schedule Settings" metabox with day-of-week selectors, venue and
  organizer fields, pricing, capacity, and an active/paused toggle.
- Custom admin columns and the `shelter_events_program_imported` action hook.

## [1.0.0] - 2025-03-19

### Added

- Initial release: JSON config-driven programs, The Events Calendar ORM event
  generation, WP-Cron scheduling, REST API, server-rendered Gutenberg block,
  and WP 6.9 Abilities API support.
