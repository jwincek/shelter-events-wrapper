=== Shelter Events Wrapper ===
Contributors: jeromewincek
Tags: events, recurring events, animal shelter, the events calendar, nonprofit
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 2.2.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Staff-friendly recurring events for The Events Calendar — define shelter programs once and individual events are generated automatically.

== Description ==

Shelter Events Wrapper adds a staff-friendly recurring-event manager on top of the free [The Events Calendar](https://wordpress.org/plugins/the-events-calendar/) plugin. Recurring shelter programs — BINGO nights, spay/neuter clinics, adoption events — are managed as a custom post type from the WordPress admin, and the plugin automatically generates the individual calendar events on a daily WP-Cron schedule.

= Features =

* **Programs as a custom post type** — staff define a program's schedule (days of the week, times, timezone), venue, organizer, pricing, capacity, and contact info in a dedicated metabox. No calendar plumbing required.
* **Automatic event generation** — a daily cron job creates The Events Calendar events for each active program over a configurable lookahead window (8 weeks by default). Duplicate-safe: re-running generation never creates the same event twice.
* **Manual generation with dry run** — generate on demand from Events → Generate Events, previewing what would be created before committing.
* **Event sync** — updating a program automatically updates all of its future events (title, description, times, venue, organizer, cost, links, tags). Past events are preserved unless you explicitly opt in per save.
* **Cancel and replace** — cancel a single event instance, or replace it with a one-off special event: the original is cancelled and a pre-populated draft replacement opens in the block editor.
* **Blackout dates** — global and per-program date lists on which no events are generated (holidays, closures).
* **Variable pricing** — display "Varies" instead of a fixed cost for programs with multiple price points.
* **Shelter Event List block** — a server-rendered Gutenberg block listing upcoming events, filterable by program, with three layouts.
* **REST API** — public read endpoints for programs and upcoming events; authenticated endpoints for generate, cancel, and replace.
* **Abilities API** — on WordPress 6.9+, the generate/list/cancel/replace operations are registered as Abilities.

Venues and organizers are matched by exact name and reused, so repeated generation never duplicates them.

= Requirements =

* The Events Calendar (free) — declared via the plugin dependency header, so WordPress will ask you to install and activate it first.
* PHP 8.1 or newer.
* WordPress 6.9 or newer.

== Installation ==

1. Install and activate **The Events Calendar** (free version).
2. Install **Shelter Events Wrapper** through the WordPress plugins screen, or upload the plugin files to `/wp-content/plugins/shelter-events-wrapper/`.
3. Activate the plugin. Two starter programs are imported from bundled seed data on first activation.
4. Go to **Events → All Programs** to manage programs, and **Events → Generate Events** to generate events, set blackout dates, and configure options.

== Frequently Asked Questions ==

= Does this replace The Events Calendar? =

No — it requires it. The Events Calendar provides the calendar, event pages, and views; this plugin adds a recurring-program layer that generates and maintains those events for you.

= Doesn't The Events Calendar already support recurring events? =

Recurring events are a feature of the paid Events Calendar Pro. This plugin provides a recurrence workflow for the free version, designed around how shelter staff actually manage programs.

= What happens if I run generation twice? =

Nothing bad. Each generated event carries a deterministic fingerprint of its program and date, and generation skips any event that already exists.

= Does editing a program change events that already happened? =

No. Past events are historical records and are left untouched by default. A per-save checkbox lets you consciously opt in to updating past events too.

= What data is removed when I delete the plugin? =

By default your programs, generated events, and settings are all preserved. If you want a full cleanup, enable "Delete all data when this plugin is deleted" under Events → Generate Events → Uninstall Options before deleting the plugin.

== Changelog ==

= 2.2.0 =
* New: Replace action on generated events — cancels the original and opens a pre-populated draft replacement in the block editor.
* New: Global and per-program blackout dates skipped during generation, with admin flagging of pre-existing events on blackout days.
* New: Variable-pricing flag displays "Varies" across all cost surfaces.
* New: `POST /shelter-events/v1/replace` REST endpoint and `shelter_replace_event` ability.
* New: Uninstall support with preserve-by-default data retention and an opt-in full cleanup.
* Fixed: block editor script now loads correctly in the editor.
* Fixed: seeded programs are imported with their organizer attached.
* Fixed: events with missing or malformed date meta no longer break the admin table or the event list block.
* Security: hardened input unslashing/sanitization and output escaping throughout.

= 2.1.0 =
* New: event sync — future events are updated automatically when their program changes; optional per-save inclusion of past events.
* New: Website/Booking URL and Facebook URL fields on programs.
* New: in-dashboard staff guide at Events → Help.
* Fixed: duplicate venue/organizer creation on repeated generation.

= 2.0.0 =
* Programs are now a custom post type with a full schedule-settings metabox, replacing JSON config editing.
* One-time import of bundled seed programs on activation.

= 1.0.0 =
* Initial release: config-driven programs, daily event generation, REST API, Gutenberg block.

== Upgrade Notice ==

= 2.2.0 =
First release prepared for the WordPress.org plugin directory. Adds event replacement, blackout dates, variable pricing, and uninstall cleanup options.
