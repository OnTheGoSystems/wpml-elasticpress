<?php

namespace WPML\ElasticPress\Traits;

use WPML\ElasticPress\Traits\TranslationModes;

trait CrudPropagation {

	use TranslationModes;

	/** @var array */
	private $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/** @var array */
	private $mainIdsPerLanguage = [];

	/** @var array */
	private $relatedIdsPerLanguage = [];

	private function clearIds() {
		$this->mainIdsPerLanguage    = [];
		$this->relatedIdsPerLanguage = [];
	}

	/**
	 * @param array  $objectIds
	 */
	private function propagateIds( $objectIds ) {
		foreach ( $objectIds as $objectId ) {
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

			$this->mainIdsPerLanguage[ $language ]    = [];
			$this->relatedIdsPerLanguage[ $language ] = [];

			$this->registerObject( $object, $language );
		}
	}

	/**
	 * @param \WP_Post $object
	 * @param string   $language
	 */
	private function registerObject( $object, $language ) {
		// Items in non-default languages have their own language
		if ( $language !== $this->defaultLanguage ) {
			$this->registerIdInLanguage( $object->ID, $language );
			$this->syncDefaultLanguage( $object, $language );
			return;
		}

		// Non-translatable types should appear in all languages
		if ( $this->isNotTranslatable( $object->post_type ) ) {
			$this->registerIdInLanguages( $object->ID, $this->activeLanguages );
			return;
		}

		// Display-as-translated types in default languages should include all untranslated languages
		if ( $this->isDisplayAsTranslated( $object->post_type ) ) {
			$this->registerIdInLanguage( $object->ID, $language );
			$this->registerIdInLanguages(
				$object->ID,
				$this->getDisplayAsTranslatedLanguages( $object->ID, $object->post_type, $language )
			);
			return;
		}


		$this->registerIdInLanguage( $object->ID, $language );
	}

	/**
	 * @param int   $objectId
	 * @param array $languages
	 */
	private function registerIdInLanguages( $objectId, $languages ) {
		foreach ( $languages as $language ) {
			$this->registerIdInLanguage( $objectId, $language );
		}
	}

	/**
	 * @param int    $objectId
	 * @param string $language
	 */
	private function registerIdInLanguage( $objectId, $language ) {
		$this->mainIdsPerLanguage[ $language ][] = $objectId;
	}

	/**
	 * @param \WP_Post $object
	 * @param string   $language
	 */
	private function syncDefaultLanguage( $object, $language ) {
		if ( ! $this->isDisplayAsTranslated( $object->post_type ) ) {
			return;
		}
		$originalPostId = $this->getDisplayAsTranslatedDefaultPostId( $object->ID, $object->post_type, $language );
		if ( ! $originalPostId ) {
			return;
		}

		$this->relatedIdsPerLanguage[ $language ][] = $originalPostId;
	}

	/**
	 * @param string $action
	 * @param string $role
	 */
	private function manageIds( $action = 'sync', $role = 'main' ) {
		$deletedIds  = [];
		$affectedIds = $this->getIds( $role );
		array_walk( $affectedIds, function( $objectIds, $language, $args ) {
			$action     = $args['action'];
			$deletedIds = $args['deletedIds'];
			$this->indicesManager->setCurrentIndexLanguage( $language );
			if ( 'sync' === $action ) {
				$this->syncIds( $objectIds );
			} else {
				$deletedIds[ $language ] = $this->deleteIds( $objectIds );
			}
		}, [
			'action' => $action,
			'deletedIds' => &$deletedIds,
		] );
		if ( 'delete' === $action ) {
			$this->setIds( $deletedIds, $role );
		}
	}

	/**
	 * @param  string $role
	 *
	 * @return array
	 */
	private function getIds( $role = 'main' ) {
		if ( 'main' === $role ) {
			return $this->mainIdsPerLanguage;
		}
		return $this->relatedIdsPerLanguage;
	}

	/**
	 * @param array  $ids
	 * @param string $role
	 */
	private function setIds( $ids, $role = 'main' ) {
		if ( 'main' === $role ) {
			$this->mainIdsPerLanguage = $ids;
			return;
		}
		$this->relatedIdsPerLanguage = $ids;
	}

	/**
	 * @param array $ids
	 */
	private function syncIds( $ids ) {
		$postIndexable = $this->indexables->get( \WPML\ElasticPress\Constants::INDEXABLE_SLUG_POST );
		$this->indicesManager->generateIndexByIndexable( $postIndexable );
		$postIndexable->bulk_index_dynamically( $ids );
	}

	/**
	 * @param array $ids
	 */
	private function deleteIds( $ids ) {
		$postIndexable = $this->indexables->get( \WPML\ElasticPress\Constants::INDEXABLE_SLUG_POST );
		$indexName = $postIndexable->get_index_name();
		if ( ! $this->indicesManager->indexExists( $indexName ) ) {
			return [];
		}
		return array_values(
			array_filter( $ids, function( $id ) use ( $postIndexable ) {
				// Skip docs that do not exist in the current index
				if ( false === $postIndexable->get( $id ) ) {
					return false;
				}
				$postIndexable->delete( $id );
				return true;
			} )
		);
	}

	/**
	 * @param string $role
	 */
	private function combineIds( $role = 'main' ) {
		$idsPerLanguage = $this->getIds( $role );
		return array_unique( call_user_func_array( 'array_merge', array_values( $idsPerLanguage ) ) );
	}

	/**
	 * @param array $ids
	 */
	private function lateSyncIds( $ids ) {
		$syncManager = $this->indexables->get( \WPML\ElasticPress\Constants::INDEXABLE_SLUG_POST )->sync_manager;
		array_walk(
			$ids,
			function( $id, $index, $args ) {
				$syncManager = $args['syncManager'];
				$syncManager->action_sync_on_update( $id );
			},
			[
				'syncManager' => $syncManager,
			]
		);
	}
}
