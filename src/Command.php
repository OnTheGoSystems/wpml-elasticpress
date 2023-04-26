<?php


namespace WPML\ElasticPress;


class Command extends \ElasticPress\Command {
	// Extending the native class to add `--post-lang` argument

	/**
	 * Index all posts for a site or network wide
	 *
	 * @synopsis [--setup] [--network-wide] [--per-page] [--nobulk] [--show-errors] [--offset] [--indexables] [--show-bulk-errors] [--show-nobulk-errors] [--post-type] [--include] [--post-ids] [--ep-host] [--ep-prefix] [--post-lang]
	 * @param array  $args  Positional CLI args.
	 * @param array  $assoc_args  Associative CLI args.
	 *
	 * @since 0.1.2
	 * @deprecated on ElasticPress 4.4.0
	 */
	public function index( $args, $assoc_args ) {
		if ( version_compare( EP_VERSION, '4.4.0', '<' ) ) {
			return parent::index( $args, $assoc_args );
		}
		return parent::sync( $args, $assoc_args );
	}

	/**
	 * Sync all posts for a site or network wide
	 *
	 * @synopsis [--setup] [--network-wide] [--per-page] [--nobulk] [--show-errors] [--offset] [--indexables] [--show-bulk-errors] [--show-nobulk-errors] [--post-type] [--include] [--post-ids] [--ep-host] [--ep-prefix] [--post-lang]
	 * @param array  $args  Positional CLI args.
	 * @param array  $assoc_args  Associative CLI args.
	 */
	public function sync( $args, $assoc_args ) {
		return $this->index( $args, $assoc_args );
	}
}
