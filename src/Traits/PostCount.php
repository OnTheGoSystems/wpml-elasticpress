<?php

namespace WPML\ElasticPress\Traits;

trait PostCount {

	use CompareLanguages;

	/** @var \wpdb */
	private $wpdb;

	/** @var string */
	private $currentLanguage = '';

	/** @var array */
	private $translatableDocuments = [];

	/**
	 * Hijack the cached count of posts per post type
	 * so it counts only posts in the right language.
	 *
	 * @param array $assocArgs
	 */
	private function setCache( $queryArgs ) {
		if ( ! isset( $queryArgs['post-type'] ) ) {
			return;
		}

		$postTypes = explode( ',', $queryArgs['post-type'] );
		$postTypes = array_map( 'trim', $postTypes );

		$notTranslatableCondition = $this->nonTranslatableWhere();

		foreach ( $postTypes as $postType ) {
			$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$this->wpdb->posts}
				LEFT JOIN {$this->wpdb->prefix}icl_translations wpml_translations
				ON {$this->wpdb->posts}.ID = wpml_translations.element_id
				AND wpml_translations.element_type = CONCAT('post_', {$this->wpdb->posts}.post_type)
				WHERE {$this->wpdb->posts}.post_type = %s
				AND (
					wpml_translations.language_code = %s
					{$notTranslatableCondition}
				)
				GROUP BY {$this->wpdb->posts}.post_status";

			$results = (array) $this->wpdb->get_results( $this->wpdb->prepare( $query, $postType, $this->currentLanguage ), ARRAY_A );

			$counts  = array_fill_keys( get_post_stati(), 0 );
			foreach ( $results as $row ) {
				$counts[ $row['post_status'] ] = $row['num_posts'];
			}
			$counts = (object) $counts;

			$cacheKey = 'posts-' . $postType;
			wp_cache_set( $cacheKey, $counts, 'counts' );
		}
	}

	/**
	 * @param array $assocArgs
	 */
	private function clearCache( $queryArgs ) {
		if ( ! isset( $queryArgs['post-type'] ) ) {
			return;
		}

		$postTypes = explode( ',', $queryArgs['post-type'] );
		$postTypes = array_map( 'trim', $postTypes );

		foreach ( $postTypes as $postType ) {
			$cacheKey = 'posts-' . $postType;
			wp_cache_delete( $cacheKey, 'counts' );
		}
	}

	/**
	 * @return string
	 */
	private function nonTranslatableWhere() {
		if ( ! $this->isCurrentDefaultLanguage() ) {
			return "";
		}

		$translatableDocuments = array_keys( apply_filters( 'wpml_translatable_documents', [] ) );

		$translatableDocumentsCount = count( $translatableDocuments );
		if ( $translatableDocumentsCount === 0 ) {
			return "";
		}

		$placeholders    = array_fill( 0, $translatableDocumentsCount, '%s' );
		$prepared_format = implode( ',', $placeholders );
		return $this->wpdb->prepare(
			" OR {$this->wpdb->posts}.post_type NOT IN ({$prepared_format})",
			array_keys( $translatableDocuments )
		);
	}

}
