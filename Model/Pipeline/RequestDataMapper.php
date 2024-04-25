<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline;

use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentRequest\RequestExtractorFactory;
use Dhl\Sdk\ParcelDe\Returns\Api\ReturnLabelRequestBuilderInterface;
use Dhl\Sdk\ParcelDe\Returns\Exception\RequestValidatorException;
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

        $this->requestBuilder->setReceiverId($requestExtractor->getReceiverId());
        $this->requestBuilder->setCustomerReference($requestExtractor->getOrder()->getRealOrderId());
        $this->requestBuilder->setShipmentReference($requestExtractor->getReferenceNumber());

        $this->requestBuilder->setShipper(
            $requestExtractor->getShipper()->getContactPersonName(),
            $requestExtractor->getShipper()->getCountryCode(),
            $requestExtractor->getShipper()->getPostalCode(),
            $requestExtractor->getShipper()->getCity(),
            $requestExtractor->getShipper()->getStreetName(),
            $requestExtractor->getShipper()->getStreetNumber(),
            $requestExtractor->getShipper()->getContactCompanyName(),
            null,
            [],
            $requestExtractor->getShipper()->getState()
        );

        $this->requestBuilder->setShipperContact(
            $requestExtractor->getContactEmail(),
            $requestExtractor->getContactPhoneNumber()
        );

        $this->requestBuilder->setPackageValue(
            $requestExtractor->getPackageAmount(),
            $requestExtractor->getOrder()->getBaseCurrencyCode()
        );
        $this->requestBuilder->setPackageWeight(
            (int) $requestExtractor->getPackageWeight(),
            ReturnLabelRequestBuilderInterface::WEIGHT_G
        );

        if (!$requestExtractor->isEuShipping()) {
            foreach ($requestExtractor->getPackageItems() as $item) {
                $this->requestBuilder->addCustomsItem(
                    (int)$item->getQty(),
                    $item->getExportDescription() ?: $item->getName(),
                    $item->getPrice(),
                    $requestExtractor->getOrder()->getBaseCurrencyCode(),
                    (int)$requestExtractor->getItemWeight($item),
                    ReturnLabelRequestBuilderInterface::WEIGHT_G,
                    $item->getCountryOfOrigin(),
                    $item->getHsCode()
                );
            }
        }

        try {
            return $this->requestBuilder->create();
        } catch (RequestValidatorException $exception) {
            $message = __('Return shipment request could not be created: %1', $exception->getMessage());
            throw new ReturnShipmentException($message, $exception);
        }
    }
}
