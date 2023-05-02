<?php

namespace WPML\ElasticPress\Frontend;

use WPML\ElasticPress\Manager\Indices;

class Search {

	/** @var Indices */
	private $indicesManager;

	/** @var string */
	private $defaultLanguage;

	/** @var string */
	private $currentLanguage = '';

	/**
	 * @param Indices $indicesManager
	 * @param string  $defaultLanguage
	 * @param string  $currentLanguage
	 */
	public function __construct(
		Indices $indicesManager,
		$currentLanguage
	) {
		$this->indicesManager  = $indicesManager;
		$this->currentLanguage = $currentLanguage;
	}

	public function addHooks() {
		add_filter( 'ep_wp_query_cached_posts', [ $this, 'useIndexByLanguage' ], 999, 1 );
		add_action( 'ep_wp_query_search', [ $this, 'restoreIndexLanguage' ], 999 );
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
