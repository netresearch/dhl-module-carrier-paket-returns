<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Config\ItemValidator;

use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class ReturnsApiCredentialsValidator implements ItemValidatorInterface
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

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ModuleConfig $config
    ) {
        $this->resultFactory = $resultFactory;
        $this->config = $config;
    }

    public function execute(int $storeId): ResultInterface
    {
        if (!$this->config->getUser($storeId) || !$this->config->getPassword($storeId)) {
            $status = ResultInterface::ERROR;
            $message = __(
                'Web service credentials are incomplete. Please review your %1.',
                __('Account Settings')
            );
        } else {
            $status = ResultInterface::OK;
            $message = __('Web service credentials are configured.');
        }

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
}
