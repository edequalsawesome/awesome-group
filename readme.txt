=== Awesome Group ===
Contributors: edequalsawesome
Tags: blocks, group, grid, layout, alignment
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2026.08.001
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Fills two gaps core leaves on Group blocks: vertical alignment on Grid layouts, and reversed order at any viewport.

== Description ==

Almost everything this plugin used to do is now core. Two gaps are left, and they are what it is for.

= Grid Vertical Alignment =

Core's layout support applies `verticalAlignment` to flex layouts only — the grid branch emits `grid-template-columns` and `grid-template-rows` and never `align-items`.

* Top, Center, Bottom, and Stretch, in the block toolbar
* Grid layouts only; flex layouts already have this in core

= Reverse Order =

Core's flex `orientation` accepts `horizontal` or `vertical` only. There is no reversed option anywhere in its layout support, so a viewport override cannot express it.

* One toggle per viewport on flex Group blocks, under Responsive Order
* The viewports and their breakpoints come from core — `theme.json` `settings.viewport`, defaulting to `@mobile` ≤480px, `@tablet` >480px and ≤782px, `@desktop` >782px. This plugin defines no breakpoint of its own; the list is read from the same core call the front end uses and handed to the editor from PHP. The editor keeps a literal fallback for the one case where that inline script does not print — a toggle it offers that the server does not support is inert, never wrong
* Composes with core's stacking rather than replacing it: core flips the block to a column at a viewport, this reverses the direction
* Works on its own too. A block core has not stacked is still a row at that width, so it gets `row-reverse` — the row reverses, it does not silently become a column. The axis is decided per viewport
* Desktop always follows the block's base orientation. Core's layout support does not render `@desktop` layout overrides at all, so honouring one here would stack a block core leaves as a row
* Emitted through core's style engine under this plugin's own context, with a deliberately doubled selector so it wins on specificity rather than on print order — no `!important` needed

Reversing visual order does **not** reverse keyboard focus order or screen reader reading order, so the two will disagree. The control says so. If the sequence genuinely matters, reorder the blocks instead.

= Everything else this plugin used to do is now core =

WordPress 7.0 shipped per-viewport block visibility and WordPress 7.1 added configurable viewport breakpoints, so you no longer need a plugin for responsive Group behaviour. Core's implementation is better than what this plugin had:

* **Hide on mobile / desktop** is now block visibility. Select a block, open the Settings panel, and set its per-viewport visibility. Core has three breakpoints where this plugin had one, and its breakpoints are configurable. It hides with `display: none !important` inside a media query — the same technique this plugin used. The markup stays in the page source but `display: none` removes it from the accessibility tree, so it is not announced. That is parity with the removed code rather than an improvement. Core does additionally set `fetchpriority="auto"` on images inside hidden blocks, which this plugin never did.
* **Stack on mobile** is now a viewport layout override. Switch the editor to the mobile viewport and change the layout there; core stores an override for that breakpoint.
* **Stack direction** has no core equivalent, so it was kept — rebuilt as the Reverse Order toggles described above, and widened from one hardcoded breakpoint to all three of core's viewports. The old implementation was welded to this plugin's own stacking CSS and could not survive its removal, so the control was rewritten to layer on top of core's stacking instead.
* **Custom breakpoints** now live in `theme.json` under `settings.viewport`, so they are set once for the whole site instead of per block. Only two values are configurable there — `mobile` and `tablet`. Desktop is not a breakpoint of its own; it is the base range above the tablet value. The defaults are `@mobile` at ≤480px, `@tablet` above 480px and up to 782px, and `@desktop` above 782px.
* **Grid stacking** usually needs no setting at all: a grid using a *minimum column width* collapses to one column on its own, because core emits `repeat(auto-fill, minmax(min(WIDTH, 100%), 1fr))`. A grid using an explicit *column count* does not — core emits `repeat(N, minmax(0, 1fr))` and holds N columns at every width, so those need a mobile viewport override setting the column count to 1.

= Developer Friendly =

* Built with @wordpress/scripts
* Uses WordPress block editor hooks and filters
* Extends core blocks without replacing them
* Editor-only JavaScript, no front-end stylesheet, no jQuery

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/awesome-group`, or install through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Select a Group block using a Grid layout — the vertical alignment control appears in the block toolbar

== Frequently Asked Questions ==

= Does this work with any theme? =

Yes! This plugin extends core WordPress blocks and works with any block-enabled theme.

= Will this slow down my site? =

No — less than before. The plugin now loads no stylesheet at all on the front end; both remaining features emit a single inline rule each, and only on blocks that use them. The JavaScript only runs in the block editor.

= Can I use this with other block plugins? =

Absolutely! This plugin extends core blocks and plays nicely with other block plugins.

= What happened to the responsive controls? =

WordPress 7.0 and 7.1 shipped most of them. See the Description above for where each now lives. The exception is reversed order, which core still does not offer — that one stayed in the plugin, rebuilt to sit on top of core's stacking. Keeping a second, weaker implementation alongside core's would only cause the two to fight, so they were removed rather than maintained.

= I upgraded and my blocks stopped stacking. What do I do? =

Set the behaviour again with core's controls — per-viewport visibility for hiding, and viewport layout overrides for stacking. If you had the old Stack direction set to reverse, that one is still here: turn on Responsive Order → Reverse order on Mobile (or Tablet, or Desktop). The old setting does not carry over, so you will need to set it again. The old attributes are inert but still present in your post content, and are dropped permanently the next time you save that post in the editor. See the Upgrade Notice for details.

== Screenshots ==

1. Grid Alignment controls in the block toolbar
2. Reverse Order toggles in the block inspector

== Changelog ==

= 2026.08.001 =
* Removed: stack on mobile, custom breakpoints, and hide on mobile/desktop. Core ships all of these — per-viewport block visibility in 7.0, configurable viewport breakpoints in 7.1 — and core's versions are better by being site-wide in theme.json rather than per block, with three configurable breakpoints instead of one hardcoded 768px. Core hides using `display: none !important` inside a media query, which is the same technique the removed code used, so that part is parity rather than an improvement
* Changed: the reverse-order accessibility warning moved from inline help text to a non-dismissible warning notice, and now names links, buttons, and form fields explicitly. It stays associated with each toggle via aria-describedby, so screen reader users hear it on the control itself rather than only when reading the panel top to bottom
* Kept, rebuilt: stack direction, now per-viewport Reverse Order toggles. Core has no reversed orientation, so removing this would have lost a capability with nowhere to go. The old version could not simply be carried over — it drove a `--ag-stack-direction` custom property consumed only by this plugin's own stacking CSS, which is gone — so it was rewritten to scope the reversal to whichever of core's viewport media queries the block selects, derived from theme.json, layering on core's stacking instead of replacing it. The axis follows the block's real orientation at each viewport, so a row reverses as a row rather than silently stacking. The accessibility warning about visual order diverging from focus and reading order is preserved on the control
* Removed: the front-end stylesheet, which existed only for the features above. Both remaining features emit a single inline rule each, on the blocks that use them
* Fixed: the custom breakpoint control never worked. It stored a value and rendered `--ag-breakpoint`, but media queries cannot read custom properties, so stacking always triggered at a hardcoded 768px no matter what was set. Rather than build per-block generated media queries to fix it, the control is gone and theme.json `settings.viewport` does the job properly
* Changed: minimum WordPress version raised to 7.1, since the migration path depends on theme.json `settings.viewport`, which is 7.1. Raising the floor also stops this update reaching sites that could not perform the migration
* Kept: grid vertical alignment, which core still does not provide — its layout support applies verticalAlignment to flex layouts only

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
Breaking change. Stack on mobile, custom breakpoints, and hide on mobile/desktop have been removed because WordPress now does all of them, better. Stack direction survives as the Reverse Order toggles — core has no reversed orientation, so that one was rebuilt rather than dropped, now covering all three of core's viewports. You will need to set it again. Blocks using those settings will stop behaving responsively until you set them again with core's controls: per-viewport visibility for hiding, viewport layout overrides for stacking, and theme.json `settings.viewport` for breakpoints. The old attributes stay in your post content and are inert — but only until that post is next saved in the block editor, at which point they are dropped for good. Gutenberg serialises only currently-registered attributes, and these are no longer registered. If you want a record of which blocks used them, take it before editing those posts. Grid vertical alignment is unchanged and still works. Requires WordPress 7.1 or later.

= 2026.02.10 =
Grid vertical alignment now accessible in block toolbar. Decorative borders significantly improved with smoother waves, better positioning, and working left/right borders.

= 1.0.0 =
Initial release of Awesome Group.
