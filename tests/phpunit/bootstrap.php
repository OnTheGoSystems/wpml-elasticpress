<?php
// This is global bootstrap for autoloading
//define( 'MY_NEW_PLUGIN_CORE_TESTS_ROOT', __DIR__ );

define( 'WP_PLUGIN_DIR', realpath( dirname( __FILE__ ) . '/../../' ) );

define( 'MY_NEW_PLUGIN_TESTS_SITE_DIR', __DIR__ . '/site' );
define( 'MY_NEW_PLUGIN_TESTS_SITE_URL', 'http://domain.tld' );
if ( ! defined( 'TESTS_SITE_URL' ) ) {
	define( 'TESTS_SITE_URL', MY_NEW_PLUGIN_TESTS_SITE_URL );
}

define( 'MY_NEW_PLUGIN_TESTS_MAIN_FILE', __DIR__ . '/../../plugin.php' );
define( 'MY_NEW_PLUGIN_PATH', dirname( MY_NEW_PLUGIN_TESTS_MAIN_FILE ) );

/** WP Constants */
define( 'WP_CONTENT_URL', MY_NEW_PLUGIN_TESTS_SITE_URL . '/wp-content' );
define( 'WP_CONTENT_DIR', MY_NEW_PLUGIN_TESTS_SITE_DIR . '/wp-content' );
define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins' );

/** WPML-Core constants */
define( 'MY_NEW_PLUGIN_PLUGIN_PATH', dirname( MY_NEW_PLUGIN_TESTS_MAIN_FILE ) );
define( 'MY_NEW_PLUGIN_PLUGIN_FILE', basename( MY_NEW_PLUGIN_TESTS_MAIN_FILE ) );
define( 'MY_NEW_PLUGIN_PLUGIN_BASENAME', basename( MY_NEW_PLUGIN_PLUGIN_PATH ) . '/' . MY_NEW_PLUGIN_PLUGIN_FILE );
define( 'MY_NEW_PLUGIN_PLUGIN_FOLDER', basename( MY_NEW_PLUGIN_PLUGIN_PATH ) );
define( 'MY_NEW_PLUGIN_PLUGIN_VERSION', '3.6.3' );

require_once MY_NEW_PLUGIN_PATH . '/vendor/autoload.php';

require_once MY_NEW_PLUGIN_PATH . '/vendor/otgs/unit-tests-framework/phpunit/bootstrap.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', MY_NEW_PLUGIN_PATH . '/../../' );
}
