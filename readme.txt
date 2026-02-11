=== Awesome Group ===
Contributors: edequalsawesome
Tags: blocks, group, responsive, layout, borders
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Extends the Group and Row blocks with responsive layout controls, grid vertical alignment, and decorative 90s-style borders.

== Description ==

Awesome Group supercharges WordPress core Group and Row blocks with powerful responsive controls and fun decorative options.

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

= Decorative Borders =

Add fun 90s-style squiggle or zigzag borders to your Group blocks:

* Choose which sides to add borders (top, right, bottom, left)
* Two styles: Squiggle or Zigzag
* Customizable color, thickness, and amplitude
* Pure SVG implementation for crisp rendering at any size
* Fully responsive and accessible

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

= Do the borders work on mobile? =

Yes! The SVG borders are fully responsive and scale beautifully on all screen sizes.

= Are the responsive controls accessible? =

Yes. The plugin respects prefers-reduced-motion, uses proper ARIA labels, and warns users that hidden content is removed from screen readers.

== Screenshots ==

1. Responsive Layout controls panel
2. Grid Alignment controls (the feature WordPress forgot!)
3. Decorative Borders panel with style options
4. Example of squiggle borders on a Group block
5. Visual indicators in the editor

== Changelog ==

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

= 1.0.0 =
Initial release of Awesome Group.
