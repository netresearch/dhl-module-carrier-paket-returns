<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\BulkShipment;

use Dhl\PaketReturns\Model\Pipeline\ApiGateway;
use Dhl\PaketReturns\Model\Pipeline\ApiGatewayFactory;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Netresearch\ShippingCore\Api\BulkShipment\ReturnLabelCreationInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentResponseProcessorInterface;

/**
 * Central entry point for creating return order labels.
 */
class ReturnShipmentManagement implements ReturnLabelCreationInterface
{
    /**
     * @var ApiGatewayFactory
     */
    private $apiGatewayFactory;

    /**
     * @var ShipmentResponseProcessorInterface
     */
    private $responseProcessor;

    /**
     * @var ApiGateway[]
     */
    private $apiGateways;

    public function __construct(
        ApiGatewayFactory $apiGatewayFactory,
        ShipmentResponseProcessorInterface $responseProcessor
    ) {
        $this->apiGatewayFactory = $apiGatewayFactory;
        $this->responseProcessor = $responseProcessor;
    }

    /**
     * Create api gateway.
     *
     * API gateways are created with store specific configuration and configured post-processors (bulk or popup).
     *
     * @param int $storeId
     * @return ApiGateway
     */
    private function getApiGateway(int $storeId): ApiGateway
    {
        if (!isset($this->apiGateways[$storeId])) {
            $this->apiGateways[$storeId] = $this->apiGatewayFactory->create(
                [
                    'responseProcessor' => $this->responseProcessor,
                    'storeId' => $storeId,
                ]
            );
        }

        return $this->apiGateways[$storeId];
    }

    /**
     * Create return order labels at DHL Paket Returns API.
     *
     * @param ReturnShipment[] $shipmentRequests
     *
     * @return ShipmentErrorResponseInterface[]|LabelResponseInterface[]
     */
    public function createLabels(array $shipmentRequests): array
    {
        if (empty($shipmentRequests)) {
            return [];
        }

        $apiRequests = [];
        $apiResults  = [];

        foreach ($shipmentRequests as $shipmentRequest) {
            $storeId = (int) $shipmentRequest->getData('store_id');
            $apiRequests[$storeId][] = $shipmentRequest;
        }

        foreach ($apiRequests as $storeId => $storeApiRequests) {
            $api = $this->getApiGateway($storeId);
            $apiResults[$storeId] = $api->createLabels($storeApiRequests);
        }

        if (!empty($apiResults)) {
            // Convert results per store to flat response
            $apiResults = array_reduce($apiResults, 'array_merge', []);
        }

        return $apiResults;
    }
}
