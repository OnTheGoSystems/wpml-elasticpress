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

		// These ride ElasticPress's own dashboard-sync actions at priority 9 and
		// answer the request themselves, so isDashboardSync() checks
		// ElasticPress's own sync capability directly, as DashboardRest already
		// does.
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

		// ElasticPress added get_capability() in 4.5.0. This AJAX sync serves every
		// version below 5.0.0, so older ones fall back to the administrator capability.
		$capability = function_exists( 'ElasticPress\Utils\get_capability' ) ? Utils\get_capability() : 'manage_options';

		if ( ! current_user_can( $capability ) ) {
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
