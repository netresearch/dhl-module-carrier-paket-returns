<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Adminhtml\System\Config\Backend;

use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Manipulate value of "active" flag after saving "active_rma".
 */
class ActiveRma extends Value
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        WriterInterface $configWriter,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configWriter = $configWriter;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Update "active" flag with value of "active_rma"
     *
     * @return Value
     */
    public function afterSave(): Value
    {
        $scope   = $this->getScope();
        $scopeId = $this->getScopeId();

        $this->configWriter->save(ModuleConfig::CONFIG_PATH_ACTIVE, $this->getValue(), $scope, $scopeId);

        return parent::afterSave();
    }
}
