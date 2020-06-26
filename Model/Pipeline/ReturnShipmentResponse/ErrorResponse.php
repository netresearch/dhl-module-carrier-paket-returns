<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse;

use Magento\Framework\DataObject;
use Magento\Framework\Phrase;

/**
 * ErrorResponse
 *
 * The response type consumed by the core carrier to identify errors during the shipment request.
 *
 * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
 */
class ErrorResponse extends DataObject
{
    const REQUEST_INDEX = 'request_index';
    const ERRORS = 'errors';

    /**
     * Obtain request id (package id, sequence number).
     *
     * @return string
     */
    public function getRequestIndex(): string
    {
        return $this->getData(self::REQUEST_INDEX);
    }

    /**
     * Get errors from response.
     *
     * @return Phrase
     */
    public function getErrors(): Phrase
    {
        return $this->getData(self::ERRORS);
    }
}
