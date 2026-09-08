import { createElement, createRoot } from '@wordpress/element';

jest.mock( '@wordpress/hooks', () => ( { addFilter: jest.fn() } ) );
jest.mock( '@wordpress/compose', () => ( {
	createHigherOrderComponent: ( component ) => component,
} ) );
jest.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: () => null,
	BlockControls: () => null,
	BlockVerticalAlignmentControl: () => null,
} ) );
jest.mock( '@wordpress/components', () => ( {
	PanelBody: () => null,
	ToggleControl: () => null,
	SelectControl: () => null,
	__experimentalUnitControl: () => null,
} ) );
jest.mock( '@wordpress/i18n', () => ( { __: ( value ) => value } ) );

import {
	buildStackCss,
	ResponsiveStackStyle,
	sanitizeBreakpoint,
} from '../index';

const template = `@media screen and (max-width: 768px) {
\t.ag-stack-mobile.is-layout-flex { flex-direction: var(--ag-stack-direction, column); }
\t.ag-stack-mobile.is-layout-grid > * { width: 100%; }
}`;

const flushEffects = () =>
	new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
const hasStyle = ( ownerDocument, css ) =>
	Array.from( ownerDocument.head.querySelectorAll( 'style' ) ).some(
		( style ) => style.textContent === css
	);

describe( 'responsive stack styles', () => {
	let root;
	let mountPoint;

	beforeEach( () => {
		mountPoint = document.createElement( 'div' );
		document.body.appendChild( mountPoint );
		root = createRoot( mountPoint );
	} );

	afterEach( () => {
		root.unmount();
		mountPoint.remove();
	} );

	it.each( [
		[ '600px', '600px' ],
		[ '48EM', '48em' ],
		[ ' \t\n1024PX\r\u0000\u000B', '1024px' ],
		[ '0.' + '0'.repeat( 59 ) + '1px', '0.' + '0'.repeat( 59 ) + '1px' ],
		[ '\f600px', '768px' ],
		[ '\u00a0600px', '768px' ],
		[ '600px\u2028', '768px' ],
		[ '600px\u2029', '768px' ],
		[ '\ufeff600px', '768px' ],
		[ '0px', '768px' ],
		[ '1e3px', '768px' ],
		[ '600px}</style><style>', '768px' ],
		[ '0.' + '0'.repeat( 60 ) + '1px', '768px' ],
		[ null, '768px' ],
		[ [], '768px' ],
	] )( 'normalizes %p like PHP', ( value, expected ) => {
		expect( sanitizeBreakpoint( value ) ).toBe( expected );
	} );

	it( 'scopes every stack selector to the trusted editor class', () => {
		const css = buildStackCss( template, '600px', 'ag-editor-first' );

		expect( css ).toContain( '(max-width: 600px)' );
		expect( css ).toContain(
			'.ag-editor-first.ag-stack-mobile.is-layout-flex'
		);
		expect( css ).toContain(
			'.ag-editor-first.ag-stack-mobile.is-layout-grid > *'
		);
		expect( css ).not.toContain( '.ag-editor-second' );
	} );

	it( 'mounts, updates, and removes the exact style node in the ambient document', async () => {
		const block = document.createElement( 'div' );
		block.id = 'block-first';
		document.body.appendChild( block );
		const firstCss = buildStackCss( template, '600px', 'ag-editor-first' );
		const secondCss = buildStackCss(
			template,
			'1024px',
			'ag-editor-first'
		);

		root.render(
			createElement( ResponsiveStackStyle, {
				clientId: 'first',
				css: firstCss,
			} )
		);
		await flushEffects();
		expect( hasStyle( document, firstCss ) ).toBe( true );

		root.render(
			createElement( ResponsiveStackStyle, {
				clientId: 'first',
				css: secondCss,
			} )
		);
		await flushEffects();
		expect( hasStyle( document, firstCss ) ).toBe( false );
		expect( hasStyle( document, secondCss ) ).toBe( true );

		root.unmount();
		expect( hasStyle( document, secondCss ) ).toBe( false );
		block.remove();
	} );

	it( 'uses the matching same-origin iframe document and ignores absent blocks', async () => {
		const inaccessible = document.createElement( 'iframe' );
		Object.defineProperty( inaccessible, 'contentDocument', {
			get: () => {
				throw new Error( 'cross-origin' );
			},
		} );
		document.body.appendChild( inaccessible );

		const frame = document.createElement( 'iframe' );
		document.body.appendChild( frame );
		const frameBlock = frame.contentDocument.createElement( 'div' );
		frameBlock.id = 'block-in-frame';
		frame.contentDocument.body.appendChild( frameBlock );
		const css = buildStackCss( template, '48em', 'ag-editor-frame' );

		root.render(
			createElement( ResponsiveStackStyle, {
				clientId: 'in-frame',
				css,
			} )
		);
		await flushEffects();
		expect( frame.contentDocument.head.textContent ).toContain( css );

		root.render(
			createElement( ResponsiveStackStyle, {
				clientId: 'missing',
				css,
			} )
		);
		await flushEffects();
		expect( frame.contentDocument.head.textContent ).not.toContain( css );
		inaccessible.remove();
		frame.remove();
	} );
} );
