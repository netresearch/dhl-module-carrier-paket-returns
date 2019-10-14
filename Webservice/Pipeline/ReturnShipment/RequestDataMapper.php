<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Webservice\Pipeline\ReturnShipment;

use Dhl\PaketReturns\Model\ReturnShipmentRequest\RequestExtractor;
use Dhl\PaketReturns\Model\ReturnShipmentRequest\RequestExtractorFactory;
use Dhl\Sdk\Paket\Retoure\Api\ReturnLabelRequestBuilderInterface;
use Magento\Shipping\Model\Shipment\ReturnShipment;

/**
 * Request mapper.
 *
 * @author Rico Sonntag <rico.sonntag@netresearch.de>
 * @link   https://www.netresearch.de/
 */
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

    /**
     * RequestDataMapper constructor.
     *
     * @param ReturnLabelRequestBuilderInterface $requestBuilder
     * @param RequestExtractorFactory $requestExtractorFactory
     */
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
     */
    public function mapRequest(ReturnShipment $request): \JsonSerializable
    {
        /** @var RequestExtractor $requestExtractor */
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
                $requestExtractor->getOrder()->getOrderCurrency()->getCurrencyCode(),
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

        return $this->requestBuilder->create();
    }
}
