<?php

namespace WPML\ElasticPress\Manager;

use ElasticPress\Elasticsearch;
use ElasticPress\Feature\Documents\Documents;

class Pipelines {
	const DOCUMENTS_PIPELINE_SUFFIX = '-attachment';

	/** @var Elasticsearch */
	private $elasticsearch;

	/** @var Documents */
	private $documentsFeature;

	/**
	 * @param Elasticsearch $elasticsearch
	 * @param Documents     $documentsFeature
	 */
	public function __construct(
		Elasticsearch $elasticsearch,
		Documents     $documentsFeature
	) {
		$this->elasticsearch    = $elasticsearch;
		$this->documentsFeature = $documentsFeature;
	}

	/**
	 * @return void
	 */
	public function createDocumentPipeline(): void {
		$this->documentsFeature->create_pipeline();
	}

	/**
	 * @param  string $indexName
	 *
	 * @return bool
	 */
	public function documentPipelineExists( string $indexName ): bool {
		return $this->pipelineExists( $indexName . self::DOCUMENTS_PIPELINE_SUFFIX );
	}

	/**
	 * @param  string $pipelineId
	 *
	 * @return bool
	 */
	public function pipelineExists( string $pipelineId ): bool {
		$request = $this->elasticsearch->get_pipeline($pipelineId);

		// 200 means the pipeline exists.
		if ( 200 === wp_remote_retrieve_response_code( $request ) ) {
			return true;
		}

		return false;
	}
}
