<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\ViewModel\Adminhtml\System;

use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class InfoBox
 *
 * @package Dhl\PaketReturns\ViewModel
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    http://www.netresearch.de/
 */
class InfoBox implements ArgumentInterface
{
    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * InfoBox constructor.
     *
     * @param ModuleConfig $config
     */
    public function __construct(ModuleConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getModuleVersion(): string
    {
        return $this->config->getModuleVersion();
    }
}
