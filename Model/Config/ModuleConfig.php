<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Store\Model\ScopeInterface;

class ModuleConfig
{
    // Defaults
    const CONFIG_PATH_VERSION = 'carriers/dhlpaketrma/version';
    const CONFIG_PATH_ACTIVE = 'carriers/dhlpaketrma/active';
    const CONFIG_PATH_ACTIVE_RMA = 'carriers/dhlpaketrma/active_rma';

    // 100_general.xml
    const CONFIG_PATH_DEFAULT_ITEM_WEIGHT = 'dhlshippingsolutions/dhlpaketrma/general/default_item_weight';
    const CONFIG_PATH_ENABLE_LOGGING = 'dhlshippingsolutions/dhlpaketrma/general/logging';
    const CONFIG_PATH_LOGLEVEL = 'dhlshippingsolutions/dhlpaketrma/general/logging_group/loglevel';

    // 200_account.xml
    const CONFIG_PATH_SANDBOX_MODE = 'dhlshippingsolutions/dhlpaketrma/account/sandboxmode';

    // Production settings
    const CONFIG_PATH_AUTH_USERNAME = 'dhlshippingsolutions/dhlpaketrma/account/production/auth_username';
    const CONFIG_PATH_AUTH_PASSWORD = 'dhlshippingsolutions/dhlpaketrma/account/production/auth_password';
    const CONFIG_PATH_USER = 'dhlshippingsolutions/dhlpaketrma/account/production/api_username';
    const CONFIG_PATH_SIGNATURE = 'dhlshippingsolutions/dhlpaketrma/account/production/api_password';
    const CONFIG_PATH_EKP = 'dhlshippingsolutions/dhlpaketrma/account/production/account_number';
    const CONFIG_PATH_PARTICIPATIONS = 'dhlshippingsolutions/dhlpaketrma/account/production/account_participations';
    const CONFIG_PATH_RECEIVER_IDS = 'dhlshippingsolutions/dhlpaketrma/account/production/receiver_ids';

    // Sandbox settings
    const CONFIG_PATH_SBX_AUTH_USERNAME = 'dhlshippingsolutions/dhlpaketrma/account/sandbox/auth_username';
    const CONFIG_PATH_SBX_AUTH_PASSWORD = 'dhlshippingsolutions/dhlpaketrma/account/sandbox/auth_password';
    const CONFIG_PATH_SBX_USER = 'dhlshippingsolutions/dhlpaketrma/account/sandbox/api_username';
    const CONFIG_PATH_SBX_SIGNATURE = 'dhlshippingsolutions/dhlpaketrma/account/sandbox/api_password';
    const CONFIG_PATH_SBX_EKP = 'dhlshippingsolutions/dhlpaketrma/account/sandbox/account_number';
    const CONFIG_PATH_SBX_PARTICIPATIONS = 'dhlshippingsolutions/dhlpaketrma/account/sandbox/account_participations';
    const CONFIG_PATH_SBX_RECEIVER_IDS = 'dhlshippingsolutions/dhlpaketrma/account/sandbox/receiver_ids';

    const CONFIG_PATH_MAGENTO_RMA_ENABLED = 'sales/magento_rma/enabled';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ModuleConfig constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
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
     * Get the HTTP basic authentication username (CIG application authentication).
     *
     * @param mixed $store
     * @return string
     */
    public function getAuthUsername($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxAuthUsername($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_AUTH_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the HTTP basic authentication password (CIG application authentication).
     *
     * @param mixed $store
     * @return string
     */
    public function getAuthPassword($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxAuthPassword($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_AUTH_PASSWORD,
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
    public function getSignature($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxSignature($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SIGNATURE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the user's EKP (standardized customer and product number).
     *
     * @param mixed $store
     * @return string
     */
    public function getEkp($store = null): string
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxEkp($store);
        }

        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_EKP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the procedure to user's participation numbers (partner IDs) mapping.
     *
     * @param mixed $store
     * @return string[]
     */
    public function getParticipations($store = null): array
    {
        if ($this->isSandboxMode($store)) {
            return $this->getSandboxParticipations($store);
        }

        $participations = $this->scopeConfig->getValue(
            self::CONFIG_PATH_PARTICIPATIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($participations, 'participation', 'procedure');
    }

    /**
     * Get the two letter country code to receiver IDs mapping.
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
     * Get the HTTP basic sandbox authentication username (CIG application authentication).
     *
     * @param mixed $store
     * @return string
     */
    private function getSandboxAuthUsername($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SBX_AUTH_USERNAME,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the HTTP basic sandbox authentication password (CIG application authentication).
     *
     * @param mixed $store
     * @return string
     */
    private function getSandboxAuthPassword($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SBX_AUTH_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
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
    private function getSandboxSignature($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SBX_SIGNATURE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the sandbox EKP (standardized customer and product number).
     *
     * @param mixed $store
     * @return string
     */
    private function getSandboxEkp($store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::CONFIG_PATH_SBX_EKP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get the sandbox procedure to sandbox participation numbers (partner IDs) mapping.
     *
     * @param mixed $store
     * @return string[]
     */
    private function getSandboxParticipations($store = null): array
    {
        $participations = $this->scopeConfig->getValue(
            self::CONFIG_PATH_SBX_PARTICIPATIONS,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return array_column($participations, 'participation', 'procedure');
    }

    /**
     * Get the sandbox two letter country code to sandbox receiver IDs mapping.
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
     * Obtain a carrier's title.
     *
     * @param string $carrierCode
     * @param mixed $store
     * @return string
     */
    public function getCarrierTitle(string $carrierCode, $store = null): string
    {
        return (string) $this->scopeConfig->getValue(
            'carriers/' . $carrierCode . '/title',
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if the native Magento RMA solution is enabled for customers.
     *
     * @param mixed $store
     * @return bool
     */
    public function isRmaEnabledOnStoreFront($store = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_MAGENTO_RMA_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @see \Magento\Rma\Helper\Data::getReturnAddressData
     *
     * @param mixed $store
     * @return string[]
     */
    public function getReturnAddress($store = null): array
    {
        $scope = ScopeInterface::SCOPE_STORE;

        $useStoreAddress = $this->scopeConfig->getValue('sales/magento_rma/use_store_address', $scope, $store);

        if ($useStoreAddress === null || $useStoreAddress === '1') {
            // flag not configured (CE) or explicitly set (EE).
            $address['city'] = $this->scopeConfig->getValue(Shipment::XML_PATH_STORE_CITY, $scope, $store);
            $address['country_id'] = $this->scopeConfig->getValue(Shipment::XML_PATH_STORE_COUNTRY_ID, $scope, $store);
            $address['postcode'] = $this->scopeConfig->getValue(Shipment::XML_PATH_STORE_ZIP, $scope, $store);
            $address['region_id'] = $this->scopeConfig->getValue(Shipment::XML_PATH_STORE_REGION_ID, $scope, $store);
            $address['street2'] = $this->scopeConfig->getValue(Shipment::XML_PATH_STORE_ADDRESS2, $scope, $store);
            $address['street1'] = $this->scopeConfig->getValue(Shipment::XML_PATH_STORE_ADDRESS1, $scope, $store);
        } else {
            $address['city'] = $this->scopeConfig->getValue('sales/magento_rma/city', $scope, $store);
            $address['country_id'] = $this->scopeConfig->getValue('sales/magento_rma/country_id', $scope, $store);
            $address['postcode'] = $this->scopeConfig->getValue('sales/magento_rma/zip', $scope, $store);
            $address['region_id'] = $this->scopeConfig->getValue('sales/magento_rma/region_id', $scope, $store);
            $address['street2'] = $this->scopeConfig->getValue('sales/magento_rma/address1', $scope, $store);
            $address['street1'] = $this->scopeConfig->getValue('sales/magento_rma/address', $scope, $store);
        }

        return $address;
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
