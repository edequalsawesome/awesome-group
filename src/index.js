/**
 * Awesome Group - Responsive layout controls for Group blocks
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls, BlockControls, BlockVerticalAlignmentControl } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './style.css';
import './editor.css';

/**
 * Supported blocks for responsive controls
 */
const SUPPORTED_BLOCKS = ['core/group', 'core/row'];

/**
 * Supported blocks for grid alignment (Grid layout is missing vertical alignment!)
 */
const GRID_ALIGNMENT_BLOCKS = ['core/group'];

/**
 * Add custom attributes to supported blocks
 */
function addResponsiveAttributes(settings, name) {
	// Responsive layout attributes (Group + Row)
	if (SUPPORTED_BLOCKS.includes(name)) {
		settings = {
			...settings,
			attributes: {
				...settings.attributes,
				awesomeStackOnMobile: {
					type: 'boolean',
					default: false,
				},
				awesomeMobileBreakpoint: {
					type: 'string',
					default: '768px',
				},
				awesomeStackDirection: {
					type: 'string',
					default: 'column',
				},
				awesomeHideOnMobile: {
					type: 'boolean',
					default: false,
				},
				awesomeHideOnDesktop: {
					type: 'boolean',
					default: false,
				},
			},
		};
	}

	// Grid vertical alignment (WordPress forgot to add this!)
	if (GRID_ALIGNMENT_BLOCKS.includes(name)) {
		settings = {
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

	return settings;
}

addFilter(
	'blocks.registerBlockType',
	'awesome-group/add-attributes',
	addResponsiveAttributes
);

/**
 * Add inspector controls for responsive settings and decorative borders
 */
const withResponsiveControls = createHigherOrderComponent((BlockEdit) => {
	return (props) => {
		const { name, attributes, setAttributes } = props;

		const showResponsiveControls = SUPPORTED_BLOCKS.includes(name);
		const showGridAlignmentControls = GRID_ALIGNMENT_BLOCKS.includes(name);

		if (!showResponsiveControls && !showGridAlignmentControls) {
			return <BlockEdit {...props} />;
		}

		const {
			awesomeStackOnMobile,
			awesomeMobileBreakpoint,
			awesomeStackDirection,
			awesomeHideOnMobile,
			awesomeHideOnDesktop,
			awesomeGridVerticalAlignment,
		} = attributes;

		// Only show stack controls for flex/grid layouts
		const layout = attributes.layout || {};
		const showStackControls =
			layout.type === 'flex' || layout.type === 'grid';

		// Only show grid alignment for grid layouts
		const isGridLayout = layout.type === 'grid';

		return (
			<>
				<BlockEdit {...props} />
				{showGridAlignmentControls && isGridLayout && (
					<BlockControls group="block">
						<BlockVerticalAlignmentControl
							value={ awesomeGridVerticalAlignment }
							onChange={ ( alignment ) => setAttributes({ awesomeGridVerticalAlignment: alignment }) }
						/>
					</BlockControls>
				)}
				<InspectorControls>
					{showResponsiveControls && (
						<PanelBody
							title={__('Responsive Layout', 'awesome-group')}
							initialOpen={false}
						>
							{showStackControls && (
								<>
									<ToggleControl
										label={__('Stack on mobile', 'awesome-group')}
										help={__(
											'Stack items vertically on smaller screens',
											'awesome-group'
										)}
										checked={awesomeStackOnMobile}
										onChange={(value) =>
											setAttributes({ awesomeStackOnMobile: value })
										}
									/>

									{awesomeStackOnMobile && (
										<>
											<UnitControl
												label={__('Breakpoint', 'awesome-group')}
												value={awesomeMobileBreakpoint}
												onChange={(value) =>
													setAttributes({
														awesomeMobileBreakpoint: value,
													})
												}
												units={[
													{ value: 'px', label: 'px' },
													{ value: 'em', label: 'em' },
													{ value: 'rem', label: 'rem' },
												]}
											/>

											<SelectControl
												label={__('Stack direction', 'awesome-group')}
												value={awesomeStackDirection}
												help={
													awesomeStackDirection === 'column-reverse'
														? __(
																'Warning: Reverse order changes visual order but not keyboard focus order or screen reader reading order.',
																'awesome-group'
														  )
														: ''
												}
												options={[
													{
														label: __(
															'Column (top to bottom)',
															'awesome-group'
														),
														value: 'column',
													},
													{
														label: __(
															'Column reverse (bottom to top)',
															'awesome-group'
														),
														value: 'column-reverse',
													},
												]}
												onChange={(value) =>
													setAttributes({ awesomeStackDirection: value })
												}
											/>
										</>
									)}
								</>
							)}

							<ToggleControl
								label={__('Hide on mobile', 'awesome-group')}
								help={__(
									'Completely hides this block on mobile. Note: Hidden content is also removed from screen readers.',
									'awesome-group'
								)}
								checked={awesomeHideOnMobile}
								onChange={(value) =>
									setAttributes({ awesomeHideOnMobile: value })
								}
							/>

							<ToggleControl
								label={__('Hide on desktop', 'awesome-group')}
								help={__(
									'Completely hides this block on desktop. Note: Hidden content is also removed from screen readers.',
									'awesome-group'
								)}
								checked={awesomeHideOnDesktop}
								onChange={(value) =>
									setAttributes({ awesomeHideOnDesktop: value })
								}
							/>
						</PanelBody>
					)}
				</InspectorControls>
			</>
		);
	};
}, 'withResponsiveControls');

addFilter(
	'editor.BlockEdit',
	'awesome-group/with-responsive-controls',
	withResponsiveControls
);

/**
 * Add custom classes and styles in the editor
 */
const withResponsiveClasses = createHigherOrderComponent((BlockListBlock) => {
	return (props) => {
		const { name, attributes } = props;

		const isSupported = SUPPORTED_BLOCKS.includes(name);
		const isGridAlignmentSupported = GRID_ALIGNMENT_BLOCKS.includes(name);

		if (!isSupported && !isGridAlignmentSupported) {
			return <BlockListBlock {...props} />;
		}

		const {
			awesomeStackOnMobile,
			awesomeHideOnMobile,
			awesomeHideOnDesktop,
			awesomeGridVerticalAlignment,
			layout,
		} = attributes;

		let className = props.className || '';
		let wrapperProps = props.wrapperProps || {};

		if (awesomeStackOnMobile) {
			className += ' ag-stack-mobile';
		}
		if (awesomeHideOnMobile) {
			className += ' ag-hide-mobile';
		}
		if (awesomeHideOnDesktop) {
			className += ' ag-hide-desktop';
		}

		// Grid vertical alignment in editor
		if (
			isGridAlignmentSupported &&
			layout?.type === 'grid' &&
			awesomeGridVerticalAlignment
		) {
			const alignMap = {
				top: 'start',
				center: 'center',
				bottom: 'end',
				stretch: 'stretch',
			};
			const alignValue = alignMap[awesomeGridVerticalAlignment];
			if (alignValue) {
				wrapperProps = {
					...wrapperProps,
					style: {
						...wrapperProps.style,
						alignItems: alignValue,
					},
				};
			}
		}

		return (
			<BlockListBlock
				{...props}
				className={className}
				wrapperProps={wrapperProps}
			/>
		);
	};
}, 'withResponsiveClasses');

addFilter(
	'editor.BlockListBlock',
	'awesome-group/with-responsive-classes',
	withResponsiveClasses
);
