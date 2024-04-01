<?php

namespace WPML\ElasticPress\Sync;

use ElasticPress\Indexables;
use ElasticPress\IndexHelper;

use WPML\ElasticPress\Manager\Indices;
use WPML\ElasticPress\Manager\DashboardStatus;

use WPML\ElasticPress\Traits\ManageIndexables;

abstract class Dashboard {

	use ManageIndexables;

	/** @var Indexables */
	private $indexables;

	/** @var Indices */
	protected $indicesManager;

	/** @var DashboardStatus */
	protected $status;

	/** @var array */
	protected $activeLanguages;

	/** @var string */
	private $defaultLanguage;

	/** @var string */
	private $currentLanguage = '';

	/** @var array */
	private $fullIndexArgs = [];

	/**
	 * @param Indexables      $indexables
	 * @param Indices         $indicesManager
	 * @param DashboardStatus $status
	 * @param array           $activeLanguages
	 * @param string          $defaultLanguage
	 */
	public function __construct(
		Indexables      $indexables,
		Indices         $indicesManager,
		DashboardStatus $status,
		$activeLanguages,
		$defaultLanguage
	) {
		$this->indexables            = $indexables;
		$this->indicesManager        = $indicesManager;
		$this->status                = $status;
		$this->activeLanguages       = $activeLanguages;
		$this->defaultLanguage       = $defaultLanguage;
	}

	abstract public function addHooks();

	/**
	 * @param array $args
	 */
	protected function setFullIndexArgs( $args ) {
		$this->fullIndexArgs = $args;
	}

	protected function setUpAndRun() {
		$this->prepare();
		if ( empty( $this->status->get('currentLanguage') ) ) {
			return;
		}
		$this->maybePutMapping();
		$this->beforeFullIndex();

		// This happens on an AJAX call, hence on admin: force the display-as-translated snippet in queries
		add_filter( 'wpml_should_use_display_as_translated_snippet', '__return_true' );

		$this->runFullIndex( $this->fullIndexArgs );
	}

	protected function tearDown() {
		$this->indicesManager->clearCurrentIndexLanguage();
		$this->status->delete();
	}

	private function setCurrentLanguage() {
		do_action( 'wpml_switch_language', $this->currentLanguage );
		$this->indicesManager->setCurrentIndexLanguage( $this->currentLanguage );
	}

	private function clearCurrentLanguage() {
		do_action( 'wpml_switch_language', null );
		$this->indicesManager->clearCurrentIndexLanguage();
	}

	private function prepare() {
		$this->status->prepare();
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
		$this->setCurrentLanguage();
		$this->status->logIndexablesToReset( $this->deactivateIndexables() );
	}

	/**
	 * @param array $forcedArgs
	 */
	private function runFullIndex( $forcedArgs = [] ) {
		$args = array_merge(
			[
				'method'        => 'dashboard',
				'network_wide'  => 0,
				'show_errors'   => true,
			],
			$forcedArgs
		);

    $args['put_mapping']   = false;
    $args['output_method'] = [ $this, 'indexOutput' ];

		IndexHelper::factory()->full_index( $args );
	}

	private function syncComplete() {
		$message = [
			'message'    => 'Sync complete',
			'index_meta' => null,
			'totals'     => $this->status->get('totals'),
			'status'     => 'success'
		];
		$this->tearDown();
		wp_send_json_success( $message );
		exit;
	}

	private function markLanguageAsComplete() {
		$this->clearCurrentLanguage();
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
			'method'      => 'web',
			'totals'      => $this->status->get('totals'),
			'sync_stack'  => [],
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
		if ( empty( $message['message'] ) ) {
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
