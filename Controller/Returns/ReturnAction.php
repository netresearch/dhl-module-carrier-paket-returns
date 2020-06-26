<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Controller\Returns;

use Dhl\PaketReturns\Model\Sales\OrderProvider;
use Dhl\PaketReturns\Model\Sales\OrderValidator;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

/**
 * Order access authorization for all return shipment actions.
 */
abstract class ReturnAction extends Action
{
    /**
     * @var OrderRepositoryInterface|OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderViewAuthorizationInterface
     */
    private $orderAuthorization;

    /**
     * @var OrderProvider
     */
    private $orderProvider;

    /**
     * @var OrderValidator
     */
    private $orderValidator;

    /**
     * Create constructor.
     *
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param OrderProvider $orderProvider
     * @param OrderValidator $orderValidator
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        OrderViewAuthorizationInterface $orderAuthorization,
        OrderProvider $orderProvider,
        OrderValidator $orderValidator
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderAuthorization = $orderAuthorization;
        $this->orderProvider = $orderProvider;
        $this->orderValidator = $orderValidator;

        parent::__construct($context);
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
        $orderId = (int) $request->getParam('order_id');

        try {
            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);
            $this->orderProvider->setOrder($order);
            $canView = $this->orderAuthorization->canView($order) && $this->orderValidator->canCreateRma($order);
            $displayId = $canView ? $order->getRealOrderId() : $orderId;
        } catch (LocalizedException $exception) {
            $canView = false;
            $displayId = $orderId;
        }

        if (!$canView) {
            $this->messageManager->addErrorMessage(__('You cannot create a return shipment for order %1.', $displayId));
            return $this->_redirect('sales/order/history');
        }

        return parent::dispatch($request);
    }
}
