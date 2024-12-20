<?php

namespace WPML\ElasticPress;

class Plugin {

	// Let plugins load and initialize:
	// ElasticPrtess loads its features at plugins_loaded:10.
	const INIT_PRIORITY = 11;

	// Before ElasticPress 5.0.0, the Dashboard sync was run over AJAX.
	// After ElasticPress 5.0.0, the Dashboard sync is run over the REST API.
	const DASHBOARD_SYNC_API_CHANGE_V1 = '5.0.0';

	public static function init() {
		add_action( 'plugins_loaded', function () {
			if ( ! defined( 'EP_VERSION' ) || version_compare( EP_VERSION, '3.0.0', '<' ) ) {
				return;
			}

			if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
				return;
			}

			$elasticPressVersion  = EP_VERSION;

			$activeLanguagesData  = apply_filters( 'wpml_active_languages', [] );
			$activeLanguages      = array_keys( $activeLanguagesData );
			$defaultLanguage      = apply_filters( 'wpml_default_language', '' );
			$currentLanguage      = apply_filters( 'wpml_current_language', '' );

			$elasticsearch        = \ElasticPress\Elasticsearch::factory();
			$elasticsearchVersion = $elasticsearch->get_elasticsearch_version();
			$indexables           = \ElasticPress\Indexables::factory();
			$features             = \ElasticPress\Features::factory();
			$networkActivated     = defined( 'EP_IS_NETWORK' ) && EP_IS_NETWORK;

			$indicesManager       = new Manager\Indices(
				$elasticsearch,
				$indexables,
				$activeLanguages,
				$defaultLanguage
			);
			$indicesManager->addHooks();

			$syncDashboard = version_compare( $elasticPressVersion, self::DASHBOARD_SYNC_API_CHANGE_V1, '<' )
				? new Sync\DashboardAjax(
						$indexables,
						$indicesManager,
						new Manager\DashboardStatus(
							$activeLanguages
						),
						$activeLanguages,
						$defaultLanguage
					)
				: new Sync\DashboardRest(
						$indexables,
						$indicesManager,
						new Manager\DashboardStatus(
							$activeLanguages
						),
						$activeLanguages,
						$defaultLanguage
					);

			$feature = new Feature(
				new Field\Search(
					$elasticsearchVersion,
					$activeLanguages,
					$defaultLanguage,
					$currentLanguage
				),
				new Field\Sync(
					$elasticsearchVersion,
					$activeLanguages,
					$defaultLanguage,
					$currentLanguage
				),
				$syncDashboard,
				new Sync\Singular(
					$indexables,
					$indicesManager,
					$activeLanguages,
					$defaultLanguage,
					$elasticPressVersion
				),
				new Sync\CLI(
					$indexables,
					$indicesManager,
					$activeLanguages,
					$defaultLanguage
				),
				new FeatureSupport\Search(
					$features,
					$indicesManager,
					$currentLanguage
				),
				new FeatureSupport\RelatedPosts(
					$features,
					$indicesManager,
					$currentLanguage
				),
				new FeatureSupport\Autosuggest(
					$features,
					$indicesManager,
					$currentLanguage
				),
				new Stats\Health(
					$indexables,
					$networkActivated,
					$indicesManager,
					$activeLanguages,
					$defaultLanguage
				),
				new Stats\Report(
					$indexables,
					new \ElasticPress\StatusReport\Indices(),
					$indicesManager,
					$activeLanguages,
					$defaultLanguage
				)
			);

			\ElasticPress\Features::factory()->register_feature( $feature );

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				\WP_CLI::add_command( 'wpml_elasticpress', Command::class );
			}
		}, self::INIT_PRIORITY );
	}
}
