<?php

namespace WPML\ElasticPress;

class Plugin {
	public static function init() {
		add_action( 'plugins_loaded', function () {
			if ( defined( 'EP_VERSION' ) && defined( 'ICL_SITEPRESS_VERSION' ) ) {
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
					new Sync\Dashboard(
						$indexables,
						$indicesManager,
						new Manager\DashboardStatus(
							$activeLanguages
						),
						$activeLanguages,
						$defaultLanguage,
					),
					new Sync\Singular(
						$indexables,
						$indicesManager,
						$activeLanguages,
						$defaultLanguage
					),
					new Sync\CLI(
						$indexables,
						$indicesManager,
						$activeLanguages,
						$defaultLanguage,
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
			}
		}, 11 );
	}
}
