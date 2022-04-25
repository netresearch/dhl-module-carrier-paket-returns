<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Sales;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Registry for passing a loaded order through the application.
 */
class OrderProvider
{
    /**
     * @var OrderInterface
     */
    private $order;

    public function setOrder(OrderInterface $order): void
    {
        $this->order = $order;
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }
}
