<?php

namespace WPML\ElasticPress\Stats;

use ElasticPress\Indexables;
use ElasticPress\Utils as Utils;

use WPML\ElasticPress\Manager\Indices;

class Health {

	/** @var Indexables */
	private $indexables;

	/** @var bool */
	private $networkActivated;

	/** @var Indices */
	private $indicesManager;

	/** @var array */
	private $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/**
	 * @param Indexables $indexables
	 * @param bool       $networkActivated
	 * @param Indices    $indicesManager
	 * @param array      $activeLanguages
	 * @param string     $defaultLanguage
	 */
	public function __construct(
		Indexables      $indexables,
		$networkActivated,
		Indices         $indicesManager,
		$activeLanguages,
		$defaultLanguage
	) {
		$this->indexables       = $indexables;
		$this->networkActivated = $networkActivated;
		$this->indicesManager   = $indicesManager;
		$this->activeLanguages  = $activeLanguages;
		$this->defaultLanguage  = $defaultLanguage;
	}

	public function addHooks() {
		add_filter( 'ep_index_health_stats_indices', [ $this, 'includeIndicesInHealthStats' ], 10, 2);
	}

	/**
	 * @param array  $filteredIndices
	 * @param array  $indices
	 *
	 * @return array
	 */
	public function includeIndicesInHealthStats( $filteredIndices, $indices ) {
		$siteId          = get_current_blog_id();
		$indexable_sites = [];

		if ( $this->networkActivated ) {
			$indexable_sites = Utils\get_sites();
		};

		$extraIndices = [];
		foreach ( $this->activeLanguages as $language ) {
			if ( $language === $this->defaultLanguage ) {
				continue;
			}
			$this->indicesManager->setCurrentIndexLanguage( $language );
			$extraIndices = array_merge(
				$extraIndices,
				$this->getIndicesForLanguageAndSite( $siteId )
			);
			foreach ( $indexable_sites as $indexableSiteId ) {
				$extraIndices = array_merge(
					$extraIndices,
					$this->getIndicesForLanguageAndSite( $indexableSiteId )
				);
			}
			$this->indicesManager->clearCurrentIndexLanguage();
		}
		$extraIndices = array_values( array_unique( $extraIndices ) );
		foreach ( $indices as $index ) {
			if ( in_array( $index['index'], $extraIndices ) ) {
				$filteredIndices[] = $index;
			}
		}
		return $filteredIndices;
	}

	/**
	 * @param int    $siteId
	 *
	 * @return array
	 */
	private function getIndicesForLanguageAndSite( $siteId ) {
		$indices = [];
		$indexables = $this->indexables->get_all( false );
		foreach ( $indexables as $indexable ) {
			$indices[] = $indexable->get_index_name( $siteId );
		}
		return $indices;
	}
}
