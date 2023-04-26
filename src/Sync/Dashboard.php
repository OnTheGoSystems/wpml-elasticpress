<?php

namespace WPML\ElasticPress\Sync;

class Dashboard {
	public function addHooks() {
		add_filter( 'ep_dashboard_index_args', [ $this, 'setDashboardIndexArgs' ] );
	}

	/**
	 * Dashboard index includes all posts
	 *
	 * @param  array $args
	 *
	 * @return array
	 *
	 * @todo The dashboard sync should separate per language
	 * and apply specific analyzer and snowball language.
	 */
	public function setDashboardIndexArgs( $args ) {
		$args['suppress_wpml_where_and_join_filter'] = true;
		return $args;
	}

}
