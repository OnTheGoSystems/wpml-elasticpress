<?php

namespace WPML\ElasticPress\Traits;

use ElasticPress\Indexables;

trait ManageIndexables {

	use CompareLanguages;

	/** @var Indexables */
	private $indexables;

	/** @var array */
	private $adjustedIndexables = [];

	/**
	 * @return array
	 */
	private function deactivateIndexables() {
		if (
			$this->isCurrentDefaultLanguage()
			|| ! $this->indexables->is_active( \WPML\ElasticPress\Constants::INDEXABLE_SLUG_USER )
		) {
			return [];
		}

		$this->adjustedIndexables[] = \WPML\ElasticPress\Constants::INDEXABLE_SLUG_USER;
		$this->indexables->deactivate( \WPML\ElasticPress\Constants::INDEXABLE_SLUG_USER );
		return $this->adjustedIndexables;
	}

	/**
	 * @param array $forceIndexablesToReset
	 */
	private function reactivateIndexables( $forceIndexablesToReset = [] ) {
		$indexablesToReset = array_unique( array_merge(
			$forceIndexablesToReset,
			$this->adjustedIndexables
		) );
		foreach ( $indexablesToReset as $indexableSlug ) {
			$this->indexables->activate( $indexableSlug );
		}
		$this->adjustedIndexables = [];
	}

}
