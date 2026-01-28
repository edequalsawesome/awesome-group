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
 * Add custom attributes to supported blocks
 */
function addResponsiveAttributes(settings, name) {
	if (!SUPPORTED_BLOCKS.includes(name)) {
		return settings;
	}

	return {
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

addFilter(
	'blocks.registerBlockType',
	'awesome-group/add-attributes',
	addResponsiveAttributes
);

/**
 * Add inspector controls for responsive settings
 */
const withResponsiveControls = createHigherOrderComponent((BlockEdit) => {
	return (props) => {
		const { name, attributes, setAttributes } = props;

		if (!SUPPORTED_BLOCKS.includes(name)) {
			return <BlockEdit {...props} />;
		}

		const {
			awesomeStackOnMobile,
			awesomeMobileBreakpoint,
			awesomeStackDirection,
			awesomeHideOnMobile,
			awesomeHideOnDesktop,
		} = attributes;

		// Only show stack controls for flex/grid layouts
		const layout = attributes.layout || {};
		const showStackControls =
			layout.type === 'flex' || layout.type === 'grid';

		return (
			<>
				<BlockEdit {...props} />
				<InspectorControls>
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
													label: __('Column (top to bottom)', 'awesome-group'),
													value: 'column',
												},
												{
													label: __('Column reverse (bottom to top)', 'awesome-group'),
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
							help={__('Hide this block on mobile devices', 'awesome-group')}
							checked={awesomeHideOnMobile}
							onChange={(value) =>
								setAttributes({ awesomeHideOnMobile: value })
							}
						/>

						<ToggleControl
							label={__('Hide on desktop', 'awesome-group')}
							help={__('Hide this block on desktop devices', 'awesome-group')}
							checked={awesomeHideOnDesktop}
							onChange={(value) =>
								setAttributes({ awesomeHideOnDesktop: value })
							}
						/>
					</PanelBody>
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
