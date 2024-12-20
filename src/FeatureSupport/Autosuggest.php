<?php

namespace WPML\ElasticPress\FeatureSupport;

use ElasticPress\Features;
use ElasticPress\Indexables;

use WPML\ElasticPress\Constants;
use WPML\ElasticPress\FeatureSupport;

class Autosuggest extends FeatureSupport {

	const FEATURE_SLUG = 'autosuggest';

	public function addHooks() {
		if ( ! $this->isFeatureActive() ) {
			return;
		}

		add_filter( 'ep_autosuggest_options', [ $this, 'useIndexByLanguage' ], Constants::LATE_HOOK_PRIORITY );
	}

	/**
	 * @param array  $options
	 *
	 * @return array
	 */
	public function useIndexByLanguage( $options ) {
		$endpointUrl = $options['endpointUrl'];
		$defaultIndexName = Indexables::factory()->get( 'post' )->get_index_name();
		$this->indicesManager->setCurrentIndexLanguage( $this->currentLanguage );
		$currentLanguageIndexName = Indexables::factory()->get( 'post' )->get_index_name();
		$this->indicesManager->clearCurrentIndexLanguage();
		$options['endpointUrl'] = str_replace( $defaultIndexName, $currentLanguageIndexName, $endpointUrl );
		return $options;
	}

}
