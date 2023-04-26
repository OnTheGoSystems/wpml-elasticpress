<?php

namespace WPML\ElasticPress\Sync;

class CLI {
	/** @var array */
	private $active_languages;

	/**
	 * @param array $active_languages
	 */
	public function __construct( $active_languages ) {
		$this->active_languages = $active_languages;
	}

	public function addHooks() {
		add_action( 'ep_wp_cli_pre_index', [ $this, 'setLangForCLIIndexing' ], 10, 2 );
		add_filter( 'ep_cli_index_args', [ $this, 'setCliIndexArgs' ] );
	}

	/**
	 * Set the current lang for CLI index based on the --post-lang flag
	 *
	 * @param  array $args
	 * @param  array $assocArgs
	 *
	 * @return array
	 */
	public function setLangForCLIIndexing( array $args, array $assocArgs ) {
		do_action( 'wpml_switch_language', $this->getLangFromArgs( $assocArgs ) );
		// TODO if lang !== 'all', adjust the analyzer and restore it back afterwards!
		// If lang === 'all' we might need to do as in Dashboard, somehow
	}

	/**
	 * CLI index might include all posts
	 *
	 * @param  array $args
	 *
	 * @return array
	 */
	public function setCliIndexArgs( $args ) {
		if ( 'all' === apply_filters( 'wpml_current_language', null ) ) {
			$args['suppress_wpml_where_and_join_filter'] = true;
		}
		return $args;
	}

	/**
	 * @param  array  $assocArgs
	 *
	 * @return string
	 */
	private function getLangFromArgs( array $assocArgs ) {
		if ( isset( $assocArgs['post-lang'] ) ) {
			$lang = $assocArgs['post-lang'];
			if ( in_array( $lang, $this->active_languages, true ) ) {
				return $lang;
			}
		}

		return 'all';
	}

}
