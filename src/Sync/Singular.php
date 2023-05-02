<?php

namespace WPML\ElasticPress\Sync;

use ElasticPress\Indexables;

use WPML\ElasticPress\Manager\Indices;

class Singular {

	/** @var Indexables */
	private $indexables;

	/** @var Indices */
	private $indicesManager;

	/** @var array */
	private $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/**
	 * @param Indices    $indicesManager
	 * @param Indexables $indexables
	 * @param array      $activeLanguages
	 * @param string     $defaultLanguage
	 */
	public function __construct(
		Indexables $indexables,
		Indices    $indicesManager,
		$activeLanguages,
		$defaultLanguage
	) {
		$this->indexables      = $indexables;
		$this->indicesManager  = $indicesManager;
		$this->activeLanguages = $activeLanguages;
		$this->defaultLanguage = $defaultLanguage;
	}

	public function addHooks() {
		add_filter( 'pre_ep_index_sync_queue', [ $this, 'manageSyncQueue' ], 999, 3 );
	}

	/**
	 * @param array  $objectIds
	 *
	 * @return array
	 */
	private function getIdsToIndexByLanguage( $objectIds ) {
		$objectIdsByLang = [];

		foreach( $objectIds as $objectId ) {
			$object = get_post( $objectId );
			if ( empty( $object ) ) {
				continue;
			}

			$language = apply_filters( 'wpml_element_language_code', null, [
				'element_id'   => $objectId,
				'element_type' => $object->post_type,
			]);
			if ( ! in_array( $language, $this->activeLanguages, true ) ) {
				$language = $this->defaultLanguage;
			}

			if ( ! array_key_exists( $language, $objectIdsByLang ) ) {
				$objectIdsByLang[ $language ] = [];
			}

			$objectIdsByLang[ $language ][] = $objectId;
		}

		return $objectIdsByLang;
	}

	/**
	 * @param  bool                      $halt
	 * @param  \ElasticPress\SyncManager $syncManager
	 * @param  string                    $indexableSlug
	 *
	 * @return bool
	 */
	public function manageSyncQueue( $halt, $syncManager, $indexableSlug ) {
		if ( \WPML\ElasticPress\Constants::INDEXABLE_SLUG_POST !== $indexableSlug ) {
			return $halt;
		}

		$idsToIndexByLanguage = $this->getIdsToIndexByLanguage( array_keys( $syncManager->sync_queue ) );
		$postIndexable        = $this->indexables->get( $indexableSlug );

		foreach( $idsToIndexByLanguage as $language => $idsToIndex ) {
			$this->indicesManager->setCurrentIndexLanguage( $language );
			$this->indicesManager->generateIndexByIndexable( $postIndexable );
			$postIndexable->bulk_index_dynamically( $idsToIndex );
		}

		$this->indicesManager->clearCurrentIndexLanguage();

		return true;
	}

}
