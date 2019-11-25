<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Model;

use Dhl\PaketReturns\Model\ReturnShipmentResponse\ErrorResponse;
use Dhl\PaketReturns\Model\ReturnShipmentResponse\LabelResponse;
use Dhl\PaketReturns\Webservice\ApiGateway;
use Dhl\PaketReturns\Webservice\ApiGatewayFactory;
use Magento\Shipping\Model\Shipment\ReturnShipment;

/**
 * Class ReturnShipmentManagement
 *
 * Central entry point for creating return order labels.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ReturnShipmentManagement
{
    /**
     * @var ApiGatewayFactory
     */
    private $apiGatewayFactory;

    /**
     * @var ApiGateway[]
     */
    private $apiGateways;

    /**
     * ReturnShipmentManagement constructor.
     *
     * @param ApiGatewayFactory $apiGatewayFactory
     */
    public function __construct(ApiGatewayFactory $apiGatewayFactory)
    {
        $this->apiGatewayFactory = $apiGatewayFactory;
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
            $this->apiGateways[$storeId] = $this->apiGatewayFactory->create(['storeId' => $storeId]);
        }

        return $this->apiGateways[$storeId];
    }

    /**
     * Create return order labels at DHL Paket Returns API.
     *
     * @param ReturnShipment[] $returnShipmentRequests
     *
     * @return ErrorResponse[]|LabelResponse[]
     */
    public function createLabels(array $returnShipmentRequests): array
    {
        if (empty($returnShipmentRequests)) {
            return [];
        }

        $apiRequests = [];
        $apiResults  = [];

        foreach ($returnShipmentRequests as $returnShipmentRequest) {
            $storeId = (int) $returnShipmentRequest->getData('store_id');
            $apiRequests[$storeId][] = $returnShipmentRequest;
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
