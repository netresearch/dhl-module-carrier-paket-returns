<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Webservice;

use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Dhl\Sdk\ParcelDe\Returns\Api\Data\AuthenticationStorageInterfaceFactory;
use Dhl\Sdk\ParcelDe\Returns\Api\Data\ConfirmationInterface;
use Dhl\Sdk\ParcelDe\Returns\Api\ReturnLabelServiceInterface;
use Dhl\Sdk\ParcelDe\Returns\Api\ServiceFactoryInterface;
use Dhl\Sdk\ParcelDe\Returns\Exception\AuthenticationException;
use Dhl\Sdk\ParcelDe\Returns\Exception\ServiceException;
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

    public function createReturnOrder(\JsonSerializable $returnOrder, string $labelType = self::LABEL_TYPE_BOTH): ConfirmationInterface
    {
        $authStorage = $this->authStorageFactory->create([
            'apiKey' => 'pJDOxtJt03guK5eXKYcZt9Ez1bPi2Xvm',
            'user' => $this->moduleConfig->getUser($this->storeId),
            'password' => $this->moduleConfig->getPassword($this->storeId),
        ]);

        $service = $this->serviceFactory->createReturnLabelService(
            $authStorage,
            $this->logger,
            $this->moduleConfig->isSandboxMode($this->storeId)
        );

        return $service->createReturnOrder($returnOrder, $labelType);
    }
}
