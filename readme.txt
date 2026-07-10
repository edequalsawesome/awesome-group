=== Awesome Group ===
Contributors: edequalsawesome
Tags: blocks, group, responsive, layout
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2026.07.001
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Extends the Group and Row blocks with responsive layout controls and grid vertical alignment.

== Description ==

Awesome Group supercharges WordPress core Group and Row blocks with powerful responsive controls.

= Responsive Layout Controls =

* **Stack on Mobile** - Automatically convert flex/grid layouts to vertical stacks on smaller screens
* **Custom Breakpoints** - Choose your own breakpoint (480px, 600px, 768px, 1024px, or custom values)
* **Stack Direction** - Control whether items stack top-to-bottom or bottom-to-top
* **Hide on Mobile/Desktop** - Show or hide blocks based on screen size

= Grid Vertical Alignment =

WordPress forgot to add vertical alignment controls for Grid layouts. We added them for you:

* Top, Center, Bottom, and Stretch alignment options
* Works seamlessly with WordPress core grid layouts
* No additional markup or complexity

= Developer Friendly =

* Built with @wordpress/scripts
* Uses WordPress block editor hooks and filters
* Clean, documented code
* Extends core blocks without replacing them
* No jQuery or heavy dependencies

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/awesome-group`, or install through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Start using the new controls on your Group and Row blocks

== Frequently Asked Questions ==

= Does this work with any theme? =

Yes! This plugin extends core WordPress blocks and works with any block-enabled theme.

= Will this slow down my site? =

No. The plugin only loads minimal CSS and uses native browser features. The JavaScript only runs in the block editor.

= Can I use this with other block plugins? =

Absolutely! This plugin extends core blocks and plays nicely with other block plugins.

= Are the responsive controls accessible? =

Yes. The plugin respects prefers-reduced-motion, uses proper ARIA labels, and warns users that hidden content is removed from screen readers.

== Screenshots ==

1. Responsive Layout controls panel
2. Grid Alignment controls (the feature WordPress forgot!)
3. Visual indicators in the editor

== Changelog ==

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

= 2026.02.10 =
Grid vertical alignment now accessible in block toolbar. Decorative borders significantly improved with smoother waves, better positioning, and working left/right borders.

= 1.0.0 =
Initial release of Awesome Group.
