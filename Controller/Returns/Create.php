<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Controller\Returns;

use Dhl\PaketReturns\Model\Sales\OrderProvider;
use Dhl\PaketReturns\Model\Sales\OrderValidator;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;

/**
 * Return Form Controller.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Create extends ReturnAction
{
    /**
     * @var OrderProvider
     */
    private $orderProvider;

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
        $this->orderProvider = $orderProvider;

        parent::__construct($context, $orderRepository, $orderAuthorization, $orderProvider, $orderValidator);
    }

    /**
     * Customer create new return.
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Order # %1', $this->orderProvider->getOrder()->getRealOrderId()));
        $resultPage->getConfig()->getTitle()->prepend(__('New Return Shipment'));

        return $resultPage;
    }
}
