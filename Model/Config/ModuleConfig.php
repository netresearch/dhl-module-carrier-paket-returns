<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Netresearch\ShippingCore\Api\InfoBox\VersionInterface;

class ModuleConfig implements VersionInterface
{
    // Defaults
    private const CONFIG_PATH_VERSION = 'carriers/dhlpaketrma/version';
    public const CONFIG_PATH_ACTIVE = 'carriers/dhlpaketrma/active';
    private const CONFIG_PATH_ACTIVE_RMA = 'carriers/dhlpaketrma/active_rma';

    // 100_general.xml
    private const CONFIG_PATH_DEFAULT_ITEM_WEIGHT = 'dhlshippingsolutions/dhlpaketrma/general/default_item_weight';
    public const CONFIG_PATH_ENABLE_LOGGING = 'dhlshippingsolutions/dhlpaketrma/general/logging';
    public const CONFIG_PATH_LOGLEVEL = 'dhlshippingsolutions/dhlpaketrma/general/logging_group/loglevel';

    // 200_account.xml
    private const CONFIG_PATH_SANDBOX_MODE = 'dhlshippingsolutions/dhlpaketrma/account/sandboxmode';

    // Production settings
    private const CONFIG_PATH_USER = 'dhlshippingsolutions/dhlpaketrma/account/production/api_username';
    private const CONFIG_PATH_PASSWORD = 'dhlshippingsolutions/dhlpaketrma/account/production/api_password';
    public const CONFIG_PATH_RECEIVER_IDS = 'dhlshippingsolutions/dhlpaketrma/account/production/receiver_ids';

    // Sandbox settings
    private const CONFIG_PATH_SBX_USER = 'dhlshippingsolutions/dhlpaketrma/account/sandbox/api_username';
    private const CONFIG_PATH_SBX_PASSWORD = 'dhlshippingsolutions/dhlpaketrma/account/sandbox/api_password';
    private const CONFIG_PATH_SBX_RECEIVER_IDS = 'dhlshippingsolutions/dhlpaketrma/account/sandbox/receiver_ids';

    private const CONFIG_PATH_MAGENTO_RMA_ENABLED = 'sales/magento_rma/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Obtain the module version.
     *
     * @return string
     */
    public function getModuleVersion(): string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_VERSION);
    }

    /**
     * Returns TRUE if module is enabled, FALSE otherwise.
     *
     * @param mixed $store
     * @return bool
     */
    public function isEnabled($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_ACTIVE_RMA,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns TRUE if sandbox mode is enabled, FALSE otherwise.
     *
     * @param mixed $store
     * @return bool
     */
    public function isSandboxMode($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_SANDBOX_MODE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's name (API user credentials).
     *
     * @param mixed $store
     * @return string
     */
    public function getUser($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxUser($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_USER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's password (API user credentials).
     *
     * @param mixed $store
     * @return string
     */
    public function getPassword($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxPassword($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the two-letter country code to receiver IDs mapping.
     *
     * @param mixed $store
     * @return string[]
     */
    public function getReceiverIds($store = null): array
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxReceiverIds($store);
        }

        $receiverIds = $this->scopeConfig->getValue(
            self::CONFIG_PATH_RECEIVER_IDS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($receiverIds, 'receiver_id', 'iso');
    }

    /**
     * Get the name (API user sandbox credentials).
     *
     * @param mixed $store
     * @return string
     */
    private function getSandboxUser($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SBX_USER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the password (API user sandbox credentials).
     *
     * @param mixed $store
     * @return string
     */
    private function getSandboxPassword($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SBX_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the sandbox two-letter country code to sandbox receiver IDs mapping.
     *
     * @param mixed $store
     * @return string[]
     */
    private function getSandboxReceiverIds($store = null): array
    {
        $receiverIds = $this->scopeConfig->getValue(
            self::CONFIG_PATH_SBX_RECEIVER_IDS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($receiverIds, 'receiver_id', 'iso');
    }

    /**
     * Obtain default item weight for a return item.
     *
     * @param mixed $store
     * @return float
     */
    public function getDefaultItemWeight($store = null): float
    {
        return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_DEFAULT_ITEM_WEIGHT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
