<?php
/**
 * Abilities Provider v2 — delegates to CPT-backed programs.
 *
 * @package Shelter_Events\Abilities
 */

declare( strict_types=1 );

namespace Shelter_Events\Abilities;

use Shelter_Events\Core\Config;
use Shelter_Events\Core\Event_Generator;
use Shelter_Events\Core\Program_CPT;

/**
 * Registers the plugin's abilities (WP 6.9+) and implements their handlers.
 */
final class Provider {

	/**
	 * Register every ability declared in abilities.json with the Abilities API.
	 */
	public static function register(): void {
		$abilities = Config::get_item( 'abilities', 'abilities', array() );

		foreach ( $abilities as $slug => $definition ) {
			if ( ! function_exists( 'wp_register_ability' ) ) {
				break;
			}

			wp_register_ability(
				$slug,
				array(
					'label'               => $definition['label'],
					'description'         => $definition['description'],
					'category'            => $definition['category'],
					'permission_callback' => self::build_permission_callback( $definition['permission_callback'] ?? 'manage_options' ),
					'callback'            => array( __CLASS__, "handle_{$slug}" ),
					'schema'              => $definition['schema'] ?? array(),
				)
			);
		}
	}

	/**
	 * Handle: shelter_generate_events.
	 *
	 * @param array $args Ability input: weeks, dry_run, and optional program slug.
	 * @return array Result payload with per-program generation results.
	 */
	public static function handle_shelter_generate_events( array $args ): array {
		$gen_config = Config::get_item( 'events', 'generation', array() );
		$weeks      = $args['weeks'] ?? (int) ( $gen_config['lookahead_weeks'] ?? 8 );
		$dry_run    = $args['dry_run'] ?? false;
		$programs   = Program_CPT::get_active_programs();
		$results    = array();

		if ( ! empty( $args['program'] ) ) {
			// Filter to a single program by slug.
			$target   = $args['program'];
			$programs = array_filter( $programs, fn( $p ) => $p['slug'] === $target );
		}

		foreach ( $programs as $program ) {
			$results[ $program['slug'] ] = Event_Generator::generate_for_program( $program, $weeks, $dry_run );
		}

		return array(
			'success'  => true,
			'dry_run'  => $dry_run,
			'programs' => $results,
		);
	}

	/**
	 * Handle: shelter_list_programs.
	 *
	 * @param array $args Ability input (unused; the ability takes no parameters).
	 * @return array Result payload listing the active programs.
	 */
	public static function handle_shelter_list_programs( array $args ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- required by the ability callback signature.
		$programs = Program_CPT::get_active_programs();

		$output = array();
		foreach ( $programs as $program ) {
			$output[] = array(
				'slug'        => $program['slug'],
				'title'       => $program['title'],
				'description' => $program['description'] ?? '',
				'category'    => $program['category'] ?? '',
				'days'        => $program['recurrence']['days'] ?? array(),
				'start_time'  => $program['recurrence']['start_time'] ?? '',
				'end_time'    => $program['recurrence']['end_time'] ?? '',
				'cost'        => $program['cost'] ?? '',
			);
		}

		return array(
			'success'  => true,
			'programs' => $output,
		);
	}

	/**
	 * Handle: shelter_cancel_event.
	 *
	 * @param array $args Ability input: event_id and optional reason.
	 * @return array Result payload with success flag and message or error.
	 */
	public static function handle_shelter_cancel_event( array $args ): array {
		$event_id = (int) $args['event_id'];
		$post     = get_post( $event_id );

		if ( ! $post || 'tribe_events' !== $post->post_type ) {
			return array(
				'success' => false,
				'error'   => __( 'Event not found.', 'shelter-events-wrapper' ),
			);
		}

		update_post_meta( $event_id, '_shelter_cancelled', '1' );
		update_post_meta( $event_id, '_shelter_cancel_reason', sanitize_text_field( $args['reason'] ?? '' ) );

		wp_update_post(
			array(
				'ID'         => $event_id,
				'post_title' => '[CANCELLED] ' . $post->post_title,
			)
		);

		return array(
			'success'  => true,
			'event_id' => $event_id,
			'message'  => __( 'Event marked as cancelled.', 'shelter-events-wrapper' ),
		);
	}

	/**
	 * Handle: shelter_replace_event
	 *
	 * Cancels a generated event and creates a draft replacement pre-populated
	 * with the original's date, time, venue, and organizer. The replacement
	 * is not linked to the program, so the syncer will not overwrite it.
	 *
	 * @param array $args Ability input: event_id of the event to replace.
	 * @return array Result payload with the original and replacement event IDs.
	 */
	public static function handle_shelter_replace_event( array $args ): array {
		$event_id = (int) $args['event_id'];
		$post     = get_post( $event_id );

		if ( ! $post || 'tribe_events' !== $post->post_type ) {
			return array(
				'success' => false,
				'error'   => __( 'Event not found.', 'shelter-events-wrapper' ),
			);
		}

		if ( get_post_meta( $event_id, '_shelter_replaced_by', true ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Event has already been replaced.', 'shelter-events-wrapper' ),
			);
		}

		if ( get_post_meta( $event_id, '_shelter_cancelled', true ) ) {
			return array(
				'success' => false,
				'error'   => __( 'Event is already cancelled.', 'shelter-events-wrapper' ),
			);
		}

		// Read the original event's scheduling data.
		$start_date      = get_post_meta( $event_id, '_EventStartDate', true );
		$end_date        = get_post_meta( $event_id, '_EventEndDate', true );
		$timezone        = get_post_meta( $event_id, '_EventTimezone', true );
		$venue_id        = (int) get_post_meta( $event_id, '_EventVenueID', true );
		$organizer_id    = (int) get_post_meta( $event_id, '_EventOrganizerID', true );
		$cost            = get_post_meta( $event_id, '_EventCost', true );
		$currency_symbol = get_post_meta( $event_id, '_EventCurrencySymbol', true );

		if ( ! $timezone ) {
			$timezone = wp_timezone_string();
		}
		if ( ! $currency_symbol ) {
			$currency_symbol = '$';
		}

		// Cancel the original event.
		update_post_meta( $event_id, '_shelter_cancelled', '1' );
		update_post_meta( $event_id, '_shelter_cancel_reason', __( 'Replaced by a special event.', 'shelter-events-wrapper' ) );

		$original_title = $post->post_title;
		if ( strpos( $original_title, '[CANCELLED]' ) !== 0 ) {
			wp_update_post(
				array(
					'ID'         => $event_id,
					'post_title' => '[CANCELLED] ' . $original_title,
				)
			);
		}

		// Create the replacement event as a draft via TEC ORM.
		$replacement_args = array(
			'title'           => $original_title,
			'status'          => 'draft',
			'start_date'      => $start_date,
			'end_date'        => $end_date,
			'timezone'        => $timezone,
			'cost'            => $cost,
			'currency_symbol' => $currency_symbol,
		);

		if ( $venue_id ) {
			$replacement_args['venue'] = $venue_id;
		}
		if ( $organizer_id ) {
			$replacement_args['organizer'] = $organizer_id;
		}

		$replacement = tribe_events()->set_args( $replacement_args )->create();

		if ( ! $replacement instanceof \WP_Post ) {
			return array(
				'success' => false,
				'error'   => __( 'Failed to create replacement event.', 'shelter-events-wrapper' ),
			);
		}

		// Cross-reference the pair.
		update_post_meta( $event_id, '_shelter_replaced_by', $replacement->ID );
		update_post_meta( $replacement->ID, '_shelter_replaces_event', $event_id );

		return array(
			'success'              => true,
			'original_event_id'    => $event_id,
			'replacement_event_id' => $replacement->ID,
		);
	}

	/**
	 * Build a permission callback that checks the given capability.
	 *
	 * @param string $capability Capability required to run the ability.
	 * @return callable Permission callback for wp_register_ability().
	 */
	private static function build_permission_callback( string $capability ): callable {
		return static fn(): bool => current_user_can( $capability );
	}
}
