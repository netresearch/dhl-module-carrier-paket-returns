<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Sales;

use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

/**
 * Class OrderValidator
 *
 * @package Dhl\PaketReturns\Model
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class OrderValidator
{
    /**
     * The module configuration.
     *
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * Returns constructor.
     *
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        ModuleConfig $moduleConfig
    ) {
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * Checks for ability to create RMA.
     *
     * The "Create Return Label" button is shown if
     * - the return receiver is located in Germany
     * - the native RMA feature is disabled for store front and
     * - the DHL Paket returns feature is enabled and
     * - items were shipped
     *
     * @param OrderInterface|Order $order
     * @return bool
     */
    public function canCreateRma(OrderInterface $order): bool
    {
        $returnAddress = $this->moduleConfig->getReturnAddress($order->getStoreId());
        return $returnAddress['country_id'] === 'DE'
            && !$this->moduleConfig->isRmaEnabledOnStoreFront($order->getStoreId())
            && $this->moduleConfig->isEnabled($order->getStoreId())
            && $order->getShipmentsCollection()->getSize();
    }
}
