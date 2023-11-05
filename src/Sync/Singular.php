<?php

namespace WPML\ElasticPress\Sync;

use ElasticPress\Indexables;

use WPML\ElasticPress\Constants;
use WPML\ElasticPress\Manager\Indices;

use WPML\ElasticPress\Traits\CrudPropagation;

class Singular {

	use CrudPropagation;

	/**
	 * In ElasticPress 5.0.0, the \ElasticPress\SyncManager::sync_queue attribute mutated:
	 * - Before, it was an array indexed by post IDs with a TRUE value for those modified.
	 * - After, those same entries got spread in parent arrays per site ID.
	 *
	 * For example, [ 123 => true ] became [ 1 => [ 123 => true ] ],
	 * where 1 is the a block ID and 123 is a post ID for a post in the blog with ID equal 1.
	 *
	 * Nice for them to change some public data structure, right? Well, life!
	 */
	const SYNC_QUEUE_API_CHANGE_V1 = '5.0.0';

	/** @var Indexables */
	private $indexables;

	/** @var Indices */
	private $indicesManager;

	/** @var array */
	private $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/** @var string */
	private $elasticPressVersion;

	/**
	 * @param Indexables $indexables
	 * @param Indices    $indicesManager
	 * @param array      $activeLanguages
	 * @param string     $defaultLanguage
	 * @param string     $elasticPressVersion
	 */
	public function __construct(
		Indexables $indexables,
		Indices    $indicesManager,
		$activeLanguages,
		$defaultLanguage,
		$elasticPressVersion
	) {
		$this->indexables          = $indexables;
		$this->indicesManager      = $indicesManager;
		$this->activeLanguages     = $activeLanguages;
		$this->defaultLanguage     = $defaultLanguage;
		$this->elasticPressVersion = $elasticPressVersion;
	}

	public function addHooks() {
		add_filter( 'pre_ep_index_sync_queue', [ $this, 'manageSyncQueue' ], Constants::LATE_HOOK_PRIORITY, 3 );

		$beforeUnsyncHooks = [ 'wp_trash_post', 'delete_post' ];
		array_walk(
			$beforeUnsyncHooks,
			function( $hook ) {
				add_action( $hook, [ $this, 'startUnsync' ], Constants::EARLY_HOOK_PRIORITY );
				add_action( $hook, [ $this, 'clearIndexLanguage' ], Constants::LATE_HOOK_PRIORITY );
			}
		);
		$afterUnsyncHooks = [ 'trashed_post', 'deleted_post' ];
		array_walk(
			$afterUnsyncHooks,
			function( $hook ) {
				add_action( $hook, [ $this, 'completeUnsync' ], Constants::LATE_HOOK_PRIORITY );
			}
		);
	}

	/**
	 * @param  \ElasticPress\SyncManager $syncManager
	 *
	 * @return int[]
	 */
	private function getIdsInSyncQueue( $syncManager ) {
		$syncQueue = $syncManager->sync_queue;

		if ( version_compare( $this->elasticPressVersion, self::SYNC_QUEUE_API_CHANGE_V1, '<' ) ) {
			return array_keys( $syncQueue );
		}
		$currentBlogId           = get_current_blog_id();
		$syncQueueForCurrentBlog = $syncQueue[ $currentBlogId ] ?? [];
		return array_keys( $syncQueueForCurrentBlog ) ;
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

		$this->clearIds();

		// Store the affected post IDs
		//
		// Synced post is in the default language:
		// - If it is translatable AND displays as translated:
		//     - Sync it
		//     - Update it in other language indices using it
		// - If it is translatable but NOT displays as translated:
		//     - Sync it
		// - If it is not translatable:
		//     - Sync it in ALL languages
		//
		// Synced post is NOT in the default language:
		// - It is ALWAYS translatable, of course
		// - If it displays as translated:
		//     - Maybe remove the ID for the default language post from the current language index
		//         - Skip those which do not exist in the current language index (when updating an existing translation, for example)
		//     - Maybe sync the default language post in the default language index to sync language field values
		$this->propagateIds( $this->getIdsInSyncQueue( $syncManager ) );

		$this->manageIds( 'sync', 'main' );
		$this->manageIds( 'delete', 'related' );

		$this->indicesManager->clearCurrentIndexLanguage();

		$this->syncIds( $this->combineIds( 'related' ) );

		$this->clearIds();

		return true;
	}

	/**
	 * @param int $postId
	 */
	public function startUnsync( $postId ) {
		// Native hook to halt execution on autosave
		if ( apply_filters( 'ep_skip_autosave_sync', true, __FUNCTION__ ) ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				// Bypass saving if doing autosave
				// @codeCoverageIgnoreStart
				return;
				// @codeCoverageIgnoreEnd
			}
		}
		// This happens before delete_post:1 where WPML runs delete post actions
		$this->clearIds();
		// Store the affected post IDs for non-translatable and display-as-translated post types
		$this->propagateIds( [ $postId ] );
		// For display-as-translation mode, sync original language post in the language of the one deleted
		$this->manageIds( 'sync', 'related' );
		// Delete document in its current language (for translatable modes) and in all languages (for non translatable modes)
		// Note that part of this will try to happen again on the native delete_post:10 callback
		$this->manageIds( 'delete', 'main' );
		// Set the right index language so any pending indexable operation happens in the right index
		$this->setIndexLanguage( $postId );
	}

	/**
	 * @param int $postId
	 */
	public function completeUnsync( $postId ) {
		// Native hook to halt execution on autosave
		if ( apply_filters( 'ep_skip_autosave_sync', true, __FUNCTION__ ) ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				// Bypass saving if doing autosave
				// @codeCoverageIgnoreStart
				return;
				// @codeCoverageIgnoreEnd
			}
		}
		// For display-as-translated mode:
		// - make sure that the default language index includes proper values in language fields for the default language post
		// - get it propagated to any language that got its translation removed
		$this->indicesManager->clearCurrentIndexLanguage();
		$this->lateSyncIds( $this->combineIds( 'related' ) );
		$this->clearIds();

		return true;
	}

	/**
	 * @param int $postId
	 */
	public function setIndexLanguage( $postId ) {
		$object = get_post( $postId );
		if ( empty( $object ) ) {
			return;
		}

		$language = apply_filters( 'wpml_element_language_code', null, [
			'element_id'   => $postId,
			'element_type' => $object->post_type,
		]);
		if ( ! in_array( $language, $this->activeLanguages, true ) ) {
			$language = $this->defaultLanguage;
		}

		$this->indicesManager->setCurrentIndexLanguage( $language );
	}

	/**
	 * @param int $postId
	 */
	public function clearIndexLanguage( $postId ) {
		$this->indicesManager->clearCurrentIndexLanguage();
	}

	/**
	 * @param int $metaId
	 * @param int $postId
	 */
	public function setIndexLanguageByMeta( $metaId, $postId ) {
		$this->setIndexLanguage( $postId );
	}

	/**
	 * @param int $metaId
	 * @param int $postId
	 */
	public function clearIndexLanguageByMeta( $metaId, $postId ) {
		$this->clearIndexLanguage( $postId );
	}

}
