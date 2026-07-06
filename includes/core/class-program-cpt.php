<?php
/**
 * Shelter Program custom post type.
 *
 * Registers the `shelter_program` CPT and a dedicated "Event Schedule"
 * metabox below the editor where staff configure recurrence days, times,
 * venue, cost, capacity, and contact info.
 *
 * The CPT replaces config/events.json as the source of truth for program
 * definitions. The Event Generator now reads published shelter_program
 * posts instead of JSON.
 *
 * @package Shelter_Events\Core
 */

declare( strict_types=1 );

namespace Shelter_Events\Core;

/**
 * Registers the shelter_program CPT, its metabox, save handling, and admin columns.
 */
final class Program_CPT {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'shelter_program';

	/**
	 * Meta key prefix.
	 *
	 * @var string
	 */
	private const META_PREFIX = '_shelter_prog_';

	/**
	 * Meta field definitions with defaults.
	 *
	 * @var array<string, array>
	 */
	private const FIELDS = array(
		'recurrence_days'      => array(
			'type'    => 'array',
			'default' => array(),
			'label'   => 'Recurrence days',
		),
		'start_time'           => array(
			'type'    => 'string',
			'default' => '18:00',
			'label'   => 'Start time',
		),
		'end_time'             => array(
			'type'    => 'string',
			'default' => '21:00',
			'label'   => 'End time',
		),
		'timezone'             => array(
			'type'    => 'string',
			'default' => 'America/New_York',
			'label'   => 'Timezone',
		),
		'venue_name'           => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Venue name',
		),
		'venue_address'        => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Venue address',
		),
		'venue_city'           => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Venue city',
		),
		'venue_state'          => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Venue state',
		),
		'venue_zip'            => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Venue ZIP',
		),
		'organizer_name'       => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Organizer name',
		),
		'organizer_phone'      => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Organizer phone',
		),
		'organizer_email'      => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Organizer email',
		),
		'organizer_website'    => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Organizer website',
		),
		'cost'                 => array(
			'type'    => 'string',
			'default' => '0',
			'label'   => 'Cost',
		),
		'variable_pricing'     => array(
			'type'    => 'string',
			'default' => 'no',
			'label'   => 'Variable pricing',
		),
		'currency_symbol'      => array(
			'type'    => 'string',
			'default' => '$',
			'label'   => 'Currency symbol',
		),
		'capacity'             => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Capacity',
		),
		'contact_email'        => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Contact email',
		),
		'requires_appointment' => array(
			'type'    => 'string',
			'default' => 'no',
			'label'   => 'Requires appointment',
		),
		'age_restriction'      => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Age restriction',
		),
		'website_url'          => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Event website URL',
		),
		'facebook_url'         => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Facebook page URL',
		),
		'tags'                 => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Event tags (comma-separated)',
		),
		'featured'             => array(
			'type'    => 'string',
			'default' => 'no',
			'label'   => 'Featured event',
		),
		'blackout_dates'       => array(
			'type'    => 'string',
			'default' => '',
			'label'   => 'Blackout dates',
		),
		'active'               => array(
			'type'    => 'string',
			'default' => 'yes',
			'label'   => 'Active (generates events)',
		),
	);

	/**
	 * Hook into WordPress.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 10 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'admin_column_content' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register the CPT.
	 */
	public static function register_post_type(): void {
		if ( post_type_exists( self::POST_TYPE ) ) {
			return;
		}

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => __( 'Programs', 'shelter-events-wrapper' ),
					'singular_name'      => __( 'Program', 'shelter-events-wrapper' ),
					'menu_name'          => __( 'Shelter Programs', 'shelter-events-wrapper' ),
					'add_new'            => __( 'Add New Program', 'shelter-events-wrapper' ),
					'add_new_item'       => __( 'Add New Program', 'shelter-events-wrapper' ),
					'edit_item'          => __( 'Edit Program', 'shelter-events-wrapper' ),
					'new_item'           => __( 'New Program', 'shelter-events-wrapper' ),
					'view_item'          => __( 'View Program', 'shelter-events-wrapper' ),
					'search_items'       => __( 'Search Programs', 'shelter-events-wrapper' ),
					'not_found'          => __( 'No programs found.', 'shelter-events-wrapper' ),
					'not_found_in_trash' => __( 'No programs found in trash.', 'shelter-events-wrapper' ),
					'all_items'          => __( 'All Programs', 'shelter-events-wrapper' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=tribe_events',
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'editor', 'thumbnail' ),
				'menu_icon'       => 'dashicons-calendar-alt',
				'capability_type' => 'post',
				'has_archive'     => false,
				'rewrite'         => false,
			)
		);
	}

	/**
	 * Enqueue admin CSS for the metabox.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public static function enqueue_admin_assets( string $hook ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- required by the admin_enqueue_scripts signature.
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'shelter-events-program-metabox',
			SHELTER_EVENTS_URL . 'assets/css/program-metabox.css',
			array(),
			SHELTER_EVENTS_VERSION
		);
	}

	// ── Meta Boxes ────────────────────────────────────────────────────────────

	/**
	 * Register the settings metabox below the editor.
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'shelter-program-schedule',
			__( 'Event Schedule Settings', 'shelter-events-wrapper' ),
			array( __CLASS__, 'render_metabox' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the metabox.
	 *
	 * @param \WP_Post $post The program post being edited.
	 */
	public static function render_metabox( \WP_Post $post ): void {
		wp_nonce_field( 'shelter_program_save', 'shelter_program_nonce' );

		$meta = self::get_all_meta( $post->ID );

		// Show sync feedback if events were just updated.
		$sync_count = get_transient( 'shelter_events_sync_result_' . $post->ID );
		if ( $sync_count ) {
			delete_transient( 'shelter_events_sync_result_' . $post->ID );
			$sync_count = (int) $sync_count;
			printf(
				'<div class="notice notice-success inline" style="margin:0 0 12px;"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %d = number of events updated */
						_n(
							'%d existing event was updated to match this program.',
							'%d existing events were updated to match this program.',
							$sync_count,
							'shelter-events-wrapper'
						),
						$sync_count
					)
				)
			);
		}

		$days_of_week = array(
			'monday'    => __( 'Monday', 'shelter-events-wrapper' ),
			'tuesday'   => __( 'Tuesday', 'shelter-events-wrapper' ),
			'wednesday' => __( 'Wednesday', 'shelter-events-wrapper' ),
			'thursday'  => __( 'Thursday', 'shelter-events-wrapper' ),
			'friday'    => __( 'Friday', 'shelter-events-wrapper' ),
			'saturday'  => __( 'Saturday', 'shelter-events-wrapper' ),
			'sunday'    => __( 'Sunday', 'shelter-events-wrapper' ),
		);
		?>
		<div class="shelter-metabox">

			<!-- Status Banner -->
			<div class="shelter-metabox__status <?php echo 'yes' === $meta['active'] ? 'shelter-metabox__status--active' : 'shelter-metabox__status--paused'; ?>">
				<div>
					<label>
						<input type="checkbox" name="shelter_prog_active" value="yes"
							<?php checked( $meta['active'], 'yes' ); ?> />
						<strong><?php esc_html_e( 'Active', 'shelter-events-wrapper' ); ?></strong>
						— <?php esc_html_e( 'When checked, the daily cron will generate event instances for this program.', 'shelter-events-wrapper' ); ?>
					</label>
				</div>
				<div class="shelter-metabox__sync-option">
					<label>
						<input type="checkbox" name="shelter_sync_include_past" value="1" />
						<?php esc_html_e( 'Also update past events when saving changes', 'shelter-events-wrapper' ); ?>
					</label>
					<span class="description"><?php esc_html_e( '(Leave unchecked to only update future events.)', 'shelter-events-wrapper' ); ?></span>
				</div>
			</div>

			<!-- Section: Schedule -->
			<fieldset class="shelter-metabox__section">
				<legend><?php esc_html_e( 'Schedule', 'shelter-events-wrapper' ); ?></legend>

				<div class="shelter-metabox__field">
					<label><?php esc_html_e( 'Recurring days', 'shelter-events-wrapper' ); ?></label>
					<div class="shelter-metabox__days">
						<?php foreach ( $days_of_week as $value => $label ) : ?>
							<label class="shelter-metabox__day-chip">
								<input type="checkbox" name="shelter_prog_recurrence_days[]"
									value="<?php echo esc_attr( $value ); ?>"
									<?php checked( in_array( $value, (array) $meta['recurrence_days'], true ) ); ?> />
								<span><?php echo esc_html( $label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="shelter-metabox__row">
					<div class="shelter-metabox__field shelter-metabox__field--half">
						<label for="shelter_prog_start_time"><?php esc_html_e( 'Start time', 'shelter-events-wrapper' ); ?></label>
						<input type="time" id="shelter_prog_start_time"
							name="shelter_prog_start_time"
							value="<?php echo esc_attr( $meta['start_time'] ); ?>" />
					</div>
					<div class="shelter-metabox__field shelter-metabox__field--half">
						<label for="shelter_prog_end_time"><?php esc_html_e( 'End time', 'shelter-events-wrapper' ); ?></label>
						<input type="time" id="shelter_prog_end_time"
							name="shelter_prog_end_time"
							value="<?php echo esc_attr( $meta['end_time'] ); ?>" />
					</div>
				</div>

				<div class="shelter-metabox__field">
					<label for="shelter_prog_timezone"><?php esc_html_e( 'Timezone', 'shelter-events-wrapper' ); ?></label>
					<select id="shelter_prog_timezone" name="shelter_prog_timezone">
						<?php
						$timezones = timezone_identifiers_list();
						foreach ( $timezones as $tz ) :
							?>
							<option value="<?php echo esc_attr( $tz ); ?>"
								<?php selected( $meta['timezone'], $tz ); ?>>
								<?php echo esc_html( $tz ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="shelter-metabox__field">
					<label for="shelter_prog_blackout_dates"><?php esc_html_e( 'Blackout dates', 'shelter-events-wrapper' ); ?></label>
					<textarea id="shelter_prog_blackout_dates" name="shelter_prog_blackout_dates"
						class="widefat" rows="3"
						placeholder="<?php esc_attr_e( 'One date per line: 2026-12-25', 'shelter-events-wrapper' ); ?>"
					><?php echo esc_textarea( $meta['blackout_dates'] ); ?></textarea>
					<span class="description">
						<?php esc_html_e( 'Dates when this program should not generate events (YYYY-MM-DD, one per line). These are checked in addition to the global blackout dates.', 'shelter-events-wrapper' ); ?>
					</span>
				</div>
			</fieldset>

			<!-- Section: Venue -->
			<fieldset class="shelter-metabox__section">
				<legend><?php esc_html_e( 'Venue', 'shelter-events-wrapper' ); ?></legend>
				<div class="shelter-metabox__field">
					<label for="shelter_prog_venue_name"><?php esc_html_e( 'Venue name', 'shelter-events-wrapper' ); ?></label>
					<input type="text" id="shelter_prog_venue_name" name="shelter_prog_venue_name"
						value="<?php echo esc_attr( $meta['venue_name'] ); ?>" class="widefat"
						placeholder="<?php esc_attr_e( 'e.g. VCPA Humane Society — Main Hall', 'shelter-events-wrapper' ); ?>" />
				</div>
				<div class="shelter-metabox__field">
					<label for="shelter_prog_venue_address"><?php esc_html_e( 'Street address', 'shelter-events-wrapper' ); ?></label>
					<input type="text" id="shelter_prog_venue_address" name="shelter_prog_venue_address"
						value="<?php echo esc_attr( $meta['venue_address'] ); ?>" class="widefat" />
				</div>
				<div class="shelter-metabox__row">
					<div class="shelter-metabox__field shelter-metabox__field--third">
						<label for="shelter_prog_venue_city"><?php esc_html_e( 'City', 'shelter-events-wrapper' ); ?></label>
						<input type="text" id="shelter_prog_venue_city" name="shelter_prog_venue_city"
							value="<?php echo esc_attr( $meta['venue_city'] ); ?>" />
					</div>
					<div class="shelter-metabox__field shelter-metabox__field--third">
						<label for="shelter_prog_venue_state"><?php esc_html_e( 'State', 'shelter-events-wrapper' ); ?></label>
						<input type="text" id="shelter_prog_venue_state" name="shelter_prog_venue_state"
							value="<?php echo esc_attr( $meta['venue_state'] ); ?>" />
					</div>
					<div class="shelter-metabox__field shelter-metabox__field--third">
						<label for="shelter_prog_venue_zip"><?php esc_html_e( 'ZIP', 'shelter-events-wrapper' ); ?></label>
						<input type="text" id="shelter_prog_venue_zip" name="shelter_prog_venue_zip"
							value="<?php echo esc_attr( $meta['venue_zip'] ); ?>" />
					</div>
				</div>
			</fieldset>

			<!-- Section: Organizer -->
			<fieldset class="shelter-metabox__section">
				<legend><?php esc_html_e( 'Organizer', 'shelter-events-wrapper' ); ?></legend>
				<div class="shelter-metabox__row">
					<div class="shelter-metabox__field shelter-metabox__field--half">
						<label for="shelter_prog_organizer_name"><?php esc_html_e( 'Name', 'shelter-events-wrapper' ); ?></label>
						<input type="text" id="shelter_prog_organizer_name" name="shelter_prog_organizer_name"
							value="<?php echo esc_attr( $meta['organizer_name'] ); ?>" />
					</div>
					<div class="shelter-metabox__field shelter-metabox__field--half">
						<label for="shelter_prog_organizer_phone"><?php esc_html_e( 'Phone', 'shelter-events-wrapper' ); ?></label>
						<input type="tel" id="shelter_prog_organizer_phone" name="shelter_prog_organizer_phone"
							value="<?php echo esc_attr( $meta['organizer_phone'] ); ?>" />
					</div>
				</div>
				<div class="shelter-metabox__row">
					<div class="shelter-metabox__field shelter-metabox__field--half">
						<label for="shelter_prog_organizer_email"><?php esc_html_e( 'Email', 'shelter-events-wrapper' ); ?></label>
						<input type="email" id="shelter_prog_organizer_email" name="shelter_prog_organizer_email"
							value="<?php echo esc_attr( $meta['organizer_email'] ); ?>" />
					</div>
					<div class="shelter-metabox__field shelter-metabox__field--half">
						<label for="shelter_prog_organizer_website"><?php esc_html_e( 'Website', 'shelter-events-wrapper' ); ?></label>
						<input type="url" id="shelter_prog_organizer_website" name="shelter_prog_organizer_website"
							value="<?php echo esc_attr( $meta['organizer_website'] ); ?>" />
					</div>
				</div>
			</fieldset>

			<!-- Section: Pricing & Logistics -->
			<fieldset class="shelter-metabox__section">
				<legend><?php esc_html_e( 'Pricing & Logistics', 'shelter-events-wrapper' ); ?></legend>
				<div class="shelter-metabox__row">
					<div class="shelter-metabox__field shelter-metabox__field--quarter">
						<label for="shelter_prog_currency_symbol"><?php esc_html_e( 'Currency', 'shelter-events-wrapper' ); ?></label>
						<input type="text" id="shelter_prog_currency_symbol" name="shelter_prog_currency_symbol"
							value="<?php echo esc_attr( $meta['currency_symbol'] ); ?>" style="width:60px;" />
					</div>
					<div class="shelter-metabox__field shelter-metabox__field--quarter">
						<label for="shelter_prog_cost"><?php esc_html_e( 'Cost', 'shelter-events-wrapper' ); ?></label>
						<input type="text" id="shelter_prog_cost" name="shelter_prog_cost"
							value="<?php echo esc_attr( $meta['cost'] ); ?>"
							placeholder="<?php esc_attr_e( '0 = Free', 'shelter-events-wrapper' ); ?>" />
					</div>
					<div class="shelter-metabox__field shelter-metabox__field--quarter">
						<label>
							<input type="checkbox" name="shelter_prog_variable_pricing" value="yes"
								<?php checked( $meta['variable_pricing'], 'yes' ); ?> />
							<?php esc_html_e( 'Variable pricing', 'shelter-events-wrapper' ); ?>
						</label>
						<span class="description"><?php esc_html_e( 'Displays "Varies" instead of a fixed cost.', 'shelter-events-wrapper' ); ?></span>
					</div>
					<div class="shelter-metabox__field shelter-metabox__field--quarter">
						<label for="shelter_prog_capacity"><?php esc_html_e( 'Capacity', 'shelter-events-wrapper' ); ?></label>
						<input type="text" id="shelter_prog_capacity" name="shelter_prog_capacity"
							value="<?php echo esc_attr( $meta['capacity'] ); ?>"
							placeholder="<?php esc_attr_e( 'e.g. 120', 'shelter-events-wrapper' ); ?>" />
					</div>
				</div>
				<div class="shelter-metabox__row">
					<div class="shelter-metabox__field shelter-metabox__field--quarter">
						<label for="shelter_prog_age_restriction"><?php esc_html_e( 'Age restriction', 'shelter-events-wrapper' ); ?></label>
						<input type="text" id="shelter_prog_age_restriction" name="shelter_prog_age_restriction"
							value="<?php echo esc_attr( $meta['age_restriction'] ); ?>"
							placeholder="<?php esc_attr_e( 'e.g. 18+', 'shelter-events-wrapper' ); ?>" />
					</div>
				</div>
				<div class="shelter-metabox__row">
					<div class="shelter-metabox__field shelter-metabox__field--half">
						<label for="shelter_prog_contact_email"><?php esc_html_e( 'Contact email', 'shelter-events-wrapper' ); ?></label>
						<input type="email" id="shelter_prog_contact_email" name="shelter_prog_contact_email"
							value="<?php echo esc_attr( $meta['contact_email'] ); ?>" />
					</div>
					<div class="shelter-metabox__field shelter-metabox__field--half">
						<label>
							<input type="checkbox" name="shelter_prog_requires_appointment" value="yes"
								<?php checked( $meta['requires_appointment'], 'yes' ); ?> />
							<?php esc_html_e( 'Requires appointment', 'shelter-events-wrapper' ); ?>
						</label>
					</div>
				</div>
			</fieldset>

			<!-- Section: Event Links -->
			<fieldset class="shelter-metabox__section">
				<legend><?php esc_html_e( 'Event Links', 'shelter-events-wrapper' ); ?></legend>
				<p class="description" style="margin-top:0;">
					<?php esc_html_e( 'The website URL is written to each generated event\'s "Event Website" field in The Events Calendar.', 'shelter-events-wrapper' ); ?>
				</p>
				<div class="shelter-metabox__field">
					<label for="shelter_prog_website_url"><?php esc_html_e( 'Website / Booking URL', 'shelter-events-wrapper' ); ?></label>
					<input type="url" id="shelter_prog_website_url" name="shelter_prog_website_url"
						value="<?php echo esc_attr( $meta['website_url'] ); ?>" class="widefat"
						placeholder="<?php esc_attr_e( 'e.g. https://www.supersaas.com/schedule/Your_Org/CATS', 'shelter-events-wrapper' ); ?>" />
				</div>
				<div class="shelter-metabox__field">
					<label for="shelter_prog_facebook_url"><?php esc_html_e( 'Facebook Page URL', 'shelter-events-wrapper' ); ?></label>
					<input type="url" id="shelter_prog_facebook_url" name="shelter_prog_facebook_url"
						value="<?php echo esc_attr( $meta['facebook_url'] ); ?>" class="widefat"
						placeholder="<?php esc_attr_e( 'e.g. https://www.facebook.com/YourShelterBingo', 'shelter-events-wrapper' ); ?>" />
				</div>
			</fieldset>

			<!-- Section: Event Display -->
			<fieldset class="shelter-metabox__section">
				<legend><?php esc_html_e( 'Event Display', 'shelter-events-wrapper' ); ?></legend>
				<div class="shelter-metabox__row">
					<div class="shelter-metabox__field shelter-metabox__field--half">
						<label for="shelter_prog_tags"><?php esc_html_e( 'Tags (comma-separated)', 'shelter-events-wrapper' ); ?></label>
						<input type="text" id="shelter_prog_tags" name="shelter_prog_tags"
							value="<?php echo esc_attr( $meta['tags'] ); ?>" class="widefat"
							placeholder="<?php esc_attr_e( 'bingo, fundraiser, community', 'shelter-events-wrapper' ); ?>" />
					</div>
					<div class="shelter-metabox__field shelter-metabox__field--half">
						<label>
							<input type="checkbox" name="shelter_prog_featured" value="yes"
								<?php checked( $meta['featured'], 'yes' ); ?> />
							<?php esc_html_e( 'Mark generated events as Featured', 'shelter-events-wrapper' ); ?>
						</label>
					</div>
				</div>
			</fieldset>

		</div>
		<?php
	}

	/**
	 * Save metabox data.
	 *
	 * @param int      $post_id Program post ID.
	 * @param \WP_Post $post    Program post object.
	 */
	public static function save_meta( int $post_id, \WP_Post $post ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- required by the save_post_{$post_type} signature.
		$nonce = isset( $_POST['shelter_program_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['shelter_program_nonce'] ) )
			: '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'shelter_program_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Recurrence days is a checkbox array.
		$days       = isset( $_POST['shelter_prog_recurrence_days'] )
			? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['shelter_prog_recurrence_days'] ) )
			: array();
		$valid_days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
		$days       = array_intersect( $days, $valid_days );
		update_post_meta( $post_id, self::META_PREFIX . 'recurrence_days', $days );

		// Checkbox fields (value="yes" if checked, "no" if not).
		$checkbox_fields = array( 'active', 'requires_appointment', 'featured', 'variable_pricing' );
		foreach ( $checkbox_fields as $field ) {
			$value = isset( $_POST[ 'shelter_prog_' . $field ] ) ? 'yes' : 'no';
			update_post_meta( $post_id, self::META_PREFIX . $field, $value );
		}

		// Text fields.
		$text_fields = array(
			'start_time',
			'end_time',
			'timezone',
			'venue_name',
			'venue_address',
			'venue_city',
			'venue_state',
			'venue_zip',
			'organizer_name',
			'organizer_phone',
			'organizer_email',
			'organizer_website',
			'cost',
			'currency_symbol',
			'capacity',
			'contact_email',
			'age_restriction',
			'tags',
			'website_url',
			'facebook_url',
		);

		foreach ( $text_fields as $field ) {
			$key   = 'shelter_prog_' . $field;
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			update_post_meta( $post_id, self::META_PREFIX . $field, $value );
		}

		// Blackout dates — textarea, sanitized to preserve newlines and validate format.
		$raw_dates   = isset( $_POST['shelter_prog_blackout_dates'] )
			? sanitize_textarea_field( wp_unslash( $_POST['shelter_prog_blackout_dates'] ) )
			: '';
		$valid_dates = self::parse_blackout_dates( $raw_dates );
		update_post_meta( $post_id, self::META_PREFIX . 'blackout_dates', implode( "\n", $valid_dates ) );
	}

	/**
	 * Parse a newline-separated string of dates into an array of valid Y-m-d strings.
	 *
	 * @param string $raw Raw textarea value.
	 * @return string[] Valid date strings.
	 */
	public static function parse_blackout_dates( string $raw ): array {
		if ( trim( $raw ) === '' ) {
			return array();
		}

		$lines = preg_split( '/[\r\n,]+/', $raw );
		$dates = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			// Accept YYYY-MM-DD format only.
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $line ) ) {
				$dt = \DateTime::createFromFormat( 'Y-m-d', $line );
				if ( $dt && $dt->format( 'Y-m-d' ) === $line ) {
					$dates[] = $line;
				}
			}
		}

		return array_unique( $dates );
	}

	// ── Admin Columns ─────────────────────────────────────────────────────────

	/**
	 * Add custom columns to the programs list table.
	 *
	 * @param array $columns Existing list table columns.
	 * @return array Columns with the schedule columns inserted after the title.
	 */
	public static function admin_columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['shelter_days']   = __( 'Days', 'shelter-events-wrapper' );
				$new['shelter_time']   = __( 'Time', 'shelter-events-wrapper' );
				$new['shelter_cost']   = __( 'Cost', 'shelter-events-wrapper' );
				$new['shelter_active'] = __( 'Active', 'shelter-events-wrapper' );
			}
		}
		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column key being rendered.
	 * @param int    $post_id Program post ID for the current row.
	 */
	public static function admin_column_content( string $column, int $post_id ): void {
		$meta = self::get_all_meta( $post_id );

		switch ( $column ) {
			case 'shelter_days':
				$days = (array) $meta['recurrence_days'];
				echo esc_html(
					implode(
						', ',
						array_map(
							function ( $d ) {
								return ucfirst( substr( $d, 0, 3 ) );
							},
							$days
						)
					)
				);
				break;

			case 'shelter_time':
				echo esc_html( $meta['start_time'] . ' – ' . $meta['end_time'] );
				break;

			case 'shelter_cost':
				if ( 'yes' === $meta['variable_pricing'] ) {
					echo esc_html__( 'Varies', 'shelter-events-wrapper' );
				} else {
					$cost = $meta['cost'];
					echo esc_html(
						( '0' === $cost || '' === $cost )
							? __( 'Free', 'shelter-events-wrapper' )
							: $meta['currency_symbol'] . $cost
					);
				}
				break;

			case 'shelter_active':
				echo 'yes' === $meta['active']
					? '<span style="color:green;">&#9679; ' . esc_html__( 'Active', 'shelter-events-wrapper' ) . '</span>'
					: '<span style="color:#999;">&#9675; ' . esc_html__( 'Paused', 'shelter-events-wrapper' ) . '</span>';
				break;
		}
	}

	// ── Data Access ───────────────────────────────────────────────────────────

	/**
	 * Get all meta for a program post, with defaults applied.
	 *
	 * @param int $post_id Program post ID.
	 * @return array<string, mixed> Associative array of field values.
	 */
	public static function get_all_meta( int $post_id ): array {
		$data = array();
		foreach ( self::FIELDS as $field => $def ) {
			$raw = get_post_meta( $post_id, self::META_PREFIX . $field, true );

			if ( '' === $raw || false === $raw ) {
				$data[ $field ] = $def['default'];
			} else {
				$data[ $field ] = $raw;
			}
		}
		return $data;
	}

	/**
	 * Get all active program posts with their meta, ready for the generator.
	 *
	 * @return array<int, array> Keyed by post ID, values are program config arrays
	 *                           matching the shape the Event_Generator expects.
	 */
	public static function get_active_programs(): array {
		$posts = get_posts(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);

		$programs = array();
		foreach ( $posts as $post ) {
			$meta = self::get_all_meta( $post->ID );

			if ( 'yes' !== $meta['active'] ) {
				continue;
			}

			if ( empty( $meta['recurrence_days'] ) ) {
				continue;
			}

			$programs[ $post->ID ] = array(
				'post_id'         => $post->ID,
				'slug'            => $post->post_name,
				'title'           => $post->post_title,
				'description'     => $post->post_content,
				'category'        => '', // Will be set from taxonomy term if assigned.
				'recurrence'      => array(
					'days'       => (array) $meta['recurrence_days'],
					'start_time' => $meta['start_time'],
					'end_time'   => $meta['end_time'],
					'timezone'   => $meta['timezone'],
				),
				'venue'           => array(
					'venue'   => $meta['venue_name'],
					'address' => $meta['venue_address'],
					'city'    => $meta['venue_city'],
					'state'   => $meta['venue_state'],
					'zip'     => $meta['venue_zip'],
				),
				'organizer'       => array(
					'organizer' => $meta['organizer_name'],
					'phone'     => $meta['organizer_phone'],
					'email'     => $meta['organizer_email'],
					'website'   => $meta['organizer_website'],
				),
				'cost'            => $meta['cost'],
				'currency_symbol' => $meta['currency_symbol'],
				'featured'        => 'yes' === $meta['featured'],
				'tags'            => array_filter( array_map( 'trim', explode( ',', $meta['tags'] ) ) ),
				'website_url'     => $meta['website_url'],
				'facebook_url'    => $meta['facebook_url'],
				'blackout_dates'  => self::parse_blackout_dates( $meta['blackout_dates'] ),
				'meta'            => array(
					'_shelter_program'              => $post->post_name,
					'_shelter_capacity'             => $meta['capacity'],
					'_shelter_contact_email'        => $meta['contact_email'],
					'_shelter_requires_appointment' => $meta['requires_appointment'],
					'_shelter_age_restriction'      => $meta['age_restriction'],
					'_shelter_variable_pricing'     => $meta['variable_pricing'],
				),
			);

			// Attach taxonomy term as category.
			$terms = wp_get_object_terms( $post->ID, 'shelter_program_cat', array( 'fields' => 'slugs' ) );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$programs[ $post->ID ]['category']  = $terms[0];
				$programs[ $post->ID ]['event_cat'] = wp_get_object_terms( $post->ID, 'shelter_program_cat', array( 'fields' => 'names' ) );
			}
		}

		return $programs;
	}
}
