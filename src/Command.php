<?php

namespace WPML\ElasticPress;

class Command extends \ElasticPress\Command {
	// Extending the native class to add `--post-lang` argument

	/**
	 * Index all posts for a site or network wide
	 *
	 * @synopsis [--setup] [--network-wide] [--per-page] [--nobulk] [--show-errors] [--offset] [--indexables] [--show-bulk-errors] [--show-nobulk-errors] [--post-type] [--include] [--post-ids] [--ep-host] [--ep-prefix] [--post-lang]
	 * @param array $args      Positional CLI args.
	 * @param array $assocArgs Associative CLI args.
	 *
	 * @since 0.1.2
	 * @deprecated on ElasticPress 4.4.0
	 */
	public function index( $args, $assocArgs ) {
		$this->sync( $args, $assocArgs );
	}

	/**
	 * Sync all posts for a site or network wide
	 *
	 * @synopsis [--setup] [--network-wide] [--per-page] [--nobulk] [--show-errors] [--offset] [--indexables] [--show-bulk-errors] [--show-nobulk-errors] [--post-type] [--include] [--post-ids] [--ep-host] [--ep-prefix] [--post-lang]
	 * @param array $args      Positional CLI args.
	 * @param array $assocArgs Associative CLI args.
	 */
	public function sync( $args, $assocArgs ) {
		$languages = [];
		if ( isset( $assocArgs['post-lang'] ) ) {
			$languages = explode( ',', $assocArgs['post-lang'] );
		} else {
			$languages = array_keys( apply_filters( 'wpml_active_languages', [] ) );
		}

		if ( empty( $languages ) ) {
			return;
		}

		$this->manageIndices( $assocArgs, $languages );

		foreach ( $languages as $language ) {
			\WP_CLI::log( \WP_CLI::colorize( '%7%k' . sprintf( esc_html__( 'Processing... %s%s%s%s', 'sitepress' ), '%N', '%3%k ', $language, ' %n' ) . '%n' ) );
			$assocArgs['post-lang'] = $language;
			if ( version_compare( EP_VERSION, '4.4.0', '<' ) ) {
				parent::index( $args, $assocArgs );
			} else {
				parent::sync( $args, $assocArgs );
			}
		}
		\WP_CLI::log( \WP_CLI::colorize( '%2%kWPML ElasticPress sync complete%n' ) );
	}

	/**
	 * @param array $assocArgs
	 */
	private function manageIndices( &$assocArgs, $languages ) {
		$setupOption = \WP_CLI\Utils\get_flag_value( $assocArgs, 'setup', false );
		if ( $setupOption ) {
			\WP_CLI::confirm( esc_html__( 'Indexing with the setup flag will recreate anew all your Elasticsearch indices. Are you sure you want to delete your current Elasticsearch indices?', 'sitepress' ), $assocArgs );
			$assocArgs['setup'] = false;
			\WP_CLI::log( \WP_CLI::colorize( '%7%k' . sprintf( esc_html__( 'Processing... %s%s%s%s', 'sitepress' ), '%N', '%3%k ', esc_html( 'deleting and generating new indices', 'sitepress' ), ' %n' ) . '%n' ) );
			do_action( 'wpml_ep_regenerate_indices', $languages );
			\WP_CLI::success( esc_html__( 'ElasticPress: indices recreated and ready', 'sitepress' ) );
			return;
		}
		\WP_CLI::log( \WP_CLI::colorize( '%7%k' . sprintf( esc_html__( 'Processing... %s%s%s%s', 'sitepress' ), '%N', '%3%k ', esc_html( 'generating missing indices', 'sitepress' ), ' %n' ) . '%n' ) );
		do_action( 'wpml_ep_check_indices', $languages );
		\WP_CLI::success( esc_html__( 'ElasticPress: indices ready', 'sitepress' ) );
	}
}
