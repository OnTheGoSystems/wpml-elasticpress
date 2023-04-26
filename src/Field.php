<?php

namespace WPML\ElasticPress;

use ElasticPress\Elasticsearch;

abstract class Field {
	/** @var Elasticsearch */
	protected $elasticsearch;

	/** @var array */
	protected $active_languages;

	/**
	 * @param array $active_languages
	 */
	public function __construct(
		Elasticsearch $elasticsearch,
		$active_languages
	) {
		$this->elasticsearch    = $elasticsearch;
		$this->active_languages = $active_languages;
	}

	public function addHooks() {
		add_filter( 'ep_post_sync_args_post_prepare_meta', [ $this, 'addLangInfo' ], 10, 2 );
		add_filter( 'ep_post_mapping', [ $this, 'mapping' ] );
	}

	/**
	 * @return string;
	 */
	abstract protected function getFieldSlug();

	/**
	 * @param  array $post_args
	 * @param  int   $post_id
	 *
	 * @return array
	 */
	abstract public function addLangInfo( $post_args, $post_id );

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
		$fieldSlug                                    = $this->getFieldSlug();
		$mapping_properties[ $fieldSlug ]['type']     = 'text';
		$mapping_properties[ $fieldSlug ]['analyzer'] = 'post_lang_field';

		return $mapping;
	}

	/**
	 * @param  array  $post_args
	 * @param  int    $post_id
	 *
	 * @return string
	 */
	protected function getPostLang( $post_args, $post_id ) {
		$lang = apply_filters( 'wpml_element_language_code', null, [
			'element_id'   => $post_id,
			'element_type' => $post_args['post_type'],
		]);

		if ( ! in_array( $lang, $this->active_languages, true ) ) {
			$lang = 'en';

			$pattern = $this->buildLangPattern();
			if (
				isset( $post_args['guid'] ) &&
				! empty( $post_args['guid'] ) &&
				preg_match( $pattern, $post_args['guid'], $match )
			) {
				$lang = end( $match );
			}
		}

		return $lang;
	}

	/**
	 * @return string
	 */
	protected function buildLangPattern() {
		$pattern = $this->buildPatternForLangAsDirectory();
		$pattern .= '|' . $this->buildPatternForLangAsParameter();
		$pattern .= '|' . $this->buildPatternForLangAsSubdomain();

		return '/' . $pattern . '/';
	}

	/**
	 * @return string
	 */
	protected function buildPatternForLangAsDirectory() {
		return sprintf( '\/(%s)\/', implode( '|', $this->active_languages ) );
	}

	/**
	 * @return string
	 */
	protected function buildPatternForLangAsParameter() {
		$lang_in_params = [];
		foreach ( $this->active_languages as $lang ) {
			$lang_in_params[] = 'lang=(' . $lang . ')';
		}

		return '(' . implode( '|', $lang_in_params ) . ')';
	}

	/**
	 * @return string
	 */
	protected function buildPatternForLangAsSubdomain() {
		return sprintf( '\/\/(%s)\.', implode( '|', $this->active_languages ) );
	}

}
