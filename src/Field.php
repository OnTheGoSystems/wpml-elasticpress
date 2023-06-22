<?php

namespace WPML\ElasticPress;

use ElasticPress\Elasticsearch;

abstract class Field {

	const FIELD_SLUG = 'post_lang';

	/** @var string */
	protected $elasticsearchVersion;

	/** @var array */
	protected $activeLanguages;

	/** @var string */
	protected $defaultLanguage;

	/** @var string */
	protected $currentLanguage;

	/**
	 * @param string $elasticsearchVersion
	 * @param array  $activeLanguages
	 * @param string $defaultLanguage
	 * @param string $currentLanguage
	 */
	public function __construct(
		$elasticsearchVersion,
		$activeLanguages,
		$defaultLanguage,
		$currentLanguage
	) {
		$this->elasticsearchVersion = $elasticsearchVersion;
		$this->activeLanguages      = $activeLanguages;
		$this->defaultLanguage      = $defaultLanguage;
		$this->currentLanguage      = $currentLanguage;
	}

	public function addHooks() {
		add_filter( 'ep_post_sync_args_post_prepare_meta', [ $this, 'addLanguageInfo' ], 10, 2 );
		add_filter( 'ep_post_mapping', [ $this, 'mapping' ] );
	}

	/**
	 * @param  array $postArgs
	 * @param  int   $postId
	 *
	 * @return array
	 */
	abstract public function addLanguageInfo( $postArgs, $postId );

	/**
	 * @param  array $mapping
	 *
	 * @return array
	 */
	public function mapping( $mapping ) {
		// Define an analyzer with no filters (no stopwords).
		$mapping['settings']['analysis']['analyzer']['post_lang_field'] = array(
			'type'      => 'custom',
			'tokenizer' => 'post_lang_tokenizer',
			'filter'    => [],
		);
		$mapping['settings']['analysis']['tokenizer']['post_lang_tokenizer'] = array(
			'type'    => 'pattern',
			'pattern' => ',',
		);

		// Note the assignment by reference below.
		if ( version_compare( $this->elasticsearchVersion, '7.0', '<' ) ) {
			$mappingProperties = &$mapping['mappings']['post']['properties'];
		} else {
			$mappingProperties = &$mapping['mappings']['properties'];
		}

		// Apply the analyzer.
		$mappingProperties[ static::FIELD_SLUG ]['type']     = 'text';
		$mappingProperties[ static::FIELD_SLUG ]['analyzer'] = 'post_lang_field';

		return $mapping;
	}

	/**
	 * @param  array  $postArgs
	 * @param  int    $postId
	 *
	 * @return string
	 */
	protected function getPostLanguage( $postArgs, $postId ) {
		$language = apply_filters( 'wpml_element_language_code', null, [
			'element_id'   => $postId,
			'element_type' => $postArgs['post_type'],
		]);

		if ( ! in_array( $language, $this->activeLanguages, true ) ) {
			$language = $this->defaultLanguage;

			$pattern = $this->buildLanguagePattern();
			if (
				isset( $postArgs['guid'] ) &&
				! empty( $postArgs['guid'] ) &&
				preg_match( $pattern, $postArgs['guid'], $match )
			) {
				$language = end( $match );
			}
		}

		return $language;
	}

	/**
	 * @return string
	 */
	protected function buildLanguagePattern() {
		$pattern = $this->buildPatternForLanguageAsDirectory();
		$pattern .= '|' . $this->buildPatternForLanguageAsParameter();
		$pattern .= '|' . $this->buildPatternForLanguageAsSubdomain();

		return '/' . $pattern . '/';
	}

	/**
	 * @return string
	 */
	protected function buildPatternForLanguageAsDirectory() {
		return sprintf( '\/(%s)\/', implode( '|', $this->activeLanguages ) );
	}

	/**
	 * @return string
	 */
	protected function buildPatternForLanguageAsParameter() {
		$languageInParams = [];
		foreach ( $this->activeLanguages as $language ) {
			$languageInParams[] = 'lang=(' . $language . ')';
		}

		return '(' . implode( '|', $languageInParams ) . ')';
	}

	/**
	 * @return string
	 */
	protected function buildPatternForLanguageAsSubdomain() {
		return sprintf( '\/\/(%s)\.', implode( '|', $this->activeLanguages ) );
	}

}
