<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline;

use Magento\Shipping\Model\Shipment\ReturnShipment;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsPipelineInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentResponseProcessorInterface;

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
     * @var ShipmentResponseProcessorInterface
     */
    private $responseProcessor;

    /**
     * @var int
     */
    private $storeId;

    public function __construct(
        CreateShipmentsPipelineInterface $pipeline,
        ShipmentResponseProcessorInterface $responseProcessor,
        int $storeId
    ) {
        $this->pipeline = $pipeline;
        $this->responseProcessor = $responseProcessor;
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
     * @return ShipmentErrorResponseInterface[]|LabelResponseInterface[]
     */
    public function createLabels(array $returnShipmentRequests): array
    {
        /** @var ArtifactsContainer $artifactsContainer */
        $artifactsContainer = $this->pipeline->run($this->storeId, $returnShipmentRequests);

        $this->responseProcessor->processResponse(
            $artifactsContainer->getLabelResponses(),
            $artifactsContainer->getErrorResponses()
        );

        return array_merge($artifactsContainer->getErrorResponses(), $artifactsContainer->getLabelResponses());
    }
}
