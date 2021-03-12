<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Webservice;

use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Dhl\Sdk\Paket\Retoure\Api\Data\AuthenticationStorageInterfaceFactory;
use Dhl\Sdk\Paket\Retoure\Api\Data\ConfirmationInterface;
use Dhl\Sdk\Paket\Retoure\Api\ReturnLabelServiceInterface;
use Dhl\Sdk\Paket\Retoure\Api\ServiceFactoryInterface;
use Psr\Log\LoggerInterface;

class ReturnLabelService implements ReturnLabelServiceInterface
{
    /**
     * @var AuthenticationStorageInterfaceFactory
     */
    private $authStorageFactory;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var ServiceFactoryInterface
     */
    private $serviceFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $storeId;

    public function __construct(
        AuthenticationStorageInterfaceFactory $authStorageFactory,
        ModuleConfig $moduleConfig,
        ServiceFactoryInterface $serviceFactory,
        LoggerInterface $logger,
        int $storeId
    ) {
        $this->authStorageFactory = $authStorageFactory;
        $this->moduleConfig = $moduleConfig;
        $this->serviceFactory = $serviceFactory;
        $this->logger = $logger;
        $this->storeId = $storeId;
    }

    public function bookLabel(\JsonSerializable $returnOrder): ConfirmationInterface
    {
        $authStorage = $this->authStorageFactory->create([
            'applicationId' => $this->moduleConfig->getAuthUsername($this->storeId),
            'applicationToken' => $this->moduleConfig->getAuthPassword($this->storeId),
            'user' => $this->moduleConfig->getUser($this->storeId),
            'signature' => $this->moduleConfig->getSignature($this->storeId),
        ]);

        $service = $this->serviceFactory->createReturnLabelService(
            $authStorage,
            $this->logger,
            $this->moduleConfig->isSandboxMode($this->storeId)
        );

        return $service->bookLabel($returnOrder);
    }
}
