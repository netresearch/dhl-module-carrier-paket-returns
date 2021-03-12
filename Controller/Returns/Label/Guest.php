<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Controller\Returns\Label;

use Dhl\PaketReturns\Controller\Returns\Label;
use Dhl\PaketReturns\Model\BulkShipment\ReturnShipmentManagement;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentRequest\RequestModifier;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse\LabelDataProvider;
use Dhl\PaketReturns\Model\Sales\OrderProvider;
use Dhl\PaketReturns\Model\Sales\OrderValidator;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;
use Magento\Sales\Helper\Guest as GuestHelper;
use Magento\Shipping\Model\Shipment\ReturnShipmentFactory;

/**
 * Request and display return shipment labels for guests.
 */
class Guest extends Label
{
    /**
     * @var GuestHelper
     */
    private $guestHelper;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        OrderViewAuthorizationInterface $orderAuthorization,
        OrderProvider $orderProvider,
        OrderValidator $orderValidator,
        ReturnShipmentFactory $shipmentRequestFactory,
        RequestModifier $requestModifier,
        ReturnShipmentManagement $returnShipmentManagement,
        LabelDataProvider $labelDataProvider,
        GuestHelper $guestHelper
    ) {
        $this->guestHelper = $guestHelper;

        parent::__construct(
            $context,
            $orderRepository,
            $orderAuthorization,
            $orderProvider,
            $orderValidator,
            $shipmentRequestFactory,
            $requestModifier,
            $returnShipmentManagement,
            $labelDataProvider
        );
    }

    /**
     * Dispatch request. If loading the order fails, do not dispatch.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        try {
            $this->guestHelper->loadValidOrder($request);
        } catch (LocalizedException $exception) {
            return $this->_redirect('sales/order/history');
        }

        return parent::dispatch($request);
    }
}
