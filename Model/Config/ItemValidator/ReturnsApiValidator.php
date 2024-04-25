<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Config\ItemValidator;

use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Dhl\PaketReturns\Model\Webservice\ReturnLabelServiceFactory;
use Dhl\Sdk\ParcelDe\Returns\Api\ReturnLabelRequestBuilderInterface;
use Dhl\Sdk\ParcelDe\Returns\Exception\RequestValidatorException;
use Dhl\Sdk\ParcelDe\Returns\Exception\ServiceException;
use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Magento\Framework\Phrase;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class ReturnsApiValidator implements ItemValidatorInterface
{
    use DhlSection;
    use DhlReturnsGroup;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var ReturnLabelRequestBuilderInterface
     */
    private $requestBuilder;

    /**
     * @var ReturnLabelServiceFactory
     */
    private $returnLabelServiceFactory;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ModuleConfig $config,
        ReturnLabelRequestBuilderInterface $requestBuilder,
        ReturnLabelServiceFactory $returnLabelServiceFactory
    ) {
        $this->resultFactory = $resultFactory;
        $this->config = $config;
        $this->requestBuilder = $requestBuilder;
        $this->returnLabelServiceFactory = $returnLabelServiceFactory;
    }

    private function createResult(string $status, Phrase $message): ResultInterface
    {
        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Returns API'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }

    public function execute(int $storeId): ResultInterface
    {
        $receiverIds = $this->config->getReceiverIds($storeId);
        $this->requestBuilder->setReceiverId($receiverIds['DE'] ?? '');
        $this->requestBuilder->setShipperContact('john.doe@example.org');
        $this->requestBuilder->setShipper('John Doe', 'DEU', '53113', 'Bonn', 'Charles-de-Gaulle-Straße', '20');

        try {
            $apiRequest = $this->requestBuilder->create();
        } catch (RequestValidatorException $exception) {
            $message = __('Invalid request: %1', $exception->getMessage());
            return $this->createResult(ResultInterface::ERROR, $message);
        }

        $labelService = $this->returnLabelServiceFactory->create([
            'storeId' => $storeId,
        ]);

        try {
            $labelService->createReturnOrder($apiRequest);

            $status = ResultInterface::OK;
            $message = __('Retoure API connection established successfully.');
        } catch (ServiceException $exception) {
            $status = ResultInterface::ERROR;
            $message = __(
                'Web service error: %1 Please review your %2.',
                $exception->getMessage(),
                __('Account Settings')
            );
        }

        return $this->createResult($status, $message);
    }
}
