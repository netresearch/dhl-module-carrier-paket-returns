<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\OnlineRetoure\Model\Carrier;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Shipment\Request;
use Magento\Shipping\Model\Shipment\ReturnShipment;

/**
 * Class Paket
 *
 * @package Dhl\OnlineRetoure\Model
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Paket extends AbstractCarrierOnline implements CarrierInterface
{
    const CARRIER_CODE = 'dhlpaketrma';

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * Collect and get rates
     *
     * @param RateRequest $request
     * @return DataObject|Result
     */
    public function collectRates(RateRequest $request)
    {
        $result = $this->_rateFactory->create();
        if (!$request->getData('is_return')) {
            return $result;
        }

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
    protected function _doShipmentRequest(DataObject $request)
    {
        if (!$request->getData('is_return') || !$request instanceof ReturnShipment) {
            // todo(nr): error handling
            return new DataObject();
        } else {
            // todo(nr): fetch label & track
            return new DataObject(['tracking_number' => '123', 'shipping_label_content' => '']);
        }
    }

    /**
     * Get allowed shipping methods
     *
     * @return string[] Associative array of method names with method code as key.
     */
    public function getAllowedMethods()
    {
        return ['rma' => 'Online Retoure'];
    }
}
