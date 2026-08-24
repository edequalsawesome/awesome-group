# Awesome Group

Adds the vertical alignment control that core's Grid layout is missing on Group blocks.

## What it does

Core's Grid layout has no vertical alignment control. Core's layout support applies `verticalAlignment` to flex layouts only — the grid branch in `wp-includes/block-supports/layout.php` emits `grid-template-columns` and `grid-template-rows` and never `align-items`.

This plugin adds Top / Center / Bottom / Stretch to the block toolbar for Group blocks using a Grid layout. That is all it does.

## What it used to do, and where that lives now

Most of the rest of this plugin is now core — per-viewport block visibility in WordPress 7.0, configurable viewport breakpoints in 7.1:

| Was | Now |
|---|---|
| Hide on mobile / desktop | Block visibility, per viewport. Three configurable breakpoints instead of one hardcoded. Note it hides with `display: none !important` in a media query — same technique as the removed code, so the screen-reader caveat still applies. Core does also set `fetchpriority="auto"` on images in hidden blocks |
| Stack on mobile | Viewport layout overrides — switch the editor to a viewport and change the layout there |
| Stack direction (reverse) | **No core equivalent.** Core's `orientation` is `horizontal` or `vertical` only. Removed rather than kept: it existed only to modify stack-on-mobile, and reversing visual order without reversing focus/reading order is an accessibility trap. Use `flex-direction: column-reverse` in theme CSS if you need it |
| Custom breakpoint | `theme.json` → `settings.viewport`. Site-wide instead of per block. Defaults: `@mobile` ≤480px, `@tablet` 480–782px, `@desktop` >782px |
| Grid stacking | Nothing to set. A grid with a minimum column width already collapses to one column via `repeat(auto-fill, minmax(min(WIDTH, 100%), 1fr))` |

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

1. Add a Group block and set its layout to Grid.
2. Select the block. The vertical alignment control appears in the block toolbar.
3. Pick Top, Center, Bottom, or Stretch.

Flex layouts already have this in core, so the control only appears for Grid.

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
npm run lint:css
npm run format
```

### Project Structure
```
awesome-group/
├── src/
│   ├── index.js       # Main JavaScript (block extensions)
│   ├── editor.css     # Editor-only styles
│   └── style.css      # Frontend + editor styles
├── build/             # Compiled assets
├── awesome-group.php  # Main plugin file
└── package.json       # Dependencies and scripts
```

## Accessibility

- Respects `prefers-reduced-motion` for users who prefer less animation
- Uses both color AND shape for visual indicators (not color alone)
- Includes proper help text and ARIA labels
- Warns users that hidden content is removed from screen readers

## Roadmap

- [ ] Container query support for custom breakpoints

## License

GPL-3.0

## Author

eD! Thomas - [edequalsaweso.me](https://edequalsaweso.me)

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/edequalsawesome/awesome-group).
