<?php
/**
 * Plugin Name:       Awesome Group
 * Description:       Extends the Group block with responsive layout controls - stack on mobile, custom breakpoints, and more.
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            eD! Thomas
 * Author URI:        https://edequalsaweso.me
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
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
	$registry = WP_Block_Type_Registry::get_instance();

	// Responsive layout attributes for Group and Row
	$responsive_blocks = array( 'core/group', 'core/row' );
	foreach ( $responsive_blocks as $block_name ) {
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

	// Grid vertical alignment (WordPress forgot to add this!)
	$grid_alignment_blocks = array( 'core/group' );
	foreach ( $grid_alignment_blocks as $block_name ) {
		$block_type = $registry->get_registered( $block_name );
		if ( $block_type ) {
			$block_type->attributes['awesomeGridVerticalAlignment'] = array(
				'type'    => 'string',
				'default' => '',
			);
		}
	}

	// Decorative border attributes for Group only
	$border_blocks = array( 'core/group' );
	foreach ( $border_blocks as $block_name ) {
		$block_type = $registry->get_registered( $block_name );
		if ( $block_type ) {
			$block_type->attributes['awesomeBorderTop'] = array(
				'type'    => 'boolean',
				'default' => false,
			);
			$block_type->attributes['awesomeBorderRight'] = array(
				'type'    => 'boolean',
				'default' => false,
			);
			$block_type->attributes['awesomeBorderBottom'] = array(
				'type'    => 'boolean',
				'default' => false,
			);
			$block_type->attributes['awesomeBorderLeft'] = array(
				'type'    => 'boolean',
				'default' => false,
			);
			$block_type->attributes['awesomeBorderStyle'] = array(
				'type'    => 'string',
				'default' => 'squiggle',
			);
			$block_type->attributes['awesomeBorderColor'] = array(
				'type'    => 'string',
				'default' => '',
			);
			$block_type->attributes['awesomeBorderThickness'] = array(
				'type'    => 'number',
				'default' => 3,
			);
			$block_type->attributes['awesomeBorderAmplitude'] = array(
				'type'    => 'number',
				'default' => 10,
			);
		}
	}
}
add_action( 'init', 'awesome_group_register_attributes', 20 );

/**
 * Validate and sanitize a CSS color value.
 *
 * @param string $color The color value to validate.
 * @return string Sanitized color or empty string if invalid.
 */
function awesome_group_sanitize_color( $color ) {
	if ( empty( $color ) ) {
		return '';
	}

	// Allow CSS variables
	if ( preg_match( '/^var\(--[a-zA-Z0-9-_]+\)$/', $color ) ) {
		return $color;
	}

	// Allow hex colors (3, 4, 6, or 8 digits)
	if ( preg_match( '/^#([A-Fa-f0-9]{3,4}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $color ) ) {
		return $color;
	}

	// Allow rgb/rgba
	if ( preg_match( '/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*(0|1|0?\.\d+))?\s*\)$/', $color ) ) {
		return $color;
	}

	// Allow hsl/hsla
	if ( preg_match( '/^hsla?\(\s*\d{1,3}\s*,\s*\d{1,3}%\s*,\s*\d{1,3}%\s*(,\s*(0|1|0?\.\d+))?\s*\)$/', $color ) ) {
		return $color;
	}

	// Allow named colors (basic validation - alphanumeric only)
	if ( preg_match( '/^[a-zA-Z]+$/', $color ) ) {
		return $color;
	}

	return '';
}

/**
 * Validate and sanitize a CSS breakpoint value.
 *
 * @param string $breakpoint The breakpoint value to validate.
 * @return string Sanitized breakpoint or default if invalid.
 */
function awesome_group_sanitize_breakpoint( $breakpoint ) {
	$default = '768px';

	if ( empty( $breakpoint ) ) {
		return $default;
	}

	// Must be a number followed by px, em, or rem
	if ( preg_match( '/^\d+(\.\d+)?(px|em|rem)$/', $breakpoint ) ) {
		return $breakpoint;
	}

	return $default;
}

/**
 * Clamp a numeric value within bounds.
 *
 * @param int $value The value to clamp.
 * @param int $min   Minimum allowed value.
 * @param int $max   Maximum allowed value.
 * @return int Clamped value.
 */
function awesome_group_clamp( $value, $min, $max ) {
	return max( $min, min( $max, intval( $value ) ) );
}

/**
 * Generate a horizontal squiggle SVG path.
 *
 * @param int $width     Width of the SVG.
 * @param int $height    Height of the SVG.
 * @param int $amplitude Wave amplitude.
 * @return string SVG path d attribute.
 */
function awesome_group_generate_squiggle_path( $width, $height, $amplitude = 10 ) {
	$mid_y = $height / 2;
	$wavelength = 40; // Distance between wave peaks
	$path = "M0,{$mid_y}";

	for ( $x = 0; $x < $width; $x += $wavelength ) {
		$cp1_x = $x + ( $wavelength / 4 );
		$cp1_y = $mid_y - $amplitude;
		$cp2_x = $x + ( $wavelength / 4 );
		$cp2_y = $mid_y - $amplitude;
		$end_x = $x + ( $wavelength / 2 );
		$end_y = $mid_y;

		$path .= " C{$cp1_x},{$cp1_y} {$cp2_x},{$cp2_y} {$end_x},{$end_y}";

		$cp3_x = $x + ( 3 * $wavelength / 4 );
		$cp3_y = $mid_y + $amplitude;
		$cp4_x = $x + ( 3 * $wavelength / 4 );
		$cp4_y = $mid_y + $amplitude;
		$end2_x = $x + $wavelength;
		$end2_y = $mid_y;

		$path .= " C{$cp3_x},{$cp3_y} {$cp4_x},{$cp4_y} {$end2_x},{$end2_y}";
	}

	return $path;
}

/**
 * Generate a horizontal zigzag SVG path.
 *
 * @param int $width     Width of the SVG.
 * @param int $height    Height of the SVG.
 * @param int $amplitude Zigzag amplitude.
 * @return string SVG path d attribute.
 */
function awesome_group_generate_zigzag_path( $width, $height, $amplitude = 10 ) {
	$mid_y = $height / 2;
	$wavelength = 20; // Distance between zigzag points
	$path = "M0,{$mid_y}";
	$up = true;

	for ( $x = $wavelength; $x <= $width; $x += $wavelength ) {
		$y = $up ? ( $mid_y - $amplitude ) : ( $mid_y + $amplitude );
		$path .= " L{$x},{$y}";
		$up = ! $up;
	}

	return $path;
}

/**
 * Generate a decorative border SVG element.
 *
 * @param string $position  Border position: top, right, bottom, left.
 * @param string $style     Border style: squiggle or zigzag.
 * @param string $color     Border color.
 * @param int    $thickness Stroke width.
 * @param int    $amplitude Wave/zigzag amplitude.
 * @return string SVG HTML.
 */
function awesome_group_generate_border_svg( $position, $style, $color, $thickness, $amplitude ) {
	$is_horizontal = in_array( $position, array( 'top', 'bottom' ), true );

	// SVG dimensions - horizontal borders are wide, vertical are tall
	// Using viewBox for scalability
	if ( $is_horizontal ) {
		$width = 2000;
		$height = ( $amplitude * 2 ) + $thickness + 4;
		$viewbox = "0 0 {$width} {$height}";
		$preserve = 'xMidYMid slice';
	} else {
		// TODO: Vertical borders - will need rotated/different path generation
		// For now, we'll use a rotated horizontal path via CSS transform
		$width = 2000;
		$height = ( $amplitude * 2 ) + $thickness + 4;
		$viewbox = "0 0 {$width} {$height}";
		$preserve = 'xMidYMid slice';
	}

	// Generate path based on style
	if ( 'zigzag' === $style ) {
		$path = awesome_group_generate_zigzag_path( $width, $height, $amplitude );
	} else {
		$path = awesome_group_generate_squiggle_path( $width, $height, $amplitude );
	}

	$svg = sprintf(
		'<svg class="ag-border ag-border-%s" viewBox="%s" preserveAspectRatio="%s" aria-hidden="true">
			<path d="%s" fill="none" stroke="%s" stroke-width="%d" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>',
		esc_attr( $position ),
		esc_attr( $viewbox ),
		esc_attr( $preserve ),
		esc_attr( $path ),
		esc_attr( $color ),
		intval( $thickness )
	);

	return $svg;
}

/**
 * Filter the Group block output to add responsive classes and decorative borders.
 */
function awesome_group_render_block( $block_content, $block ) {
	$supported_blocks = array( 'core/group', 'core/row' );

	if ( ! in_array( $block['blockName'], $supported_blocks, true ) ) {
		return $block_content;
	}

	$attrs = $block['attrs'] ?? array();
	$classes = array();
	$styles = array();
	$borders = array();
	$unique_id = '';

	// Stack on mobile
	if ( ! empty( $attrs['awesomeStackOnMobile'] ) ) {
		$unique_id = 'ag-' . substr( md5( wp_json_encode( $block ) . wp_rand() ), 0, 8 );
		$classes[] = $unique_id;
		$classes[] = 'ag-stack-mobile';

		$breakpoint = awesome_group_sanitize_breakpoint( $attrs['awesomeMobileBreakpoint'] ?? '768px' );
		$direction = in_array( $attrs['awesomeStackDirection'] ?? 'column', array( 'column', 'column-reverse' ), true )
			? $attrs['awesomeStackDirection']
			: 'column';

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

	// Grid vertical alignment (WordPress forgot to add this!)
	if ( 'core/group' === $block['blockName'] && ! empty( $attrs['awesomeGridVerticalAlignment'] ) ) {
		$layout = $attrs['layout'] ?? array();
		if ( isset( $layout['type'] ) && 'grid' === $layout['type'] ) {
			$align_map = array(
				'top'     => 'start',
				'center'  => 'center',
				'bottom'  => 'end',
				'stretch' => 'stretch',
			);
			$alignment = $attrs['awesomeGridVerticalAlignment'];

			// Validate alignment value
			if ( isset( $align_map[ $alignment ] ) ) {
				if ( empty( $unique_id ) ) {
					$unique_id = 'ag-' . substr( md5( wp_json_encode( $block ) . wp_rand() ), 0, 8 );
					$classes[] = $unique_id;
				}
				$styles[] = sprintf(
					'<style>.%s { align-items: %s; }</style>',
					esc_attr( $unique_id ),
					esc_attr( $align_map[ $alignment ] )
				);
			}
		}
	}

	// Decorative borders (Group only)
	if ( 'core/group' === $block['blockName'] ) {
		$border_style = in_array( $attrs['awesomeBorderStyle'] ?? 'squiggle', array( 'squiggle', 'zigzag' ), true )
			? $attrs['awesomeBorderStyle']
			: 'squiggle';
		$border_color = awesome_group_sanitize_color( $attrs['awesomeBorderColor'] ?? '' );
		if ( empty( $border_color ) ) {
			$border_color = '#000000'; // Safe default
		}
		$border_thickness = awesome_group_clamp( $attrs['awesomeBorderThickness'] ?? 3, 1, 10 );
		$border_amplitude = awesome_group_clamp( $attrs['awesomeBorderAmplitude'] ?? 10, 5, 30 );

		$border_positions = array(
			'top'    => ! empty( $attrs['awesomeBorderTop'] ),
			'right'  => ! empty( $attrs['awesomeBorderRight'] ),
			'bottom' => ! empty( $attrs['awesomeBorderBottom'] ),
			'left'   => ! empty( $attrs['awesomeBorderLeft'] ),
		);

		$has_borders = array_filter( $border_positions );

		if ( ! empty( $has_borders ) ) {
			$classes[] = 'ag-has-borders';

			foreach ( $border_positions as $position => $enabled ) {
				if ( $enabled ) {
					$borders[] = awesome_group_generate_border_svg(
						$position,
						$border_style,
						$border_color,
						$border_thickness,
						$border_amplitude
					);
				}
			}
		}
	}

	// If nothing to add, return original content
	if ( empty( $classes ) && empty( $borders ) ) {
		return $block_content;
	}

	// Add classes to the block
	if ( ! empty( $classes ) ) {
		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			foreach ( $classes as $class ) {
				$processor->add_class( $class );
			}
			$block_content = $processor->get_updated_html();
		}
	}

	// Add borders inside the block (after opening tag)
	if ( ! empty( $borders ) ) {
		$border_html = '<div class="ag-borders-container">' . implode( '', $borders ) . '</div>';
		// Insert borders after the first tag
		$block_content = preg_replace( '/^(<[^>]+>)/', '$1' . $border_html, $block_content, 1 );
	}

	// Prepend inline styles if any
	if ( ! empty( $styles ) ) {
		$block_content = implode( '', $styles ) . $block_content;
	}

	return $block_content;
}
add_filter( 'render_block', 'awesome_group_render_block', 10, 2 );
