import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { Fragment, useEffect } from '@wordpress/element';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis -- no stable UnitControl exists yet.
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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

addFilter(
	'blocks.registerBlockType',
	'awesome-group/custom-stack-attributes',
	( settings, name ) => {
		if ( name !== 'core/group' ) {
			return settings;
		}
		return {
			...settings,
			attributes: {
				...settings.attributes,
				awesomeStackOnMobile: { type: 'boolean', default: false },
				awesomeMobileBreakpoint: {
					type: 'string',
					default: DEFAULT_BREAKPOINT,
				},
				awesomeStackDirection: { type: 'string', default: 'column' },
			},
		};
	}
);

const withCustomStackControls = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		const { name, attributes, setAttributes } = props;
		const supported =
			name === 'core/group' &&
			[ 'flex', 'grid' ].includes( attributes.layout?.type );
		return (
			<>
				<BlockEdit { ...props } />
				{ supported && (
					<InspectorControls>
						<PanelBody
							title={ __( 'Custom Stacking', 'awesome-group' ) }
							initialOpen={ false }
						>
							<ToggleControl
								label={ __(
									'Stack at a custom breakpoint',
									'awesome-group'
								) }
								help={ __(
									'Within this width, custom stacking overrides viewport layout and reversal and stretches grid children to full width.',
									'awesome-group'
								) }
								checked={ attributes.awesomeStackOnMobile }
								onChange={ ( value ) =>
									setAttributes( {
										awesomeStackOnMobile: value,
									} )
								}
							/>
							{ attributes.awesomeStackOnMobile && (
								<>
									<UnitControl
										label={ __(
											'Stack at or below',
											'awesome-group'
										) }
										value={
											attributes.awesomeMobileBreakpoint
										}
										onChange={ ( value ) =>
											setAttributes( {
												awesomeMobileBreakpoint: value,
											} )
										}
										units={ [
											{ value: 'px', label: 'px' },
											{ value: 'em', label: 'em' },
											{ value: 'rem', label: 'rem' },
										] }
									/>
									<SelectControl
										label={ __(
											'Stack direction',
											'awesome-group'
										) }
										value={
											attributes.awesomeStackDirection
										}
										onChange={ ( value ) =>
											setAttributes( {
												awesomeStackDirection: value,
											} )
										}
										options={ [
											{
												label: __(
													'Normal',
													'awesome-group'
												),
												value: 'column',
											},
											{
												label: __(
													'Reversed',
													'awesome-group'
												),
												value: 'column-reverse',
											},
										] }
										help={ __(
											'Reversing changes visual order only. Keyboard and screen reader order stay as authored.',
											'awesome-group'
										) }
									/>
								</>
							) }
						</PanelBody>
					</InspectorControls>
				) }
			</>
		);
	},
	'withCustomStackControls'
);
addFilter(
	'editor.BlockEdit',
	'awesome-group/custom-stack-controls',
	withCustomStackControls
);

const withCustomStackStyles = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		const { name, attributes, clientId } = props;
		if ( name !== 'core/group' ) {
			return <BlockListBlock { ...props } />;
		}
		const enabled =
			attributes.awesomeStackOnMobile === true &&
			[ 'flex', 'grid' ].includes( attributes.layout?.type );
		const editorClass = `ag-editor-${ String( clientId || 'block' ).replace(
			/[^A-Za-z0-9_-]/g,
			''
		) }`;
		const css = enabled
			? buildStackCss(
					stackCssTemplate,
					sanitizeBreakpoint( attributes.awesomeMobileBreakpoint ),
					editorClass
			  )
			: '';
		const className = enabled
			? [ props.className, 'ag-stack-mobile', editorClass ]
					.filter( Boolean )
					.join( ' ' )
			: props.className;
		const wrapperProps = enabled
			? {
					...props.wrapperProps,
					style: {
						...props.wrapperProps?.style,
						'--ag-stack-direction':
							attributes.awesomeStackDirection ===
							'column-reverse'
								? 'column-reverse'
								: 'column',
					},
			  }
			: props.wrapperProps;
		return (
			<Fragment>
				<BlockListBlock
					{ ...props }
					className={ className }
					wrapperProps={ wrapperProps }
				/>
				<ResponsiveStackStyle clientId={ clientId } css={ css } />
			</Fragment>
		);
	},
	'withCustomStackStyles'
);
addFilter(
	'editor.BlockListBlock',
	'awesome-group/custom-stack-styles',
	withCustomStackStyles
);
