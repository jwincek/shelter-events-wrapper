<?php
/**
 * Dependency manifest for editor.js.
 *
 * The editor script is hand-written (no build step), so this file provides
 * the dependency list that wp-scripts would otherwise generate. WordPress
 * reads it when registering the block's editorScript from block.json.
 *
 * @package Shelter_Events
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-api-fetch',
		'wp-block-editor',
		'wp-blocks',
		'wp-components',
		'wp-element',
		'wp-i18n',
	),
	'version'      => '2.2.0',
);
