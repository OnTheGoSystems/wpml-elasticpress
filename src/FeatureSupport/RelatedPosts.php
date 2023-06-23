<?php

namespace WPML\ElasticPress\FeatureSupport;

use ElasticPress\Features;

use WPML\ElasticPress\FeatureSupport;
use WPML\ElasticPress\Manager\Indices;

class RelatedPosts extends FeatureSupport {

	const FEATURE_SLUG = 'related_posts';

	public function addHooks() {
		if ( ! $this->isFeatureActive() ) {
			return;
		}

		add_filter( 'ep_find_related_args', [ $this, 'useIndexByLanguage' ], 999, 1 );
	}

	/**
	 * @param array  $args Arguments for the query on related posts
	 *
	 * @return array
	 */
	public function useIndexByLanguage( $args ) {
		$this->indicesManager->setCurrentIndexLanguage( $this->currentLanguage );
		return $args;
	}

}
