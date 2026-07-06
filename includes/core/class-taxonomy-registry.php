<?php
/**
 * Config-driven taxonomy registration.
 *
 * Reads taxonomies.json and registers custom taxonomies against both
 * the TEC events post type and the shelter_program CPT.
 *
 * @package Shelter_Events\Core
 */

declare( strict_types=1 );

namespace Shelter_Events\Core;

/**
 * Registers config-defined taxonomies and seeds their default terms.
 */
final class Taxonomy_Registry {

	/**
	 * Hook taxonomy registration and term seeding into init.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 11 );
		add_action( 'init', array( __CLASS__, 'seed_default_terms' ), 12 );
	}

	/**
	 * Register each taxonomy defined in taxonomies.json.
	 *
	 * If a taxonomy already exists (e.g. registered by another plugin), it
	 * is only attached to the configured post types instead of re-registered.
	 */
	public static function register_taxonomies(): void {
		$taxonomies = Config::get_item( 'taxonomies', 'taxonomies', array() );

		foreach ( $taxonomies as $slug => $definition ) {
			if ( taxonomy_exists( $slug ) ) {
				foreach ( $definition['post_types'] as $pt ) {
					register_taxonomy_for_object_type( $slug, $pt );
				}
				continue;
			}

			register_taxonomy(
				$slug,
				$definition['post_types'],
				$definition['args']
			);
		}
	}

	/**
	 * Insert the default terms declared in taxonomies.json, skipping existing ones.
	 */
	public static function seed_default_terms(): void {
		$taxonomies = Config::get_item( 'taxonomies', 'taxonomies', array() );

		foreach ( $taxonomies as $slug => $definition ) {
			if ( empty( $definition['default_terms'] ) ) {
				continue;
			}

			foreach ( $definition['default_terms'] as $term_def ) {
				if ( term_exists( $term_def['slug'], $slug ) ) {
					continue;
				}

				wp_insert_term(
					$term_def['name'],
					$slug,
					array(
						'slug'        => $term_def['slug'],
						'description' => $term_def['description'] ?? '',
					)
				);
			}
		}
	}
}
