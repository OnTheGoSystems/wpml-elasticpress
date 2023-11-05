<?php

namespace WPML\ElasticPress\Sync;

use ElasticPress\Utils;

use WPML\ElasticPress\Sync\Dashboard;

/**
 * After ElasticPress 5.0.0 the Dashboard sync was run over the REST API.
 */
class DashboardRest extends Dashboard {

	public function addHooks() {
		if ( 0 === count( $this->activeLanguages ) ) {
			return;
		}

		add_filter( 'rest_request_before_callbacks', [ $this, 'rest_request_before_callbacks' ], 10, 3 ) ;
	}

	private function isSyncMethod( $method, $request ) {
		if ( $method !== $request->get_method() ) {
			return false;
		}

		$route = $request->get_route();
		if ( preg_match( "/^\/elasticpress\/v\d+\/sync$/", $route ) ) {
			return true;
		}
	
		return false;
	}

	/**
	 * @param  \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed $response
	 * @param  array                                               $handler
	 * @param  \WP_REST_Request                                    $request
	 *
	 * @return \WP_REST_Response|\WP_HTTP_Response|\WP_Error|mixed
	 */
	public function rest_request_before_callbacks( $response, $handler, $request  ) {
		$capability = Utils\get_capability();

		if ( ! current_user_can( $capability ) ) {
			return $response;
		}
		
		if ( $this->isSyncMethod( 'POST', $request ) ) {
			$this->setFullIndexArgs( $request->get_params() );
			$this->setUpAndRun();
		}

		if ( $this->isSyncMethod( 'DELETE', $request ) ) {
			$this->tearDown();
		}

		return $response;

	}

}