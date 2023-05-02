<?php

namespace WPML\ElasticPress;

class Feature extends \ElasticPress\Feature {

	/** @var Field\Search */
	private $fieldSearch;

	/** @var Field\Sync */
	private $fieldSync;

	/** @var Sync\Dashboard */
	private $syncDashboard;

	/** @var Sync\Singular */
	private $syncSingular;

	/** @var Sync\CLI */
	private $syncCli;

	/** @var Frontend\Search */
	private $frontendSearch;

	/** @var Stats\Health */
	private $statsHealth;

	/** @var Stats\Report */
	private $statsReport;

	/**
	 * @param Field\Search    $fieldSearch
	 * @param Field\Sync      $fieldSync
	 * @param Sync\Dashboard  $syncDashboard
	 * @param Sync\singular   $syncSingular
	 * @param Sync\CLI        $syncCli
	 * @param Frontend\Search $frontendSearch
	 * @param Stats\Health    $statsHealth
	 * @param Stats\Report    $statsHealth
	 */
	public function __construct(
		Field\Search    $fieldSearch,
		Field\Sync      $fieldSync,
		Sync\Dashboard  $syncDashboard,
		Sync\Singular   $syncSingular,
		Sync\CLI        $syncCli,
		Frontend\Search $frontendSearch,
		Stats\Health    $statsHealth,
		Stats\Report    $statsReport
	) {
		$this->fieldSearch    = $fieldSearch;
		$this->fieldSync      = $fieldSync;
		$this->syncDashboard  = $syncDashboard;
		$this->syncSingular   = $syncSingular;
		$this->syncCli        = $syncCli;
		$this->frontendSearch = $frontendSearch;
		$this->statsHealth    = $statsHealth;
		$this->statsReport    = $statsReport;

		$this->slug                     = 'wpml';
		$this->title                    = esc_html__( 'WPML integration', 'sitepress' );
		$this->requires_install_reindex = false;

		parent::__construct();
	}

	public function setup() {
		$this->fieldSearch->addHooks();
		$this->fieldSync->addHooks();
		$this->syncDashboard->addHooks();
		$this->syncSingular->addHooks();
		$this->syncCli->addHooks();
		$this->frontendSearch->addHooks();
		$this->statsHealth->addHooks();
		$this->statsReport->addHooks();
	}

	public function output_feature_box_summary() {
		$content = esc_html__( 'Index and search content in its specific language.', 'sitepress' );
		echo '<p>' . $content . '</p>';
	}

	public function output_feature_box_long() {
		$content = esc_html__( 'Index your content with the right stopwords, and get search results in the relevant frontend language.', 'sitepress' );
		echo '<p>' . $content . '</p>';
	}


}
