<?php

namespace WPML\ElasticPress\Traits;

trait TranslateLanguages {

	/**
	 * Languages supported in Elasticsearch mappings.
	 * Array format: Elasticsearch analyzer name => WPML language codes
	 *
	 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html
	 */
	private $wpmlLanguages = [
		'arabic'     => [ 'ar' ],
		'armenian'   => [ 'hy' ],
		'basque'     => [ 'eu' ],
		'bengali'    => [ 'bn' ],
		'brazilian'  => [ 'pt-br' ],
		'bulgarian'  => [ 'bg' ],
		'catalan'    => [ 'ca' ],
		'czech'      => [ 'cs' ],
		'danish'     => [ 'da' ],
		'dutch'      => [ 'nl' ],
		'english'    => [ 'en' ],
		'estonian'   => [ 'et' ],
		'finnish'    => [ 'fi' ],
		'french'     => [ 'fr' ],
		'galician'   => [ 'gl' ],
		'german'     => [ 'de' ],
		'greek'      => [ 'el' ],
		'hindi'      => [ 'hi' ],
		'hungarian'  => [ 'hu' ],
		'indonesian' => [ 'id' ],
		'irish'      => [ 'ga' ],
		'italian'    => [ 'it' ],
		'latvian'    => [ 'lv' ],
		'lithuanian' => [ 'lt' ],
		'norwegian'  => [ 'no', 'nn' ],
		'persian'    => [ 'fa' ],
		'portuguese' => [ 'pt-pt' ],
		'romanian'   => [ 'ro' ],
		'russian'    => [ 'ru' ],
		'sorani'     => [ 'ku' ],
		'spanish'    => [ 'es' ],
		'swedish'    => [ 'sv' ],
		'turkish'    => [ 'tr' ],
		'thai'       => [ 'th' ],
	];

	/**
	 * Languages supported in Elasticsearch snowball token filters.
	 *
	 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-snowball-tokenfilter.html
	 */
	private $snowballLanguages = [
		'Armenian',
		'Basque',
		'Catalan',
		'Danish',
		'Dutch',
		'English',
		'Finnish',
		'French',
		'German',
		'Hungarian',
		'Italian',
		'Lithuanian',
		'Norwegian',
		'Portuguese',
		'Romanian',
		'Russian',
		'Spanish',
		'Swedish',
		'Turkish',
	];

	/** @var string */
	private $analyzerLanguage = 'english';

	/** @var string */
	private $snowballLanguage = 'English';

	/**
	 * @param  string                                    $languageCode
	 *
	 * @return array{analyzer: string, snowball: string} $languages
	 */
	private function generateAnalysisLanguages( $languageCode ) {
		$analyzerLanguage = $this->analyzerLanguage;
		$snowballLanguage = $this->snowballLanguage;

		foreach ( $this->wpmlLanguages as $analyzerName => $analyzerLanguageCodes ) {
			if ( in_array( $languageCode, $analyzerLanguageCodes, true ) ) {
				$analyzerLanguage = $analyzerName;
				break;
			}
		}

		if ( in_array( ucfirst( $analyzerLanguage ), $this->snowballLanguages, true ) ) {
			$snowballLanguage = ucfirst( $analyzerLanguage );
		}

		return [
			'analyzer' => $analyzerLanguage,
			'snowball' => $snowballLanguage
		];
	}
}
