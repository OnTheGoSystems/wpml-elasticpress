<?php

namespace WPML\ElasticPress\Field;

use WPML\ElasticPress\Field as Field;

class Search extends Field {

	/** @var string */
	protected $fieldSlug = 'post_unique_lang';

	public function addHooks() {
		parent::addHooks();
		add_filter( 'ep_post_formatted_args', [ $this, 'filterByLang' ], 10, 1 );
	}

	/**
	 * @return string;
	 */
	protected function getFieldSlug() {
		return $this->fieldSlug;
	}

	/**
	 * @param  array  $post_args
	 * @param  int  $post_id
	 *
	 * @return array
	 */
	public function addLangInfo( $post_args, $post_id ) {
		if ( apply_filters( 'wpml_is_display_as_translated_post_type', false, $post_args['post_type'] ) ) {
			$post_args['post_lang'] = $this->getPostLangAsTranslated( $post_args, $post_id );
		} else {
			$post_args['post_lang'] = $this->getPostLang( $post_args, $post_id );
		}

		return $post_args;
	}

	/**
	 * @param  array  $post_args
	 * @param  int  $post_id
	 *
	 * @return string
	 */
	private function getPostLangAsTranslated( $post_args, $post_id ) {
		$active_languages = $this->active_languages;
		$element_type     = apply_filters( 'wpml_element_type', $post_args['post_type'] );
		$trid             = apply_filters( 'wpml_element_trid', null, $post_id, $element_type );
		$translations     = apply_filters( 'wpml_get_element_translations', null, $trid, $element_type );
		foreach ( $active_languages as $key => $language ) {
			if ( array_key_exists( $language, $translations ) && $translations[ $language ]->element_id != $post_id ) {
				unset( $active_languages[ $key ] );
			}
		}

		if ( empty( $active_languages ) ) {
			return $this->getPostLang( $post_args, $post_id );
		}

		return implode( ',', $active_languages );
	}

	/**
	 * @param  array $args
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
	 * @return string|null
	 */
	private function getQueryLang() {
		$lang = apply_filters( 'wpml_current_language', null );
		if ( isset( $_GET['lang'] ) ) {
			if ( in_array( $_GET['lang'], $this->active_languages, true ) ) {
				$lang = $_GET['lang'];
			}
		}

		return $lang;
	}

}
