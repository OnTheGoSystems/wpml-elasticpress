<?php

namespace WPML\ElasticPress\Manager;

use ElasticPress\Elasticsearch;
use ElasticPress\Indexables;
use ElasticPress\Indexable;

use WPML\ElasticPress\Traits\TranslateLanguages;

class Indices {

	use TranslateLanguages;

	/** @var Elasticsearch */
	private $elasticsearch;

	/** @var Indexables */
	private $indexables;

	/** @var array */
	private $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/** @var string */
	private $currentIndexLanguage = '';

	/**
	 * @param Elasticsearch $elasticsearch
	 * @param Indexables    $indexables
	 * @param array         $activeLanguages
	 * @param string        $defaultLanguage
	 */
	public function __construct(
		Elasticsearch $elasticsearch,
		Indexables    $indexables,
		$activeLanguages,
		$defaultLanguage
	) {
		$this->elasticsearch   = $elasticsearch;
		$this->indexables      = $indexables;
		$this->activeLanguages = $activeLanguages;
		$this->defaultLanguage = $defaultLanguage;
	}

	public function addHooks() {
		add_filter( 'ep_index_name', [ $this, 'filterIndexName' ], 10, 1 );
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
	 * @param Indexable $indexable
	 */
	public function generateIndexByIndexable( $indexable ) {
		$indexName = $indexable->get_index_name();
		if ( $this->elasticsearch->index_exists( $indexName ) ) {
			return;
		}
		$mapping = $indexable->generate_mapping();
		if ( 'en' === $this->currentIndexLanguage ) {
			$this->elasticsearch->put_mapping( $indexName, $mapping );
			return;
		}
		$currentIndexLanguage = $this->currentIndexLanguage;
		$languages            = $this->generateAnalysisLanguages( $this->currentIndexLanguage );

		// Set analyzer and snowball languages
		$mapping['settings']['analysis']['analyzer']['default']['language'] = $languages['analyzer'];
		$mapping['settings']['analysis']['filter']['ewp_snowball']['language'] = $languages['snowball'];

		// Set language stopwords and stemmer filters
		$mapping['settings']['analysis']['analyzer']['default']['filter'] = array_map(
			function( $filter ) use ( $currentIndexLanguage ) {
				if ( 'stop' === $filter ) {
					return 'stop_' . $currentIndexLanguage;
				}
				return $filter;
			},
			$mapping['settings']['analysis']['analyzer']['default']['filter']
		);
		$mapping['settings']['analysis']['analyzer']['default']['filter'][] = 'stemmer_' . $this->currentIndexLanguage;

		// Define language stopwords and stemmer filters
		$mapping['settings']['analysis']['filter']['stop_' . $this->currentIndexLanguage] = [
			'type'        => 'stop',
			'ignore_case' => true,
			'stopwords'   => '_' . $languages['analyzer'] . '_',
		];
		$mapping['settings']['analysis']['filter']['stemmer_' . $this->currentIndexLanguage] = [
			'type'        => 'stemmer',
			'language'    => $languages['analyzer'],
		];

		$this->elasticsearch->put_mapping( $indexName, $mapping );
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
	}

	public function generateMissingIndices() {
		$indexables = $this->indexables->get_all();
		foreach ( $this->activeLanguages as $language ) {
			$this->setCurrentIndexLanguage( $language );
			$this->generateIndexableIndexes( $indexables );
			$this->clearCurrentIndexLanguage();
		}
	}

}
