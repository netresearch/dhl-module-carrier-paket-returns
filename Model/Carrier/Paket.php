<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Carrier;

use Dhl\PaketReturns\Model\ReturnShipmentManagement;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Shipment\Request;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Magento\Shipping\Model\Simplexml\ElementFactory;
use Magento\Shipping\Model\Tracking\Result as TrackingResult;
use Magento\Shipping\Model\Tracking\Result\ErrorFactory as TrackErrorFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory as TrackResultFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Paket
 *
 * @package Dhl\PaketReturns\Model
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Paket extends AbstractCarrierOnline implements CarrierInterface
{
    const CARRIER_CODE = 'dhlpaketrma';

    /**
     * Tracking URL
     */
    const TRACKING_URL_TEMPLATE = 'https://nolp.dhl.de/nextt-online-public/set_identcodes.do?lang=de&idc=%s';

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @var ReturnShipmentManagement
     */
    private $returnShipmentManagement;

    /**
     * Paket constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param ElementFactory $xmlElFactory
     * @param ResultFactory $rateFactory
     * @param MethodFactory $rateMethodFactory
     * @param TrackResultFactory $trackFactory
     * @param TrackErrorFactory $trackErrorFactory
     * @param StatusFactory $trackStatusFactory
     * @param RegionFactory $regionFactory
     * @param CountryFactory $countryFactory
     * @param CurrencyFactory $currencyFactory
     * @param Data $directoryData
     * @param StockRegistryInterface $stockRegistry
     * @param ReturnShipmentManagement $returnShipmentManagement
     * @param mixed[] $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        Security $xmlSecurity,
        ElementFactory $xmlElFactory,
        ResultFactory $rateFactory,
        MethodFactory $rateMethodFactory,
        TrackResultFactory $trackFactory,
        TrackErrorFactory $trackErrorFactory,
        StatusFactory $trackStatusFactory,
        RegionFactory $regionFactory,
        CountryFactory $countryFactory,
        CurrencyFactory $currencyFactory,
        Data $directoryData,
        StockRegistryInterface $stockRegistry,
        ReturnShipmentManagement $returnShipmentManagement,
        array $data = []
    ) {
        $this->returnShipmentManagement = $returnShipmentManagement;

        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
    }

    /**
     * Collect and get rates.
     *
     * The carrier's rates must not be collected during checkout.
     * Collect rates only when a return shipment is requested.
     *
     * @param RateRequest $request
     * @return DataObject|Result
     */
    public function collectRates(RateRequest $request)
    {
        $result = $this->_rateFactory->create();

        foreach ($this->getAllowedMethods() as $method => $methodTitle) {
            $method = $this->_rateMethodFactory->create(
                [
                    'data' => [
                        'carrier' => self::CARRIER_CODE,
                        'carrier_title' => $this->getConfigData('title'),
                        'method' => $method,
                        'method_title' => $methodTitle,
                    ],
                ]
            );

            $result->append($method);
        }

        return $result;
    }

    /**
     * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
     *
     * @param DataObject|ReturnShipment|Request $request
     * @return DataObject
     */
    protected function _doShipmentRequest(DataObject $request): DataObject
    {
        $apiResult = $this->returnShipmentManagement->createLabels([$request->getData('package_id') => $request]);

        // One request, one response.
        return $apiResult[0];
    }

    /**
     * Check if the carrier can handle the given rate request.
     *
     * DHL Paket Returns carrier only offers rates for return shipments to DE.
     *
     * @param DataObject $request
     * @return $this|bool|DataObject
     */
    public function processAdditionalValidation(DataObject $request)
    {
        $isReturn = (bool)$request->getData('is_return');
        $shippingDestination = (string)$request->getData('dest_country_id');

        if (!$isReturn || $shippingDestination !== 'DE') {
            return false;
        }

        return parent::processAdditionalValidation($request);
    }

    /**
     * Get allowed shipping methods
     *
     * @return string[] Associative array of method names with method code as key.
     */
    public function getAllowedMethods(): array
    {
        return ['rma' => 'Return Shipment'];
    }

    /**
     * Returns tracking information.
     *
     * @param string $shipmentNumber
     * @return TrackingResult
     *
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::getTrackingInfo
     */
    public function getTracking(string $shipmentNumber): TrackingResult
    {
        $result = $this->_trackFactory->create();

        $statusData = [
            'tracking' => $shipmentNumber,
            'carrier_title' => $this->getConfigData('title'),
            'url' => sprintf(self::TRACKING_URL_TEMPLATE, $shipmentNumber),
        ];

        $status = $this->_trackStatusFactory->create(['data' => $statusData]);
        $result->append($status);

        return $result;
    }
}
