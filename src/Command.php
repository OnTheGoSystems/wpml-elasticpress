<?php

namespace WPML\ElasticPress;

/**
 * Extending the ElasticPress's native class to add `--post-lang` argument
 */
class Command extends \ElasticPress\Command {
	/**
	 * Index all posts for a site or network wide.
	 *
	 * ## OPTIONS
	 *
	 * [--network-wide]
	 * : Force indexing on all the blogs in the network. `--network-wide` takes an optional argument to limit the number of blogs to be indexed across where 0 is no limit. For example, `--network-wide=5` would limit indexing to only 5 blogs on the network
	 *
	 * [--setup]
	 * : Clear the index first and re-send the put mapping. Use `--yes` to skip the confirmation
	 *
	 * [--force]
	 * : Stop any ongoing sync
	 *
	 * [--per-page=<per_page_number>]
	 * : Determine the amount of posts to be indexed per bulk index (or cycle)
	 *
	 * [--nobulk]
	 * : Disable bulk indexing
	 *
	 * [--static-bulk]
	 * : Do not use dynamic bulk requests, i.e., send only one request per batch of documents.
	 *
	 * [--show-errors]
	 * : Show all errors
	 *
	 * [--show-bulk-errors]
	 * : Display the error message returned from Elasticsearch when a post fails to index using the /_bulk endpoint
	 *
	 * [--show-nobulk-errors]
	 * : Display the error message returned from Elasticsearch when a post fails to index while not using the /_bulk endpoint
	 *
	 * [--stop-on-error]
	 * : Stop indexing if an error is encountered and display the error.
	 *
	 * [--offset=<offset_number>]
	 * : Skip the first n posts (don't forget to remove the `--setup` flag when resuming or the index will be emptied before starting again).
	 *
	 * [--indexables=<indexables>]
	 * : Specify the Indexable(s) which will be indexed
	 *
	 * [--post-type=<post_types>]
	 * : Specify which post types will be indexed (by default: all indexable post types are indexed). For example, `--post-type="my_custom_post_type"` would limit indexing to only posts from the post type "my_custom_post_type". Accepts multiple post types separated by comma
	 *
	 * [--include=<IDs>]
	 * : Choose which object IDs to include in the index
	 *
	 * [--post-ids=<IDs>]
	 * : Choose which post_ids to include when indexing the Posts Indexable (deprecated)
	 *
	 * [--upper-limit-object-id=<ID>]
	 * : Upper limit of a range of IDs to be indexed. If indexing IDs from 30 to 45, this should be 45
	 *
	 * [--lower-limit-object-id=<ID>]
	 * : Lower limit of a range of IDs to be indexed. If indexing IDs from 30 to 45, this should be 30
	 *
	 * [--ep-host=<host>]
	 * : Custom Elasticsearch host
	 *
	 * [--ep-prefix=<prefix>]
	 * : Custom ElasticPress prefix
	 *
	 * [--yes]
	 * : Skip confirmation needed by `--setup`
	 *
	 * [--post-lang]
	 * : Choose which language include in the index
	 *
	 * @param array $args Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 *
	 * @return void
	 *
	 * @since 0.1.2
	 * @deprecated on ElasticPress 4.4.0
	 */
	public function index( $args, $assoc_args ): void {
		$this->sync( $args, $assoc_args );
	}

	/**
	 *
	 * Index all posts for a site or network wide.
	 *
	 * ## OPTIONS
	 *
	 * [--network-wide]
	 * : Force indexing on all the blogs in the network. `--network-wide` takes an optional argument to limit the number of blogs to be indexed across where 0 is no limit. For example, `--network-wide=5` would limit indexing to only 5 blogs on the network
	 *
	 * [--setup]
	 * : Clear the index first and re-send the put mapping. Use `--yes` to skip the confirmation
	 *
	 * [--force]
	 * : Stop any ongoing sync
	 *
	 * [--per-page=<per_page_number>]
	 * : Determine the amount of posts to be indexed per bulk index (or cycle)
	 *
	 * [--nobulk]
	 * : Disable bulk indexing
	 *
	 * [--static-bulk]
	 * : Do not use dynamic bulk requests, i.e., send only one request per batch of documents.
	 *
	 * [--show-errors]
	 * : Show all errors
	 *
	 * [--show-bulk-errors]
	 * : Display the error message returned from Elasticsearch when a post fails to index using the /_bulk endpoint
	 *
	 * [--show-nobulk-errors]
	 * : Display the error message returned from Elasticsearch when a post fails to index while not using the /_bulk endpoint
	 *
	 * [--stop-on-error]
	 * : Stop indexing if an error is encountered and display the error.
	 *
	 * [--offset=<offset_number>]
	 * : Skip the first n posts (don't forget to remove the `--setup` flag when resuming or the index will be emptied before starting again).
	 *
	 * [--indexables=<indexables>]
	 * : Specify the Indexable(s) which will be indexed
	 *
	 * [--post-type=<post_types>]
	 * : Specify which post types will be indexed (by default: all indexable post types are indexed). For example, `--post-type="my_custom_post_type"` would limit indexing to only posts from the post type "my_custom_post_type". Accepts multiple post types separated by comma
	 *
	 * [--include=<IDs>]
	 * : Choose which object IDs to include in the index
	 *
	 * [--post-ids=<IDs>]
	 * : Choose which post_ids to include when indexing the Posts Indexable (deprecated)
	 *
	 * [--upper-limit-object-id=<ID>]
	 * : Upper limit of a range of IDs to be indexed. If indexing IDs from 30 to 45, this should be 45
	 *
	 * [--lower-limit-object-id=<ID>]
	 * : Lower limit of a range of IDs to be indexed. If indexing IDs from 30 to 45, this should be 30
	 *
	 * [--ep-host=<host>]
	 * : Custom Elasticsearch host
	 *
	 * [--ep-prefix=<prefix>]
	 * : Custom ElasticPress prefix
	 *
	 * [--yes]
	 * : Skip confirmation needed by `--setup`
	 *
	 * [--post-lang]
	 * : Choose which language include in the index
	 *
	 * @param array $args Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 *
	 * @return void
	 *
	 * @since 4.4.0
	 */
	public function sync( $args, $assoc_args ): void {
		$languages = [];
		if ( isset( $assoc_args['post-lang'] ) ) {
			$languages = explode( ',', $assoc_args['post-lang'] );
		} else {
			$languages = array_keys( apply_filters( 'wpml_active_languages', [] ) );
		}

		if ( empty( $languages ) ) {
			return;
		}

		$this->manageIndices( $assoc_args, $languages );

		foreach ( $languages as $language ) {
			\WP_CLI::log( \WP_CLI::colorize( '%7%k' . sprintf( esc_html( 'Processing... %s%s%s%s' ), '%N', '%3%k ', $language, ' %n' ) . '%n' ) );
			$assoc_args['post-lang'] = $language;
			if ( version_compare( EP_VERSION, '4.4.0', '<' ) ) {
				parent::index( $args, $assoc_args );
			} else {
				parent::sync( $args, $assoc_args );
			}
		}
		\WP_CLI::log( \WP_CLI::colorize( '%2%kWPML ElasticPress sync complete%n' ) );
	}

	/**
	 * @param array $assocArgs
	 * @param array $languages
	 *
	 * @return void
	 */
	private function manageIndices( &$assocArgs, $languages ): void {
		$setupOption = \WP_CLI\Utils\get_flag_value( $assocArgs, 'setup', false );

		if ( $setupOption ) {
			\WP_CLI::confirm( esc_html( 'Indexing with the setup flag will recreate anew all your Elasticsearch indices. Are you sure you want to delete your current Elasticsearch indices?' ), $assocArgs );
			$assocArgs['setup'] = false;
			\WP_CLI::log( \WP_CLI::colorize( '%7%k' . sprintf( esc_html( 'Processing... %s%s%s%s' ), '%N', '%3%k ', esc_html( 'deleting and generating new indices' ), ' %n' ) . '%n' ) );
			do_action( 'wpml_ep_regenerate_indices', $languages );
			\WP_CLI::success( esc_html( 'ElasticPress: indices recreated and ready' ) );

			return;
		}

		\WP_CLI::log( \WP_CLI::colorize( '%7%k' . sprintf( esc_html( 'Processing... %s%s%s%s' ), '%N', '%3%k ', esc_html( 'generating missing indices' ), ' %n' ) . '%n' ) );
		do_action( 'wpml_ep_check_indices', $languages );
		\WP_CLI::success( esc_html( 'ElasticPress: indices ready' ) );
	}
}
