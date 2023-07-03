<?php

namespace WPML\ElasticPress\Manager;

use ElasticPress\Elasticsearch;
use ElasticPress\Indexables;

use WPML\ElasticPress\Constants;

class DashboardStatus {

	/** @var array */
	private $activeLanguages;

	/** @var array */
	private $status = [];

	/**
	 * @param array $activeLanguages
	 */
	public function __construct(
		$activeLanguages
	) {
		$this->activeLanguages = $activeLanguages;
	}

	public function prepare() {
		$storedStatus                      = get_option( Constants::DASHBOARD_INDEX_STATUS, [] );
		$this->status['syncStack']         = isset( $storedStatus['syncStack'] ) && is_array( $storedStatus['syncStack'] )
			? $storedStatus['syncStack']
			: $this->activeLanguages;
		$this->status['currentLanguage']   = ! empty( $storedStatus['currentLanguage'] )
			? $storedStatus['currentLanguage']
			: (	count( $this->status['syncStack'] ) > 0 ? $this->status['syncStack'][0] : '' );
		$this->status['totals']            = isset( $storedStatus['totals'] ) && is_array( $storedStatus['totals'] )
			? $storedStatus['totals']
			: [
				'total'           => 0,
				'synced'          => 0,
				'skipped'         => 0,
				'failed'          => 0,
				'total_time'      => 0,
				'errors'          => [],
				'end_date_time'   => '',
				'start_date_time' => '',
				'end_time_gmt'    => 0,
				'method'          => 'web',
				'is_full_sync'    => false
			];
		$this->status['putMapping']        = isset( $storedStatus['putMapping'] )
			? $storedStatus['putMapping']
			: false;
		$this->status['indexablesToReset'] = isset( $storedStatus['indexablesToReset'] )
			? $storedStatus['indexablesToReset']
			: [];
	}

	/**
	 * @param  string $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		if ( array_key_exists( $key, $this->status ) ) {
			return $this->status[ $key ];
		}
		return null;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set( $key, $value ) {
		if ( array_key_exists( $key, $this->status ) ) {
			$this->status[ $key ] = $value;
		}
	}

	public function store() {
		update_option( Constants::DASHBOARD_INDEX_STATUS, $this->status );
	}

	public function delete() {
		delete_option( Constants::DASHBOARD_INDEX_STATUS );
		$this->status = [];
	}

	/**
	 * @param string[] $indexableSlugs
	 */
	public function logIndexablesToReset( $indexableSlugs ) {
		$this->set('indexablesToReset', array_unique( array_merge(
			$this->status['indexablesToReset'],
			$indexableSlugs
		) ) );
	}

	/**
	 * @param array $totals
	 */
	public function logTotals( $totals ) {
		// Log incremental counters
		$intIncremental = [ 'total', 'synced', 'skipped', 'failed' ];
		array_map( function( $key ) use ( $totals ) {
			$this->status['totals'][ $key ] += empty( $totals[ $key ] ) ? 0 : (int) $totals[ $key ];
		}, $intIncremental );

		// log incremental floating values
		$floatIncremental = [ 'total_time' ];
		array_map( function( $key ) use ( $totals ) {
			$this->status['totals'][ $key ] += empty( $totals[ $key ] ) ? 0 : (float) $totals[ $key ];
		}, $floatIncremental );

		// Log merging lists
		$arrayMergers = [ 'errors' ];
		array_map( function( $key ) use ( $totals ) {
			$this->status['totals'][ $key ] = array_merge(
				$this->status['totals'][ $key ],
				empty( $totals[ $key ] ) ? [] : $totals[ $key ]
			);
		}, $arrayMergers );

		// Log las value provided
		$replacers = [ 'end_date_time', 'end_time_gmt' ];
		array_map( function( $key ) use ( $totals ) {
			$this->status['totals'][ $key ] = empty( $partialTotals['end_date_time'] )
			? $this->status['totals'][ $key ]
			: $totals[ $key ];
		}, $replacers );

		// Keep first value provided
		$keepers = [ 'start_date_time' ];
		array_map( function( $key ) use ( $totals ) {
			$this->status['totals'][ $key ] = empty( $this->status['totals'][ $key ] ) && ! empty( $totals[ $key ] )
				? $totals[ $key ]
				: $this->status['totals'][ $key ];
		}, $keepers );

		$this->status['totals']['is_full_sync'] = $this->status['putMapping'];
	}

	public function resetForNextLanguage() {
		$this->status['syncStack']          = array_values( array_diff(
			$this->status['syncStack'],
			[ $this->status['currentLanguage'] ]
		) );
		$this->status['currentLanguage']    = '';
		$this->status['indexablesToReset']  = [];
	}

}
