<?php


namespace WPML\ElasticPress;


class Plugin {
	public static function init() {
		add_action( 'plugins_loaded', function () {
			if ( defined( 'EP_VERSION' ) && defined( 'ICL_SITEPRESS_VERSION' ) ) {
				$active_languages_data = apply_filters( 'wpml_active_languages', [] );
				$active_languages      = array_keys( $active_languages_data );

				$feature = new Feature(
					new Field\Search(
						\ElasticPress\Elasticsearch::factory(),
						$active_languages
					),
					new Field\Sync(
						\ElasticPress\Elasticsearch::factory(),
						$active_languages
					),
					new Sync\Dashboard(),
					new Sync\CLI(
						$active_languages
					)
				);

				\ElasticPress\Features::factory()->register_feature( $feature );

				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					\WP_CLI::add_command( 'wpml_elasticpress', Command::class );
				}
			}
		} );
	}
}
