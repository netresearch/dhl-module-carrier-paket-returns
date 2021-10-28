<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline\Stage;

use Dhl\PaketReturns\Model\Pipeline\ArtifactsContainer;
use Dhl\PaketReturns\Model\Pipeline\ResponseDataMapper;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class MapResponseStage implements CreateShipmentsStageInterface
{
    /**
     * @var ResponseDataMapper
     */
    private $responseDataMapper;

    public function __construct(ResponseDataMapper $responseDataMapper)
    {
        $this->responseDataMapper = $responseDataMapper;
    }

    /**
     * Transform collected results into response objects suitable for processing by the core.
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

        foreach ($errors as $requestIndex => $details) {
            // validation error, request mapping error, or negative response received from webservice
            $message = __('Label could not be created: %1', $details['message']);
            $response = $this->responseDataMapper->createErrorResponse(
                (string) $requestIndex,
                $message,
                $details['shipment']
            );
            $artifactsContainer->addErrorResponse((string) $requestIndex, $response);
        }

        foreach ($apiResponses as $requestIndex => $apiResponse) {
            // positive response received from webservice
            $response = $this->responseDataMapper->createLabelResponse(
                (string) $requestIndex,
                $apiResponse,
                $requests[$requestIndex]->getOrderShipment()
            );
            $artifactsContainer->addLabelResponse((string) $requestIndex, $response);
        }

        return $requests;
    }
}
