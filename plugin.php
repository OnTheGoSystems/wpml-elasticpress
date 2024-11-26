<?php
/**
 * Plugin Name: WPML ElasticPress
 * Plugin URI:
 * Description: Add full WPML support for ElasticPress.
 * Author: OnTheGoSystems
 * Author URI: http://www.onthegosystems.com/
 * Version: 2.0.4
 * Plugin Slug: wpmlelasticpress
 *
 * @package wpml/bridge/elasticpress
 */

register_activation_hook( __FILE__, 'wpmlElasticPressDeleteVersionWithWrongSlug' );
if ( ! function_exists( 'wpmlElasticPressDeleteVersionWithWrongSlug' ) ) {
	function wpmlElasticPressDeleteVersionWithWrongSlug() {
		// See https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlbridge-276
		$plugins    = get_plugins();
		$pluginSlug = plugin_basename( __FILE__ );
		foreach ( $plugins as $pluginId => $pluginData ) {
			if (
				'WPML ElasticPress' === $pluginData['Name']
				&& $pluginId !== $pluginSlug
			) {
				deactivate_plugins( $pluginId );
				delete_plugins( [ $pluginId ] );
			}
		}
	}
}

if ( defined( 'WPMLELASTICPRESS_VERSION' ) ) {
	return;
}

define( 'WPMLELASTICPRESS_VERSION', '2.0.4' );
define( 'WPMLELASTICPRESS_PLUGIN_PATH', dirname( __FILE__ ) );

require_once WPMLELASTICPRESS_PLUGIN_PATH . '/vendor/autoload.php';

\WPML\ElasticPress\Plugin::init();
