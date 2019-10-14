<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Webservice\Pipeline\ReturnShipment\Stage;

use Dhl\PaketReturns\Webservice\Pipeline\ReturnShipment\ArtifactsContainer;
use Dhl\PaketReturns\Webservice\Pipeline\ReturnShipment\ResponseDataMapper;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Magento\Shipping\Model\Shipment\ReturnShipment;

/**
 * Class MapResponseStage
 *
 * @package Dhl\PaketReturns\Webservice
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class MapResponseStage implements CreateShipmentsStageInterface
{
    /**
     * @var ResponseDataMapper
     */
    private $responseDataMapper;

    /**
     * MapResponseStage constructor.
     *
     * @param ResponseDataMapper $responseDataMapper
     */
    public function __construct(ResponseDataMapper $responseDataMapper)
    {
        $this->responseDataMapper = $responseDataMapper;
    }

    /**
     * Transform collected results into response objects suitable for processing by the core.
     *
     * The `sequence_number` property is set to the shipment request packages during request mapping.
     *
     * @param ReturnShipment[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     *
     * @return ReturnShipment[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $errors = $artifactsContainer->getErrors();
        $apiResponses = $artifactsContainer->getApiResponses();

        foreach ($errors as $requestIndex => $message) {
            // validation error or negative response received from webservice
            $message = __('Label could not be created: %1.', $message);
            $response = $this->responseDataMapper->createErrorResponse((string) $requestIndex, $message);
            $artifactsContainer->addErrorResponse((string) $requestIndex, $response);
        }

        foreach ($apiResponses as $requestIndex => $apiResponse) {
            // positive response received from webservice
            $response = $this->responseDataMapper->createLabelResponse((string) $requestIndex, $apiResponse);
            $artifactsContainer->addLabelResponse((string) $requestIndex, $response);
        }

        return $requests;
    }
}
