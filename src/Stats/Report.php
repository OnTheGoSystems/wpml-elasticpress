<?php

namespace WPML\ElasticPress\Stats;

use ElasticPress\Indexables;
use ElasticPress\StatusReport\Report as epReport;
use ElasticPress\StatusReport\Indices;

use WPML\ElasticPress\Manager\Indices as IndicesManager;

class Report extends epReport {

	/** @var Indexables */
	private $indexables;

	/** @var Indices */
	private $indices;

	/** @var IndicesManager */
	private $indicesManager;

	/** @var array */
	private $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/** @var array */
	private $groups;

	public function __construct(
		Indexables     $indexables,
		Indices        $indices,
		IndicesManager $indicesManager,
		$activeLanguages,
		$defaultLanguage
	) {
		$this->indexables      = $indexables;
		$this->indices         = $indices;
		$this->indicesManager  = $indicesManager;
		$this->activeLanguages = $activeLanguages;
		$this->defaultLanguage = $defaultLanguage;
	}

	public function addHooks() {
		add_filter( 'ep_status_report_reports', [ $this, 'includeIndicesInStatusReport' ] );
	}

	/**
	 * @param array  $reports
	 *
	 * @return array
	 */
	public function includeIndicesInStatusReport( $reports ) {
		$indices = $reports['indices'];
		$this->groups = $indices->get_groups();

		$isUsersFeatureActive = $this->indexables->is_active( \WPML\ElasticPress\Constants::INDEXABLE_SLUG_USER );
		if ( $isUsersFeatureActive ) {
			$this->indexables->deactivate( \WPML\ElasticPress\Constants::INDEXABLE_SLUG_USER );
		}

		foreach ( $this->activeLanguages as $language ) {
			if ( $language === $this->defaultLanguage ) {
				continue;
			}
			$this->indicesManager->setCurrentIndexLanguage( $language );
			$this->groups = array_merge(
				$this->groups,
				$this->indices->get_groups()
			);
			$this->indicesManager->clearCurrentIndexLanguage();
		}

		if ( $isUsersFeatureActive ) {
			$this->indexables->activate( \WPML\ElasticPress\Constants::INDEXABLE_SLUG_USER );
		}

		$reports['indices'] = $this;

		return $reports;
	}

	/**
	 * @return string
	 */
	public function get_title() : string {
		return $this->indices->get_title();
	}

	/**
	 * @return array
	 */
	public function get_groups() : array {
		return $this->groups;
	}

}
