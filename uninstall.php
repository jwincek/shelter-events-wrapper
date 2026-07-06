<?php
/**
 * Uninstall cleanup for Shelter Events Wrapper.
 *
 * Two-tier cleanup: ephemeral state (cron, transients) is always removed;
 * content (programs, generated events, options) is only removed when the
 * site owner has opted in via the "Delete all data when this plugin is
 * deleted" setting on the Generate Events page. Default is preserve.
 *
 * @package Shelter_Events
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Clean up plugin data for the current site.
 */
function shelter_events_uninstall_site(): void {
	global $wpdb;

	// ── Ephemeral state — always removed ─────────────────────────────────
	wp_clear_scheduled_hook( 'shelter_events_generate_recurring' );
	delete_transient( 'shelter_events_last_generation' );

	// Sync-result transients are keyed by program post ID — remove by prefix.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup of plugin-owned transients; no API for prefix deletion.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_shelter_events_sync_result_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_shelter_events_sync_result_' ) . '%'
		)
	);

	// ── User content — only with explicit opt-in ─────────────────────────
	if ( ! get_option( 'shelter_events_delete_data_on_uninstall' ) ) {
		return;
	}

	// Program CPT posts (post meta is removed by wp_delete_post).
	$program_ids = get_posts(
		[
			'post_type'   => 'shelter_program',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		]
	);

	foreach ( $program_ids as $program_id ) {
		wp_delete_post( (int) $program_id, true );
	}

	// Generated TEC events, identified by the generator's dedup hash.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup; meta-key lookup across all posts.
	$event_ids = $wpdb->get_col(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_shelter_generated_hash'"
	);

	foreach ( $event_ids as $event_id ) {
		wp_delete_post( (int) $event_id, true );
	}

	// Strip remaining plugin meta from surviving posts (e.g. manually created
	// replacement events keep their _shelter_replaces_event marker).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup of plugin-owned meta keys.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
			$wpdb->esc_like( '_shelter_' ) . '%'
		)
	);

	// Program-category terms. The taxonomy isn't registered during uninstall
	// (the plugin doesn't load), so stub-register it for the term APIs.
	register_taxonomy( 'shelter_program_cat', [] );

	$terms = get_terms(
		[
			'taxonomy'   => 'shelter_program_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
		]
	);

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( (int) $term_id, 'shelter_program_cat' );
		}
	}

	// Options (the opt-in flag last).
	delete_option( 'shelter_events_blackout_dates' );
	delete_option( 'shelter_events_programs_imported' );
	delete_option( 'shelter_events_delete_data_on_uninstall' );
}

if ( is_multisite() ) {
	$shelter_events_site_ids = get_sites( [ 'fields' => 'ids' ] );

	foreach ( $shelter_events_site_ids as $shelter_events_site_id ) {
		switch_to_blog( (int) $shelter_events_site_id );
		shelter_events_uninstall_site();
		restore_current_blog();
	}
} else {
	shelter_events_uninstall_site();
}
