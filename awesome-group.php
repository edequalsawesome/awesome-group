<?php
/**
 * Plugin Name:       Awesome Group
 * Description:       Adds the vertical alignment control that core's Grid layout is missing on Group blocks.
 * Requires at least: 7.1
 * Requires PHP:      7.4
 * Version:           2026.08.001
 * Author:            eD! Thomas
 * Author URI:        https://edequalsaweso.me
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       awesome-group
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AWESOME_GROUP_VERSION', '2026.08.001' );
define( 'AWESOME_GROUP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AWESOME_GROUP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Get cached asset data to avoid multiple file_exists + include calls per request.
 *
 * @return array|false Asset data array or false if file doesn't exist.
 */
function awesome_group_get_asset_data() {
	static $asset = null;

	if ( null === $asset ) {
		$file  = AWESOME_GROUP_PLUGIN_DIR . 'build/index.asset.php';
		$asset = file_exists( $file ) ? include $file : false;
	}

	return $asset;
}

/**
 * Enqueue block editor assets.
 *
 * Editor-only: the single remaining feature renders through an inline style
 * on the front end, so there is no stylesheet to enqueue there.
 */
function awesome_group_enqueue_editor_assets() {
	$asset = awesome_group_get_asset_data();

	if ( false === $asset ) {
		return;
	}

	wp_enqueue_script(
		'awesome-group-editor',
		AWESOME_GROUP_PLUGIN_URL . 'build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'awesome_group_enqueue_editor_assets' );

/**
 * Register the grid vertical alignment attribute on the Group block.
 *
 * Note: core's Row and Stack are layout *variations* of core/group, not
 * separate registered block types, so core/group alone covers them all.
 */
function awesome_group_register_attributes() {
	$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'core/group' );

	if ( ! $block_type ) {
		return;
	}

	// Core's layout support applies verticalAlignment to flex layouts only —
	// see wp-includes/block-supports/layout.php, where the grid branch emits
	// grid-template-columns and grid-template-rows but never align-items.
	$block_type->attributes['awesomeGridVerticalAlignment'] = array(
		'type'    => 'string',
		'default' => '',
	);
}
add_action( 'init', 'awesome_group_register_attributes', 20 );

/**
 * Apply grid vertical alignment on the front end.
 *
 * @param string $block_content The block content.
 * @param array  $block         The full block, including name and attributes.
 * @return string Filtered block content.
 */
function awesome_group_render_block( $block_content, $block ) {
	// Row/Stack are core/group variations, so this single check covers them.
	// Plain string compare: this filter runs for every block on the page.
	if ( 'core/group' !== $block['blockName'] ) {
		return $block_content;
	}

	$attrs = $block['attrs'] ?? array();

	if ( empty( $attrs['awesomeGridVerticalAlignment'] ) ) {
		return $block_content;
	}

	$layout = $attrs['layout'] ?? array();
	if ( ! isset( $layout['type'] ) || 'grid' !== $layout['type'] ) {
		return $block_content;
	}

	$align_map = array(
		'top'     => 'start',
		'center'  => 'center',
		'bottom'  => 'end',
		'stretch' => 'stretch',
	);

	$alignment = $attrs['awesomeGridVerticalAlignment'];

	// is_string: block JSON is not type-enforced server-side, so a crafted
	// block comment can supply an array here.
	if ( ! is_string( $alignment ) || ! isset( $align_map[ $alignment ] ) ) {
		return $block_content;
	}

	$unique_id = 'ag-' . wp_unique_id();

	$processor = new WP_HTML_Tag_Processor( $block_content );
	if ( ! $processor->next_tag() ) {
		return $block_content;
	}
	$processor->add_class( $unique_id );
	$block_content = $processor->get_updated_html();

	// Safe: only values from the closed $align_map above reach this sprintf.
	// esc_attr() is defense-in-depth and does NOT make arbitrary text safe in
	// a <style> context, so any value added here must be whitelisted first.
	$style = sprintf(
		'<style>.%s { align-items: %s; }</style>',
		esc_attr( $unique_id ),
		esc_attr( $align_map[ $alignment ] )
	);

	return $style . $block_content;
}
add_filter( 'render_block', 'awesome_group_render_block', 10, 2 );
