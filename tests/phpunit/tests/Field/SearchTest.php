<?php

namespace WPML\ElasticPress\Field;

/**
 * @group field
 * @group search
 */
class SearchTest extends \OTGS_TestCase {

	const ES_VERSION = '7.10';

	/**
	 * The language set resolved at plugins_loaded, before `init`, when WPML's
	 * show_hidden() is still true for every request. It always contains the
	 * hidden languages, whoever the viewer is.
	 */
	const PRE_INIT_LANGUAGES = [ 'en', 'fr', 'de' ];

	public function setUp(): void {
		parent::setUp();
		unset( $_GET['lang'] );
	}

	public function tearDown(): void {
		unset( $_GET['lang'] );
		parent::tearDown();
	}

	private function getSubject( $currentLanguage = 'en' ) {
		return new Search( self::ES_VERSION, self::PRE_INIT_LANGUAGES, 'en', $currentLanguage );
	}

	/**
	 * Mock the viewer-sensitive active-language set resolved at query time,
	 * after `init`: hidden languages are only present when Core's show_hidden()
	 * policy allows this viewer to see them.
	 */
	private function mockViewerVisibleLanguages( array $codes ) {
		\WP_Mock::onFilter( 'wpml_active_languages' )
			->with( [] )
			->reply( array_fill_keys( $codes, [] ) );
	}

	private function filteredLanguage( Search $subject ) {
		$args = $subject->filterByLanguage( [] );

		return $args['post_filter']['bool']['must'][0]['term']['post_lang'];
	}

	/**
	 * @test
	 *
	 * `fr` is in the pre-`init` constructor language set (which always includes
	 * hidden languages), but the viewer-sensitive set at query time does not
	 * contain it. The request-selected language must be rejected and the search
	 * stays in the current language.
	 */
	public function itRejectsARequestSelectedLanguageTheViewerCannotSee() {
		$_GET['lang'] = 'fr';
		$this->mockViewerVisibleLanguages( [ 'en', 'de' ] );

		$this->assertSame( 'en', $this->filteredLanguage( $this->getSubject( 'en' ) ) );
	}

	/**
	 * @test
	 */
	public function itAcceptsARequestSelectedViewerVisibleLanguage() {
		$_GET['lang'] = 'de';
		$this->mockViewerVisibleLanguages( [ 'en', 'de' ] );

		$this->assertSame( 'de', $this->filteredLanguage( $this->getSubject( 'en' ) ) );
	}

	/**
	 * @test
	 *
	 * Positive control: when Core's show_hidden() policy makes the hidden
	 * language visible to this viewer, the query-time set contains it and the
	 * selection is honored.
	 */
	public function itAcceptsAHiddenLanguageWhenCoreShowsHiddenToThisViewer() {
		$_GET['lang'] = 'fr';
		$this->mockViewerVisibleLanguages( [ 'en', 'fr', 'de' ] );

		$this->assertSame( 'fr', $this->filteredLanguage( $this->getSubject( 'en' ) ) );
	}

	/**
	 * @test
	 */
	public function itRejectsANonexistentLanguage() {
		$_GET['lang'] = 'xx';
		$this->mockViewerVisibleLanguages( [ 'en', 'de' ] );

		$this->assertSame( 'en', $this->filteredLanguage( $this->getSubject( 'en' ) ) );
	}

	/**
	 * @test
	 */
	public function itRejectsANonStringLanguage() {
		$_GET['lang'] = [ 'fr' ];
		$this->mockViewerVisibleLanguages( [ 'en', 'fr', 'de' ] );

		$this->assertSame( 'en', $this->filteredLanguage( $this->getSubject( 'en' ) ) );
	}

	/**
	 * @test
	 */
	public function itUsesTheCurrentLanguageWithoutARequestSelection() {
		$this->mockViewerVisibleLanguages( [ 'en', 'de' ] );

		$this->assertSame( 'de', $this->filteredLanguage( $this->getSubject( 'de' ) ) );
	}
}
