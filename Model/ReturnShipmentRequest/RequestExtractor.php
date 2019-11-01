<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Model\ReturnShipmentRequest;

use Dhl\PaketReturns\Model\Adminhtml\System\Config\Source\Procedure;
use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Api\ConfigInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\PackageItemInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\PackageItemInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\ShipperInterface;
use Dhl\ShippingCore\Api\Data\ShipmentRequest\ShipperInterfaceFactory;
use Dhl\ShippingCore\Api\ItemAttributeReaderInterface;
use Dhl\ShippingCore\Api\UnitConverterInterface;
use Dhl\ShippingCore\Util\StreetSplitter;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Shipping\Model\Shipment\ReturnShipment;

/**
 * Class RequestExtractor
 *
 * The original return shipment request is a rather limited DTO with unstructured data (DataObject, array).
 * The extractor and its subtypes offer a well-defined interface to extract the request data and
 * isolates the toxic part of extracting unstructured array data from the shipment request.
 *
 * @package Dhl\PaketReturns\Model
 */
class RequestExtractor
{
    /**
     * @var ReturnShipment
     */
    private $returnShipmentRequest;

    /**
     * @var ConfigInterface
     */
    private $coreConfig;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var StreetSplitter
     */
    private $streetSplitter;

    /**
     * @var UnitConverterInterface
     */
    private $unitConverter;

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
     * @var Collection
     */
    private $countryCollection;

    /**
     * @var ShipperInterface
     */
    private $shipper;

    /**
     * @var PackageItemInterface[]
     */
    private $items = [];

    /**
     * @var string[]
     */
    private $countryMap = [];

    /**
     * RequestExtractor constructor.
     *
     * @param ReturnShipment $returnShipmentRequest
     * @param ConfigInterface $coreConfig
     * @param ModuleConfig $moduleConfig
     * @param StreetSplitter $streetSplitter
     * @param UnitConverterInterface $unitConverter
     * @param ItemAttributeReaderInterface $attributeReader
     * @param ShipperInterfaceFactory $shipperFactory
     * @param PackageItemInterfaceFactory $packageItemFactory
     * @param CollectionFactory $countryCollectionFactory
     */
    public function __construct(
        ReturnShipment $returnShipmentRequest,
        ConfigInterface $coreConfig,
        ModuleConfig $moduleConfig,
        StreetSplitter $streetSplitter,
        UnitConverterInterface $unitConverter,
        ItemAttributeReaderInterface $attributeReader,
        ShipperInterfaceFactory $shipperFactory,
        PackageItemInterfaceFactory $packageItemFactory,
        CollectionFactory $countryCollectionFactory
    ) {
        $this->returnShipmentRequest = $returnShipmentRequest;
        $this->coreConfig = $coreConfig;
        $this->moduleConfig = $moduleConfig;
        $this->streetSplitter = $streetSplitter;
        $this->unitConverter = $unitConverter;
        $this->attributeReader = $attributeReader;
        $this->shipperFactory = $shipperFactory;
        $this->packageItemFactory = $packageItemFactory;
        $this->countryCollection = $countryCollectionFactory->create();
    }

    /**
     * Get three-letter country code from two-letter country code.
     *
     * @param string $iso2Code
     * @return string
     */
    private function getIso3Code(string $iso2Code): string
    {
        if (empty($this->countryMap)) {
            foreach ($this->countryCollection as $country) {
                $this->countryMap[$country->getData('iso2_code')] = $country->getData('iso3_code');
            }
        }

        return $this->countryMap[$iso2Code] ?? '';
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
        /** @var DataObject $shipping */
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
            $street = (string)$this->returnShipmentRequest->getShipperAddressStreet();
            $streetParts = $this->streetSplitter->splitStreet($street);
            $streetData = [
                'streetName' => $streetParts['street_name'],
                'streetNumber' => $streetParts['street_number'],
                'addressAddition' => $streetParts['supplement'],
            ];

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
            ];

            $shipperData = array_merge($shipperData, $streetData);
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
            $orderItemsData = [];

            /** @var Item $item */
            foreach ($this->getOrder()->getAllItems() as $item) {
                $orderItemsData[$item->getItemId()] = [
                    'sku' => $item->getSku(),
                    'customs' => [
                        'exportDescription' => $this->attributeReader->getExportDescription($item),
                        'hsCode' => $this->attributeReader->getHsCode($item),
                        'countryOfOrigin' => $this->getIso3Code($this->attributeReader->getCountryOfManufacture($item))
                    ],
                ];
            }

            foreach ($packages as $packageId => $packageData) {
                $packageItems = array_map(
                    function (array $itemData) use ($packageId, $orderItemsData) {
                        $orderItemData = $orderItemsData[$itemData['order_item_id']];
                        $packageItem = $this->packageItemFactory->create(
                            [
                                'orderItemId' => (int)$itemData['order_item_id'],
                                'productId' => (int)$itemData['product_id'],
                                'packageId' => (int)$packageId,
                                'name' => $itemData['name'],
                                'qty' => (float)$itemData['qty'],
                                'weight' => (float)$itemData['weight'],
                                'price' => (float)$itemData['price'],
                                'customsValue' => isset($itemData['customs_value'])
                                    ? (float)$itemData['customs_value']
                                    : null,
                                'sku' => $orderItemData['sku'],
                                'exportDescription' => $orderItemData['customs']['exportDescription'] ?? '',
                                'hsCode' => $orderItemData['customs']['hsCode'] ?? '',
                                'countryOfOrigin' => $orderItemData['customs']['countryOfOrigin'] ?? '',
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
     * Returns the total value of all package items or the package customs value for non-EU shipping.
     *
     * @return float
     */
    public function getPackageAmount(): float
    {
        if (!$this->isEuShipping()) {
            $packageId = $this->returnShipmentRequest->getData('package_id');
            $packages = $this->returnShipmentRequest->getData('packages');
            return (float)$packages[$packageId]['params']['customs_value'];
        }

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
