<?php

namespace WPML\ElasticPress\Field;

use WPML\ElasticPress\Field as Field;

class Sync extends Field {

	const FIELD_SLUG = 'post_unique_lang';

	/**
	 * @param  array $postArgs
	 * @param  int   $postId
	 *
	 * @return array
	 */
	public function addLanguageInfo( $postArgs, $postId ) {
		$postArgs[ static::FIELD_SLUG ] = $this->getPostLanguage( $postArgs, $postId );
		return $postArgs;
	}

}
