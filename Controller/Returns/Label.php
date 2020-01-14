<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Controller\Returns;

use Dhl\PaketReturns\Model\BulkShipment\ReturnShipmentManagement;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentRequest\RequestModifier;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse\ErrorResponse;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse\LabelDataProvider;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse\LabelResponse;
use Dhl\PaketReturns\Model\Sales\OrderProvider;
use Dhl\PaketReturns\Model\Sales\OrderValidator;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;
use Magento\Shipping\Model\Shipment\ReturnShipmentFactory;

/**
 * Request and display return shipment labels.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Label extends ReturnAction
{
    /**
     * @var OrderProvider
     */
    private $orderProvider;

    /**
     * @var ReturnShipmentFactory
     */
    private $shipmentRequestFactory;

    /**
     * @var RequestModifier
     */
    private $requestModifier;

    /**
     * @var ReturnShipmentManagement
     */
    private $returnShipmentManagement;

    /**
     * @var LabelDataProvider
     */
    private $labelDataProvider;

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
        LabelDataProvider $labelDataProvider
    ) {
        $this->orderProvider = $orderProvider;
        $this->shipmentRequestFactory = $shipmentRequestFactory;
        $this->requestModifier = $requestModifier;
        $this->returnShipmentManagement = $returnShipmentManagement;
        $this->labelDataProvider = $labelDataProvider;

        parent::__construct($context, $orderRepository, $orderAuthorization, $orderProvider, $orderValidator);
    }

    /**
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        try {
            $requestData = [
                'shipper' => $this->getRequest()->getPost('address', []),
                'shipments' => $this->getRequest()->getPost('shipments', []),
            ];
            $request = $this->shipmentRequestFactory->create(['data' => $requestData]);
            $this->requestModifier->modify($request);

            $apiResult = $this->returnShipmentManagement->createLabels([$request->getData('package_id') => $request]);
            $response = $apiResult[$request->getData('package_id')];

            if ($response instanceof LabelResponse) {
                $this->labelDataProvider->setLabelResponse($response);

                /** @var Page $resultPage */
                $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
                $resultPage->getConfig()->getTitle()->set(__('Order # %1', $this->orderProvider->getOrder()->getRealOrderId()));
                $resultPage->getConfig()->getTitle()->prepend(__('Return Labels'));
                return $resultPage;
            }

            if ($response instanceof ErrorResponse) {
                $this->messageManager->addErrorMessage($response->getErrors());
            }
        } catch (LocalizedException $exception) {
            $msg = __('You cannot create a return shipment for order %1: %2.', $this->orderProvider->getOrder()->getRealOrderId(), $exception->getMessage());
            $this->messageManager->addErrorMessage($msg);
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}
