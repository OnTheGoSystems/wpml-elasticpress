<?php

namespace WPML\ElasticPress\FeatureSupport;

use ElasticPress\Features;

use WPML\ElasticPress\Constants;
use WPML\ElasticPress\FeatureSupport;

class Search extends FeatureSupport {

	const FEATURE_SLUG = 'search';

	public function addHooks() {
		if ( ! $this->isFeatureActive() ) {
			return;
		}

		add_filter( 'ep_wp_query_cached_posts', [ $this, 'useIndexByLanguage' ], Constants::LATE_HOOK_PRIORITY );
		add_action( 'ep_wp_query_search', [ $this, 'restoreIndexLanguage' ], Constants::LATE_HOOK_PRIORITY );
	}

	/**
	 * @param array  $newPosts Array of posts
	 *
	 * @return array
	 */
	public function useIndexByLanguage( $newPosts ) {
		$this->indicesManager->setCurrentIndexLanguage( $this->currentLanguage );
		return $newPosts;
	}

	public function restoreIndexLanguage() {
		$this->indicesManager->clearCurrentIndexLanguage();
	}

}
