<?php

namespace WPML\ElasticPress\Traits;

trait TranslationModes {

	/** @var null|array */
	private $translatablePostTypes = null;

	/**
	 * @return array
	 */
	private function getTranslatablePostTypes() {
		if ( null === $this->translatablePostTypes ) {
			$this->translatablePostTypes = array_keys( apply_filters( 'wpml_translatable_documents', [] ) );
		}
		return $this->translatablePostTypes;
	}

	/**
	 * @param  string $postType
	 *
	 * @return bool
	 */
	private function isNotTranslatable( $postType ) {
		return ( ! in_array( $postType, $this->getTranslatablePostTypes(), true ) );
	}

	/**
	 * @param  string $postType
	 *
	 * @return bool
	 */
	private function isDisplayAsTranslated( $postType ) {
		return apply_filters( 'wpml_is_display_as_translated_post_type', false, $postType );
	}

	/**
	 * @param  int    $postId
	 * @param  string $postType
	 * @param  string $postLanguage
	 *
	 * @return array
	 */
	private function getDisplayAsTranslatedLanguages( $postId, $postType, $postLanguage ) {
		if ( $postLanguage !== $this->defaultLanguage ) {
			return [ $postLanguage ];
		}

		$activeLanguages = $this->activeLanguages;
		$elementType     = apply_filters( 'wpml_element_type', $postType );
		$trid            = apply_filters( 'wpml_element_trid', null, $postId, $elementType );
		$translations    = apply_filters( 'wpml_get_element_translations', null, $trid, $elementType );
		foreach ( $activeLanguages as $key => $language ) {
			if (
				array_key_exists( $language, $translations )
				&& $translations[ $language ]->element_id != $postId
			) {
				unset( $activeLanguages[ $key ] );
			}
		}

		if ( empty( $activeLanguages ) ) {
			return [ $postLanguage ];
		}

		return $activeLanguages;
	}

	/**
	 * @return int|false
	 */
	private function getDisplayAsTranslatedDefaultPostId( $postId, $postType, $postLanguage ) {
		if ( $postLanguage === $this->defaultLanguage ) {
			return $postId;
		}
		$elementType    = apply_filters( 'wpml_element_type', $postType );
		$originalPostId = apply_filters( 'wpml_original_element_id', '', $postId, $elementType );

		return $originalPostId;
	}
}
