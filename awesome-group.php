<?php
/**
 * Plugin Name:       Awesome Group
 * Description:       Extends the Group block with responsive layout controls - stack on mobile, custom breakpoints, and more.
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Version:           2026.07.001
 * Author:            eD! Thomas
 * Author URI:        https://edequalsaweso.me
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       awesome-group
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AWESOME_GROUP_VERSION', '2026.07.001' );
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
		$file = AWESOME_GROUP_PLUGIN_DIR . 'build/index.asset.php';
		$asset = file_exists( $file ) ? include $file : false;
	}

	return $asset;
}

/**
 * Enqueue block editor assets.
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
	$css_file = AWESOME_GROUP_PLUGIN_DIR . 'build/style-index.css';

	if ( ! file_exists( $css_file ) ) {
		return;
	}

	$asset   = awesome_group_get_asset_data();
	$version = $asset ? $asset['version'] : AWESOME_GROUP_VERSION;

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
 *
 * Note: core's Row and Stack are layout *variations* of core/group, not
 * separate registered block types, so core/group alone covers them all.
 */
function awesome_group_register_attributes() {
	$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'core/group' );

	if ( ! $block_type ) {
		return;
	}

	// Responsive layout attributes
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

	// Grid vertical alignment (WordPress forgot to add this!)
	$block_type->attributes['awesomeGridVerticalAlignment'] = array(
		'type'    => 'string',
		'default' => '',
	);
}
add_action( 'init', 'awesome_group_register_attributes', 20 );


/**
 * Validate and sanitize a CSS breakpoint value.
 *
 * @param string $breakpoint The breakpoint value to validate.
 * @return string Sanitized breakpoint or default if invalid.
 */
function awesome_group_sanitize_breakpoint( $breakpoint ) {
	$default = '768px';

	// Block attribute JSON is not type-enforced server-side: a crafted block
	// comment can supply an array/object here, which would fatal in preg_match.
	if ( ! is_string( $breakpoint ) || '' === $breakpoint ) {
		return $default;
	}

	$breakpoint = strtolower( trim( $breakpoint ) );

	// Must be a number followed by px, em, or rem ('D' so '$' can't match before a trailing newline)
	if ( preg_match( '/^\d+(\.\d+)?(px|em|rem)$/D', $breakpoint ) ) {
		return $breakpoint;
	}

	return $default;
}


/**
 * Filter the Group block output to add responsive classes and grid alignment.
 */
function awesome_group_render_block( $block_content, $block ) {
	// Row/Stack are core/group variations, so this single check covers them.
	// Plain string compare: this filter runs for every block on the page.
	if ( 'core/group' !== $block['blockName'] ) {
		return $block_content;
	}

	$attrs = $block['attrs'] ?? array();
	$classes = array();
	$styles = array();
	$unique_id = '';

	// Stack on mobile
	if ( ! empty( $attrs['awesomeStackOnMobile'] ) ) {
		$unique_id = 'ag-' . wp_unique_id();
		$classes[] = $unique_id;
		$classes[] = 'ag-stack-mobile';

		$breakpoint = awesome_group_sanitize_breakpoint( $attrs['awesomeMobileBreakpoint'] ?? '768px' );
		$direction = in_array( $attrs['awesomeStackDirection'] ?? 'column', array( 'column', 'column-reverse' ), true )
			? ( $attrs['awesomeStackDirection'] ?? 'column' )
			: 'column';

		// Generate inline style for custom breakpoint.
		// CSS-injection safety here depends entirely on the upstream
		// validation ($breakpoint regex, $direction whitelist) — esc_attr()
		// is defense-in-depth only and does NOT make arbitrary text safe for
		// a <style> context. Any new value added to this sprintf must be
		// equally strictly whitelisted first.
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

	// Grid vertical alignment (WordPress forgot to add this!)
	if ( ! empty( $attrs['awesomeGridVerticalAlignment'] ) ) {
		$layout = $attrs['layout'] ?? array();
		if ( isset( $layout['type'] ) && 'grid' === $layout['type'] ) {
			$align_map = array(
				'top'     => 'start',
				'center'  => 'center',
				'bottom'  => 'end',
				'stretch' => 'stretch',
			);
			$alignment = $attrs['awesomeGridVerticalAlignment'];

			// Validate alignment value (is_string: block JSON is not type-enforced server-side)
			if ( is_string( $alignment ) && isset( $align_map[ $alignment ] ) ) {
				if ( empty( $unique_id ) ) {
					$unique_id = 'ag-' . wp_unique_id();
					$classes[] = $unique_id;
				}
				// Safe: only values from the closed $align_map above reach this sprintf.
				$styles[] = sprintf(
					'<style>.%s { align-items: %s; }</style>',
					esc_attr( $unique_id ),
					esc_attr( $align_map[ $alignment ] )
				);
			}
		}
	}

	// If nothing to add, return original content
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
