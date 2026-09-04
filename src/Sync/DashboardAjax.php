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

		// wpmldev-7976: these ride ElasticPress's own dashboard-sync actions at
		// priority 9 and take the request over (they answer and exit), so the
		// host's capability check at priority 10 never runs - isDashboardSync()
		// therefore enforces ElasticPress's own sync capability itself, as the
		// REST twin (DashboardRest) already does.
		\WPML\ElasticPress\Request\Ajax::listen(
			'ep_index',
			[ $this, 'action_wp_ajax_ep_index' ],
			'ElasticPress dashboard sync: per-language index run; verifies the ep_dashboard_nonce and ElasticPress\Utils\get_capability() itself',
			9
		);
		\WPML\ElasticPress\Request\Ajax::listen(
			'ep_cancel_index',
			[ $this, 'action_wp_ajax_ep_cancel_index' ],
			'ElasticPress dashboard sync cancel: verifies the ep_dashboard_nonce and ElasticPress\Utils\get_capability() itself',
			9
		);
	}

  private function isDashboardSync() {
		if ( ! check_ajax_referer( 'ep_dashboard_nonce', 'nonce', false ) || ! EP_DASHBOARD_SYNC ) {
			wp_send_json_error( null, 403 );
			exit;
		}

		if ( ! current_user_can( Utils\get_capability() ) ) {
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
