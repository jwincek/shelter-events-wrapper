<?php
/**
 * REST API routes v2 — reads programs from the CPT.
 *
 * @package Shelter_Events
 */

declare( strict_types=1 );

/**
 * REST API routes for reading programs/events and triggering generation.
 */
class Shelter_Events_REST {

	private const NAMESPACE = 'shelter-events/v1';

	/**
	 * Register the shelter-events/v1 REST routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/programs',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_programs' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/upcoming',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_upcoming' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'program'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 10,
						'minimum' => 1,
						'maximum' => 50,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/generate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'generate_events' ),
				'permission_callback' => fn() => current_user_can( 'manage_options' ),
				'args'                => array(
					'program' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'weeks'   => array(
						'type'    => 'integer',
						'default' => 8,
						'minimum' => 1,
						'maximum' => 52,
					),
					'dry_run' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/cancel',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'cancel_event' ),
				'permission_callback' => fn() => current_user_can( 'edit_others_posts' ),
				'args'                => array(
					'event_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
					'reason'   => array( 'type' => 'string' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/replace',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'replace_event' ),
				'permission_callback' => fn() => current_user_can( 'edit_others_posts' ),
				'args'                => array(
					'event_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * GET /programs — list active programs.
	 *
	 * @param \WP_REST_Request $request The REST request (no parameters used).
	 * @return \WP_REST_Response
	 */
	public static function get_programs( \WP_REST_Request $request ): \WP_REST_Response { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- required by the REST callback signature.
		$programs = \Shelter_Events\Core\Program_CPT::get_active_programs();

		$output = array();
		foreach ( $programs as $prog ) {
			$output[] = array(
				'slug'        => $prog['slug'],
				'title'       => $prog['title'],
				'description' => $prog['description'] ?? '',
				'days'        => $prog['recurrence']['days'] ?? array(),
				'start_time'  => $prog['recurrence']['start_time'] ?? '',
				'end_time'    => $prog['recurrence']['end_time'] ?? '',
				'cost'        => ( $prog['currency_symbol'] ?? '$' ) . ( $prog['cost'] ?? '0' ),
				'category'    => $prog['category'] ?? '',
			);
		}

		return new \WP_REST_Response( $output, 200 );
	}

	/**
	 * GET /upcoming — list upcoming events, optionally filtered by program.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public static function get_upcoming( \WP_REST_Request $request ): \WP_REST_Response {
		$program  = $request->get_param( 'program' );
		$per_page = $request->get_param( 'per_page' );

		$query = tribe_events()->where( 'starts_after', 'now' )->per_page( $per_page )->order( 'ASC' );

		if ( $program ) {
			$query = $query->where( 'meta_equals', '_shelter_program_slug', $program );
		}

		$events = $query->all();

		// Hide cancelled events that have been replaced.
		$events = array_filter(
			$events,
			function ( $event ) {
				return ! get_post_meta( $event->ID, '_shelter_replaced_by', true );
			}
		);

		$output = array();

		foreach ( $events as $event ) {
			$variable = get_post_meta( $event->ID, '_shelter_variable_pricing', true );

			$output[] = array(
				'id'               => $event->ID,
				'title'            => get_the_title( $event ),
				'start_date'       => get_post_meta( $event->ID, '_EventStartDate', true ),
				'end_date'         => get_post_meta( $event->ID, '_EventEndDate', true ),
				'url'              => get_permalink( $event ),
				'programme'        => get_post_meta( $event->ID, '_shelter_program_slug', true ),
				'cancelled'        => (bool) get_post_meta( $event->ID, '_shelter_cancelled', true ),
				'venue'            => tribe_get_venue( $event->ID ),
				'cost'             => ( 'yes' === $variable ) ? __( 'Varies', 'shelter-events-wrapper' ) : tribe_get_cost( $event->ID, true ),
				'variable_pricing' => ( 'yes' === $variable ),
			);
		}

		return new \WP_REST_Response( $output, 200 );
	}

	/**
	 * POST /generate — run event generation, optionally as a dry run.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public static function generate_events( \WP_REST_Request $request ): \WP_REST_Response {
		$result = \Shelter_Events\Abilities\Provider::handle_shelter_generate_events(
			array(
				'program' => $request->get_param( 'program' ),
				'weeks'   => $request->get_param( 'weeks' ),
				'dry_run' => $request->get_param( 'dry_run' ),
			)
		);

		return new \WP_REST_Response( $result, 200 );
	}

	/**
	 * POST /cancel — mark a generated event as cancelled.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public static function cancel_event( \WP_REST_Request $request ): \WP_REST_Response {
		$result = \Shelter_Events\Abilities\Provider::handle_shelter_cancel_event(
			array(
				'event_id' => $request->get_param( 'event_id' ),
				'reason'   => $request->get_param( 'reason' ) ?? '',
			)
		);

		return new \WP_REST_Response( $result, $result['success'] ? 200 : 404 );
	}

	/**
	 * POST /replace — cancel an event and create a draft replacement.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return \WP_REST_Response
	 */
	public static function replace_event( \WP_REST_Request $request ): \WP_REST_Response {
		$result = \Shelter_Events\Abilities\Provider::handle_shelter_replace_event(
			array(
				'event_id' => $request->get_param( 'event_id' ),
			)
		);

		return new \WP_REST_Response( $result, $result['success'] ? 200 : 400 );
	}
}
