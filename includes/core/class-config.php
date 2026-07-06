<?php
/**
 * Config loader — reads JSON config files from the config/ directory.
 *
 * In v2, config files provide schema defaults, generation settings, and
 * seed data for the importer. The shelter_program CPT is the live source
 * of truth for program definitions.
 *
 * @package Shelter_Events\Core
 */

declare( strict_types=1 );

namespace Shelter_Events\Core;

/**
 * Loads and caches JSON config files from the plugin's config/ directory.
 */
final class Config {

	/**
	 * Decoded config data, keyed by file name (without extension).
	 *
	 * @var array
	 */
	private static array $cache = array();

	/**
	 * Absolute path to the config directory, with trailing slash.
	 *
	 * @var string
	 */
	private static string $dir = '';

	/**
	 * Set the config directory and reset the cache.
	 *
	 * @param string $config_dir Absolute path to the config directory.
	 */
	public static function init( string $config_dir ): void {
		self::$dir   = trailingslashit( $config_dir );
		self::$cache = array();
	}

	/**
	 * Load and decode a JSON config file, with per-request caching.
	 *
	 * @param string $file Config file name without the .json extension.
	 * @return array Decoded config data, or an empty array if the file is missing.
	 */
	public static function get( string $file ): array {
		if ( ! isset( self::$cache[ $file ] ) ) {
			$path = self::$dir . $file . '.json';

			if ( ! file_exists( $path ) ) {
				self::$cache[ $file ] = array();
				return array();
			}

			$raw                  = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$decoded              = json_decode( $raw, true, 512, JSON_THROW_ON_ERROR );
			self::$cache[ $file ] = is_array( $decoded ) ? $decoded : array();
		}

		return self::$cache[ $file ];
	}

	/**
	 * Get a top-level key from a config file.
	 *
	 * @param string $file          Config file name without the .json extension.
	 * @param string $key           Top-level array key to read.
	 * @param mixed  $default_value Value to return when the key is absent.
	 * @return mixed The config value, or the default.
	 */
	public static function get_item( string $file, string $key, mixed $default_value = null ): mixed {
		$config = self::get( $file );
		return $config[ $key ] ?? $default_value;
	}

	/**
	 * Get a nested config value using dot notation (e.g. "generation.lookahead_weeks").
	 *
	 * @param string $file          Config file name without the .json extension.
	 * @param string $path          Dot-separated path of array keys.
	 * @param mixed  $default_value Value to return when any segment is absent.
	 * @return mixed The config value, or the default.
	 */
	public static function dot( string $file, string $path, mixed $default_value = null ): mixed {
		$config   = self::get( $file );
		$segments = explode( '.', $path );
		$current  = $config;

		foreach ( $segments as $segment ) {
			if ( ! is_array( $current ) || ! array_key_exists( $segment, $current ) ) {
				return $default_value;
			}
			$current = $current[ $segment ];
		}

		return $current;
	}

	/**
	 * Clear the in-memory config cache.
	 */
	public static function flush(): void {
		self::$cache = array();
	}
}
