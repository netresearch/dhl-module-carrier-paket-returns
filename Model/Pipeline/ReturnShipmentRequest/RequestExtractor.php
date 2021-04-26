<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline\ReturnShipmentRequest;

use Dhl\PaketReturns\Model\Adminhtml\System\Config\Source\Procedure;
use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageItemInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageItemInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\ShipperInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\ShipperInterfaceFactory;
use Netresearch\ShippingCore\Api\Util\CountryCodeInterface;
use Netresearch\ShippingCore\Api\Util\ItemAttributeReaderInterface;
use Netresearch\ShippingCore\Api\Util\UnitConverterInterface;

/**
 * Class RequestExtractor
 *
 * The original return shipment request is a rather limited DTO with unstructured data (DataObject, array).
 * The extractor and its subtypes offer a well-defined interface to extract the request data and
 * isolates the toxic part of extracting unstructured array data from the shipment request.
 */
class RequestExtractor
{
    /**
     * @var ReturnShipment
     */
    private $returnShipmentRequest;

    /**
     * @var ShippingConfigInterface
     */
    private $coreConfig;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var UnitConverterInterface
     */
    private $unitConverter;

    /**
     * @var CountryCodeInterface
     */
    private $country;

    /**
     * @var ItemAttributeReaderInterface
     */
    private $attributeReader;

    /**
     * @var ShipperInterfaceFactory
     */
    private $shipperFactory;

    /**
     * @var PackageItemInterfaceFactory
     */
    private $packageItemFactory;

    /**
     * @var ShipperInterface
     */
    private $shipper;

    /**
     * @var PackageItemInterface[]
     */
    private $items = [];

    public function __construct(
        ReturnShipment $returnShipmentRequest,
        ShippingConfigInterface $coreConfig,
        ModuleConfig $moduleConfig,
        UnitConverterInterface $unitConverter,
        CountryCodeInterface $country,
        ItemAttributeReaderInterface $attributeReader,
        ShipperInterfaceFactory $shipperFactory,
        PackageItemInterfaceFactory $packageItemFactory
    ) {
        $this->returnShipmentRequest = $returnShipmentRequest;
        $this->coreConfig = $coreConfig;
        $this->moduleConfig = $moduleConfig;
        $this->unitConverter = $unitConverter;
        $this->country = $country;
        $this->attributeReader = $attributeReader;
        $this->shipperFactory = $shipperFactory;
        $this->packageItemFactory = $packageItemFactory;
    }

    /**
     * Get three-letter country code from two-letter country code.
     *
     * @param string $iso2Code
     * @return string
     */
    private function getIso3Code(string $iso2Code): string
    {
        try {
            return $this->country->getIso3Code($iso2Code);
        } catch (NoSuchEntityException $exception) {
            return '';
        }
    }

    /**
     * Extract the store ID as assigned to the current shipment (where the order was initially placed).
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return (int)$this->returnShipmentRequest->getData('store_id');
    }

    /**
     * Extract order from return shipment request.
     *
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->returnShipmentRequest->getOrderShipment()->getOrder();
    }

    /**
     * Returns the billing number (= customer reference number).
     *
     * @return string
     */
    public function getBillingNumber(): string
    {
        $storeId = $this->getStoreId();
        $ekp = $this->moduleConfig->getEkp($storeId);

        $shipperCountry = $this->returnShipmentRequest->getShipperAddressCountryCode();
        $recipientCountry = $this->returnShipmentRequest->getRecipientAddressCountryCode();

        if ($shipperCountry === $recipientCountry) {
            $procedure = Procedure::PROCEDURE_RETURNSHIPMENT_NATIONAL;
        } else {
            $procedure = Procedure::PROCEDURE_RETURNSHIPMENT_INTERNATIONAL;
        }

        $participationNumbers = $this->moduleConfig->getParticipations($storeId);
        $participation = $participationNumbers[$procedure] ?? '';

        return $ekp . $procedure . $participation;
    }

    /**
     * Returns the reference number to identify the return shipment.
     *
     * @return string RMA increment ID if available, order increment ID otherwise.
     */
    public function getReferenceNumber(): string
    {
        $shipping = $this->returnShipmentRequest->getOrderShipment();
        $rma = $shipping->getData('rma');

        if ($rma instanceof DataObject && $rma->getData('increment_id')) {
            return (string)$rma->getData('increment_id');
        }

        return $this->getOrder()->getIncrementId();
    }

    /**
     * Returns the receiver id from module configuration.
     *
     * @return string
     */
    public function getReceiverId(): string
    {
        $storeId = $this->getStoreId();
        $receiverIds = $this->moduleConfig->getReceiverIds($storeId);

        return $receiverIds[$this->returnShipmentRequest->getShipperAddressCountryCode()] ?? '';
    }

    /**
     * Returns the shipper (consumer) email address.
     *
     * @return string
     */
    public function getContactEmail(): string
    {
        return (string)$this->returnShipmentRequest->getData('shipper_email');
    }

    /**
     * Returns the shipper (consumer) phone number.
     *
     * @return string
     */
    public function getContactPhoneNumber(): string
    {
        return (string)$this->returnShipmentRequest->getShipperContactPhoneNumber();
    }

    /**
     * Extract shipper from shipment request.
     *
     * @return ShipperInterface
     */
    public function getShipper(): ShipperInterface
    {
        if (empty($this->shipper)) {
            $shipperData = [
                'contactPersonName' => (string)$this->returnShipmentRequest->getShipperContactPersonName(),
                'contactPersonFirstName' => (string)$this->returnShipmentRequest->getShipperContactPersonFirstName(),
                'contactPersonLastName' => (string)$this->returnShipmentRequest->getShipperContactPersonLastName(),
                'contactCompanyName' => (string)$this->returnShipmentRequest->getShipperContactCompanyName(),
                'contactEmail' => (string)$this->returnShipmentRequest->getData('shipper_email'),
                'contactPhoneNumber' => (string)$this->returnShipmentRequest->getShipperContactPhoneNumber(),
                'street' => [
                    $this->returnShipmentRequest->getShipperAddressStreet1(),
                    $this->returnShipmentRequest->getShipperAddressStreet2(),
                ],
                'city' => (string)$this->returnShipmentRequest->getShipperAddressCity(),
                'state' => (string)$this->returnShipmentRequest->getShipperAddressStateOrProvinceCode(),
                'postalCode' => (string)$this->returnShipmentRequest->getShipperAddressPostalCode(),
                'countryCode' => $this->getIso3Code(
                    (string)$this->returnShipmentRequest->getShipperAddressCountryCode()
                ),
                'streetName' => (string)$this->returnShipmentRequest->getData('street_name'),
                'streetNumber' => (string)$this->returnShipmentRequest->getData('street_number'),
                'addressAddition' => '',
            ];

            $this->shipper = $this->shipperFactory->create($shipperData);
        }

        return $this->shipper;
    }

    /**
     * Obtain all items from all packages.
     *
     * @return PackageItemInterface[]
     */
    public function getAllItems(): array
    {
        if (empty($this->items)) {
            $allItems = [];
            $packages = $this->returnShipmentRequest->getData('packages');

            $orderItems = $this->getOrder()->getAllItems();
            $orderItemIds = array_map(function (Item $item) {
                return $item->getId();
            }, $orderItems);
            $orderItems = array_combine($orderItemIds, $orderItems);

            foreach ($packages as $packageId => $packageData) {
                $packageItems = array_map(
                    function (array $itemData) use ($packageId, $orderItems) {
                        /** @var Item $orderItem */
                        $orderItem = $orderItems[$itemData['order_item_id']];
                        $countryOfManufacture = $this->attributeReader->getCountryOfManufacture($orderItem);

                        $packageItem = $this->packageItemFactory->create(
                            [
                                'orderItemId' => (int) $itemData['order_item_id'],
                                'productId' => (int) $itemData['product_id'],
                                'packageId' => (int) $packageId,
                                'name' => $itemData['name'],
                                'qty' => (float) $itemData['qty'],
                                'weight' => (float) $itemData['weight'],
                                'price' => (float) $itemData['price'],
                                'customsValue' => isset($itemData['customs_value'])
                                    ? (float) $itemData['customs_value']
                                    : null,
                                'sku' => $orderItem->getSku(),
                                'countryOfOrigin' => $this->getIso3Code($countryOfManufacture),
                                'exportDescription' => $this->attributeReader->getExportDescription($orderItem),
                                'hsCode' => $this->attributeReader->getHsCode($orderItem),
                            ]
                        );

                        return $packageItem;
                    },
                    $packageData['items']
                );

                $allItems[] = $packageItems;
            }

            $this->items = array_merge(...$allItems);
        }

        return $this->items;
    }

    /**
     * Obtain all items for the current package.
     *
     * @return PackageItemInterface[]
     */
    public function getPackageItems(): array
    {
        $packageId = $this->returnShipmentRequest->getData('package_id');
        $items = array_filter(
            $this->getAllItems(),
            static function (PackageItemInterface $item) use ($packageId) {
                return ($packageId === $item->getPackageId());
            }
        );

        return $items;
    }

    /**
     * Returns the total value of all package items. We use the amount of
     * all items including tax and not the the package customs value.
     *
     * @return float
     */
    public function getPackageAmount(): float
    {
        $totalAmount = array_reduce(
            $this->getPackageItems(),
            static function (float $totalAmount, PackageItemInterface $item) {
                $totalAmount += $item->getQty() * $item->getPrice();
                return $totalAmount;
            },
            0
        );

        return $totalAmount;
    }

    /**
     * Extract package weight from shipment request, convert to gram.
     *
     * @return float
     */
    public function getPackageWeight(): float
    {
        $packageId = $this->returnShipmentRequest->getData('package_id');
        $packages = $this->returnShipmentRequest->getData('packages');
        $weightUnit = $packages[$packageId]['params']['weight_units'];

        return $this->unitConverter->convertWeight(
            (float)$this->returnShipmentRequest->getPackageWeight(),
            $weightUnit,
            \Zend_Measure_Weight::GRAM
        );
    }

    /**
     * Convert item weight to gram.
     *
     * @param PackageItemInterface $packageItem
     * @return float
     */
    public function getItemWeight(PackageItemInterface $packageItem): float
    {
        $packageId = $this->returnShipmentRequest->getData('package_id');
        $packages = $this->returnShipmentRequest->getData('packages');
        $weightUnit = $packages[$packageId]['params']['weight_units'];

        return $this->unitConverter->convertWeight($packageItem->getWeight(), $weightUnit, \Zend_Measure_Weight::GRAM);
    }

    /**
     * Obtain a list of shipment increment IDs that originally contained item(s) to be returned .
     *
     * return string[]
     */
    public function getOriginalShipmentNumbers(): array
    {
        // get all order item ids contained in the current return
        $returnItems = $this->getPackageItems();
        $orderItemIds = array_map(
            static function (PackageItemInterface $packageItem) {
                return $packageItem->getOrderItemId();
            },
            $returnItems
        );

        // find shipments that contained these order item ids
        $fnFilter = static function (ShipmentInterface $shipment) use ($orderItemIds) {
            foreach ($shipment->getItems() as $item) {
                if (in_array($item->getOrderItemId(), $orderItemIds, false)) {
                    return true;
                }
            }

            return false;
        };

        $shipments = array_filter($this->getOrder()->getShipmentsCollection()->getItems(), $fnFilter);
        $incrementIds = array_map(
            static function (ShipmentInterface $shipment) {
                return $shipment->getIncrementId();
            },
            $shipments
        );

        return $incrementIds;
    }

    /**
     * Obtain carrier title of the original order.
     *
     * @return string Carrier title if available, carrier code otherwise.
     */
    public function getOriginalCarrier(): string
    {
        $carrierCode = strtok((string)$this->getOrder()->getShippingMethod(), '_');
        $carrierTitle = $this->moduleConfig->getCarrierTitle($carrierCode, $this->getOrder()->getStoreId());
        return $carrierTitle ?: $carrierCode;
    }

    /**
     * Check if the return is shipped from a EU country.
     *
     * @return bool
     */
    public function isEuShipping(): bool
    {
        return in_array(
            $this->returnShipmentRequest->getShipperAddressCountryCode(),
            $this->coreConfig->getEuCountries($this->getStoreId()),
            true
        );
    }
}
