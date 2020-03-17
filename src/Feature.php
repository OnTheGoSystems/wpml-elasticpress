<?php

namespace WPML\ElasticPress;

class Feature extends \ElasticPress\Feature {
	/** @var LanguageSearch */
	private $languageSearch;

	/** @var IndexingLangParam */
	private $indexLangParam;

	/**
	 * @param  LanguageSearch  $languageSearch
	 */
	public function __construct( LanguageSearch $languageSearch, IndexingLangParam $indexLangParam ) {
		parent::__construct();

		$this->languageSearch = $languageSearch;
		$this->indexLangParam = $indexLangParam;

		$this->slug                     = 'wpml';
		$this->title                    = __( 'Integration WPML with ElasticPress', 'sitepress' );
		$this->requires_install_reindex = false;
	}

	public function setup() {
		add_filter( 'ep_post_sync_args_post_prepare_meta', [ $this->languageSearch, 'addLangInfo' ], 10, 2 );
		add_filter( 'ep_post_formatted_args', [ $this->languageSearch, 'filterByLang' ], 10, 1 );

		$this->indexLangParam->addHooks();
	}

	public function output_feature_box_summary() {
		$content = esc_html__( 'Integration WPML with ElasticPress', 'sitepress' );
		echo '<p>' . $content . '</p>';
	}

	public function output_feature_box_long() {
		$content = esc_html__( 'This allows to search for content in a specific language only.', 'sitepress' );
		echo '<p>' . $content . '</p>';
	}


}
