<?php

namespace WPML\ElasticPress;

class Constants {

	// Indexable slugs
	const INDEXABLE_SLUG_POST    = 'post';
	const INDEXABLE_SLUG_TERM    = 'term';
	const INDEXABLE_SLUG_USER    = 'user';
	const INDEXABLE_SLUG_COMMENT = 'comment';

	// Query args
	const QUERY_ARG_SUPPRESS_WPML_FILTERS         = 'suppress_wpml_where_and_join_filter';
	const QUERY_ARG_SET_WPML_ELASTICPRESS_FILTERS = 'set_wpml_elasticpress_filters';

	const DASHBOARD_INDEX_STATUS = 'wpml_ep_dashboard_index_status';

}
