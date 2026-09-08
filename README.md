# Awesome Group

Grid alignment, viewport reversal, and custom stacking breakpoints for core Group blocks, including Row and Stack variations.

## Controls

- **Grid vertical alignment:** Top, Center, Bottom, and Stretch in the block toolbar. Core already provides alignment for flex layouts.
- **Responsive Order:** Reverse flex items on Mobile, Tablet, or Desktop. These ranges use the site's core viewport settings. Rows reverse horizontally; columns reverse vertically.
- **Custom Stacking:** Stack a flex or grid Group at a breakpoint specific to that block, in px, em, or rem. Choose normal or reversed stacking. The editor preview uses the same validated media query as the frontend.

Custom stacking takes precedence within its breakpoint: it overrides viewport layout and reversal and stretches grid children to full width. Above that width, core layout, viewport reversal, and grid alignment apply normally. Each nested Group keeps its own breakpoint and direction.

Reversing changes visual order only. Keyboard focus and screen reader order stay as authored. Reorder the blocks themselves when the reading sequence matters.

## Core viewport settings

Core visibility controls handle hiding. Core viewport layout overrides handle layout changes at site-wide breakpoints. Customize those ranges in theme.json settings.viewport; the defaults are Mobile up to 480px, Tablet above 480px through 782px, and Desktop above 782px. Only Mobile and Tablet breakpoint values are configurable.

Custom Stacking serves a different need: a breakpoint for one Group without changing those site-wide ranges. A minimum-column-width grid may already collapse naturally; a fixed-column-count grid can use a core viewport override or Custom Stacking.

Responsive Order uses core's media queries and style engine. Its doubled selectors preserve precedence over theme layout rules. Desktop reversal follows the base layout orientation because core does not render desktop layout overrides. Custom stacking rules are generated only for enabled blocks; there is no global frontend stylesheet.

## Existing content

Existing awesomeStackOnMobile, awesomeMobileBreakpoint, and awesomeStackDirection attributes remain supported. Custom breakpoint values now work instead of always switching at 768px. Invalid values fall back to 768px.

The old hide-on-mobile/desktop controls and their blue editor indicators remain removed. Use core's visibility controls. Old hide attributes may remain in content until it is edited, but they no longer affect output. Grid alignment and the newer per-viewport reversal controls are retained.

## Installation

Upload the awesome-group folder to wp-content/plugins and activate it. Requires WordPress 7.1 or later and PHP 7.4 or later.

## Development

```sh
npm install
npm run build
npm run lint:js
npm run lint:css
npm run test:unit -- --runInBand
php tests/responsive.php
npm run plugin-zip
```

The shared src/stack.css template ships with the plugin. Source tests are excluded from the ZIP. Build output is generated from src/index.js and src/custom-stacking.js.

## License and support

GPL-3.0. Created by [eD! Thomas](https://edequalsaweso.me). Report issues in the [GitHub repository](https://github.com/edequalsawesome/awesome-group).
