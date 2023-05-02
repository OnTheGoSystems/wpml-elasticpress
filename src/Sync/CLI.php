<?php

namespace WPML\ElasticPress\Sync;

use ElasticPress\Indexables;

use WPML\ElasticPress\Manager\Indices;

use WPML\ElasticPress\Traits\ManageIndexables;
use WPML\ElasticPress\Traits\PostCount;
use WPML\ElasticPress\Traits\QueryFilters;

class CLI {

	use ManageIndexables;
	use PostCount;
	use QueryFilters;

	/** @var \wpdb */
	private $wpdb;

	/** @var Indexables */
	private $indexables;

	/** @var Indices */
	private $indicesManager;

	/** @var array */
	private $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/** @var string */
	private $currentLanguage = '';

	/**
	 * @param \wpdb      $wpdb
	 * @param Indexables $indexables
	 * @param Indices    $indicesManager
	 * @param array      $activeLanguages
	 * @param string     $defaultLanguage
	 */
	public function __construct(
		\wpdb        $wpdb,
		Indexables   $indexables,
		Indices      $indicesManager,
		$activeLanguages,
		$defaultLanguage
	) {
		$this->wpdb                  = $wpdb;
		$this->indexables            = $indexables;
		$this->indicesManager        = $indicesManager;
		$this->activeLanguages       = $activeLanguages;
		$this->defaultLanguage       = $defaultLanguage;
	}

	public function addHooks() {
		add_action( 'wpml_ep_regenerate_indices', [ $this, 'regenerateIndices' ] );
		add_action( 'wpml_ep_check_indices', [ $this, 'checkIndices' ] );
		add_action( 'ep_wp_cli_pre_index', [ $this, 'setUp' ], 10, 2 );
		add_filter( 'ep_cli_index_args', [ $this, 'setQueryArgs' ] );
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
		$this->setCache( $assocArgs );
		$this->setQueryFilters();
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
		$this->indicesManager->clearCurrentIndexLanguage();
		$this->clearCache( $assocArgs );
		$this->clearQueryFilters();
		$this->reactivateIndexables();
	}

	/**
	 * @param array $assocArgs
	 */
	private function setCurrentLanguage( $assocArgs ) {
		$this->currentLanguage = $this->defaultLanguage;

		if ( isset( $assocArgs['post-lang'] ) ) {
			$language = $assocArgs['post-lang'];
			if ( in_array( $language, $this->activeLanguages, true ) ) {
				$this->currentLanguage = $language;
			}
		}

		$this->indicesManager->setCurrentIndexLanguage( $this->currentLanguage );
	}

}
