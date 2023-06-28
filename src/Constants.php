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

	// TODO Decide if this is -1 or 5 based on whether our logic runs nicely on delete_post
	// based on what WPML does with translations for deleting posts
	const EARLY_HOOK_PRIORITY = -1;
	const LATE_HOOK_PRIORITY  = 1999;

}
