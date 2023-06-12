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
		add_filter( 'ep_dashboard_index_args', [ $this, 'setDashboardIndexArgs' ] );
		add_action( 'ep_wp_cli_pre_index', [ $this, 'setLangForCLIIndexing' ], 10, 2 );
		add_filter( 'ep_cli_index_args', [ $this, 'setCliIndexArgs' ] );
		add_filter( 'ep_post_mapping', [ $this, 'mapping' ] );
	}

	/**
	 * Dashboard index includes all posts
	 *
	 * @param  array $args
	 *
	 * @return array
	 */
	public function setDashboardIndexArgs( $args ) {
		$args['suppress_wpml_where_and_join_filter'] = true;
		return $args;
	}

	/**
	 * Set lang for CLI index based on the --post-lang flag
	 *
	 * @param  array $args
	 * @param  array $assocArgs
	 *
	 * @return array
	 */
	public function setLangForCLIIndexing( array $args, array $assocArgs ) {
		$this->sitepress->switch_lang( $this->getLangFromArgs( $assocArgs ) );
	}

	/**
	 * CLI index might include all posts
	 *
	 * @param  array $args
	 *
	 * @return array
	 */
	public function setCliIndexArgs( $args ) {
		if ( 'all' === apply_filters( 'wpml_current_language', null ) ) {
			$args['suppress_wpml_where_and_join_filter'] = true;
		}
		return $args;
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
		$mapping_properties['post_lang']['fields'] = array(
			'keyword' => array(
				'type' => 'keyword',
				'ignore_above' => '256',
			)
		);

		return $mapping;
	}

}
