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
        $apiRequests = $artifactsContainer->getApiRequests();
        if (!empty($apiRequests)) {
            $returnLabelService = $this->returnLabelServiceFactory->create([
                'storeId' => $artifactsContainer->getStoreId(),
            ]);

            foreach ($apiRequests as $requestIndex => $apiRequest) {
                try {
                    $labelConfirmation = $returnLabelService->bookLabel($apiRequest);
                    $artifactsContainer->addApiResponse((string) $requestIndex, $labelConfirmation);
                } catch (DetailedServiceException $exception) {
                    $artifactsContainer->addError((string) $requestIndex, $exception->getMessage());
                } catch (ServiceException $exception) {
                    $artifactsContainer->addError((string) $requestIndex, 'Web service request failed.');
                }
            }
        }

        return $requests;
    }
}
