<?php

namespace WPML\ElasticPress;


class IndexingLangParam {
	/** @var \SitePress */
	private $sitepress;

	/**
	 * @param  \SitePress  $sitepress
	 */
	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}


	public function addHooks() {
		add_action( 'ep_pre_dashboard_index', [ $this, 'setsALLLangForDashboardIndexing' ], 10, 0 );
		add_action( 'ep_wp_cli_pre_index', [ $this, 'setLangForCLIIndexing' ], 10, 2 );
	}

	public function setsALLLangForDashboardIndexing() {
		$this->sitepress->switch_lang( 'all' );
	}

	public function setLangForCLIIndexing( array $args, array $assocArgs ) {
		$this->sitepress->switch_lang( $this->getLangFromArgs( $assocArgs ) );
	}

	/**
	 * @param  array  $assocArgs
	 *
	 * @return string
	 */
	private function getLangFromArgs( array $assocArgs ) {
		if ( isset( $assocArgs['post-lang'] ) ) {
			$lang = $assocArgs['post-lang'];
			if ( in_array( $lang, array_keys( $this->sitepress->get_active_languages() ) ) ) {
				return $lang;
			}
		}

		return 'all';
	}
}