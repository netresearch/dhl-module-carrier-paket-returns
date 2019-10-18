<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Controller\Returns\Create;

use Dhl\PaketReturns\Controller\Returns\Create;
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

/**
 * Return Form Controller.
 *
 * @package Dhl\PaketReturns\Controller
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Guest extends Create
{
    /**
     * @var GuestHelper
     */
    private $guestHelper;

    /**
     * Create constructor.
     *
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderViewAuthorizationInterface $orderAuthorization
     * @param OrderProvider $orderProvider
     * @param OrderValidator $orderValidator
     * @param GuestHelper $guestHelper
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        OrderViewAuthorizationInterface $orderAuthorization,
        OrderProvider $orderProvider,
        OrderValidator $orderValidator,
        GuestHelper $guestHelper
    ) {
        $this->guestHelper = $guestHelper;

        parent::__construct($context, $orderRepository, $orderAuthorization, $orderProvider, $orderValidator);
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
