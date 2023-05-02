<?php

namespace WPML\ElasticPress\Sync;

use ElasticPress\Indexables;
use ElasticPress\IndexHelper;
use ElasticPress\Utils;

use WPML\ElasticPress\Manager\Indices;
use WPML\ElasticPress\Manager\DashboardStatus;

use WPML\ElasticPress\Traits\ManageIndexables;
use WPML\ElasticPress\Traits\QueryFilters;

class Dashboard {

	use ManageIndexables;
	use QueryFilters;

	/** @var \wpdb */
	private $wpdb;

	/** @var Indexables */
	private $indexables;

	/** @var Indices */
	private $indicesManager;

	/** @var DashboardStatus */
	private $status;

	/** @var array */
	private $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/** @var string */
	private $currentLanguage = '';

	/**
	 * @param \wpdb           $wpdb
	 * @param Indexables      $indexables
	 * @param Indices         $indicesManager
	 * @param DashboardStatus $status
	 * @param array           $activeLanguages
	 * @param string          $defaultLanguage
	 */
	public function __construct(
		\wpdb           $wpdb,
		Indexables      $indexables,
		Indices         $indicesManager,
		DashboardStatus $status,
		$activeLanguages,
		$defaultLanguage
	) {
		$this->wpdb                  = $wpdb;
		$this->indexables            = $indexables;
		$this->indicesManager        = $indicesManager;
		$this->status                = $status;
		$this->activeLanguages       = $activeLanguages;
		$this->defaultLanguage       = $defaultLanguage;
	}

	public function addHooks() {
		if ( 0 === count( $this->activeLanguages ) ) {
			return;
		}

		add_action( 'wp_ajax_ep_index', [ $this, 'action_wp_ajax_ep_index' ], 9 );
		//add_filter( 'ep_dashboard_index_args', [ $this, 'setQueryArgs' ] );
		add_action( 'wp_ajax_ep_cancel_index', [ $this, 'action_wp_ajax_ep_cancel_index' ], 9 );
	}

	private function setUp() {
		$this->status->prepare();
	}

	private function tearDown() {
		$this->indicesManager->clearCurrentIndexLanguage();
		$this->status->delete();
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

	private function maybePutMapping() {
		$putMapping = ! empty( $_REQUEST['put_mapping'] );

		if ( $putMapping && false === $this->status->get('putMapping') ) {
			$this->indicesManager->clearAllIndices();
			$this->status->set('putMapping', true);
		}

		$this->indicesManager->generateMissingIndices();

		add_filter( 'ep_skip_index_reset', '__return_true' );
	}

	private function beforeFullIndex() {
		$this->currentLanguage = $this->status->get('currentLanguage');
		$this->indicesManager->setCurrentIndexLanguage( $this->currentLanguage );
		$this->setQueryFilters();
		$this->status->logIndexablesToReset( $this->deactivateIndexables() );
	}

	public function action_wp_ajax_ep_cancel_index() {
		if ( false === $this->isDashboardSync() ) {
			return;
		}
		$this->tearDown();
		return;
	}

	public function action_wp_ajax_ep_index() {
		if ( false === $this->isDashboardSync() ) {
			return;
		}
		$this->setUp();
		if ( empty( $this->status->get('currentLanguage') ) ) {
			return;
		}
		$this->maybePutMapping();
		$this->beforeFullIndex();

		IndexHelper::factory()->full_index(
			[
				'method'        => 'dashboard',
				'put_mapping'   => false,
				'output_method' => [ $this, 'indexOutput' ],
				'show_errors'   => true,
				'network_wide'  => 0,
			]
		);
	}

	private function syncComplete() {
		$message = [
			'message' => 'Sync complete',
			'index_meta' => null,
			'totals' => $this->status->get('totals'),
			'status' => 'success'
		];
		$this->tearDown();
		wp_send_json_success( $message );
		exit;
	}

	private function markLanguageAsComplete() {
		$this->indicesManager->clearCurrentIndexLanguage();
		$this->reactivateIndexables( $this->status->get('indexablesToReset') );
		$this->status->resetForNextLanguage();
	}

	/**
	 * @param  array $message
	 *
	 * @return array
	 */
	private function setResponseMapping( $message ) {
		if ( isset( $message['index_meta']['put_mapping'] ) ) {
			$message['index_meta']['put_mapping'] = $this->status->get('putMapping');
		}
		return $message;
	}

	/**
	 * @param  array $message
	 *
	 * @return array
	 */
	private function setLanguageCompletedResponse( $message ) {
		if ( empty( $message['totals'] ) ) {
			return $message;
		}
		// We completed one language
		$this->status->logTotals( $message['totals'] );
		$this->markLanguageAsComplete();

		if ( empty( $this->status->get('syncStack') ) ) {
			// We completed the last language
			$this->syncComplete();
			exit;
		}

		// Hijack the message data so the next language gets processed
		$message['totals']     = [];
		$message['index_meta'] = [
			'method' => 'web',
			'totals' => $this->status->get('totals'),
			'sync_stack' => [],
			'put_mapping' => $this->status->get('putMapping'),
		];
		return $message;
	}

	/**
	 * @param  array $message
	 *
	 * @return array
	 */
	private function setResponseLanguagePrefix( $message ) {
		if ( empty ( $message['message'] ) ) {
			return $message;
		}
		$message['message']  = '[' . $this->currentLanguage . '] ' . $message['message'];
		return $message;
	}

	/**
	 * @param  array $message
	 *
	 * @return array
	 */
	private function setResponseMessage( $message ) {
		$message = $this->setResponseMapping( $message );
		$message = $this->setLanguageCompletedResponse( $message );
		$message = $this->setResponseLanguagePrefix( $message );
		return $message;
	}

	/**
	 * @param array $message
	 */
	public function indexOutput( $message ) {
		$message = $this->setResponseMessage( $message );
		switch ( $message['status'] ) {
			case 'success':
				$this->status->store();
				wp_send_json_success( $message );
				break;

			case 'error':
				$this->status->delete();
				wp_send_json_error( $message );
				break;

			default:
				$this->status->store();
				wp_send_json( [ 'data' => $message ] );
				break;
		}
		exit;
	}

}
