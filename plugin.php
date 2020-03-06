<?php
/*
Plugin Name: WPML ElasticPRess
Description: Provides compatibility between WPML and ElasticPress
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com
Version: 0.0.1
Plugin Slug: wpml-elasticpress
*/

define( 'MY_NEW_PLUGIN_PATH', __DIR__ );
define( 'MY_NEW_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

require_once MY_NEW_PLUGIN_PATH . '/vendor/autoload.php';

