<?php
/**
 * Plugin Name:       Awesome Group
 * Description:       Extends the Group block with responsive layout controls - stack on mobile, custom breakpoints, and more.
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            eD! Thomas
 * Author URI:        https://edequalsaweso.me
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       awesome-group
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AWESOME_GROUP_VERSION', '1.0.0' );
define( 'AWESOME_GROUP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AWESOME_GROUP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Enqueue block editor assets.
 */
function awesome_group_enqueue_editor_assets() {
	$asset_file = AWESOME_GROUP_PLUGIN_DIR . 'build/index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = include $asset_file;

	wp_enqueue_script(
		'awesome-group-editor',
		AWESOME_GROUP_PLUGIN_URL . 'build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_enqueue_style(
		'awesome-group-editor',
		AWESOME_GROUP_PLUGIN_URL . 'build/index.css',
		array(),
		$asset['version']
	);
}
add_action( 'enqueue_block_editor_assets', 'awesome_group_enqueue_editor_assets' );

/**
 * Enqueue frontend styles.
 */
function awesome_group_enqueue_frontend_styles() {
	$asset_file = AWESOME_GROUP_PLUGIN_DIR . 'build/index.asset.php';
	$version = AWESOME_GROUP_VERSION;

	if ( file_exists( $asset_file ) ) {
		$asset = include $asset_file;
		$version = $asset['version'];
	}

	wp_enqueue_style(
		'awesome-group-frontend',
		AWESOME_GROUP_PLUGIN_URL . 'build/style-index.css',
		array(),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'awesome_group_enqueue_frontend_styles' );
add_action( 'enqueue_block_editor_assets', 'awesome_group_enqueue_frontend_styles' );

/**
 * Register custom attributes for the Group block.
 */
function awesome_group_register_attributes() {
	$blocks_to_extend = array( 'core/group', 'core/row' );

	foreach ( $blocks_to_extend as $block_name ) {
		$registry = WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( $block_name );

		if ( $block_type ) {
			$block_type->attributes['awesomeStackOnMobile'] = array(
				'type'    => 'boolean',
				'default' => false,
			);
			$block_type->attributes['awesomeMobileBreakpoint'] = array(
				'type'    => 'string',
				'default' => '768px',
			);
			$block_type->attributes['awesomeStackDirection'] = array(
				'type'    => 'string',
				'default' => 'column',
			);
			$block_type->attributes['awesomeHideOnMobile'] = array(
				'type'    => 'boolean',
				'default' => false,
			);
			$block_type->attributes['awesomeHideOnDesktop'] = array(
				'type'    => 'boolean',
				'default' => false,
			);
		}
	}
}
add_action( 'init', 'awesome_group_register_attributes', 20 );

/**
 * Filter the Group block output to add responsive classes and inline styles.
 */
function awesome_group_render_block( $block_content, $block ) {
	$supported_blocks = array( 'core/group', 'core/row' );

	if ( ! in_array( $block['blockName'], $supported_blocks, true ) ) {
		return $block_content;
	}

	$attrs = $block['attrs'] ?? array();
	$classes = array();
	$styles = array();
	$unique_id = '';

	// Stack on mobile
	if ( ! empty( $attrs['awesomeStackOnMobile'] ) ) {
		$unique_id = 'ag-' . substr( md5( wp_json_encode( $block ) . wp_rand() ), 0, 8 );
		$classes[] = $unique_id;
		$classes[] = 'ag-stack-mobile';

		$breakpoint = $attrs['awesomeMobileBreakpoint'] ?? '768px';
		$direction = $attrs['awesomeStackDirection'] ?? 'column';

		// Generate inline style for custom breakpoint
		$styles[] = sprintf(
			'<style>.%s { --ag-breakpoint: %s; --ag-stack-direction: %s; }</style>',
			esc_attr( $unique_id ),
			esc_attr( $breakpoint ),
			esc_attr( $direction )
		);
	}

	// Hide on mobile
	if ( ! empty( $attrs['awesomeHideOnMobile'] ) ) {
		$classes[] = 'ag-hide-mobile';
	}

	// Hide on desktop
	if ( ! empty( $attrs['awesomeHideOnDesktop'] ) ) {
		$classes[] = 'ag-hide-desktop';
	}

	if ( empty( $classes ) ) {
		return $block_content;
	}

	// Add classes to the block
	$processor = new WP_HTML_Tag_Processor( $block_content );
	if ( $processor->next_tag() ) {
		foreach ( $classes as $class ) {
			$processor->add_class( $class );
		}
		$block_content = $processor->get_updated_html();
	}

	// Prepend inline styles if any
	if ( ! empty( $styles ) ) {
		$block_content = implode( '', $styles ) . $block_content;
	}

	return $block_content;
}
add_filter( 'render_block', 'awesome_group_render_block', 10, 2 );
