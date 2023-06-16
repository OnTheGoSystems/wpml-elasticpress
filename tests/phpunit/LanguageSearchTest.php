<?php

namespace Test\WPML\ElasticPress;

use WPML\ElasticPress\LanguageSearch;

/**
 * @group search
 */
class LanguageSearchTest extends \OTGS_TestCase {

	public function tearDown() {
		parent::tearDown();

		unset( $_GET['lang'] );
	}

	/**
	 * @test
	 */
	public function it_adds_default_lang_if_it_is_neither_set_in_db_nor_in_guid() {
		$post_id = 21;

		$post_element = $this->getMockBuilder( 'WPML_Post_Element' )->setMethods( [ 'get_language_code' ] )->getMock();
		$post_element->method( 'get_language_code' )->willReturn( null );

		$translation_element_factory = $this->getMockBuilder( 'WPML_Translation_Element_Factory' )->setMethods( [ 'create' ] )->getMock();
		$translation_element_factory->method( 'create' )->with( $post_id, 'post' )->willReturn( $post_element );

		$post_args       = [ 'ID' => $post_id, 'post_title' => 'publish', 'post_type' => 'custom' ];
		$expected_result = $post_args + [ 'post_lang' => 'en' ];

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'is_display_as_translated_post_type' )->willReturn( false );

		$subject = new LanguageSearch(
			$translation_element_factory,
			$sitepress
		);
		$result  = $subject->addLangInfo( $post_args, $post_id );

		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * @test
	 */
	public function it_adds_lang_defined_in_db() {
		$lang    = 'fr';
		$post_id = 21;

		$post_element = $this->getMockBuilder( 'WPML_Post_Element' )->setMethods( [ 'get_language_code' ] )->getMock();
		$post_element->method( 'get_language_code' )->willReturn( $lang );

		$translation_element_factory = $this->get_element_factory();
		$translation_element_factory->method( 'create' )->with( $post_id, 'post' )->willReturn( $post_element );

		$post_args       = [ 'ID' => $post_id, 'post_title' => 'publish', 'post_type' => 'custom' ];
		$expected_result = $post_args + [ 'post_lang' => $lang ];

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'is_display_as_translated_post_type' )->willReturn( false );

		$subject = new LanguageSearch( $translation_element_factory, $sitepress );
		$result  = $subject->addLangInfo( $post_args, $post_id );

		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * @param  string  $guid
	 * @param  string  $expected_lang
	 *
	 * @test
	 * @dataProvider dp_guid
	 */
	public function it_adds_lang_from_guid_if_it_is_not_set_in_db( $guid, $expected_lang ) {
		$post_id = 21;

		$post_element = $this->getMockBuilder( 'WPML_Post_Element' )->setMethods( [ 'get_language_code' ] )->getMock();
		$post_element->method( 'get_language_code' )->willReturn( null );

		$translation_element_factory = $this->get_element_factory();
		$translation_element_factory->method( 'create' )->with( $post_id, 'post' )->willReturn( $post_element );

		$post_args       = [ 'ID' => $post_id, 'post_title' => 'publish', 'guid' => $guid, 'post_type' => 'custom' ];
		$expected_result = $post_args + [ 'post_lang' => $expected_lang ];

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'is_display_as_translated_post_type' )->willReturn( false );

		$subject = new LanguageSearch(
			$translation_element_factory,
			$sitepress
		);
		$result  = $subject->addLangInfo( $post_args, $post_id );

		$this->assertEquals( $expected_result, $result );
	}


	public function dp_guid() {
		return [
			[
				'https://wpml.org/it/forums/topic/dopo-laggiornamento-il-sito-non-caricaerror-sitepress-class-php-on-line-2832/',
				'it',
			],
			[ 'https://wpml.org/pt-br/faq/como-depurar-problemas-de-desempenho/', 'pt-br' ],
			[ 'https://wpml.org/pl/faq/artykul-o-czyms/', 'en' ], // PL is not active lang
			[ 'https://wpml.org/de/vorzeigeprojekte/rjrinnovations-com/', 'de' ],
			[ 'https://wpml.org/vorzeigeprojekte/rjrinnovations-com/?param=some&lang=de', 'de' ],
			[ 'https://wpml.org/vorzeigeprojekte/rjrinnovations-com?lang=de', 'de' ],
			[ 'https://fr.wpml.org/vorzeigeprojekte/rjrinnovations-com', 'fr' ],
			[ 'http://pt-br.wpml.org/vorzeigeprojekte/rjrinnovations-com', 'pt-br' ],
		];
	}

	/**
	 * @test
	 */
	public function it_adds_lang_on_default_language_when_display_as_translated() {
		$post_id      = 21;
		$trid         = 99;
		$post_args    = [ 'ID' => $post_id, 'post_title' => 'publish', 'post_type' => 'custom' ];
		$default_lang = 'en';

		\WP_Mock::onFilter( 'wpml_element_trid' )
			->with( null )
			->reply( $trid );
		\WP_Mock::onFilter( 'wpml_get_element_translations' )
			->with( null )
			->reply( array(
				'en'    => (object) array(
					'element_id' => $post_id,
				),
				'pt-br' => (object) array(
					'element_id' => 55,
				),
				'it'    => (object) array(
					'element_id' => 66,
				),
			) );

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'is_display_as_translated_post_type' )->willReturn( true );

		$post_element = $this->getMockBuilder( 'WPML_Post_Element' )->setMethods( [ 'get_language_code' ] )->getMock();
		$post_element->method( 'get_language_code' )->willReturn( $default_lang );

		$translation_element_factory = $this->get_element_factory();
		$translation_element_factory->method( 'create' )->with( $post_id, 'post' )->willReturn( $post_element );

		$expected_result = $post_args + [ 'post_lang' => 'en,fr,de' ];

		$subject = new LanguageSearch(
			$translation_element_factory,
			$sitepress
		);
		$result  = $subject->addLangInfo( $post_args, $post_id );

		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * @test
	 */
	public function it_keeps_lang_on_secondary_language_when_display_as_translated() {
		$post_id      = 21;
		$trid         = 99;
		$post_args    = [ 'ID' => $post_id, 'post_title' => 'publish', 'post_type' => 'custom' ];
		$object_lang  = 'it';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'is_display_as_translated_post_type' )->willReturn( true );

		$post_element = $this->getMockBuilder( 'WPML_Post_Element' )->setMethods( [ 'get_language_code' ] )->getMock();
		$post_element->method( 'get_language_code' )->willReturn( $object_lang );

		$translation_element_factory = $this->get_element_factory();
		$translation_element_factory->method( 'create' )->with( $post_id, 'post' )->willReturn( $post_element );

		$expected_result = $post_args + [ 'post_lang' => $object_lang ];

		$subject = new LanguageSearch(
			$translation_element_factory,
			$sitepress
		);
		$result  = $subject->addLangInfo( $post_args, $post_id );

		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * @test
	 */
	public function it_adds_current_lang_to_filters_if_the_parameter_is_not_send() {
		$lang = 'en';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( $lang );

		$args            = [];
		$expected_result = [ 'post_filter' => [ 'bool' => [ 'must' => [ [ 'term' => [ 'post_lang' => $lang ] ] ] ] ] ];

		$subject = new LanguageSearch( $this->get_element_factory(), $sitepress );
		$this->assertEquals( $expected_result, $subject->filterByLang( $args ) );
	}

	/**
	 * @test
	 */
	public function it_adds_current_lang_to_filters_if_param_lang_does_not_belong_to_active_languages() {
		$_GET['lang'] = 'pl';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( 'en' );

		$args            = [];
		$expected_result = [ 'post_filter' => [ 'bool' => [ 'must' => [ [ 'term' => [ 'post_lang' => 'en' ] ] ] ] ] ];

		$subject = new LanguageSearch( $this->get_element_factory(), $sitepress );
		$this->assertEquals( $expected_result, $subject->filterByLang( $args ) );
	}

	/**
	 * @test
	 */
	public function it_adds_param_lang_to_filters() {
		$_GET['lang'] = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_current_language' )->willReturn( 'en' );

		$args            = [];
		$expected_result = [ 'post_filter' => [ 'bool' => [ 'must' => [ [ 'term' => [ 'post_lang' => $_GET['lang'] ] ] ] ] ] ];

		$subject = new LanguageSearch( $this->get_element_factory(), $sitepress );
		$this->assertEquals( $expected_result, $subject->filterByLang( $args ) );
	}

	private function get_sitepress() {
		$active_langs = [
			'en'    => [],
			'fr'    => [],
			'de'    => [],
			'pt-br' => [],
			'it'    => [],
		];

		$sitepress = $this->getMockBuilder( 'SitePress' )->setMethods( [
			'get_active_languages',
			'get_current_language',
			'get_default_language',
			'is_display_as_translated_post_type',
		] )->getMock();
		$sitepress->method( 'get_active_languages' )->willReturn( $active_langs );
		$sitepress->method( 'get_default_language' )->willReturn( 'en' );

		return $sitepress;
	}

	private function get_element_factory() {
		return $this->getMockBuilder( 'WPML_Translation_Element_Factory' )->setMethods( [ 'create' ] )->getMock();
	}
}
