<?php

namespace WPML\ElasticPress\Manager;

use ElasticPress\Elasticsearch;
use ElasticPress\Indexables;
use ElasticPress\Indexable;
use ElasticPress\Feature\Documents\Documents;
use WPML\ElasticPress\Traits\TranslateLanguages;

class Indices {

	use TranslateLanguages;

	const STOPWORD_FILTER_SLUG        = 'ep_stop';
	// Before ElasticPress 4.7.0
	const LEGACY_STOPWORD_FILTER_SLUG = 'stop';

	/** @var Elasticsearch */
	private $elasticsearch;

	/** @var Indexables */
	private $indexables;

	/** @var Documents */
	private $documentsFeature;

	/** @var Pipelines */
	private $pipelinesManager;

	/** @var array */
	private $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/** @var string */
	private $currentIndexLanguage = '';

	/** @var string|null */
	private $stopwordFilterSlug = null;

	/** @var string[]|null */
	private static $clusterIndices = null;

	/**
	 * @param Elasticsearch $elasticsearch
	 * @param Indexables    $indexables
	 * @param Documents     $documentsFeature
	 * @param Pipelines     $pipelinesManager
	 * @param array         $activeLanguages
	 * @param string        $defaultLanguage
	 */
	public function __construct(
		Elasticsearch $elasticsearch,
		Indexables    $indexables,
		Documents     $documentsFeature,
		Pipelines     $pipelinesManager,
		$activeLanguages,
		$defaultLanguage
	) {
		$this->elasticsearch    = $elasticsearch;
		$this->indexables       = $indexables;
		$this->documentsFeature = $documentsFeature;
		$this->pipelinesManager = $pipelinesManager;
		$this->activeLanguages  = $activeLanguages;
		$this->defaultLanguage  = $defaultLanguage;
	}

	public function addHooks() {
		add_filter( 'ep_index_name', [ $this, 'filterIndexName' ], 10 );
		add_filter( 'ep_global_alias', [ $this, 'filterIndexName' ], 10 );

		add_action( 'wp_initialize_site', [ $this, 'createBlogIndices' ], \WPML\ElasticPress\Constants::LATE_HOOK_PRIORITY );
		add_action( 'delete_blog', array( $this, 'deleteBlogIndices' ), \WPML\ElasticPress\Constants::LATE_HOOK_PRIORITY );
		add_action( 'make_delete_blog', array( $this, 'deleteBlogIndices' ), \WPML\ElasticPress\Constants::LATE_HOOK_PRIORITY );
		add_action( 'make_spam_blog', array( $this, 'deleteBlogIndices' ), \WPML\ElasticPress\Constants::LATE_HOOK_PRIORITY );
		add_action( 'archive_blog', array( $this, 'deleteBlogIndices' ), \WPML\ElasticPress\Constants::LATE_HOOK_PRIORITY );
		add_action( 'deactivate_blog', array( $this, 'deleteBlogIndices' ), \WPML\ElasticPress\Constants::LATE_HOOK_PRIORITY );
	}

	/**
	 * @param string  $indexName
	 *
	 * @return string
	 */
	public function filterIndexName( $indexName ) {
		if ( $this->currentIndexLanguage === $this->defaultLanguage ) {
			return $indexName;
		}
		if ( empty( $this->currentIndexLanguage ) ) {
			return $indexName;
		}
		return $indexName . '-' . $this->currentIndexLanguage;
	}

	/**
	 * @param string $language
	 */
	public function setCurrentIndexLanguage( $language ) {
		$this->currentIndexLanguage = $language;
	}

	public function clearCurrentIndexLanguage() {
		$this->currentIndexLanguage = '';
	}

	/**
	 * @return string[]
	 */
	private function getClusterIndicesCache() {
		if ( null === self::$clusterIndices ) {
			$clusterIndices = $this->elasticsearch->get_cluster_indices();
			self::$clusterIndices = wp_list_pluck( $clusterIndices, 'index' );
		}

		return self::$clusterIndices;
	}

	/**
	 * @param string $indexName
	 */
	private function saveIndexInClusterCache( $indexName ) {
		if ( null === self::$clusterIndices ) {
			self::$clusterIndices = [];
		}

		if ( in_array( $indexName, self::$clusterIndices, true ) ) {
			return;
		}

		self::$clusterIndices[] = $indexName;
	}

	private function clearClusterIndicesCache() {
		self::$clusterIndices = null;
	}

	/**
	 * @param  string $indexName
	 *
	 * @return bool
	 */
	public function indexExists( $indexName ) {
		$clusterIndices = $this->getClusterIndicesCache();

		return in_array( $indexName, $clusterIndices, true );
	}

	/**
	 * @param  array $filtersList
	 *
	 * @return string|null
	 */
	private function getStopwordFilterKey( $filtersList ) {
		if ( null !== $this->stopwordFilterSlug ) {
			return $this->stopwordFilterSlug;
		}
		if ( in_array( self::LEGACY_STOPWORD_FILTER_SLUG, $filtersList, true ) ) {
			$this->stopwordFilterSlug = self::LEGACY_STOPWORD_FILTER_SLUG;
		}
		if ( in_array( self::STOPWORD_FILTER_SLUG, $filtersList, true ) ) {
			$this->stopwordFilterSlug = self::STOPWORD_FILTER_SLUG;
		}
		return $this->stopwordFilterSlug;
	}

	/**
	 * @param Indexable $indexable
	 */
	public function generateIndexByIndexable( $indexable ) {
		$indexName = $indexable->get_index_name();
		// if document pipeline does not exist create it
		if ( $this->documentsFeature->is_active() && ! $this->pipelinesManager->documentPipelineExists( $indexName ) ) {
			$this->pipelinesManager->createDocumentPipeline();
		}
		if ( $this->indexExists( $indexName ) ) {
			return;
		}
		$mapping = $indexable->generate_mapping();

		// add attachments mapping if documents feature is active
		if ( $this->documentsFeature->is_active() ) {
			$mapping = $this->documentsFeature->attachments_mapping( $mapping );
		}
		if ( $this->defaultLanguage === $this->currentIndexLanguage ) {
			$this->elasticsearch->put_mapping( $indexName, $mapping );
			return;
		}
		$currentIndexLanguage = $this->currentIndexLanguage;
		$languages            = $this->generateAnalysisLanguages( $this->currentIndexLanguage );

		// Set analyzer and snowball languages
		$mapping['settings']['analysis']['analyzer']['default']['language'] = $languages['analyzer'];
		$mapping['settings']['analysis']['filter']['ewp_snowball']['language'] = $languages['snowball'];

		// Define language stopwords
		$stopwordFilterKey = $this->getStopwordFilterKey( $mapping['settings']['analysis']['analyzer']['default']['filter'] );
		if ( null !== $stopwordFilterKey ) {
			$mapping['settings']['analysis']['filter'][ $stopwordFilterKey ] = [
				'type'        => 'stop',
				'ignore_case' => true,
				'stopwords'   => '_' . $languages['analyzer'] . '_',
			];
		}

		// Define language stemmer
		if ( $this->languageHasStemmer( strtolower( $languages['analyzer'] ) ) ) {
			$mapping['settings']['analysis']['analyzer']['default']['filter'][] = 'stemmer_' . $this->currentIndexLanguage;
			$mapping['settings']['analysis']['filter']['stemmer_' . $this->currentIndexLanguage] = [
				'type'        => 'stemmer',
				'language'    => $languages['analyzer'],
			];
		}

		$this->elasticsearch->put_mapping( $indexName, $mapping );
		$this->saveIndexInClusterCache( $indexName );
	}

	/**
	 * @param Indexable[] $indexables
	 */
	public function generateIndexableIndexes( $indexables ) {
		foreach ( $indexables as $indexable ) {
			$this->generateIndexByIndexable( $indexable );
		}
	}

	public function clearAllIndices() {
		$this->elasticsearch->delete_all_indices();
		$this->clearClusterIndicesCache();
	}

	public function generateMissingIndices() {
		$indexables = $this->indexables->get_all();
		foreach ( $this->activeLanguages as $language ) {
			$this->setCurrentIndexLanguage( $language );
			$this->generateIndexableIndexes( $indexables );
			$this->clearCurrentIndexLanguage();
		}
	}

	/**
	 * @param \WP_Site $blog
	 */
	public function createBlogIndices( $blog ) {
		$indexable = $this->indexables->get( \WPML\ElasticPress\Constants::INDEXABLE_SLUG_POST );
		if ( false === $indexable ) {
			return;
		}

		foreach ( $this->activeLanguages as $language ) {
			$this->setCurrentIndexLanguage( $language );
			$indexable->sync_manager->action_create_blog_index( $blog );
			$this->clearCurrentIndexLanguage();
		}
	}

	/**
	 * @param int $blogId
	 */
	public function deleteBlogIndices( $blogId ) {
		$indexables = $this->indexables->get_all();
		foreach ( $indexables as $indexable ) {
			$this->deleteBlogLanguageIndices( $blogId, $indexable );
		}
	}

	/**
	 * @param int                     $blogId
	 * @param \ElasticPress\Indexable $indexable
	 */
	private function deleteBlogLanguageIndices( $blogId, $indexable ) {
		foreach ( $this->activeLanguages as $language ) {
			$this->setCurrentIndexLanguage( $language );
			$indexable->sync_manager->action_delete_blog_from_index( $blogId );
			$this->clearCurrentIndexLanguage();
		}
	}

}
