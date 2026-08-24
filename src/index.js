/**
 * Awesome Group - fills the gaps core leaves on Group blocks
 *
 * Almost everything else this plugin used to do is now core. WordPress 7.0
 * shipped per-viewport block visibility (block-supports/block-visibility.php)
 * and viewport layout overrides, with breakpoints configurable in theme.json
 * under settings.viewport (WP 7.1). Core's implementation beats what was here
 * by being site-wide rather than per-block, with three configurable breakpoints
 * instead of one hardcoded 768px. It hides the same way the removed code did,
 * with `display: none !important` inside a media query — parity, not an
 * improvement: markup stays in the source, out of the accessibility tree.
 *
 * Two gaps remain, and they are what this plugin is now for:
 *
 * 1. Vertical alignment on a Grid layout. Core's layout support applies
 *    verticalAlignment to flex only — the grid branch emits
 *    grid-template-columns and grid-template-rows and never align-items.
 * 2. Reversed order at the mobile viewport. Core's flex `orientation` accepts
 *    horizontal or vertical only, with no reversed option anywhere in its
 *    layout support, so a viewport override cannot express it.
 *
 * Both use core's own primitives rather than a parallel system: the reverse
 * rule is scoped to the mobile media query core derives from theme.json
 * settings.viewport, and both are emitted through the style engine into the
 * same block-supports store core's layout styles use.
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import {
	BlockControls,
	BlockVerticalAlignmentControl,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Row and Stack are layout variations of core/group, not separate block types,
 * so core/group alone covers Group, Row, and Stack.
 */
const SUPPORTED_BLOCKS = [ 'core/group' ];

/**
 * Is this block a column at the mobile viewport?
 *
 * Mirrors awesome_group_is_column_at_mobile() in PHP. Core writes viewport
 * layout overrides to style['@mobile'].layout, and emits no flex-direction at
 * all for horizontal flex — so reversing a still-horizontal row needs
 * row-reverse, and reversing a stacked one needs column-reverse.
 *
 * @param {Object} attributes Block attributes.
 * @return {boolean} True when the block is a column at that width.
 */
function isColumnAtMobile( attributes ) {
	const override = attributes?.style?.[ '@mobile' ]?.layout;

	if ( override && 'orientation' in override ) {
		return override.orientation === 'vertical';
	}

	return ( attributes?.layout?.orientation ?? 'horizontal' ) === 'vertical';
}

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
			awesomeReverseOnMobile: {
				type: 'boolean',
				default: false,
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
const withGroupGapControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, attributes, setAttributes } = props;

		if ( ! SUPPORTED_BLOCKS.includes( name ) ) {
			return <BlockEdit { ...props } />;
		}

		const layoutType = attributes.layout?.type;
		const isGrid = layoutType === 'grid';
		const isFlex = layoutType === 'flex';

		if ( ! isGrid && ! isFlex ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<BlockEdit { ...props } />
				{ isGrid && (
					<BlockControls group="block">
						<BlockVerticalAlignmentControl
							value={ attributes.awesomeGridVerticalAlignment }
							onChange={ ( alignment ) =>
								setAttributes( {
									awesomeGridVerticalAlignment: alignment,
								} )
							}
							controls={ [
								'top',
								'center',
								'bottom',
								'stretch',
							] }
						/>
					</BlockControls>
				) }
				{ isFlex && (
					<InspectorControls>
						<PanelBody
							title={ __( 'Responsive Order', 'awesome-group' ) }
							initialOpen={ false }
						>
							<ToggleControl
								label={ __(
									'Reverse order on mobile',
									'awesome-group'
								) }
								help={ __(
									'Warning: this reverses visual order only. Keyboard focus order and screen reader reading order stay as written, so the two will disagree. Reorder the blocks themselves if the sequence genuinely matters.',
									'awesome-group'
								) }
								checked={ !! attributes.awesomeReverseOnMobile }
								onChange={ ( value ) =>
									setAttributes( {
										awesomeReverseOnMobile: value,
									} )
								}
							/>
						</PanelBody>
					</InspectorControls>
				) }
			</>
		);
	};
}, 'withGroupGapControls' );

addFilter(
	'editor.BlockEdit',
	'awesome-group/with-group-controls',
	withGroupGapControls
);

/**
 * Mirror the alignment in the editor canvas.
 */
const withGridAlignmentStyle = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => {
			const { name, attributes } = props;

			if ( ! SUPPORTED_BLOCKS.includes( name ) ) {
				return <BlockListBlock { ...props } />;
			}

			const layoutType = attributes.layout?.type;
			const alignValue =
				layoutType === 'grid'
					? ALIGN_MAP[ attributes.awesomeGridVerticalAlignment ]
					: undefined;
			const reverse =
				layoutType === 'flex' && !! attributes.awesomeReverseOnMobile;

			if ( ! alignValue && ! reverse ) {
				return <BlockListBlock { ...props } />;
			}

			let wrapperProps = props.wrapperProps || {};

			if ( alignValue ) {
				wrapperProps = {
					...wrapperProps,
					style: { ...wrapperProps.style, alignItems: alignValue },
				};
			}

			// A media query cannot live in an inline style, so the reverse
			// preview rides a class the plugin's canvas CSS targets. Which
			// class depends on the axis the block has at that width, decided
			// by the same rule the front end uses.
			let reverseClass = false;
			if ( reverse ) {
				reverseClass = isColumnAtMobile( attributes )
					? 'ag-reverse-column'
					: 'ag-reverse-row';
			}

			const className = [ props.className, reverseClass ]
				.filter( Boolean )
				.join( ' ' );

			return (
				<BlockListBlock
					{ ...props }
					className={ className }
					wrapperProps={ wrapperProps }
				/>
			);
		};
	},
	'withGridAlignmentStyle'
);

addFilter(
	'editor.BlockListBlock',
	'awesome-group/with-group-styles',
	withGridAlignmentStyle
);
