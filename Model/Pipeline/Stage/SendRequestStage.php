<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline\Stage;

use Dhl\PaketReturns\Model\Pipeline\ArtifactsContainer;
use Dhl\PaketReturns\Model\Webservice\ReturnLabelServiceFactory;
use Dhl\Sdk\Paket\Retoure\Exception\DetailedServiceException;
use Dhl\Sdk\Paket\Retoure\Exception\ServiceException;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class SendRequestStage implements CreateShipmentsStageInterface
{
    /**
     * @var ReturnLabelServiceFactory
     */
    private $returnLabelServiceFactory;

    public function __construct(ReturnLabelServiceFactory $returnLabelServiceFactory)
    {
        $this->returnLabelServiceFactory = $returnLabelServiceFactory;
    }

    /**
     * Send return label request objects to shipment service.
     *
     * @param ReturnShipment[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     *
     * @return ReturnShipment[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        if (empty($requests)) {
            return $requests;
        }

        $labelService = $this->returnLabelServiceFactory->create([
            'storeId' => $artifactsContainer->getStoreId(),
        ]);

        $callback = static function (ReturnShipment $request, int $requestIndex) use ($artifactsContainer, $labelService) {
            try {
                $apiRequest = $artifactsContainer->getApiRequests()[$requestIndex];
                $labelConfirmation = $labelService->bookLabel($apiRequest);
                $artifactsContainer->addApiResponse((string) $requestIndex, $labelConfirmation);

                return true;
            } catch (ServiceException $exception) {
                $message = $exception instanceof DetailedServiceException
                    ? $exception->getMessage()
                    : 'Web service request failed.';
                $artifactsContainer->addError((string) $requestIndex, $request->getOrderShipment(), $message);

                return false;
            }
        };

        // Pass on only the shipment requests that could be booked
        return array_filter($requests, $callback, ARRAY_FILTER_USE_BOTH);
    }
}
