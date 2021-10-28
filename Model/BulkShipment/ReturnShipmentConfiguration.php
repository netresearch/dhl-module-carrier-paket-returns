<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\BulkShipment;

use Dhl\PaketReturns\Model\Carrier\Paket;
use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentRequest\RequestModifier;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Netresearch\ShippingCore\Api\BulkShipment\ReturnLabelCreationInterface;
use Netresearch\ShippingCore\Api\BulkShipment\ReturnShipmentConfigurationInterface;
use Netresearch\ShippingCore\Api\Config\RmaConfigInterface;
use Netresearch\ShippingCore\Api\Pipeline\ReturnShipmentRequest\RequestModifierInterface;

class ReturnShipmentConfiguration implements ReturnShipmentConfigurationInterface
{
    /**
     * @var RequestModifier
     */
    private $requestModifier;

    /**
     * @var ReturnShipmentManagement
     */
    private $shipmentManagement;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var RmaConfigInterface
     */
    private $rmaConfig;

    public function __construct(
        RequestModifier $requestModifier,
        ReturnShipmentManagement $shipmentManagement,
        StoreManagerInterface $storeManager,
        ModuleConfig $config,
        RmaConfigInterface $rmaConfig
    ) {
        $this->requestModifier = $requestModifier;
        $this->shipmentManagement = $shipmentManagement;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->rmaConfig = $rmaConfig;
    }

    public function getCarrierCode(): string
    {
        return Paket::CARRIER_CODE;
    }

    public function getRequestModifier(): RequestModifierInterface
    {
        return $this->requestModifier;
    }

    public function getLabelService(): ReturnLabelCreationInterface
    {
        return $this->shipmentManagement;
    }

    public function canProcessOrder(OrderInterface $order): bool
    {
        try {
            $currentStore = $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $exception) {
            return false;
        }

        if (($currentStore !== Store::DEFAULT_STORE_ID) && !$this->config->isEnabled($order->getStoreId())) {
            // creating returns in storefront (customer account) is not allowed by configuration
            return false;
        }

        $returnAddress = $this->rmaConfig->getReturnAddress($order->getStoreId());
        return $returnAddress['country_id'] === 'DE';
    }
}
