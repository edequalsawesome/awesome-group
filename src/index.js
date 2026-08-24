/**
 * Awesome Group - the vertical alignment control core's Grid layout is missing
 *
 * Almost everything else this plugin used to do is now core. WordPress 7.0
 * shipped per-viewport block visibility (block-supports/block-visibility.php)
 * and viewport layout overrides, with breakpoints configurable in theme.json
 * under settings.viewport (WP 7.1). Core's implementation beats what was here
 * by being site-wide rather than per-block, with three configurable breakpoints
 * instead of one hardcoded 768px. It hides the same way the removed code did,
 * with `display: none !important` inside a media query — that part is parity,
 * not an improvement, and the screen-reader caveat still applies.
 *
 * One exception, stated honestly: reversed stack direction has NO core
 * equivalent — core's `orientation` is horizontal or vertical only. It was
 * dropped anyway, because it only ever modified stack-on-mobile (now core's
 * job) and because reversing visual order without reversing focus and reading
 * order is an accessibility trap. Theme CSS can do it if it is really wanted.
 *
 * What core still does not do is vertical alignment on a Grid layout. Core's
 * layout support applies verticalAlignment to flex only — the grid branch emits
 * grid-template-columns and grid-template-rows and never align-items. That gap
 * is the whole of this plugin now.
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import {
	BlockControls,
	BlockVerticalAlignmentControl,
} from '@wordpress/block-editor';

/**
 * Row and Stack are layout variations of core/group, not separate block types,
 * so core/group alone covers Group, Row, and Stack.
 */
const SUPPORTED_BLOCKS = [ 'core/group' ];

/** Editor alignment keyword to its CSS align-items value. */
const ALIGN_MAP = {
	top: 'start',
	center: 'center',
	bottom: 'end',
	stretch: 'stretch',
};

/**
 * Register the alignment attribute on supported blocks.
 *
 * @param {Object} settings Block settings being filtered.
 * @param {string} name     Block name.
 * @return {Object} Filtered block settings.
 */
function addGridAlignmentAttribute( settings, name ) {
	if ( ! SUPPORTED_BLOCKS.includes( name ) ) {
		return settings;
	}

	return {
		...settings,
		attributes: {
			...settings.attributes,
			awesomeGridVerticalAlignment: {
				type: 'string',
				default: '',
			},
		},
	};
}

addFilter(
	'blocks.registerBlockType',
	'awesome-group/add-attributes',
	addGridAlignmentAttribute
);

/**
 * Add the toolbar control, for grid layouts only.
 */
const withGridAlignmentControl = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, attributes, setAttributes } = props;

		if (
			! SUPPORTED_BLOCKS.includes( name ) ||
			attributes.layout?.type !== 'grid'
		) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<BlockEdit { ...props } />
				<BlockControls group="block">
					<BlockVerticalAlignmentControl
						value={ attributes.awesomeGridVerticalAlignment }
						onChange={ ( alignment ) =>
							setAttributes( {
								awesomeGridVerticalAlignment: alignment,
							} )
						}
						controls={ [ 'top', 'center', 'bottom', 'stretch' ] }
					/>
				</BlockControls>
			</>
		);
	};
}, 'withGridAlignmentControl' );

addFilter(
	'editor.BlockEdit',
	'awesome-group/with-grid-alignment-control',
	withGridAlignmentControl
);

/**
 * Mirror the alignment in the editor canvas.
 */
const withGridAlignmentStyle = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => {
			const { name, attributes } = props;
			const alignValue =
				ALIGN_MAP[ attributes.awesomeGridVerticalAlignment ];

			if (
				! SUPPORTED_BLOCKS.includes( name ) ||
				attributes.layout?.type !== 'grid' ||
				! alignValue
			) {
				return <BlockListBlock { ...props } />;
			}

			const wrapperProps = {
				...( props.wrapperProps || {} ),
				style: {
					...( props.wrapperProps?.style || {} ),
					alignItems: alignValue,
				},
			};

			return (
				<BlockListBlock { ...props } wrapperProps={ wrapperProps } />
			);
		};
	},
	'withGridAlignmentStyle'
);

addFilter(
	'editor.BlockListBlock',
	'awesome-group/with-grid-alignment-style',
	withGridAlignmentStyle
);
