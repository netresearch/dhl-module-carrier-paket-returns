<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Webservice;

use Dhl\PaketReturns\Model\ReturnShipmentResponse\ErrorResponse;
use Dhl\PaketReturns\Model\ReturnShipmentResponse\LabelResponse;
use Dhl\PaketReturns\Webservice\Pipeline\ReturnShipment\ArtifactsContainer;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsPipelineInterface;
use Magento\Shipping\Model\Shipment\ReturnShipment;

/**
 * Class ApiGateway
 *
 * Magento carrier-aware wrapper around the DHL Paket Returns API SDK.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
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

    /**
     * ApiGateway constructor.
     *
     * @param CreateShipmentsPipelineInterface $pipeline
     * @param int $storeId
     */
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
