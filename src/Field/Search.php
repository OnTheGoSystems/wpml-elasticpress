<?php

namespace WPML\ElasticPress\Field;

use WPML\ElasticPress\Field as Field;

class Search extends Field {

	const FIELD_SLUG = 'post_lang';

	/** @var null|array */
	private $translatablePostTypes = null;

	public function addHooks() {
		parent::addHooks();
		add_filter( 'ep_post_formatted_args', [ $this, 'filterByLanguage' ], 10, 1 );
	}

	/**
	 * @return array
	 */
	private function getTranslatablePostTypes() {
		if ( null === $this->translatablePostTypes ) {
			$this->translatablePostTypes = array_keys( apply_filters( 'wpml_translatable_documents', [] ) );
		}
		return $this->translatablePostTypes;
	}

	/**
	 * @param  array  $postArgs
	 * @param  int    $postId
	 *
	 * @return array
	 */
	public function addLanguageInfo( $postArgs, $postId ) {
		$postLanguage = $this->getPostLanguage( $postArgs, $postId );

		// Items in non-default languages have their own language
		if ( $postLanguage !== $this->defaultLanguage ) {
			$postArgs[ static::FIELD_SLUG ] = $postLanguage;
			return $postArgs;
		}

		// Non-translatable types should appear in all languages
		if ( ! in_array( $postArgs['post_type'], $this->getTranslatablePostTypes, true ) ) {
			$postArgs[ static::FIELD_SLUG ] = implode( ',', $this->activeLanguages );
			return $postArgs;
		}

		// Display-as-translated types in default languages should include all untranslated languages
		if ( apply_filters( 'wpml_is_display_as_translated_post_type', false, $postArgs['post_type'] ) ) {
			$postArgs[ static::FIELD_SLUG ] = $this->getPostLanguageAsTranslated( $postArgs, $postId, $postLanguage );
			return $postArgs;
		}

		$postArgs[ static::FIELD_SLUG ] = $postLanguage;
		return $postArgs;
	}

	/**
	 * @param  array  $postArgs
	 * @param  int    $postId
	 *
	 * @return string
	 */
	private function getPostLanguageAsTranslated( $postArgs, $postId, $postLanguage ) {
		if ( $postLanguage !== $this->defaultLanguage ) {
			return $postLanguage;
		}

		$activeLanguages = $this->activeLanguages;
		$elementType     = apply_filters( 'wpml_element_type', $postArgs['post_type'] );
		$trid            = apply_filters( 'wpml_element_trid', null, $postId, $elementType );
		$translations    = apply_filters( 'wpml_get_element_translations', null, $trid, $elementType );
		foreach ( $activeLanguages as $key => $language ) {
			if (
				array_key_exists( $language, $translations )
				&& $translations[ $language ]->element_id != $postId
			) {
				unset( $activeLanguages[ $key ] );
			}
		}

		if ( empty( $activeLanguages ) ) {
			return $postLanguage;
		}

		return implode( ',', $activeLanguages );
	}

	/**
	 * @param  array $args
	 *
	 * @return array
	 */
	public function filterByLanguage( $args ) {
		$args['post_filter']['bool']['must'][] = [
			'term' => [
				'post_lang' => $this->getQueryLanguage(),
			],
		];

		return $args;
	}

	/**
	 * @return string|null
	 */
	private function getQueryLanguage() {
		$lang = $this->currentLanguage;

		if (
			isset( $_GET['lang'] )
			&& is_string( $_GET['lang'] )
			&& in_array( $_GET['lang'], $this->activeLanguages, true )
		) {
			$lang = $_GET['lang'];
		}

		return $lang;
	}

}
