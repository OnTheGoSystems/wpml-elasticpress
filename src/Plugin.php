<?php


namespace WPML\ElasticPress;


class Plugin {
	public static function init() {
		add_action( 'plugins_loaded', function () {
			if ( defined( 'EP_VERSION' ) && defined( 'ICL_SITEPRESS_VERSION' ) ) {
				global $sitepress;

				$feature = new Feature(
					new LanguageSearch(
						new \WPML_Translation_Element_Factory( $sitepress ),
						$sitepress
					),
					new IndexingLangParam( $sitepress )
				);

				\ElasticPress\Features::factory()->register_feature( $feature );

				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					\WP_CLI::add_command( 'wpml_elasticpress', Command::class );
				}
			}
		} );
	}
}