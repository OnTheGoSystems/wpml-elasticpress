<?php

namespace WPML\ElasticPress\Sync;

use ElasticPress\Utils;

use WPML\ElasticPress\Sync\Dashboard;

/**
 * Before ElasticPress 5.0.0 the Dashboard sync was run over AJAX.
 */
class DashboardAjax extends Dashboard {

	public function addHooks() {
		if ( 0 === count( $this->activeLanguages ) ) {
			return;
		}

		add_action( 'wp_ajax_ep_index', [ $this, 'action_wp_ajax_ep_index' ], 9 );
		add_action( 'wp_ajax_ep_cancel_index', [ $this, 'action_wp_ajax_ep_cancel_index' ], 9 );
	}

  private function isDashboardSync() {
		if ( ! check_ajax_referer( 'ep_dashboard_nonce', 'nonce', false ) || ! EP_DASHBOARD_SYNC ) {
			wp_send_json_error( null, 403 );
			exit;
		}

		$index_meta = Utils\get_indexing_status();

		if ( isset( $index_meta['method'] ) && 'cli' === $index_meta['method'] ) {
			return false;
		}

		return true;
	}

  public function action_wp_ajax_ep_index() {
		if ( false === $this->isDashboardSync() ) {
			return;
		}
		$this->setUpAndRun();
	}

  public function action_wp_ajax_ep_cancel_index() {
		if ( false === $this->isDashboardSync() ) {
			return;
		}
		$this->tearDown();
	}

}
