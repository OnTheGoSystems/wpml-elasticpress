<?php

use WPML\ElasticPress\Traits\TranslateLanguages;

class TranslateLanguagesTestDouble {
	use TranslateLanguages;

	public function callGenerateAnalysisLanguages( $languageCode ) {
		return $this->generateAnalysisLanguages( $languageCode );
	}

	public function callLanguageHasStemmer( $language ) {
		return $this->languageHasStemmer( $language );
	}
}

class TranslateLanguagesTest extends OTGS_TestCase {

	public function analysisLanguagesProvider() {
		return [
			'german has analyzer and snowball' => [ 'de', 'german', 'German' ],
			'english is fully supported'       => [ 'en', 'english', 'English' ],
			'arabic has no snowball'           => [ 'ar', 'arabic', 'English' ],
			'thai has no snowball'             => [ 'th', 'thai', 'English' ],
			'brazilian portuguese maps'        => [ 'pt-br', 'brazilian', 'English' ],
			'norwegian nynorsk maps'           => [ 'nn', 'norwegian', 'Norwegian' ],
			'unknown falls back to english'    => [ 'xx', 'english', 'English' ],
		];
	}

	/**
	 * @dataProvider analysisLanguagesProvider
	 *
	 * @param string $languageCode
	 * @param string $expectedAnalyzer
	 * @param string $expectedSnowball
	 */
	public function test_generate_analysis_languages( $languageCode, $expectedAnalyzer, $expectedSnowball ) {
		$subject = new TranslateLanguagesTestDouble();

		$this->assertSame(
			[
				'analyzer' => $expectedAnalyzer,
				'snowball' => $expectedSnowball,
			],
			$subject->callGenerateAnalysisLanguages( $languageCode )
		);
	}

	public function test_language_has_stemmer() {
		$subject = new TranslateLanguagesTestDouble();

		$this->assertTrue( $subject->callLanguageHasStemmer( 'german' ) );
		$this->assertTrue( $subject->callLanguageHasStemmer( 'arabic' ) );
		$this->assertFalse( $subject->callLanguageHasStemmer( 'thai' ) );
		$this->assertFalse( $subject->callLanguageHasStemmer( 'unknown' ) );
	}
}
