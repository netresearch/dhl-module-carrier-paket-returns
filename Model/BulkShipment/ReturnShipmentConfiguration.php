<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\BulkShipment;

use Dhl\PaketReturns\Model\Carrier\Paket;
use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentRequest\RequestModifier;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
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
     * @var State
     */
    private $appState;

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
        State $appState,
        ModuleConfig $config,
        RmaConfigInterface $rmaConfig
    ) {
        $this->requestModifier = $requestModifier;
        $this->shipmentManagement = $shipmentManagement;
        $this->appState = $appState;
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
            $appState = $this->appState->getAreaCode();
        } catch (LocalizedException $exception) {
            return false;
        }

        if (($appState !== Area::AREA_ADMINHTML) && !$this->config->isEnabled($order->getStoreId())) {
            // creating returns in storefront (customer account) is not allowed by configuration
            return false;
        }

        $returnAddress = $this->rmaConfig->getReturnAddress($order->getStoreId());
        return $returnAddress['country_id'] === 'DE';
    }
}
