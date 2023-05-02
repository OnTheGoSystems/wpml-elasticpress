<?php

namespace WPML\ElasticPress\Field;

use WPML\ElasticPress\Field as Field;

class Search extends Field {

	const FIELD_SLUG = 'post_lang';

	public function addHooks() {
		parent::addHooks();
		add_filter( 'ep_post_formatted_args', [ $this, 'filterByLanguage' ], 10, 1 );
	}

	/**
	 * @param  array  $postArgs
	 * @param  int    $postId
	 *
	 * @return array
	 */
	public function addLanguageInfo( $postArgs, $postId ) {
		if ( apply_filters( 'wpml_is_display_as_translated_post_type', false, $postArgs['post_type'] ) ) {
			$postArgs[ static::FIELD_SLUG ] = $this->getPostLanguageAsTranslated( $postArgs, $postId );
			return $postArgs;
		}

		$postArgs[ static::FIELD_SLUG ] = $this->getPostLanguage( $postArgs, $postId );
		return $postArgs;
	}

	/**
	 * @param  array  $postArgs
	 * @param  int    $postId
	 *
	 * @return string
	 */
	private function getPostLanguageAsTranslated( $postArgs, $postId ) {
		$activeLanguages = $this->activeLanguages;
		$elementType     = apply_filters( 'wpml_element_type', $postArgs['post_type'] );
		$trid            = apply_filters( 'wpml_element_trid', null, $postId, $elementType );
		$translations    = apply_filters( 'wpml_get_element_translations', null, $trid, $elementType );
		foreach ( $activeLanguages as $key => $language ) {
			if ( array_key_exists( $language, $translations ) && $translations[ $language ]->element_id != $postId ) {
				unset( $activeLanguages[ $key ] );
			}
		}

		if ( empty( $activeLanguages ) ) {
			return $this->getPostLanguage( $postArgs, $postId );
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
