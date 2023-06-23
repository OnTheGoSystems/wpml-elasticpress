<?php

namespace WPML\ElasticPress\Sync;

use ElasticPress\Indexables;

use WPML\ElasticPress\Manager\Indices;

use WPML\ElasticPress\Traits\ManageIndexables;

class CLI {

	use ManageIndexables;

	/** @var Indexables */
	private $indexables;

	/** @var Indices */
	private $indicesManager;

	/** @var array */
	private $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/**
	 * @param Indexables $indexables
	 * @param Indices    $indicesManager
	 * @param array      $activeLanguages
	 * @param string     $defaultLanguage
	 */
	public function __construct(
		Indexables   $indexables,
		Indices      $indicesManager,
		$activeLanguages,
		$defaultLanguage
	) {
		$this->indexables            = $indexables;
		$this->indicesManager        = $indicesManager;
		$this->activeLanguages       = $activeLanguages;
		$this->defaultLanguage       = $defaultLanguage;
	}

	public function addHooks() {
		add_action( 'wpml_ep_regenerate_indices', [ $this, 'regenerateIndices' ] );
		add_action( 'wpml_ep_check_indices', [ $this, 'checkIndices' ] );
		add_action( 'ep_wp_cli_pre_index', [ $this, 'setUp' ], 10, 2 );
		add_action( 'ep_wp_cli_after_index', [ $this, 'tearDown' ], 10, 2 );
	}

	public function regenerateIndices() {
		$this->indicesManager->clearAllIndices();
		$this->indicesManager->generateMissingIndices();
	}

	public function checkIndices() {
		$this->indicesManager->generateMissingIndices();
	}

	/**
	 * @param array $args
	 * @param array $assocArgs
	 */
	public function setUp( $args, $assocArgs ) {
		$this->setCurrentLanguage( $assocArgs );
		$this->clearCache( $assocArgs );
		$this->deactivateIndexables();
	}

	/**
	 * Restore the original analyzer languages.
	 * Clear post type counters cache.
	 * Restore deactivated indexables.
	 *
	 * @param array $args
	 * @param array $assocArgs
	 */
	public function tearDown( $args, $assocArgs ) {
		$this->clearCurrentLanguage();
		$this->clearCache( $assocArgs );
		$this->reactivateIndexables();
	}

	/**
	 * @param array $assocArgs
	 */
	private function setCurrentLanguage( $assocArgs ) {
		$currentLanguage = $this->defaultLanguage;

		if ( isset( $assocArgs['post-lang'] ) ) {
			$language = $assocArgs['post-lang'];
			if ( in_array( $language, $this->activeLanguages, true ) ) {
				$currentLanguage = $language;
			}
		}

		do_action( 'wpml_switch_language', $currentLanguage );
		$this->indicesManager->setCurrentIndexLanguage( $currentLanguage );
	}

	private function clearCurrentLanguage() {
		do_action( 'wpml_switch_language', null );
		$this->indicesManager->clearCurrentIndexLanguage();
	}

	/**
	 * @param array $assocArgs
	 */
	private function clearCache( $queryArgs ) {
		// Elasticpress caches item counters during sync to know synced and remaining counts
		// TODO We might want to generate our own during setUp instead of deleting it
		if ( ! isset( $queryArgs['post-type'] ) ) {
			return;
		}

		$postTypes = explode( ',', $queryArgs['post-type'] );
		$postTypes = array_map( 'trim', $postTypes );

		foreach ( $postTypes as $postType ) {
			$cacheKey = 'posts-' . $postType;
			wp_cache_delete( $cacheKey, 'counts' );
		}
	}

}
