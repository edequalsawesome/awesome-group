<?php
/**
 * Plugin Name:       Awesome Group
 * Description:       Extends the Group block with responsive layout controls - stack on mobile, custom breakpoints, and more.
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Version:           2026.03.10
 * Author:            eD! Thomas
 * Author URI:        https://edequalsaweso.me
 * License:           GPL-3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       awesome-group
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AWESOME_GROUP_VERSION', '2026.03.10' );
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
 * Extract background color from block attributes.
 * Supports both preset colors (backgroundColor) and custom colors (style.color.background).
 *
 * @param array $attrs Block attributes.
 * @return string Background color or empty string if none set.
 */
function awesome_group_get_background_color( $attrs ) {
	// Check for custom background color
	if ( isset( $attrs['style']['color']['background'] ) ) {
		return awesome_group_sanitize_color( $attrs['style']['color']['background'] );
	}

	// Check for preset background color
	if ( isset( $attrs['backgroundColor'] ) ) {
		// Return as CSS variable for theme preset colors
		return 'var(--wp--preset--color--' . sanitize_key( $attrs['backgroundColor'] ) . ')';
	}

	return '';
}

/**
 * Generate a horizontal squiggle SVG path as a closed filled shape.
 * Creates a rectangle with a wavy edge - wavy on top, straight on bottom.
 *
 * @param int $width     Width of the SVG.
 * @param int $height    Height of the SVG.
 * @param int $amplitude Wave amplitude.
 * @return string SVG path d attribute.
 */
function awesome_group_generate_squiggle_path( $width, $height, $amplitude = 10 ) {
	// Use same wavelength as awesome-squiggle for consistency
	$wavelength = 40;

	// Center line for the wave oscillation
	$mid_y = $amplitude;

	// Start at bottom left
	$path = "M0,{$height}";

	// Go to starting position at centerline
	$path .= " L0,{$mid_y}";

	// Create smooth sine-wave using awesome-squiggle's approach
	// Each wavelength creates one complete up-down cycle
	$is_up_peak = true;
	for ( $x = 0; $x < $width; $x += $wavelength ) {
		// Calculate peak position - oscillate around centerline
		// Up peak: centerline - amplitude (goes toward 0)
		// Down peak: centerline + amplitude (goes toward 2*amplitude)
		$peak_y = $is_up_peak ? ( $mid_y - $amplitude ) : ( $mid_y + $amplitude );
		$end_x = $x + $wavelength;
		$end_y = $mid_y; // Always return to centerline

		// Control points at 0.375 and 0.625 of wavelength, both at peak Y
		// This is the key to smooth sine-like waves from awesome-squiggle
		$cp1_x = $x + ( $wavelength * 0.375 );
		$cp2_x = $x + ( $wavelength * 0.625 );

		$path .= " C{$cp1_x},{$peak_y} {$cp2_x},{$peak_y} {$end_x},{$end_y}";

		$is_up_peak = ! $is_up_peak;
	}

	// Complete the rectangle
	$path .= " L{$width},{$height}"; // Bottom right corner
	$path .= " Z"; // Close path back to start

	return $path;
}

/**
 * Generate a horizontal zigzag SVG path as a closed filled shape.
 * Creates a rectangle with a wavy edge - zigzag on top, straight on bottom.
 *
 * @param int $width     Width of the SVG.
 * @param int $height    Height of the SVG.
 * @param int $amplitude Zigzag amplitude.
 * @return string SVG path d attribute.
 */
function awesome_group_generate_zigzag_path( $width, $height, $amplitude = 10 ) {
	// Use same wavelength as squiggle for consistency (half-wavelength = 20px per point)
	$wavelength = 20;

	// Start at bottom left
	$path = "M0,{$height}";

	// Go to starting position at baseline
	$path .= " L0,{$amplitude}";

	// Draw zigzag across the top - alternating between peak and baseline
	$is_peak = true;
	for ( $x = $wavelength; $x <= $width; $x += $wavelength ) {
		$y = $is_peak ? 0 : $amplitude;
		$path .= " L{$x},{$y}";
		$is_peak = ! $is_peak;
	}

	// Complete the rectangle
	$path .= " L{$width},{$height}"; // Bottom right corner
	$path .= " Z"; // Close path back to start

	return $path;
}

/**
 * Generate a VERTICAL squiggle SVG path as a closed filled shape.
 * Creates a rectangle with a wavy edge on the left side, straight on the right.
 * Used for left/right borders.
 *
 * @param int $width     Width of the SVG (horizontal extent).
 * @param int $height    Height of the SVG (vertical length).
 * @param int $amplitude Wave amplitude (horizontal extent of waves).
 * @return string SVG path d attribute.
 */
function awesome_group_generate_squiggle_path_vertical( $width, $height, $amplitude = 10 ) {
	// Use larger wavelength for vertical to match visual density of horizontal
	// (blocks are typically wider than tall, so vertical needs more spacing)
	$wavelength = 60;

	// Center line for the wave oscillation (vertical waves go left-right)
	$mid_x = $amplitude;

	// Start at bottom right
	$path = "M{$width},0";

	// Go to bottom of the wavy edge at centerline
	$path .= " L{$mid_x},0";

	// Create smooth sine-wave vertically using awesome-squiggle's approach
	// Each wavelength creates one complete left-right cycle as we move down
	$is_left_peak = true;
	for ( $y = 0; $y < $height; $y += $wavelength ) {
		// Calculate peak position - oscillate around centerline
		// Left peak: centerline - amplitude (goes toward 0)
		// Right peak: centerline + amplitude (goes toward 2*amplitude)
		$peak_x = $is_left_peak ? ( $mid_x - $amplitude ) : ( $mid_x + $amplitude );
		$end_y = $y + $wavelength;
		$end_x = $mid_x; // Always return to centerline

		// Control points at 0.375 and 0.625 of wavelength, both at peak X
		// This is the key to smooth sine-like waves from awesome-squiggle
		$cp1_y = $y + ( $wavelength * 0.375 );
		$cp2_y = $y + ( $wavelength * 0.625 );

		$path .= " C{$peak_x},{$cp1_y} {$peak_x},{$cp2_y} {$end_x},{$end_y}";

		$is_left_peak = ! $is_left_peak;
	}

	// Complete the rectangle
	$path .= " L{$width},{$height}"; // Top right corner
	$path .= " Z"; // Close path back to start

	return $path;
}

/**
 * Generate a VERTICAL zigzag SVG path as a closed filled shape.
 * Creates a rectangle with a zigzag edge on the left side, straight on the right.
 * Used for left/right borders.
 *
 * @param int $width     Width of the SVG (horizontal extent).
 * @param int $height    Height of the SVG (vertical length).
 * @param int $amplitude Zigzag amplitude (horizontal extent).
 * @return string SVG path d attribute.
 */
function awesome_group_generate_zigzag_path_vertical( $width, $height, $amplitude = 10 ) {
	// Use larger wavelength for vertical to match visual density of horizontal
	$wavelength = 30;

	// Start at bottom right
	$path = "M{$width},0";

	// Go to starting position at baseline
	$path .= " L{$amplitude},0";

	// Draw zigzag down the left side - alternating between peak and baseline
	$is_peak = true;
	for ( $y = $wavelength; $y <= $height; $y += $wavelength ) {
		$x = $is_peak ? 0 : $amplitude;
		$path .= " L{$x},{$y}";
		$is_peak = ! $is_peak;
	}

	// Complete the rectangle
	$path .= " L{$width},{$height}"; // Top right corner
	$path .= " Z"; // Close path back to start

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
	static $path_cache = array();

	$is_horizontal = in_array( $position, array( 'top', 'bottom' ), true );

	// SVG dimensions - horizontal borders are wide, vertical are tall
	// Using viewBox for scalability
	if ( $is_horizontal ) {
		// Horizontal borders: wide and short
		$width = 2000;
		$height = ( $amplitude * 2 ) + $thickness + 4;
		$viewbox = "0 0 {$width} {$height}";
		$preserve = 'none'; // Stretch to fit - won't be noticeable with repeating pattern

		// Cache path by deterministic inputs (color excluded — it varies per block)
		$cache_key = "{$style}_h_{$width}_{$height}_{$amplitude}";
		if ( ! isset( $path_cache[ $cache_key ] ) ) {
			if ( 'zigzag' === $style ) {
				$path_cache[ $cache_key ] = awesome_group_generate_zigzag_path( $width, $height, $amplitude );
			} else {
				$path_cache[ $cache_key ] = awesome_group_generate_squiggle_path( $width, $height, $amplitude );
			}
		}
		$path = $path_cache[ $cache_key ];
	} else {
		// Vertical borders: tall and narrow
		$width = ( $amplitude * 2 ) + $thickness + 4;
		$height = 2000;
		$viewbox = "0 0 {$width} {$height}";
		$preserve = 'none'; // Stretch to fit vertically

		// Cache path by deterministic inputs (color excluded — it varies per block)
		$cache_key = "{$style}_v_{$width}_{$height}_{$amplitude}";
		if ( ! isset( $path_cache[ $cache_key ] ) ) {
			if ( 'zigzag' === $style ) {
				$path_cache[ $cache_key ] = awesome_group_generate_zigzag_path_vertical( $width, $height, $amplitude );
			} else {
				$path_cache[ $cache_key ] = awesome_group_generate_squiggle_path_vertical( $width, $height, $amplitude );
			}
		}
		$path = $path_cache[ $cache_key ];
	}

	// Use fill instead of stroke for the wavy edge effect
	// Use inline style to support CSS variables (like var(--wp--preset--color--accent-1))
	$svg = sprintf(
		'<svg class="ag-border ag-border-%s" viewBox="%s" preserveAspectRatio="%s" aria-hidden="true">
			<path d="%s" style="fill: %s;"/>
		</svg>',
		esc_attr( $position ),
		esc_attr( $viewbox ),
		esc_attr( $preserve ),
		esc_attr( $path ),
		esc_attr( $color )
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
		$unique_id = 'ag-' . wp_unique_id();
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
					$unique_id = 'ag-' . wp_unique_id();
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

		// Get the Group block's background color to use for the border fill
		$background_color = awesome_group_get_background_color( $attrs );

		// If no background color is set on the block, use the user's border color setting as fallback
		if ( empty( $background_color ) ) {
			$background_color = awesome_group_sanitize_color( $attrs['awesomeBorderColor'] ?? '' );
		}

		// Final fallback to a visible color if still empty
		if ( empty( $background_color ) ) {
			$background_color = '#cccccc'; // Light gray as safe default
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
						$background_color,
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

	// Add classes and margin styles to the block
	if ( ! empty( $classes ) || ! empty( $has_borders ) ) {
		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag() ) {
			foreach ( $classes as $class ) {
				$processor->add_class( $class );
			}

			// Add margins for border spacing (30px border width/height + 30px gap)
			if ( ! empty( $has_borders ) ) {
				$existing_style = $processor->get_attribute( 'style' ) ?? '';
				$margin_styles = array();

				if ( ! empty( $border_positions['top'] ) ) {
					$margin_styles[] = 'margin-top: 60px';
				}
				if ( ! empty( $border_positions['bottom'] ) ) {
					$margin_styles[] = 'margin-bottom: 60px';
				}
				if ( ! empty( $border_positions['left'] ) ) {
					$margin_styles[] = 'margin-left: 60px';
				}
				if ( ! empty( $border_positions['right'] ) ) {
					$margin_styles[] = 'margin-right: 60px';
				}

				if ( ! empty( $margin_styles ) ) {
					// Append border margins AFTER existing styles so they take precedence
					$new_style = $existing_style ? $existing_style . '; ' : '';
					$new_style .= implode( '; ', $margin_styles );
					$processor->set_attribute( 'style', $new_style );
				}
			}

			$block_content = $processor->get_updated_html();
		}
	}

	// Add borders inside the block (after opening tag)
	// Note: Group blocks always render as <div>. If core ever changes this,
	// update the regex below. WP_HTML_Tag_Processor can't insert sibling content.
	if ( ! empty( $borders ) ) {
		$border_html = '<div class="ag-borders-container">' . implode( '', $borders ) . '</div>';
		$block_content = preg_replace( '/(<!--[^>]*-->)*\s*(<div[^>]*>)/', '$1$2' . $border_html, $block_content, 1 );
	}

	// Prepend inline styles if any
	if ( ! empty( $styles ) ) {
		$block_content = implode( '', $styles ) . $block_content;
	}

	return $block_content;
}
add_filter( 'render_block', 'awesome_group_render_block', 10, 2 );
