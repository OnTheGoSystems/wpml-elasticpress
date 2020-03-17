<?php

namespace Test\WPML\ElasticPress;


use WPML\ElasticPress\IndexingLangParam;

class IndexingLangParamTest extends \OTGS_TestCase {
	/**
	 * @test
	 */
	public function itAddsHooks() {
		$subject = new IndexingLangParam( $this->getSitePresMock() );

		\WP_Mock::expectActionAdded( 'ep_pre_dashboard_index', [ $subject, 'setsALLLangForDashboardIndexing' ], 10, 0 );
		\WP_Mock::expectActionAdded( 'ep_wp_cli_pre_index', [ $subject, 'setLangForCLIIndexing' ], 10, 2 );

		$subject->addHooks();
	}

	/**
	 * @test
	 */
	public function itSetsALLLangValueForCLIIfParamIsNotDefined() {
		$sitepress = $this->getSitePresMock();
		$sitepress->expects( $this->once() )->method( 'switch_lang' )->with( 'all' );

		$subject = new IndexingLangParam( $sitepress );
		$subject->setLangForCLIIndexing( [], [] );
	}

	/**
	 * @test
	 */
	public function itSetsSpecificLangValueForCLIIfParamIsDefined() {
		$lang      = 'fr';
		$sitepress = $this->getSitePresMock();
		$sitepress->expects( $this->once() )->method( 'switch_lang' )->with( $lang );

		$subject = new IndexingLangParam( $sitepress );
		$subject->setLangForCLIIndexing( [], [ 'post-lang' => $lang ] );
	}

	/**
	 * @test
	 */
	public function itSetsALLLangValueForCLIIfParamLangDoesNotBelongToActiveLanguages() {
		$lang      = 'pl';
		$sitepress = $this->getSitePresMock();
		$sitepress->expects( $this->once() )->method( 'switch_lang' )->with( 'all' );

		$subject = new IndexingLangParam( $sitepress );
		$subject->setLangForCLIIndexing( [], [ 'post-lang' => $lang ] );
	}


	private function getSitePresMock() {
		$sitepress = $this->getMockBuilder( '\Sitepress' )->setMethods( [
			'switch_lang',
			'get_active_languages',
		] )->getMock();
		$sitepress->method( 'get_active_languages' )->willReturn( [
			'en' => [],
			'fr' => [],
			'de' => [],
		] );

		return $sitepress;
	}
}