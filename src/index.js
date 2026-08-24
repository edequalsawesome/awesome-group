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
 * 2. Reversed order at a viewport. Core's flex `orientation` accepts
 *    horizontal or vertical only, with no reversed option anywhere in its
 *    layout support, so a viewport override cannot express it.
 *
 * Both use core's own primitives rather than a parallel system: the reverse
 * rule is scoped to whichever of core's viewport media queries the block
 * selects, derived from theme.json settings.viewport, and both are emitted
 * through the style engine under this plugin's own context rather than core's
 * block-supports bucket.
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent, useInstanceId } from '@wordpress/compose';
import {
	BlockControls,
	BlockVerticalAlignmentControl,
	InspectorControls,
} from '@wordpress/block-editor';
import { Notice, PanelBody, ToggleControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Row and Stack are layout variations of core/group, not separate block types,
 * so core/group alone covers Group, Row, and Stack.
 */
const SUPPORTED_BLOCKS = [ 'core/group' ];

/**
 * Core's viewport names, supplied by PHP from the same call the front end uses.
 *
 * ponytail: the literal list below is a fallback for one case only — the inline
 * script not printing (CSP, load-order edge). It is not the source of truth and
 * must not be treated as one. If PHP's supported set is narrower, a toggle here
 * can be checked but produces no CSS: awesome_group_reverse_viewports() filters
 * against PHP's list, so the result is an inert toggle, not wrong output.
 * Revisit if core ever gains a fourth viewport.
 */
const VIEWPORTS = window.awesomeGroupViewports ?? [
	'mobile',
	'tablet',
	'desktop',
];

/** Viewport name to its editor label. */
const VIEWPORT_LABELS = {
	mobile: __( 'Mobile', 'awesome-group' ),
	tablet: __( 'Tablet', 'awesome-group' ),
	desktop: __( 'Desktop', 'awesome-group' ),
};

/**
 * Is this block a column at the given viewport?
 *
 * Mirrors awesome_group_is_column_at_viewport() in PHP. Core writes viewport
 * layout overrides to style['@{viewport}'].layout, and emits no flex-direction
 * at all for horizontal flex — so reversing a still-horizontal row needs
 * row-reverse, and reversing a stacked one needs column-reverse.
 *
 * @param {Object} attributes Block attributes.
 * @param {string} viewport   Viewport name.
 * @return {boolean} True when the block is a column at that width.
 */
function isColumnAtViewport( attributes, viewport ) {
	// Desktop always uses the base orientation: core's layout support omits
	// include_desktop, so it never renders a @desktop layout override, and
	// honouring one here would disagree with what actually ships.
	const override =
		viewport === 'desktop'
			? undefined
			: attributes?.style?.[ `@${ viewport }` ]?.layout;

	// typeof guard: `in` throws on a non-object, and a crafted block comment
	// can set layout to a string. PHP falls back to base here, so without this
	// the editor would crash where the front end quietly renders.
	if (
		override &&
		typeof override === 'object' &&
		'orientation' in override
	) {
		return override.orientation === 'vertical';
	}

	return ( attributes?.layout?.orientation ?? 'horizontal' ) === 'vertical';
}

/**
 * The viewports a block reverses at, ignoring anything core does not define.
 *
 * @param {Object} attributes Block attributes.
 * @return {string[]} Viewport names.
 */
function reverseViewports( attributes ) {
	const requested = attributes?.awesomeReverseViewports;

	if ( ! Array.isArray( requested ) ) {
		return [];
	}

	return VIEWPORTS.filter( ( viewport ) => requested.includes( viewport ) );
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
			awesomeReverseViewports: {
				type: 'array',
				items: { type: 'string' },
				default: [],
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

		// The warning used to live in ToggleControl's `help` prop, which wires
		// aria-describedby automatically. Moving it into a Notice for visual
		// weight silently dropped that: a screen reader user tabbing straight
		// to a toggle heard only its label. Re-associate it explicitly.
		const warningId = useInstanceId(
			withGroupGapControls,
			'awesome-group-reverse-warning'
		);

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
							<Notice status="warning" isDismissible={ false }>
								<span id={ warningId }>
									{ __(
										'Reversing changes visual order only. Keyboard focus order and screen reader reading order stay as written, so the two will disagree — which matters most when the block contains links, buttons, or form fields. Reorder the blocks themselves if the sequence genuinely matters.',
										'awesome-group'
									) }
								</span>
							</Notice>
							{ VIEWPORTS.map( ( viewport ) => {
								const active =
									reverseViewports( attributes ).includes(
										viewport
									);

								return (
									<ToggleControl
										key={ viewport }
										aria-describedby={ warningId }
										label={ sprintf(
											/* translators: %s: viewport name, e.g. Mobile. */
											__(
												'Reverse order on %s',
												'awesome-group'
											),
											VIEWPORT_LABELS[ viewport ] ??
												viewport
										) }
										checked={ active }
										onChange={ ( value ) => {
											const current =
												reverseViewports( attributes );
											const next = VIEWPORTS.filter(
												( candidate ) =>
													candidate === viewport
														? value
														: current.includes(
																candidate
														  )
											);
											setAttributes( {
												awesomeReverseViewports: next,
											} );
										} }
									/>
								);
							} ) }
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
			const reversed =
				layoutType === 'flex' ? reverseViewports( attributes ) : [];

			if ( ! alignValue && ! reversed.length ) {
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
			// preview rides classes the plugin's canvas CSS targets. Which
			// class depends on the axis the block has at that width, decided
			// by the same rule the front end uses.
			const reverseClasses = reversed.map( ( viewport ) =>
				isColumnAtViewport( attributes, viewport )
					? `ag-rev-${ viewport }-column`
					: `ag-rev-${ viewport }-row`
			);

			const className = [ props.className, ...reverseClasses ]
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
