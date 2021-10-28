<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline\ReturnShipmentRequest;

use Dhl\PaketReturns\Model\Carrier\Paket;
use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment\Item;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Netresearch\ShippingCore\Api\Config\RmaConfigInterface;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Pipeline\ReturnShipmentRequest\RequestModifierInterface;

class RequestModifier implements RequestModifierInterface
{
    /**
     * @var ShippingConfigInterface
     */
    private $config;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var RmaConfigInterface
     */
    private $rmaConfig;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var ShipmentInterface[]
     */
    private $shipments = [];

    public function __construct(
        ShippingConfigInterface $config,
        ModuleConfig $moduleConfig,
        RmaConfigInterface $rmaConfig,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        ShipmentRepositoryInterface $shipmentRepository,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->config = $config;
        $this->moduleConfig = $moduleConfig;
        $this->rmaConfig = $rmaConfig;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder = $filterBuilder;
        $this->shipmentRepository = $shipmentRepository;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @param int $orderId
     * @param int[] $shipmentIds
     * @param int $shipmentItemId
     * @return ShipmentItemInterface
     * @throws NoSuchEntityException
     */
    private function getShipmentItem(int $orderId, array $shipmentIds, int $shipmentItemId): ShipmentItemInterface
    {
        if (!$this->shipments) {
            $orderIdFilter = $this->filterBuilder
                ->setField(ShipmentInterface::ORDER_ID)
                ->setValue($orderId)
                ->setConditionType('eq')
                ->create();
            $shipmentIdFilter = $this->filterBuilder
                ->setField(ShipmentInterface::ENTITY_ID)
                ->setValue($shipmentIds)
                ->setConditionType('in')
                ->create();

            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteria = $searchCriteriaBuilder->addFilter($orderIdFilter)->addFilter($shipmentIdFilter)->create();
            $searchResult = $this->shipmentRepository->getList($searchCriteria);
            $this->shipments = $searchResult->getItems();
        }

        foreach ($this->shipments as $shipment) {
            foreach ($shipment->getItems() as $item) {
                if ((int) $item->getEntityId() === $shipmentItemId) {
                    $weight = $item->getWeight() ?: $this->moduleConfig->getDefaultItemWeight($shipment->getStoreId());
                    $item->setWeight($weight);
                    return $item;
                }
            }
        }

        throw new NoSuchEntityException(__('Shipment item %1 is not available.', $shipmentItemId));
    }

    /**
     * Set general request params.
     *
     * The return shipment may contain items from multiple partial shipments,
     * there is not the one `order_shipment`. We add a fake `order_shipment` to the request
     * object as it is only used as transport object for the original `order` anyway.
     *
     * @param ReturnShipment $request
     * @return void
     */
    private function modifyGeneralParams(ReturnShipment $request)
    {
        $order = $request->getOrderShipment()->getOrder();

        $request->setShippingMethod(Paket::METHOD_CODE);
        $request->setData('base_currency_code', $order->getBaseCurrencyCode());
        $request->setData('store_id', $order->getStoreId());
        $request->setData('is_return', true);
    }

    /**
     * Set package sender.
     *
     * @param ReturnShipment $request
     * @return void
     * @throws LocalizedException
     */
    private function modifyShipper(ReturnShipment $request)
    {
        $origShippingAddress = $request->getOrderShipment()->getOrder()->getShippingAddress();

        $addressData = $request->getData('shipper');
        if (empty($addressData)) {
            throw new LocalizedException(__('Please specify a return shipment sender address.'));
        }

        $name = array_filter([$addressData['firstname'] ?? '', $addressData['lastname'] ?? '']);

        $request->setShipperContactPersonName(implode(' ', $name));
        $request->setShipperContactPersonFirstName($addressData['firstname'] ?? '');
        $request->setShipperContactPersonLastName($addressData['lastname'] ?? '');
        $request->setShipperContactCompanyName($addressData['company'] ?? '');
        $request->setData('shipper_contact_phone_number', $addressData['telephone'] ?? '');
        $request->setShipperAddressStreet(implode(' ', $origShippingAddress->getStreet()));
        $request->setShipperAddressStreet1($origShippingAddress->getStreetLine(1));
        $request->setShipperAddressStreet2($origShippingAddress->getStreetLine(2));
        $request->setShipperAddressCity($addressData['city'] ?? '');
        $request->setShipperAddressStateOrProvinceCode($addressData['region'] ?? '');
        $request->setData('shipper_address_postal_code', $addressData['postcode'] ?? '');
        $request->setShipperAddressCountryCode($addressData['country'] ?? '');
        $request->setData('shipper_email', $addressData['email'] ?? '');

        $request->addData([
            'street_name' => $addressData['street_name'] ?? '',
            'street_number' => $addressData['street_number'] ?? '',
        ]);

        $request->unsetData('shipper');
    }

    /**
     * Set package receiver.
     *
     * The actual merchant return address is configured at the business customer portal
     * and identified via the receiver ID submitted with the web service request.
     * The receiver address is still needed in the return shipment request to determine
     * the correct billing number.
     *
     * @see \Magento\Rma\Helper\Data::getReturnAddressData
     *
     * @param ReturnShipment $request
     * @return void
     */
    private function modifyReceiver(ReturnShipment $request)
    {
        $address = $this->rmaConfig->getReturnAddress($request->getOrderShipment()->getOrder()->getStoreId());
        $street = array_filter([$address['street1'], $address['street2']]);

        $request->setRecipientAddressStreet(implode(' ', $street));
        $request->setRecipientAddressStreet1($address['street1']);
        $request->setRecipientAddressStreet2($address['street2']);
        $request->setRecipientAddressCity($address['city']);
        $request->setRecipientAddressStateOrProvinceCode($address['region_id']);
        $request->setData('recipient_address_postal_code', $address['postcode']);
        $request->setRecipientAddressCountryCode($address['country_id']);
    }

    /**
     * Set package params and items.
     *
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::returnOfShipment
     *
     * @param ReturnShipment $request
     * @return void
     * @throws LocalizedException
     */
    private function modifyPackage(ReturnShipment $request)
    {
        $order = $request->getOrderShipment()->getOrder();
        $shipmentsData = $request->getData('shipments');

        $items = [];
        $totalWeight = 0;
        $totalPrice = 0;

        foreach ($shipmentsData as $shipmentData) {
            foreach ($shipmentData['items'] as $itemId => $qty) {
                if (!$qty) {
                    continue;
                }

                try {
                    /** @var Item $shipmentItem */
                    $shipmentItem = $this->getShipmentItem((int) $order->getId(), array_keys($shipmentsData), $itemId);
                } catch (NoSuchEntityException $exception) {
                    continue;
                }

                $rowAmount = $shipmentItem->getOrderItem()->getBaseRowTotal()
                    - $shipmentItem->getOrderItem()->getBaseDiscountAmount()
                    + $shipmentItem->getOrderItem()->getBaseTaxAmount()
                    + $shipmentItem->getOrderItem()->getbaseDiscountTaxCompensationAmount();
                $itemPrice = $rowAmount / $shipmentItem->getOrderItem()->getQtyOrdered();

                $totalWeight += $qty * $shipmentItem->getWeight();
                $totalPrice += $qty * $itemPrice;

                if (!isset($items[$shipmentItem->getOrderItemId()])) {
                    $items[$shipmentItem->getOrderItemId()] = [
                        'qty' => $qty,
                        'customs_value' => $itemPrice,
                        'price' => $itemPrice,
                        'name' => $shipmentItem->getName(),
                        'weight' => $shipmentItem->getWeight(),
                        'product_id' => $shipmentItem->getProductId(),
                        'order_item_id' => $shipmentItem->getOrderItemId(),
                    ];
                } else {
                    $items[$shipmentItem->getOrderItemId()]['qty'] += $qty;
                }
            }
        }

        if (empty($items)) {
            throw new LocalizedException(__('Please select items to return.'));
        }

        $unit = $this->config->getWeightUnit($order->getStoreId());
        $package = [
            'params' => [
                'container' => '',
                'weight' => $totalWeight,
                'customs_value' => $totalPrice,
                'length' => '',
                'width' => '',
                'height' => '',
                'weight_units' => $unit === 'kg' ? \Zend_Measure_Weight::KILOGRAM : \Zend_Measure_Weight::POUND,
                'dimension_units' => $unit === 'kg' ? \Zend_Measure_Length::CENTIMETER : \Zend_Measure_Length::INCH,
                'content_type' => '',
                'content_type_other' => '',
            ],
            'items' => $items,
        ];

        $request->setData('package_id', 0);
        $request->setData('packages', [$package]);
        $request->setPackageWeight($totalWeight);
        $request->setData('package_params', $this->dataObjectFactory->create(['data' => $package['params']]));
        $request->setData('package_items', $package['items']);

        $request->unsetData('shipments');
    }

    /**
     * Add return data to return shipment request.
     *
     * @param ReturnShipment $shipmentRequest
     * @return void
     * @throws LocalizedException
     */
    public function modify(ReturnShipment $shipmentRequest): void
    {
        $this->modifyGeneralParams($shipmentRequest);
        $this->modifyShipper($shipmentRequest);
        $this->modifyReceiver($shipmentRequest);
        $this->modifyPackage($shipmentRequest);
    }
}
