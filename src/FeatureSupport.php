<?php

namespace WPML\ElasticPress;

use ElasticPress\Features;

use WPML\ElasticPress\Manager\Indices;

class FeatureSupport {

	const FEATURE_SLUG = '';

	/** @var Features */
	protected $features;

	/** @var Indices */
	protected $indicesManager;

	/** @var string */
	protected $currentLanguage = '';

	/**
	 * @param Features $features
	 * @param Indices  $indicesManager
	 * @param string   $currentLanguage
	 */
	public function __construct(
		Features $features,
		Indices  $indicesManager,
		$currentLanguage
	) {
		$this->features        = $features;
		$this->indicesManager  = $indicesManager;
		$this->currentLanguage = $currentLanguage;
	}

	/**
	 * @return bool
	 */
	protected function isFeatureActive() {
		$feature = $this->features->get_registered_feature( static::FEATURE_SLUG );

		if ( empty( $feature ) ) {
			return false;
		}

		/**
		 * Get whether the feature was already active, and the value of the
		 * setting that requires a reindex, if it exists.
		 */
		return $feature->is_active();
	}

}
