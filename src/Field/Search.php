<?php

namespace WPML\ElasticPress\Field;

use WPML\ElasticPress\Field as Field;

use WPML\ElasticPress\Traits\TranslationModes;

class Search extends Field {

	use TranslationModes;

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
		$postLanguage = $this->getPostLanguage( $postArgs, $postId );

		// Items in non-default languages have their own language
		if ( $postLanguage !== $this->defaultLanguage ) {
			$postArgs[ static::FIELD_SLUG ] = $postLanguage;
			return $postArgs;
		}

		// Non-translatable types should appear in all languages
		if ( $this->isNotTranslatable( $postArgs['post_type'] ) ) {
			$postArgs[ static::FIELD_SLUG ] = implode( ',', $this->activeLanguages );
			return $postArgs;
		}

		// Display-as-translated types in default languages should include all untranslated languages
		if ( $this->isDisplayAsTranslated( $postArgs['post_type'] ) ) {
			$displayAsTranslatedLanguages = $this->getDisplayAsTranslatedLanguages( $postId, $postArgs['post_type'], $postLanguage );
			$postArgs[ static::FIELD_SLUG ] = implode( ',', $displayAsTranslatedLanguages );
			return $postArgs;
		}

		$postArgs[ static::FIELD_SLUG ] = $postLanguage;
		return $postArgs;
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
			&& $this->isViewerVisibleLanguage( $_GET['lang'] )
		) {
			$lang = $_GET['lang'];
		}

		return $lang;
	}

	/**
	 * Whether the current viewer may select this language for searching.
	 *
	 * `$this->activeLanguages` is resolved at plugins_loaded, before `init`, when
	 * WPML's show_hidden() is still true for every request, so it contains hidden
	 * languages regardless of the viewer. That set is the right one for indexing.
	 * For a requester-selected search language, resolve the viewer-sensitive
	 * active-language set at query time instead: hidden languages are only
	 * present when Core's show_hidden() policy allows this viewer to see them.
	 *
	 * @param string $lang
	 *
	 * @return bool
	 */
	private function isViewerVisibleLanguage( $lang ) {
		$viewerLanguages = array_keys( (array) apply_filters( 'wpml_active_languages', [] ) );

		return in_array( $lang, $viewerLanguages, true );
	}

}
