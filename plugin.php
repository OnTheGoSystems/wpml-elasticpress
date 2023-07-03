<?php
/**
 * Plugin Name: WPML ElasticPress
 * Plugin URI:
 * Description: Add full WPML support for ElasticPress.
 * Author: OnTheGoSystems
 * Author URI: http://www.onthegosystems.com/
 * Version: 2.0.0
 * Plugin Slug: wpmlelasticpress
 *
 * @package wpml/bridge/elasticpress
 */

if ( defined( 'WPMLELASTICPRESS_VERSION' ) ) {
    return;
}

define( 'WPMLELASTICPRESS_VERSION', '2.0.0' );
define( 'WPMLELASTICPRESS_PLUGIN_PATH', dirname( __FILE__ ) );

require_once WPMLELASTICPRESS_PLUGIN_PATH . '/vendor/autoload.php';

\WPML\ElasticPress\Plugin::init();
