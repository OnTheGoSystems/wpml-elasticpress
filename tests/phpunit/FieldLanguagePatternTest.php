<?php

use WPML\ElasticPress\Field\Sync;

class FieldLanguagePatternTestDouble extends Sync {
	public function callBuildLanguagePattern() {
		return $this->buildLanguagePattern();
	}
}

class FieldLanguagePatternTest extends OTGS_TestCase {

	public function guidProvider() {
		return [
			'language as directory'   => [ 'http://example.com/de/some-post/', 'de' ],
			'language as parameter'   => [ 'http://example.com/?p=1&lang=fr', 'fr' ],
			'language as subdomain'   => [ 'http://de.example.com/some-post/', 'de' ],
			'default language guid'   => [ 'http://example.com/some-post/', null ],
			'unrelated language code' => [ 'http://example.com/it/some-post/', null ],
		];
	}

	/**
	 * @dataProvider guidProvider
	 *
	 * @param string      $guid
	 * @param string|null $expectedLanguage
	 */
	public function test_pattern_extracts_language_from_guid( $guid, $expectedLanguage ) {
		$subject = new FieldLanguagePatternTestDouble( '7.10', [ 'en', 'de', 'fr' ], 'en', 'en' );

		$pattern = $subject->callBuildLanguagePattern();
		$matched = preg_match( $pattern, $guid, $match );

		if ( null === $expectedLanguage ) {
			$this->assertSame( 0, $matched );
			return;
		}

		$this->assertSame( 1, $matched );
		// getPostLanguage() reads the last captured group.
		$this->assertSame( $expectedLanguage, end( $match ) );
	}
}
