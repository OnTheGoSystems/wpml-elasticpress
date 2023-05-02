<?php

namespace WPML\ElasticPress\Traits;

use WPML\API\PostTypes;
use WPML\ElasticPress\Constants;

trait QueryFilters {

	use CompareLanguages;

	/** @var \wpdb */
	private $wpdb;

	/** @var string */
	private $currentLanguage = '';

	/**
	 * Flag the query with the current language so ElasticPress query cache keys are unique.
	 * Remove native WPML query filters on non-default languages.
	 * Enforce custom query filters by language on non-default languages.
	 *
	 * @param  array $args
	 *
	 * @return array
	 */
	public function setQueryArgs( $args ) {
		// Set unique query arg so cache keys are unique per language
		$args['wpml_elasticpress_lang'] = $this->currentLanguage;
		if ( $this->isCurrentDefaultLanguage() ) {
			return $args;
		}

		// Remove native WPML query filters on non-default languages
		// Enforce our custom query filters by language on non-default languages
		$args[ Constants::QUERY_ARG_SUPPRESS_WPML_FILTERS ]         = true;
		$args[ Constants::QUERY_ARG_SET_WPML_ELASTICPRESS_FILTERS ] = true;
		return $args;
	}

	/**
	 * @param  \WP_Query $query
	 *
	 * @return bool
	 */
	private function shouldApplyQueryFilters( $query ) {
		if (
			isset( $query->query[ Constants::QUERY_ARG_SET_WPML_ELASTICPRESS_FILTERS ] )
			&& $query->query[ Constants::QUERY_ARG_SET_WPML_ELASTICPRESS_FILTERS ]
		) {
			return true;
		}

		return false;
	}

	private function setQueryFilters() {
		if ( $this->isCurrentDefaultLanguage() ) {
			return;
		}

		add_filter( 'posts_join', [ $this, 'postsJoin' ], 10, 2 );
		add_filter( 'posts_where', [ $this, 'postsWhere' ], 10, 2 );
	}

	private function clearQueryFilters() {
		remove_filter( 'posts_join', [ $this, 'postsJoin' ], 10, 2 );
		remove_filter( 'posts_where', [ $this, 'postsWhere' ], 999, 2 );
	}

	/**
	 * @param  string    $join
	 * @param  \WP_Query $query
	 *
	 * @return string
	 */
	public function postsJoin( $join, $query ) {
		if ( ! $this->shouldApplyQueryFilters( $query ) ) {
			return $join;
		}

		$extraJoin = " JOIN {$this->wpdb->prefix}icl_translations wpml_translations
			ON {$this->wpdb->posts}.ID = wpml_translations.element_id
			AND wpml_translations.element_type = CONCAT('post_', {$this->wpdb->posts}.post_type) ";
		return $join . $extraJoin;
	}

	private function postsWhereAsTranslatable() {
		$displayAsTranslated = PostTypes::getDisplayAsTranslated();

		if ( empty( $displayAsTranslated ) ) {
			return '';
		}

		$displayAsTranslatedCount = count( $displayAsTranslated );
		$placeholders             = array_fill( 0, $displayAsTranslatedCount, '%s' );
		$preparedFormat           = implode( ',', $placeholders );
		$displayAsTranslatedTypes = $this->wpdb->prepare( $preparedFormat, $displayAsTranslated );

		return $this->wpdb->prepare(
			" OR (
				wpml_translations.language_code = %s
				AND {$this->wpdb->posts}.post_type IN ( {$displayAsTranslatedTypes} )
				AND ( SELECT COUNT(element_id)
					FROM {$this->wpdb->prefix}icl_translations
					WHERE trid = wpml_translations.trid
					AND language_code = %s
				) = 0
		 	)",
			[
				$this->defaultLanguage,
				$this->currentLanguage,
			]
		);
	}

	/**
	 * @param  string    $where
	 * @param  \WP_Query $query
	 *
	 * @return string
	 */
	public function postsWhere( $where, $query ) {
		if ( ! $this->shouldApplyQueryFilters( $query ) ) {
			return $where;
		}

		$extraWhere = $this->wpdb->prepare(
			" AND wpml_translations.language_code = %s" . $this->postsWhereAsTranslatable(),
			[
				$this->currentLanguage,
			]
		);

		return $where . $extraWhere;
	}

}
