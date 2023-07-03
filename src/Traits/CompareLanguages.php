<?php

namespace WPML\ElasticPress\Traits;

trait CompareLanguages {

	/** @var string */
	private $currentLanguage = '';

	/** @var string */
	private $defaultLanguage;

	/**
	 * @return boolean
	 */
	private function isCurrentDefaultLanguage() {
		return $this->currentLanguage === $this->defaultLanguage;
	}
}
