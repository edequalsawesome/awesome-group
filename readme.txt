=== Awesome Group ===
Contributors: edequalsawesome
Tags: blocks, group, grid, layout, alignment
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2026.08.001
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html


Grid alignment, viewport reversal, and custom stacking breakpoints for Group blocks.

== Description ==

= Grid Vertical Alignment =

Top, Center, Bottom, and Stretch in the toolbar for Grid layouts. Flex alignment is provided by core.

= Responsive Order =

Reverse flex items at any of core's Mobile, Tablet, or Desktop viewports. Rows reverse horizontally and columns reverse vertically. The breakpoints come from theme.json settings.viewport; Desktop follows the base layout orientation.

= Custom Stacking =

Stack a flex or grid Group at its own breakpoint in px, em, or rem, in normal or reversed order. Custom stacking overrides viewport layout and reversal within that width and stretches grid children to full width. Above it, the existing layout and viewport settings apply. Nested Groups keep independent breakpoints and directions. Editor and frontend use the same validated media rules.

Reversing changes visual order only. Keyboard and screen reader order stay as authored. Reorder the blocks when the reading sequence matters.

Use core's visibility controls for hiding and core's viewport layout controls for site-wide responsive layouts. Custom Stacking is for a breakpoint on one block.

== Installation ==

1. Upload awesome-group to wp-content/plugins and activate it.
2. Select a Group with a Grid layout for toolbar alignment.
3. Use Responsive Order for flex reversal, or Custom Stacking for a per-block breakpoint.

== Frequently Asked Questions ==

= Are existing stacking settings preserved? =

Yes. Existing stacking, direction, and custom breakpoint attributes are supported. Breakpoints now work; invalid values fall back to 768px.

= What happened to hide-on-mobile/desktop? =

Those controls remain removed in favor of core visibility. Old hide attributes no longer affect output and may be dropped when the post is edited. The old blue indicators are also removed; the inspector shows whether custom stacking is enabled.

= Does this load a stylesheet everywhere? =

No. Frontend styles are generated for blocks using these features. The shared stacking template is read once per request. JavaScript runs in the editor only.

== Changelog ==

= 2026.08.001 =
* Added per-viewport Reverse Order controls using core's viewport settings and style engine.
* Fixed per-block custom stacking breakpoints in the editor and frontend; preserves existing stacking attributes and directions.
* Kept Grid vertical alignment, including Stretch.
* Removed legacy hide-on-mobile/desktop controls and blue indicators; use core visibility controls.
* Raised the minimum WordPress version to 7.1 for core viewport settings.
* Added breakpoint validation, scoped-style lifecycle, and frontend regression checks. Excluded source tests from the plugin ZIP.

= 2026.07.001 =
* Fixed potential fatal error when block markup supplies a non-string breakpoint value (hardened breakpoint and alignment validation)
* Fixed editor indicator dots losing their positioning anchor after the decorative borders removal
* Added the missing Stretch option to the grid vertical alignment toolbar control
* Removed dead code left from the decorative borders removal (unused CSS and SVG assets)
* Removed non-functional custom-breakpoint fallback CSS that could never match rendered markup
* Removed dead core/row handling (Row and Stack are core/group layout variations, not separate block types)
* Documented that CSS output safety relies on strict value validation
* Cleaned up class name concatenation in the editor preview

= 2026.04.11 =
* Removed decorative borders feature (attributes, SVG generators, render logic) to simplify the plugin
* Fixed PHP warnings for `awesomeStackDirection` and `awesomeBorderStyle` caused by missing null coalescing fallbacks on ternary true branches

= 2026.03.10 =
* Moved @wordpress/* packages to devDependencies (build-only, not bundled)
* Replaced md5+json_encode ID generation with wp_unique_id() for better performance
* Added in-memory SVG path cache to avoid regenerating identical paths per request
* Fixed margin ordering so border spacing takes precedence over existing inline styles
* Added file existence check for frontend CSS before enqueueing
* Cached asset file data to avoid triple-loading in editor context
* Wrapped ColorPalette in BaseControl for proper screen reader label association
* Guarded ColorPalette onChange for undefined values on color clear
* Updated column-reverse warning to mention keyboard focus order
* Added visual indicator for hide-on-both-mobile-and-desktop edge case
* Tightened HTML comment regex in border injection to prevent backtracking
* Increased editor indicator size from 10px to 12px for better visibility

= 2026.02.10 =
* Moved grid vertical alignment controls from sidebar to block toolbar
* Added README.md for GitHub
* Added readme.txt for WordPress.org
* Improved decorative borders with smooth wave generation using Bezier curves
* Fixed border width to match block width for alignwide and alignfull layouts
* Added proper margin spacing around bordered blocks
* Implemented native vertical SVG paths for left/right borders
* Adjusted vertical wave spacing to match horizontal wave density
* Added side margins when left/right borders are present

= 1.0.0 =
* Initial release
* Responsive layout controls for Group and Row blocks
* Grid vertical alignment for Group blocks
* Decorative squiggle and zigzag borders for Group blocks
* Custom breakpoint support
* Hide on mobile/desktop functionality
* Visual indicators in block editor
* Full accessibility support

== Upgrade Notice ==

= 2026.08.001 =
Requires WordPress 7.1. Existing custom stacking settings now work and remain supported. Per-viewport reversal and grid alignment are retained. Migrate old hide-on-mobile/desktop settings to core visibility before editing affected posts; those legacy hide attributes are no longer registered.
