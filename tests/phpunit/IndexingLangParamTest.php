<?php

namespace Test\WPML\ElasticPress;


use WPML\ElasticPress\IndexingLangParam;

class IndexingLangParamTest extends \OTGS_TestCase {
	/**
	 * @test
	 */
	public function itAddsHooks() {
		$subject = new IndexingLangParam(
			$this->getElasticsearchMock(),
			$this->getSitePresMock()
		);

		\WP_Mock::expectFilterAdded( 'ep_dashboard_index_args', [ $subject, 'setDashboardIndexArgs' ] );
		\WP_Mock::expectActionAdded( 'ep_wp_cli_pre_index', [ $subject, 'setLangForCLIIndexing' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'ep_cli_index_args', [ $subject, 'setCliIndexArgs' ] );
		\WP_Mock::expectFilterAdded( 'ep_post_mapping', [ $subject, 'mapping' ] );

		$subject->addHooks();
	}

	/**
	 * @test
	 */
	public function itSetsALLLangValueForCLIIfParamIsNotDefined() {
		$sitepress = $this->getSitePresMock();
		$sitepress->expects( $this->once() )->method( 'switch_lang' )->with( 'all' );

		$subject = new IndexingLangParam( $this->getElasticsearchMock(), $sitepress );
		$subject->setLangForCLIIndexing( [], [] );
	}

	/**
	 * @test
	 */
	public function itSetsSpecificLangValueForCLIIfParamIsDefined() {
		$lang      = 'fr';
		$sitepress = $this->getSitePresMock();
		$sitepress->expects( $this->once() )->method( 'switch_lang' )->with( $lang );

		$subject = new IndexingLangParam( $this->getElasticsearchMock(), $sitepress );
		$subject->setLangForCLIIndexing( [], [ 'post-lang' => $lang ] );
	}

	/**
	 * @test
	 */
	public function itSetsALLLangValueForCLIIfParamLangDoesNotBelongToActiveLanguages() {
		$lang      = 'pl';
		$sitepress = $this->getSitePresMock();
		$sitepress->expects( $this->once() )->method( 'switch_lang' )->with( 'all' );

		$subject = new IndexingLangParam( $this->getElasticsearchMock(), $sitepress );
		$subject->setLangForCLIIndexing( [], [ 'post-lang' => $lang ] );
	}

	/**
	 * @test
	 */
	public function itSetsUnfilteredAnalyzerForPostLangField() {
		$elasticsearch = $this->getElasticsearchMock();
		$elasticsearch->expects( $this->once() )->method( 'get_elasticsearch_version' )->willReturn( '7.10' );

		$mapping = [
			'settings' => [
				'analysis' => [
					'analyzer' => [],
				],
			],
			'mappings' => [
				'properties' => [],
			],
		];

		$expected = [
			'settings' => [
				'analysis' => [
					'analyzer' => [
						'post_lang_field' => [
							'type'      => 'custom',
							'tokenizer' => 'standard',
							'filter'    => [],
						],
					],
				],
			],
			'mappings' => [
				'properties' => [
					'post_lang' => [
						'type'     => 'text',
						'analyzer' => 'post_lang_field',
					],
				],
			],
		];

		$subject = new IndexingLangParam(
			$elasticsearch,
			$this->getSitePresMock()
		);

		$this->assertSame( $expected, $subject->mapping( $mapping ) );
	}

	/**
	 * @test
	 */
	public function itSetsUnfilteredAnalyzerForPostLangFieldOnLegacyElasticsearch() {
		$elasticsearch = $this->getElasticsearchMock();
		$elasticsearch->expects( $this->once() )->method( 'get_elasticsearch_version' )->willReturn( '6.9' );

		$mapping = [
			'settings' => [
				'analysis' => [
					'analyzer' => [],
				],
			],
			'mappings' => [
				'post' => [
					'properties' => [],
				],
			],
		];

		$expected = [
			'settings' => [
				'analysis' => [
					'analyzer' => [
						'post_lang_field' => [
							'type'      => 'custom',
							'tokenizer' => 'standard',
							'filter'    => [],
						],
					],
				],
			],
			'mappings' => [
				'post' => [
					'properties' => [
						'post_lang' => [
							'type'     => 'text',
							'analyzer' => 'post_lang_field',
						],
					],
				],
			],
		];

		$subject = new IndexingLangParam(
			$elasticsearch,
			$this->getSitePresMock()
		);

		$this->assertSame( $expected, $subject->mapping( $mapping ) );
	}

	private function getElasticsearchMock() {
		$elasticsearch = $this->getMockBuilder( '\ElasticPress\Elasticsearch' )->setMethods( [
			'get_elasticsearch_version'
		] )->getMock();

		return $elasticsearch;
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
