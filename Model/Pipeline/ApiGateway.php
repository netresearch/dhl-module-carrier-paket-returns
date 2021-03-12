<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline;

use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse\ErrorResponse;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse\LabelResponse;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsPipelineInterface;

/**
 * Magento carrier-aware wrapper around the DHL Paket Returns API SDK.
 */
class ApiGateway
{
    /**
     * @var CreateShipmentsPipelineInterface
     */
    private $pipeline;

    /**
     * @var int
     */
    private $storeId;

    public function __construct(CreateShipmentsPipelineInterface $pipeline, int $storeId)
    {
        $this->pipeline = $pipeline;
        $this->storeId = $storeId;
    }

    /**
     * Convert return shipment requests to label requests, send to API, return result.
     *
     * The mapped result can be
     * - an array of return label pairs or
     * - an array of errors.
     *
     * @param ReturnShipment[] $returnShipmentRequests
     *
     * @return ErrorResponse[]|LabelResponse[]
     */
    public function createLabels(array $returnShipmentRequests): array
    {
        /** @var ArtifactsContainer $artifactsContainer */
        $artifactsContainer = $this->pipeline->run($this->storeId, $returnShipmentRequests);

        return array_merge($artifactsContainer->getErrorResponses(), $artifactsContainer->getLabelResponses());
    }
}
