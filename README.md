# Awesome Group

Extends the WordPress Group and Row blocks with responsive layout controls, grid vertical alignment, and decorative 90s-style borders.

## Features

### Responsive Layout Controls
- **Stack on Mobile**: Automatically convert flex/grid layouts to vertical stacks on smaller screens
- **Custom Breakpoints**: Choose your own breakpoint (480px, 600px, 768px, 1024px, or custom)
- **Stack Direction**: Control whether items stack top-to-bottom or bottom-to-top
- **Hide on Mobile/Desktop**: Show/hide blocks based on screen size

### Grid Vertical Alignment
WordPress forgot to add vertical alignment controls for Grid layouts. We got you.
- Top, Center, Bottom, Stretch alignment options
- Works seamlessly with WordPress core grid layouts

### Decorative Borders
Add 90s-style squiggle or zigzag borders to your Group blocks.
- Choose which sides to add borders (top, right, bottom, left)
- Two styles: Squiggle or Zigzag
- Customizable color, thickness, and amplitude (waviness)
- Pure SVG implementation for crisp rendering at any size

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

1. Add a Group or Row block to your content
2. Select the block and open the block settings sidebar
3. Find the "Responsive Layout", "Grid Alignment", or "Decorative Borders" panels
4. Enable the features you want and customize the settings

### Visual Indicators in the Editor
When editing, you'll see visual indicators on blocks with responsive settings:
- Blue circle: Stack on mobile enabled
- Red square: Hide on mobile enabled
- Yellow triangle: Hide on desktop enabled

## Requirements

- WordPress 6.4 or higher
- PHP 7.4 or higher

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

- [ ] True vertical SVG paths for left/right borders (currently using rotated horizontal)
- [ ] Individual border colors per side
- [ ] Corner handling options for borders
- [ ] Container query support for custom breakpoints
- [ ] Additional border styles (dots, dashes, etc.)

## License

GPL-3.0

## Author

eD! Thomas - [edequalsaweso.me](https://edequalsaweso.me)

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/edequalsawesome/awesome-group).
