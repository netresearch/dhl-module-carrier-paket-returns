<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\ViewModel\Sales\Order\Info\Button;

use Dhl\PaketReturns\Model\Sales\OrderValidator;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderRepository;

/**
 * View model class for adding a returns button to the order info view.
 *
 * @package Dhl\PaketReturns\ViewModel
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Returns implements ArgumentInterface
{
    /**
     * @var SessionManagerInterface|Session
     */
    private $customerSession;

    /**
     * @var OrderRepositoryInterface|OrderRepository
     */
    private $orderRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OrderValidator
     */
    private $orderValidator;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Returns constructor.
     *
     * @param SessionManagerInterface $customerSession
     * @param OrderRepositoryInterface $orderRepository
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     * @param OrderValidator $orderValidator
     */
    public function __construct(
        SessionManagerInterface $customerSession,
        OrderRepositoryInterface $orderRepository,
        RequestInterface $request,
        OrderValidator $orderValidator,
        UrlInterface $urlBuilder
    ) {
        $this->customerSession = $customerSession;
        $this->orderRepository = $orderRepository;
        $this->request = $request;
        $this->orderValidator = $orderValidator;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get URL to returns form.
     *
     * @return string
     */
    public function getReturnCreateUrl(): string
    {
        try {
            $order = $this->orderRepository->get((int) $this->request->getParam('order_id'));
        } catch (LocalizedException $exception) {
            return '';
        }

        if (!$this->orderValidator->canCreateRma($order)) {
            return '';
        }

        if ($this->customerSession->isLoggedIn()) {
            $routePath = 'dhlpaketrma/returns/create';
        } else {
            $routePath = 'dhlpaketrma/returns_create/guest';
        }

        return $this->urlBuilder->getUrl($routePath, ['order_id' => (int) $order->getEntityId()]);
    }
}
