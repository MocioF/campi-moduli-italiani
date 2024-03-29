<?php
/**
 * PHPUnit bootstrap file
 *
 * @package No_Unsafe_Inline
 */

$_tests_dir  = getenv( 'WP_TESTS_DIR' );
$_plugin_dir = dirname( dirname( __DIR__ ) );


if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// https://make.wordpress.org/core/2021/09/27/changes-to-the-wordpress-core-php-test-suite/
require_once $_plugin_dir . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin( $_plugin_dir ) {
	require dirname( dirname( __DIR__ ) ) . '/campi-moduli-italiani.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Downloads file and activates the plugin
require_once $_plugin_dir . '/tests/phpunit/Extensions/Boot.php';
