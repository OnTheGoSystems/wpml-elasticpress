<?php

namespace WPML\ElasticPress\Field;

use WPML\ElasticPress\Field as Field;

class Sync extends Field {

	/** @var string */
	protected $fieldSlug = 'post_unique_lang';

	/**
	 * @return string;
	 */
	protected function getFieldSlug() {
		return $this->fieldSlug;
	}

	/**
	 * @param  array $post_args
	 * @param  int   $post_id
	 *
	 * @return array
	 */
	public function addLangInfo( $post_args, $post_id ) {
		$fieldSlug               = $this->getFieldSlug();
		$post_args[ $fieldSlug ] = $this->getPostLang( $post_args, $post_id );
		return $post_args;
	}

}
