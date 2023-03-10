<?php

namespace WPML\ElasticPress;

class LanguageSearch {
	/** @var \Sitepress */
	private $sitepress;

	/** @var \WPML_Translation_Element_Factory */
	private $element_factory;

	/** @var array */
	private $active_languages;

	/**
	 * @param  \WPML_Translation_Element_Factory  $element_factory
	 * @param  \SitePress  $sitepress
	 */
	public function __construct( \WPML_Translation_Element_Factory $element_factory, \SitePress $sitepress ) {
		$this->element_factory  = $element_factory;
		$this->sitepress        = $sitepress;
		$this->active_languages = array_keys( $this->sitepress->get_active_languages() );
	}

	/**
	 * @param  array  $post_args
	 * @param  int  $post_id
	 *
	 * @return array
	 */
	public function addLangInfo( $post_args, $post_id ) {
		if ( ! $this->sitepress->is_display_as_translated_post_type( $post_args["post_type"] ) ) {
			$post_args['post_lang'] = $this->getPostLang( $post_args, $post_id );
		} else {
			$active_languages = array_keys( $this->sitepress->get_active_languages() );
			$trid             = apply_filters( 'wpml_element_trid', null, $post_id, 'post_' . $post_args["post_type"] );
			$translations     = apply_filters( 'wpml_get_element_translations', null, $trid, 'post_' . $post_args["post_type"] );
			foreach ( $active_languages as $language ) {
				if ( array_key_exists( $language, $translations ) && $translations[ $language ]->element_id != $post_id ) {
					if ( ( $key = array_search( $language, $active_languages ) ) !== false ) {
						unset( $active_languages[ $key ] );
					}
				}
			}
			$post_args['post_lang'] = implode( ',', $active_languages );
		}

		return $post_args;
	}

	/**
	 * @param  array  $args
	 *
	 * @return array
	 */
	public function filterByLang( $args ) {
		$args['post_filter']['bool']['must'][] = [
			'term' => [
				'post_lang' => $this->getQueryLang(),
			],
		];

		return $args;
	}

	/**
	 * @param  array  $post_args
	 * @param  int  $post_id
	 *
	 * @return string
	 */
	private function getPostLang( $post_args, $post_id ) {
		$post_element = $this->element_factory->create( $post_id, 'post' );
		$lang         = $post_element->get_language_code();

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

	private function buildLangPattern() {
		$pattern = $this->buildPatternForLangAsDirectory();
		$pattern .= '|' . $this->buildPatternForLangAsParameter();
		$pattern .= '|' . $this->buildPatternForLangAsSubdomain();

		return '/' . $pattern . '/';
	}

	private function getQueryLang() {
		$lang = $this->sitepress->get_current_language();
		if ( isset( $_GET['lang'] ) ) {
			if ( in_array( $_GET['lang'], $this->active_languages, true ) ) {
				$lang = $_GET['lang'];
			}
		}

		return $lang;
	}

	/**
	 * @return string
	 */
	private function buildPatternForLangAsDirectory() {
		return sprintf( '\/(%s)\/', implode( '|', $this->active_languages ) );
	}

	/**
	 * @return string
	 */
	private function buildPatternForLangAsParameter() {
		$lang_in_params = [];
		foreach ( $this->active_languages as $lang ) {
			$lang_in_params[] = 'lang=(' . $lang . ')';
		}

		return '(' . implode( '|', $lang_in_params ) . ')';
	}

	/**
	 * @return string
	 */
	private function buildPatternForLangAsSubdomain() {
		return sprintf( '\/\/(%s)\.', implode( '|', $this->active_languages ) );
	}
}
