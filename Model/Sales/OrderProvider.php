<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Sales;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class OrderProvider
 *
 * Registry for passing a loaded order through the application.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class OrderProvider
{
    /**
     * @var OrderInterface
     */
    private $order;

    /**
     * @param OrderInterface $order
     */
    public function setOrder(OrderInterface $order)
    {
        $this->order = $order;
    }

    /**
     * @return OrderInterface|null
     */
    public function getOrder()
    {
        return $this->order;
    }
}
