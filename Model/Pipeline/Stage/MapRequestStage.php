<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline\Stage;

use Dhl\PaketReturns\Model\Pipeline\ArtifactsContainer;
use Dhl\PaketReturns\Model\Pipeline\RequestDataMapper;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentException;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Magento\Shipping\Model\Shipment\ReturnShipment;

class MapRequestStage implements CreateShipmentsStageInterface
{
    /**
     * @var RequestDataMapper
     */
    private $requestDataMapper;

    /**
     * MapRequestStage constructor.
     *
     * @param RequestDataMapper $requestDataMapper
     */
    public function __construct(RequestDataMapper $requestDataMapper)
    {
        $this->requestDataMapper = $requestDataMapper;
    }

    /**
     * Transform core shipment return requests into request objects suitable for the label return API.
     *
     * @param ReturnShipment[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     *
     * @return ReturnShipment[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $callback = function (ReturnShipment $request, int $requestIndex) use ($artifactsContainer) {
            try {
                $apiRequest = $this->requestDataMapper->mapRequest($request);
                $artifactsContainer->addApiRequest((string) $requestIndex, $apiRequest);

                return true;
            } catch (ReturnShipmentException $exception) {
                $artifactsContainer->addError((string) $requestIndex, $exception->getMessage());

                return false;
            }
        };

        // Pass on only the shipment requests that could be mapped
        return array_filter($requests, $callback, ARRAY_FILTER_USE_BOTH);
    }
}
