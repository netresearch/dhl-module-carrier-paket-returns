<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\ViewModel\Sales\Order\Info\Button;

use Dhl\PaketReturns\Model\Sales\OrderValidator;
use Magento\Customer\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * View model class for adding a returns button to the order info view.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Returns implements ArgumentInterface
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var OrderValidator
     */
    private $orderValidator;

    /**
     * @var SessionManagerInterface|Session
     */
    private $customerSession;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Returns constructor.
     *
     * @param Registry $registry
     * @param OrderValidator $orderValidator
     * @param SessionManagerInterface $customerSession
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Registry $registry,
        OrderValidator $orderValidator,
        SessionManagerInterface $customerSession,
        UrlInterface $urlBuilder
    ) {
        $this->registry = $registry;
        $this->orderValidator = $orderValidator;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Get URL to returns form.
     *
     * @return string
     */
    public function getReturnCreateUrl(): string
    {
        $order = $this->registry->registry('current_order');
        if (!$order instanceof OrderInterface) {
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
