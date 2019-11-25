<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Controller\Returns\Label;

use Dhl\PaketReturns\Controller\Returns\Label;
use Dhl\PaketReturns\Model\ReturnShipmentManagement;
use Dhl\PaketReturns\Model\ReturnShipmentRequest\RequestModifier;
use Dhl\PaketReturns\Model\ReturnShipmentResponse\LabelDataProvider;
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
 * Request and display return shipment labels.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Guest extends Label
{
    /**
     * @var GuestHelper
     */
    private $guestHelper;

    /**
     * Submit constructor.
     *
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param OrderProvider $orderProvider
     * @param OrderValidator $orderValidator
     * @param ReturnShipmentFactory $shipmentRequestFactory
     * @param RequestModifier $requestModifier
     * @param ReturnShipmentManagement $returnShipmentManagement
     * @param LabelDataProvider $labelDataProvider
     * @param GuestHelper $guestHelper
     */
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
