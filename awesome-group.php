<?php
/**
 * Plugin Name:       Awesome Group
 * Description:       Fills two gaps core leaves on Group blocks: vertical alignment on Grid layouts, and reversed order at the mobile viewport.
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
 * Put the reverse preview rule inside the editor canvas.
 *
 * Styles enqueued through enqueue_block_editor_assets land in the admin shell,
 * not the iframed canvas. Core's back-compat bridge only clones a parent style
 * node into the iframe when one of its selectors mentions .editor-styles-wrapper
 * or .wp-block — see getCompatibilityStyles() in the block editor — so a rule
 * on a plugin class is silently dropped. editor_settings['styles'] is rendered
 * into the canvas unconditionally, which is the supported path.
 *
 * @param array $settings Block editor settings.
 * @return array Filtered settings.
 */
function awesome_group_editor_canvas_styles( $settings ) {
	$media_query = awesome_group_mobile_media_query();

	if ( ! $media_query ) {
		return $settings;
	}

	// Two explicit classes rather than inferring the axis from core's layout
	// classes: the editor HOC decides which to apply using the same rule the
	// front end uses, so the preview and the published page cannot disagree.
	// Doubled classes for the same reason the front end doubles its selector:
	// core's own layout rule for the block is a single class, and this needs to
	// win that tie without !important.
	$css = $media_query . ' {'
		. ' .ag-reverse-row.ag-reverse-row { flex-direction: row-reverse; }'
		. ' .ag-reverse-column.ag-reverse-column { flex-direction: column-reverse; }'
		. ' }';

	$settings['styles'][] = array(
		'css'            => $css,
		'__unstableType' => 'plugin',
		'isGlobalStyles' => false,
	);

	return $settings;
}
add_filter( 'block_editor_settings_all', 'awesome_group_editor_canvas_styles' );

/**
 * The site's mobile viewport media query, from core's own viewport settings.
 *
 * Deliberately not a breakpoint of our own: theme.json settings.viewport is
 * where the site defines these, and core's block-visibility, states, and layout
 * supports all read them through this same call.
 *
 * @return string|null Media query string, or null if core cannot supply one.
 */
function awesome_group_mobile_media_query() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached ?: null;
	}

	// ceiling: WP_Theme_JSON's class docblock disclaims extender use, and core
	// ships no procedural wrapper for the breakpoint-to-media-query conversion
	// the way it does for the style engine. Reaching in is the only option that
	// does not duplicate core's sanitiser. The guard below fails soft to null,
	// which disables the feature rather than emitting an unscoped rule.
	// Re-check this on every major WordPress upgrade; move to a public wrapper
	// if core ever adds one.
	if ( ! class_exists( 'WP_Theme_JSON' ) || ! method_exists( 'WP_Theme_JSON', 'get_viewport_media_queries' ) ) {
		$cached = false;
		return null;
	}

	$queries = WP_Theme_JSON::get_viewport_media_queries( wp_get_global_settings( array( 'viewport' ) ) );
	$cached  = $queries['@mobile'] ?? false;

	return $cached ?: null;
}

/**
 * Does this block end up as a column at the mobile viewport?
 *
 * Core writes viewport layout overrides to attrs.style['@mobile'].layout. A
 * flex block is a column there if it was overridden to vertical, or if its base
 * orientation is already vertical and nothing overrode it.
 *
 * This matters because core emits no flex-direction at all for horizontal flex
 * — it relies on the CSS initial value of `row`. Emitting column-reverse
 * against that would not reverse the row, it would silently stack it.
 *
 * @param array $attrs Block attributes.
 * @return bool
 */
function awesome_group_is_column_at_mobile( $attrs ) {
	$base     = isset( $attrs['layout'] ) && is_array( $attrs['layout'] ) ? $attrs['layout'] : array();
	$style    = isset( $attrs['style'] ) && is_array( $attrs['style'] ) ? $attrs['style'] : array();
	$override = $style['@mobile']['layout'] ?? null;

	if ( is_array( $override ) && array_key_exists( 'orientation', $override ) ) {
		return 'vertical' === $override['orientation'];
	}

	return 'vertical' === ( $base['orientation'] ?? 'horizontal' );
}

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

	// Core's flex `orientation` accepts horizontal or vertical only — there is
	// no reversed option anywhere in its layout support, so a viewport override
	// cannot express this.
	$block_type->attributes['awesomeReverseOnMobile'] = array(
		'type'    => 'boolean',
		'default' => false,
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

	// Cheapest possible bail, before building anything. Most Group blocks on a
	// page use neither feature.
	if ( empty( $attrs['awesomeGridVerticalAlignment'] ) && empty( $attrs['awesomeReverseOnMobile'] ) ) {
		return $block_content;
	}

	// is_array: a crafted block comment can set layout to a scalar, and
	// indexing a bool/string emits a warning rather than being caught by ??.
	$layout = isset( $attrs['layout'] ) && is_array( $attrs['layout'] ) ? $attrs['layout'] : array();
	$type   = $layout['type'] ?? '';

	$align_map = array(
		'top'     => 'start',
		'center'  => 'center',
		'bottom'  => 'end',
		'stretch' => 'stretch',
	);

	// is_string: block JSON is not type-enforced server-side, so a crafted
	// block comment can supply an array here.
	$alignment    = $attrs['awesomeGridVerticalAlignment'] ?? '';
	$wants_align  = 'grid' === $type && is_string( $alignment ) && isset( $align_map[ $alignment ] );

	// Reversal is a flex concept. On a grid the visual order is governed by
	// track placement, so column-reverse would do nothing there.
	//
	// Strict true, not empty(): block JSON is not type-enforced server-side, and
	// the string "false" is non-empty, so a crafted block comment could enable
	// this by passing the word false.
	$wants_reverse = 'flex' === $type && true === ( $attrs['awesomeReverseOnMobile'] ?? false );

	if ( ! $wants_align && ! $wants_reverse ) {
		return $block_content;
	}

	$unique_id = 'ag-' . wp_unique_id();

	// Doubled class, specificity (0,2,0). Print order alone is not enough: on a
	// block theme the template resolves before wp_head, so this plugin's
	// stylesheet is queued BEFORE the theme's own — verified against Twenty
	// Twenty-Five — and an equal-specificity theme rule would win the tie. The
	// previous inline <style> sat in the body and always won; this restores
	// that guarantee without !important, which is reserved for overriding
	// third-party styles.
	$selector  = '.' . $unique_id . '.' . $unique_id;
	$css_rules = array();

	if ( $wants_align ) {
		$css_rules[] = array(
			// Safe: only values from the closed $align_map above land here.
			'selector'     => $selector,
			'declarations' => array( 'align-items' => $align_map[ $alignment ] ),
		);
	}

	if ( $wants_reverse ) {
		$media_query = awesome_group_mobile_media_query();

		// No media query means core cannot tell us the site's mobile
		// breakpoint. Emitting the rule unscoped would reverse the block at
		// every width, which is worse than doing nothing.
		if ( $media_query ) {
			// A block core has stacked is a column, so reverse the column. One
			// that is still a row at that width needs row-reverse: emitting
			// column-reverse there would stack it, which is not what a control
			// called "reverse order" should silently do.
			$direction = awesome_group_is_column_at_mobile( $attrs ) ? 'column-reverse' : 'row-reverse';

			$css_rules[] = array(
				'selector'     => $selector,
				'declarations' => array( 'flex-direction' => $direction ),
				'rules_group'  => $media_query,
			);
		}
	}

	if ( empty( $css_rules ) ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );
	if ( ! $processor->next_tag() ) {
		return $block_content;
	}
	$processor->add_class( $unique_id );

	// A plugin-owned context, not core's 'block-supports'. Both are drained by
	// the same wp_enqueue_stored_styles() hooks, but core's key is reserved for
	// core's own supports and blends every source into one anonymous tag; this
	// prints under its own handle instead. Ordering is unaffected: the tag
	// still lands after core's, so on a specificity tie these rules win without
	// needing !important.
	//
	// NOTE: the style engine runs declarations through safecss_filter_attr(),
	// but interpolates `selector` and `rules_group` RAW — see
	// WP_Style_Engine_CSS_Rule::get_css(). Both are safe here only because
	// $unique_id is server-generated and the media query comes from core's
	// regex-gated viewport sanitiser. Never build either from a block
	// attribute without an allow-list of your own.
	wp_style_engine_get_stylesheet_from_css_rules(
		$css_rules,
		array(
			'context'  => 'awesome-group',
			'prettify' => false,
		)
	);

	return $processor->get_updated_html();
}
add_filter( 'render_block', 'awesome_group_render_block', 10, 2 );
