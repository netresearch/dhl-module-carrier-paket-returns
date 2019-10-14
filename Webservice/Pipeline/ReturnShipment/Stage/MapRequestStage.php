<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Webservice\Pipeline\ReturnShipment\Stage;

use Dhl\PaketReturns\Webservice\Pipeline\ReturnShipment\ArtifactsContainer;
use Dhl\PaketReturns\Webservice\Pipeline\ReturnShipment\RequestDataMapper;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Magento\Shipping\Model\Shipment\ReturnShipment;

/**
 * Class MapRequestStage
 *
 * @package Dhl\PaketReturns\Webservice
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
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
        array_walk($requests, function (ReturnShipment $request, int $requestIndex) use ($artifactsContainer) {
            $apiRequest = $this->requestDataMapper->mapRequest($request);
            $artifactsContainer->addApiRequest((string) $requestIndex, $apiRequest);
        });

        return $requests;
    }
}
