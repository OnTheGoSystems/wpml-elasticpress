<?php


namespace WPML\ElasticPress;


class Command extends \ElasticPress\Command {

	/**
	 * I have to override it to add `--post-lang` argument
	 *
	 * Index all posts for a site or network wide
	 *
	 * @synopsis [--setup] [--network-wide] [--per-page] [--nobulk] [--show-errors] [--offset] [--indexables] [--show-bulk-errors] [--show-nobulk-errors] [--post-type] [--include] [--post-ids] [--ep-host] [--ep-prefix] [--post-lang]
	 * @param  array  $args  Positional CLI args.
	 * @param  array  $assoc_args  Associative CLI args.
	 *
	 * @since 0.1.2
	 */
	public function index( $args, $assoc_args ) {
		return parent::index( $args, $assoc_args );
	}
}