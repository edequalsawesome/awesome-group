/**
 * Awesome Group - Responsive layout controls for Group blocks
 */

import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import {
	InspectorControls,
	BlockControls,
	BlockVerticalAlignmentControl,
} from '@wordpress/block-editor';
import { Fragment, useEffect } from '@wordpress/element';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- no stable UnitControl exists yet; revisit when it graduates.
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './style.css';
import './editor.css';

/**
 * Supported blocks for responsive controls.
 * Row and Stack are layout variations of core/group, not separate block
 * types, so core/group alone covers Group, Row, and Stack.
 */
const SUPPORTED_BLOCKS = [ 'core/group' ];

/**
 * Supported blocks for grid alignment (Grid layout is missing vertical alignment!)
 */
const GRID_ALIGNMENT_BLOCKS = [ 'core/group' ];

const DEFAULT_BREAKPOINT = '768px';
const PHP_TRIM_CHARACTERS = /^[ \t\n\r\u0000\u000B]+|[ \t\n\r\u0000\u000B]+$/g;
const BREAKPOINT_PATTERN = /^(\d+(?:\.\d+)?)(px|em|rem)$/;
const stackCssTemplate =
	typeof window === 'undefined' ? '' : window.awesomeGroupStackCss || '';

/**
 * Normalize saved breakpoint values exactly as the frontend does.
 *
 * @param {*} breakpoint Candidate saved attribute value.
 * @return {string} A valid CSS breakpoint or the default.
 */
export function sanitizeBreakpoint( breakpoint ) {
	if ( typeof breakpoint !== 'string' || /[^\x00-\x7F]/.test( breakpoint ) ) {
		return DEFAULT_BREAKPOINT;
	}

	const normalized = breakpoint
		.replace( PHP_TRIM_CHARACTERS, '' )
		.toLowerCase();
	const match = BREAKPOINT_PATTERN.exec( normalized );
	const number = match ? Number( match[ 1 ] ) : Number.NaN;

	if (
		normalized.length > 64 ||
		! match ||
		! Number.isFinite( number ) ||
		number <= 0
	) {
		return DEFAULT_BREAKPOINT;
	}

	return normalized;
}

/**
 * Scope the shipped stack stylesheet to one editor block.
 *
 * @param {string} template   Localized stack stylesheet.
 * @param {string} breakpoint Validated breakpoint.
 * @param {string} className  Trusted editor-only class.
 * @return {string} Scoped stylesheet.
 */
export function buildStackCss( template, breakpoint, className ) {
	if ( ! template || ! className ) {
		return '';
	}

	return template
		.replace( '(max-width: 768px)', `(max-width: ${ breakpoint })` )
		.split( '.ag-stack-mobile' )
		.join( `.${ className }.ag-stack-mobile` );
}

export function ResponsiveStackStyle( { clientId, css } ) {
	useEffect( () => {
		if ( ! clientId || ! css || typeof document === 'undefined' ) {
			return undefined;
		}

		const blockId = `block-${ clientId }`;
		const documents = [ document ];

		document.querySelectorAll( 'iframe' ).forEach( ( iframe ) => {
			try {
				if ( iframe.contentDocument ) {
					documents.push( iframe.contentDocument );
				}
			} catch ( error ) {
				// Cross-origin frames cannot contain this editor block.
			}
		} );

		const wrapper = documents
			.map( ( ownerDocument ) => ownerDocument.getElementById( blockId ) )
			.find( Boolean );

		if ( ! wrapper || ! wrapper.ownerDocument.head ) {
			return undefined;
		}

		const style = wrapper.ownerDocument.createElement( 'style' );
		style.textContent = css;
		wrapper.ownerDocument.head.appendChild( style );

		return () => {
			style.parentNode?.removeChild( style );
		};
	}, [ clientId, css ] );

	return null;
}

/**
 * Add custom attributes to supported blocks
 * @param {Object} settings Block settings being filtered.
 * @param {string} name     Block name.
 * @return {Object} Filtered block settings.
 */
function addResponsiveAttributes( settings, name ) {
	// Responsive layout attributes (Group + Row)
	if ( SUPPORTED_BLOCKS.includes( name ) ) {
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
	if ( GRID_ALIGNMENT_BLOCKS.includes( name ) ) {
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
 * Add inspector controls for responsive settings
 */
const withResponsiveControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, attributes, setAttributes } = props;

		const showResponsiveControls = SUPPORTED_BLOCKS.includes( name );
		const showGridAlignmentControls =
			GRID_ALIGNMENT_BLOCKS.includes( name );

		if ( ! showResponsiveControls && ! showGridAlignmentControls ) {
			return <BlockEdit { ...props } />;
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
				<BlockEdit { ...props } />
				{ showGridAlignmentControls && isGridLayout && (
					<BlockControls group="block">
						<BlockVerticalAlignmentControl
							value={ awesomeGridVerticalAlignment }
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
				<InspectorControls>
					{ showResponsiveControls && (
						<PanelBody
							title={ __( 'Responsive Layout', 'awesome-group' ) }
							initialOpen={ false }
						>
							{ showStackControls && (
								<>
									<ToggleControl
										label={ __(
											'Stack on mobile',
											'awesome-group'
										) }
										help={ __(
											'Stack items vertically on smaller screens',
											'awesome-group'
										) }
										checked={ awesomeStackOnMobile }
										onChange={ ( value ) =>
											setAttributes( {
												awesomeStackOnMobile: value,
											} )
										}
									/>

									{ awesomeStackOnMobile && (
										<>
											<UnitControl
												label={ __(
													'Breakpoint',
													'awesome-group'
												) }
												value={
													awesomeMobileBreakpoint
												}
												onChange={ ( value ) =>
													setAttributes( {
														awesomeMobileBreakpoint:
															value,
													} )
												}
												units={ [
													{
														value: 'px',
														label: 'px',
													},
													{
														value: 'em',
														label: 'em',
													},
													{
														value: 'rem',
														label: 'rem',
													},
												] }
											/>

											<SelectControl
												label={ __(
													'Stack direction',
													'awesome-group'
												) }
												value={ awesomeStackDirection }
												help={
													awesomeStackDirection ===
													'column-reverse'
														? __(
																'Warning: Reverse order changes visual order but not keyboard focus order or screen reader reading order.',
																'awesome-group'
														  )
														: ''
												}
												options={ [
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
												] }
												onChange={ ( value ) =>
													setAttributes( {
														awesomeStackDirection:
															value,
													} )
												}
											/>
										</>
									) }
								</>
							) }

							<ToggleControl
								label={ __(
									'Hide on mobile',
									'awesome-group'
								) }
								help={ __(
									'Completely hides this block on mobile. Note: Hidden content is also removed from screen readers.',
									'awesome-group'
								) }
								checked={ awesomeHideOnMobile }
								onChange={ ( value ) =>
									setAttributes( {
										awesomeHideOnMobile: value,
									} )
								}
							/>

							<ToggleControl
								label={ __(
									'Hide on desktop',
									'awesome-group'
								) }
								help={ __(
									'Completely hides this block on desktop. Note: Hidden content is also removed from screen readers.',
									'awesome-group'
								) }
								checked={ awesomeHideOnDesktop }
								onChange={ ( value ) =>
									setAttributes( {
										awesomeHideOnDesktop: value,
									} )
								}
							/>
						</PanelBody>
					) }
				</InspectorControls>
			</>
		);
	};
}, 'withResponsiveControls' );

addFilter(
	'editor.BlockEdit',
	'awesome-group/with-responsive-controls',
	withResponsiveControls
);

/**
 * Add custom classes and styles in the editor
 */
const withResponsiveClasses = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => {
			const { name, attributes } = props;

			const isSupported = SUPPORTED_BLOCKS.includes( name );
			const isGridAlignmentSupported =
				GRID_ALIGNMENT_BLOCKS.includes( name );

			if ( ! isSupported && ! isGridAlignmentSupported ) {
				return <BlockListBlock { ...props } />;
			}

			const {
				awesomeStackOnMobile,
				awesomeMobileBreakpoint,
				awesomeHideOnMobile,
				awesomeHideOnDesktop,
				awesomeStackDirection,
				awesomeGridVerticalAlignment,
				layout,
			} = attributes;

			const sanitizedClientId = String( props.clientId || '' ).replace(
				/[^A-Za-z0-9_-]/g,
				''
			);
			const editorClass = awesomeStackOnMobile
				? `ag-editor-${ sanitizedClientId || 'block' }`
				: '';
			let stackCss = '';
			if ( awesomeStackOnMobile ) {
				stackCss = buildStackCss(
					stackCssTemplate,
					sanitizeBreakpoint( awesomeMobileBreakpoint ),
					editorClass
				);
			}
			let wrapperProps = props.wrapperProps || {};
			if ( awesomeStackOnMobile ) {
				wrapperProps = {
					...wrapperProps,
					style: {
						...wrapperProps.style,
						'--ag-stack-direction':
							awesomeStackDirection === 'column-reverse'
								? 'column-reverse'
								: 'column',
					},
				};
			}

			const className = [
				props.className,
				awesomeStackOnMobile && 'ag-stack-mobile',
				editorClass,
				awesomeHideOnMobile && 'ag-hide-mobile',
				awesomeHideOnDesktop && 'ag-hide-desktop',
			]
				.filter( Boolean )
				.join( ' ' );

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
				const alignValue = alignMap[ awesomeGridVerticalAlignment ];
				if ( alignValue ) {
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
				<Fragment>
					<BlockListBlock
						{ ...props }
						className={ className }
						wrapperProps={ wrapperProps }
					/>
					{ awesomeStackOnMobile && (
						<ResponsiveStackStyle
							clientId={ props.clientId }
							css={ stackCss }
						/>
					) }
				</Fragment>
			);
		};
	},
	'withResponsiveClasses'
);

addFilter(
	'editor.BlockListBlock',
	'awesome-group/with-responsive-classes',
	withResponsiveClasses
);
