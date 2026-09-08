<?php
/**
 * Focused regression checks for Group's responsive CSS helpers.
 *
 * Run with: php tests/responsive.php
 */

define( 'ABSPATH', __DIR__ . '/' );

function plugin_dir_path( $file ) {
	return dirname( $file ) . '/';
}

function plugin_dir_url( $file ) {
	return 'https://example.test/plugins/awesome-group/';
}

function add_action() {}
function add_filter() {}

require dirname( __DIR__ ) . '/awesome-group.php';

function awesome_group_assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		fwrite( STDERR, 'Expected: ' . var_export( $expected, true ) . PHP_EOL );
		fwrite( STDERR, 'Actual: ' . var_export( $actual, true ) . PHP_EOL );
		exit( 1 );
	}
}

function awesome_group_assert_contains( $needle, $haystack, $message ) {
	if ( false === strpos( $haystack, $needle ) ) {
		fwrite( STDERR, $message . PHP_EOL );
		fwrite( STDERR, 'Missing: ' . $needle . PHP_EOL );
		exit( 1 );
	}
}

function awesome_group_assert_not_contains( $needle, $haystack, $message ) {
	if ( false !== strpos( $haystack, $needle ) ) {
		fwrite( STDERR, $message . PHP_EOL );
		fwrite( STDERR, 'Unexpected: ' . $needle . PHP_EOL );
		exit( 1 );
	}
}

$valid = array(
	'600px' => '600px',
	'1024px' => '1024px',
	'48em' => '48em',
	'48rem' => '48rem',
	" \t\n600PX\r\0\x0B" => '600px',
	'0.' . str_repeat( '0', 59 ) . '1px' => '0.' . str_repeat( '0', 59 ) . '1px',
);
foreach ( $valid as $input => $expected ) {
	awesome_group_assert_same( $expected, awesome_group_sanitize_breakpoint( $input ), 'Accepted breakpoint was changed.' );
}

$invalid = array(
	array(),
	42,
	false,
	null,
	'',
	'0px',
	'-1px',
	'12vh',
	'1e3px',
	"\f600px",
	"\xC2\xA0600px",
	"600px\xC2\xA0",
	"\xE2\x80\xA8600px",
	"600px\xE2\x80\xA9",
	"\xEF\xBB\xBF600px",
	str_repeat( '9', 63 ) . 'px',
	'600px}</style><style>body{display:none}',
);
foreach ( $invalid as $input ) {
	awesome_group_assert_same( '768px', awesome_group_sanitize_breakpoint( $input ), 'Invalid breakpoint was accepted.' );
}

$exact_64 = '0.' . str_repeat( '0', 59 ) . '1px';
$exact_65 = '0.' . str_repeat( '0', 60 ) . '1px';
awesome_group_assert_same( 64, strlen( $exact_64 ), '64-character fixture is incorrect.' );
awesome_group_assert_same( 65, strlen( $exact_65 ), '65-character fixture is incorrect.' );
awesome_group_assert_same( $exact_64, awesome_group_sanitize_breakpoint( $exact_64 ), '64-character breakpoint should be accepted.' );
awesome_group_assert_same( '768px', awesome_group_sanitize_breakpoint( $exact_65 ), '65-character breakpoint should be rejected.' );

$first = awesome_group_build_stack_css( '600px', 'ag-first', 'column-reverse' );
$second = awesome_group_build_stack_css( '1024px', 'ag-second', 'column' );
$fallback = awesome_group_build_stack_css( '600px}</style><style>', 'ag-fallback', 'row' );
awesome_group_assert_contains( '@media screen and (max-width: 600px)', $first, 'Custom media query is missing.' );
awesome_group_assert_contains( '.ag-first.ag-stack-mobile.is-layout-grid', $first, 'Grid rule was not scoped to the first block.' );
awesome_group_assert_contains( '.ag-first.ag-stack-mobile.is-layout-flex > *', $first, 'Child sizing rule was not retained.' );
awesome_group_assert_contains( '--ag-stack-direction: column-reverse;', $first, 'Stack direction declaration is missing.' );
awesome_group_assert_not_contains( '.ag-second', $first, 'First block CSS can select a second block.' );
awesome_group_assert_not_contains( '.ag-first', $second, 'Second block CSS can select the first block.' );
awesome_group_assert_contains( '(max-width: 768px)', $fallback, 'CSS builder did not revalidate its breakpoint.' );
awesome_group_assert_contains( '--ag-stack-direction: column;', $fallback, 'CSS builder did not revalidate its direction.' );

echo 'responsive.php: PASS' . PHP_EOL;
