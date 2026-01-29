/**
 * Awesome Group - Responsive layout controls for Group blocks
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	RangeControl,
	ColorPalette,
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import './style.css';
import './editor.css';

/**
 * Supported blocks for responsive controls
 */
const SUPPORTED_BLOCKS = ['core/group', 'core/row'];

/**
 * Supported blocks for decorative borders
 */
const BORDER_SUPPORTED_BLOCKS = ['core/group'];

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

	// Decorative border attributes (Group only)
	if (BORDER_SUPPORTED_BLOCKS.includes(name)) {
		settings = {
			...settings,
			attributes: {
				...settings.attributes,
				// Border toggles for each side
				awesomeBorderTop: {
					type: 'boolean',
					default: false,
				},
				awesomeBorderRight: {
					type: 'boolean',
					default: false,
				},
				awesomeBorderBottom: {
					type: 'boolean',
					default: false,
				},
				awesomeBorderLeft: {
					type: 'boolean',
					default: false,
				},
				// Border style: 'squiggle' or 'zigzag'
				awesomeBorderStyle: {
					type: 'string',
					default: 'squiggle',
				},
				// Border color (hex or CSS variable)
				awesomeBorderColor: {
					type: 'string',
					default: '',
				},
				// Border thickness (stroke width)
				awesomeBorderThickness: {
					type: 'number',
					default: 3,
				},
				// Wave amplitude (how "wavy" it is)
				awesomeBorderAmplitude: {
					type: 'number',
					default: 10,
				},
				// TODO: Future - individual side colors, corner handling options
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
		const showBorderControls = BORDER_SUPPORTED_BLOCKS.includes(name);

		if (!showResponsiveControls && !showBorderControls) {
			return <BlockEdit {...props} />;
		}

		const {
			// Responsive attributes
			awesomeStackOnMobile,
			awesomeMobileBreakpoint,
			awesomeStackDirection,
			awesomeHideOnMobile,
			awesomeHideOnDesktop,
			// Border attributes
			awesomeBorderTop,
			awesomeBorderRight,
			awesomeBorderBottom,
			awesomeBorderLeft,
			awesomeBorderStyle,
			awesomeBorderColor,
			awesomeBorderThickness,
			awesomeBorderAmplitude,
		} = attributes;

		// Only show stack controls for flex/grid layouts
		const layout = attributes.layout || {};
		const showStackControls =
			layout.type === 'flex' || layout.type === 'grid';

		// Get theme colors for the color palette
		const colors = useSelect((select) => {
			const settings = select('core/block-editor').getSettings();
			return settings.colors || [];
		}, []);

		const hasBordersEnabled =
			awesomeBorderTop ||
			awesomeBorderRight ||
			awesomeBorderBottom ||
			awesomeBorderLeft;

		return (
			<>
				<BlockEdit {...props} />
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
									'Hide this block on mobile devices',
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
									'Hide this block on desktop devices',
									'awesome-group'
								)}
								checked={awesomeHideOnDesktop}
								onChange={(value) =>
									setAttributes({ awesomeHideOnDesktop: value })
								}
							/>
						</PanelBody>
					)}

					{showBorderControls && (
						<PanelBody
							title={__('Decorative Borders', 'awesome-group')}
							initialOpen={false}
						>
							<p className="components-base-control__help">
								{__(
									'Add 90s-style squiggle or zigzag borders to this block.',
									'awesome-group'
								)}
							</p>

							<div
								style={{
									display: 'grid',
									gridTemplateColumns: '1fr 1fr',
									gap: '8px',
									marginBottom: '16px',
								}}
							>
								<ToggleControl
									label={__('Top', 'awesome-group')}
									checked={awesomeBorderTop}
									onChange={(value) =>
										setAttributes({ awesomeBorderTop: value })
									}
								/>
								<ToggleControl
									label={__('Bottom', 'awesome-group')}
									checked={awesomeBorderBottom}
									onChange={(value) =>
										setAttributes({ awesomeBorderBottom: value })
									}
								/>
								<ToggleControl
									label={__('Left', 'awesome-group')}
									checked={awesomeBorderLeft}
									onChange={(value) =>
										setAttributes({ awesomeBorderLeft: value })
									}
									// TODO: Vertical squiggles coming soon!
									// Currently disabled until we implement rotated SVG paths
									// This will also be used in the Awesome Quote Block plugin
								/>
								<ToggleControl
									label={__('Right', 'awesome-group')}
									checked={awesomeBorderRight}
									onChange={(value) =>
										setAttributes({ awesomeBorderRight: value })
									}
									// TODO: Vertical squiggles coming soon!
								/>
							</div>

							{hasBordersEnabled && (
								<>
									<SelectControl
										label={__('Border style', 'awesome-group')}
										value={awesomeBorderStyle}
										options={[
											{
												label: __('Squiggle', 'awesome-group'),
												value: 'squiggle',
											},
											{
												label: __('Zigzag', 'awesome-group'),
												value: 'zigzag',
											},
										]}
										onChange={(value) =>
											setAttributes({ awesomeBorderStyle: value })
										}
									/>

									<div style={{ marginBottom: '16px' }}>
										<p
											style={{
												marginBottom: '8px',
												fontWeight: '500',
											}}
										>
											{__('Border color', 'awesome-group')}
										</p>
										<ColorPalette
											colors={colors}
											value={awesomeBorderColor}
											onChange={(value) =>
												setAttributes({ awesomeBorderColor: value })
											}
										/>
									</div>

									<RangeControl
										label={__('Thickness', 'awesome-group')}
										value={awesomeBorderThickness}
										onChange={(value) =>
											setAttributes({ awesomeBorderThickness: value })
										}
										min={1}
										max={10}
									/>

									<RangeControl
										label={__('Amplitude (waviness)', 'awesome-group')}
										value={awesomeBorderAmplitude}
										onChange={(value) =>
											setAttributes({ awesomeBorderAmplitude: value })
										}
										min={5}
										max={30}
									/>
								</>
							)}
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
 * Add custom classes in the editor
 */
const withResponsiveClasses = createHigherOrderComponent((BlockListBlock) => {
	return (props) => {
		const { name, attributes } = props;

		if (!SUPPORTED_BLOCKS.includes(name)) {
			return <BlockListBlock {...props} />;
		}

		const {
			awesomeStackOnMobile,
			awesomeHideOnMobile,
			awesomeHideOnDesktop,
		} = attributes;

		let className = props.className || '';

		if (awesomeStackOnMobile) {
			className += ' ag-stack-mobile';
		}
		if (awesomeHideOnMobile) {
			className += ' ag-hide-mobile';
		}
		if (awesomeHideOnDesktop) {
			className += ' ag-hide-desktop';
		}

		return <BlockListBlock {...props} className={className} />;
	};
}, 'withResponsiveClasses');

addFilter(
	'editor.BlockListBlock',
	'awesome-group/with-responsive-classes',
	withResponsiveClasses
);
