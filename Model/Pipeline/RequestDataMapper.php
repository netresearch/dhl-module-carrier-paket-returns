<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline;

use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentRequest\RequestExtractor;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentRequest\RequestExtractorFactory;
use Dhl\Sdk\Paket\Retoure\Api\ReturnLabelRequestBuilderInterface;
use Dhl\Sdk\Paket\Retoure\Exception\RequestValidatorException;
use Magento\Shipping\Model\Shipment\ReturnShipment;

class RequestDataMapper
{
    /**
     * Utility for extracting data from request.
     *
     * @var RequestExtractorFactory
     */
    private $requestExtractorFactory;

    /**
     * The return label request builder.
     *
     * @var ReturnLabelRequestBuilderInterface
     */
    private $requestBuilder;

    public function __construct(
        ReturnLabelRequestBuilderInterface $requestBuilder,
        RequestExtractorFactory $requestExtractorFactory
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->requestExtractorFactory = $requestExtractorFactory;
    }

    /**
     * Map the Magento return shipment request to an SDK request object using the SDK request builder.
     *
     * @param ReturnShipment $request The return shipment request
     *
     * @return \JsonSerializable
     * @throws ReturnShipmentException
     */
    public function mapRequest(ReturnShipment $request): \JsonSerializable
    {
        $requestExtractor = $this->requestExtractorFactory->create(['returnShipmentRequest' => $request]);

        $this->requestBuilder->setAccountDetails(
            $requestExtractor->getReceiverId(),
            $requestExtractor->getBillingNumber()
        );
        $this->requestBuilder->setShipmentReference($requestExtractor->getReferenceNumber());

        $this->requestBuilder->setShipperAddress(
            $requestExtractor->getShipper()->getContactPersonName(),
            $requestExtractor->getShipper()->getCountryCode(),
            $requestExtractor->getShipper()->getPostalCode(),
            $requestExtractor->getShipper()->getCity(),
            $requestExtractor->getShipper()->getStreetName(),
            $requestExtractor->getShipper()->getStreetNumber(),
            $requestExtractor->getShipper()->getContactCompanyName(),
            null,
            $requestExtractor->getShipper()->getState()
        );

        $this->requestBuilder->setShipperContact(
            $requestExtractor->getContactEmail(),
            $requestExtractor->getContactPhoneNumber()
        );

        $this->requestBuilder->setPackageDetails(
            (int) $requestExtractor->getPackageWeight(),
            $requestExtractor->getPackageAmount()
        );

        if (!$requestExtractor->isEuShipping()) {
            $this->requestBuilder->setCustomsDetails(
                $requestExtractor->getOrder()->getBaseCurrencyCode(),
                implode(', ', $requestExtractor->getOriginalShipmentNumbers()),
                $requestExtractor->getOriginalCarrier()
            );

            foreach ($requestExtractor->getPackageItems() as $item) {
                $this->requestBuilder->addCustomsItem(
                    (int)$item->getQty(),
                    $item->getExportDescription() ?: $item->getName(),
                    $item->getPrice(),
                    (int)$requestExtractor->getItemWeight($item),
                    $item->getSku(),
                    $item->getCountryOfOrigin(),
                    $item->getHsCode()
                );
            }
        }

        try {
            return $this->requestBuilder->create();
        } catch (RequestValidatorException $exception) {
            $message = __('Return shipment request could not be created: %1', $exception->getMessage());
            throw new ReturnShipmentException($message);
        }
    }
}
