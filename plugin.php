<?php
/**
 * Plugin Name: WPML ElasticPress
 * Plugin URI:
 * Description: Add full WPML support for ElasticPress.
 * Author: OnTheGoSystems
 * Author URI: http://www.onthegosystems.com/
 * Version: 1.0.0
 * Plugin Slug: wpmlelasticpress
 *
 * @package wpml/bridge/elasticpress
 */

if ( defined( 'WPMLELASTICPRESS_VERSION' ) ) {
    return;
}

if ( ! defined( 'EP_VERSION' ) || version_compare( EP_VERSION, '3.0.0', '<' ) ) {
	return;
}

if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
	return;
}

define( 'WPMLELASTICPRESS_VERSION', '1.0.0' );
define( 'WPMLELASTICPRESS_PLUGIN_PATH', dirname( __FILE__ ) );

require_once WPMLELASTICPRESS_PLUGIN_PATH . '/vendor/autoload.php';

\WPML\ElasticPress\Plugin::init();
