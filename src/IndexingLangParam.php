<?php

namespace WPML\ElasticPress;

use ElasticPress\Elasticsearch;

class IndexingLangParam {
	/** @var Elasticsearch */
	private $elasticsearch;

	/** @var \SitePress */
	private $sitepress;

	/**
	 * @param  \SitePress  $sitepress
	 */
	public function __construct(
		Elasticsearch $elasticsearch,
		\SitePress $sitepress
	) {
		$this->elasticsearch = $elasticsearch;
		$this->sitepress     = $sitepress;
	}


	public function addHooks() {
		add_action( 'ep_pre_dashboard_index', [ $this, 'setsALLLangForDashboardIndexing' ], 10, 0 );
		add_action( 'ep_wp_cli_pre_index', [ $this, 'setLangForCLIIndexing' ], 10, 2 );
		add_filter( 'ep_post_mapping', [ $this, 'mapping' ] );
	}

	public function setsALLLangForDashboardIndexing() {
		$this->sitepress->switch_lang( 'all' );
	}

	public function setLangForCLIIndexing( array $args, array $assocArgs ) {
		$this->sitepress->switch_lang( $this->getLangFromArgs( $assocArgs ) );
	}

	/**
	 * @param  array  $assocArgs
	 *
	 * @return string
	 */
	private function getLangFromArgs( array $assocArgs ) {
		if ( isset( $assocArgs['post-lang'] ) ) {
			$lang = $assocArgs['post-lang'];
			if ( in_array( $lang, array_keys( $this->sitepress->get_active_languages() ) ) ) {
				return $lang;
			}
		}

		return 'all';
	}

	/**
	 * @param  array $mapping
	 *
	 * @return array
	 */
	public function mapping( $mapping ) {
		// Define an analyzer with no filters (no stopwords).
		$mapping['settings']['analysis']['analyzer']['post_lang_field'] = array(
			'type'      => 'custom',
			'tokenizer' => 'standard',
			'filter'    => [],
		);

		// Note the assignment by reference below.
		if ( version_compare( $this->elasticsearch->get_elasticsearch_version(), '7.0', '<' ) ) {
			$mapping_properties = &$mapping['mappings']['post']['properties'];
		} else {
			$mapping_properties = &$mapping['mappings']['properties'];
		}

		// Apply the analyzer.
		$mapping_properties['post_lang']['type']     = 'text';
		$mapping_properties['post_lang']['analyzer'] = 'post_lang_field';

		return $mapping;
	}

}
