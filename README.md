# Awesome Group

Fills two gaps core leaves on Group blocks: vertical alignment on Grid layouts, and reversed order at any viewport.

## What it does

Two things core still does not do:

**Grid vertical alignment.** Core's layout support applies `verticalAlignment` to flex layouts only — the grid branch in `wp-includes/block-supports/layout.php` emits `grid-template-columns` and `grid-template-rows` and never `align-items`. This adds Top / Center / Bottom / Stretch to the block toolbar for Grid layouts.

**Reversed order at a viewport.** Core's flex `orientation` accepts `horizontal` or `vertical` only, with no reversed option anywhere in its layout support, so a viewport override cannot express it. This adds one toggle per viewport for Flex layouts. The viewport list and its breakpoints come from core and are passed to the editor from PHP; the editor keeps a literal fallback for the one case where that inline script does not print, and a toggle offered by that fallback but unsupported by the server is inert rather than wrong.

Both use core's own primitives rather than a parallel system. The reverse rule is scoped to whichever of core's viewport media queries the block selects, derived from `theme.json` `settings.viewport` — no breakpoint of this plugin's own — and both are emitted through core's style engine, under this plugin's own context so they print in their own tag rather than blending anonymously into core's.

Selectors are deliberately doubled (`.ag-1.ag-1`, specificity `(0,2,0)`). Print order is not a safe basis for winning a tie: on a block theme the template resolves before `wp_head`, so this plugin's stylesheet is queued *before* the theme's own — verified against Twenty Twenty-Five — and an equal-specificity theme rule would otherwise win. Doubling the class restores the guarantee the old inline `<style>` had, without `!important`:

```css
@media (width <= 480px){.wp-container-core-group-is-layout-1444063a{flex-direction:column;align-items:flex-start;}}
@media (width <= 480px){.ag-1.ag-1{flex-direction:column-reverse;}}
```

Core stacks the block; this reverses the direction. They compose.

The reverse also works on its own. A block core has *not* stacked is still a row at that width, so it gets `row-reverse` rather than `column-reverse` — reversing the row instead of silently stacking it. The editor and the front end decide that the same way, from the same attributes.

## What it used to do, and where that lives now

Most of the rest of this plugin is now core — per-viewport block visibility in WordPress 7.0, configurable viewport breakpoints in 7.1:

| Was | Now |
|---|---|
| Hide on mobile / desktop | Block visibility, per viewport. Three configurable breakpoints instead of one hardcoded. Note it hides with `display: none !important` in a media query — same technique as the removed code, so this is parity, not an improvement. Markup stays in the source but `display: none` keeps it out of the accessibility tree. Core does also set `fetchpriority="auto"` on images in hidden blocks |
| Stack on mobile | Viewport layout overrides — switch the editor to a viewport and change the layout there |
| Stack direction (reverse) | **No core equivalent — kept, and rebuilt.** Core's `orientation` is `horizontal` or `vertical` only. Now per-viewport Reverse Order toggles across all three of core's viewports, layered on core's stacking instead of this plugin's old CSS |
| Custom breakpoint | `theme.json` → `settings.viewport`. Site-wide instead of per block. Defaults: `@mobile` ≤480px, `@tablet` >480px and ≤782px, `@desktop` >782px |
| Grid stacking | Usually nothing to set — a *minimum column width* grid already collapses via `repeat(auto-fill, minmax(min(WIDTH, 100%), 1fr))`. A fixed *column count* grid does not: core emits `repeat(N, minmax(0, 1fr))` at every width, so set a mobile viewport override of 1 column |

The custom breakpoint control never actually worked: it stored a value and rendered a `--ag-breakpoint` custom property, but media queries cannot read custom properties, so stacking always fired at a hardcoded 768px regardless. Rather than build per-block generated media queries to fix a feature core had since superseded, it was removed.

## Installation

### From WordPress.org (when available)
1. Go to Plugins > Add New in your WordPress admin
2. Search for "Awesome Group"
3. Click Install Now, then Activate

### Manual Installation
1. Download the latest release
2. Upload the `awesome-group` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

### For Development
```bash
npm install
npm run build
```

## Usage

**Grid vertical alignment** — add a Group block, set its layout to Grid, select it, and the vertical alignment control appears in the block toolbar. Flex layouts already have this in core, so the control only appears for Grid.

**Reverse order** — set a Group block's layout to Flex, then use Responsive Order in the inspector. There is one toggle per viewport (Mobile, Tablet, Desktop), using core's own breakpoints. It only appears for Flex: on a Grid the visual order comes from track placement, so `column-reverse` would do nothing there.

Reversing visual order does not reverse keyboard focus or screen reader reading order, so the two will disagree. The control says so. Reorder the blocks themselves if the sequence genuinely matters.

## Requirements

- WordPress 7.1 or later
- PHP 7.4 or later

## Development

### Build Commands
```bash
# Development build with watch mode
npm start

# Production build
npm run build

# Create distribution zip
npm run plugin-zip

# Linting
npm run lint:js
npm run format
```

### Project Structure
```
awesome-group/
├── src/
│   └── index.js       # Editor JavaScript (block extensions)
├── build/             # Compiled assets (JS only — no stylesheet)
├── awesome-group.php  # Main plugin file
└── package.json       # Dependencies and scripts
```

## Accessibility

The grid alignment control is core's own `BlockVerticalAlignmentControl`, rendered in the block toolbar, so it inherits core's labelling and keyboard behaviour.

The reverse-order toggle carries an explicit warning that it changes visual order only — keyboard focus order and screen reader reading order stay as authored. That warning existed on the old stack-direction control and was deliberately carried over, because the hazard did not go away when the implementation changed.

The reduced-motion rule, the shape-and-colour editor indicators, and the hidden-content warning all went with the features they described. Nothing here still needs them.

One thing worth knowing if you are migrating: core's per-viewport hiding uses `display: none !important` inside a media query, the same technique the removed code used. The markup stays in the page source, but `display: none` removes it from the accessibility tree, so it is not announced. That is parity with what this plugin did, not an improvement — the old warning was accurate and the same caveat still applies.

## License

GPL-3.0

## Author

eD! Thomas - [edequalsaweso.me](https://edequalsaweso.me)

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/edequalsawesome/awesome-group).
