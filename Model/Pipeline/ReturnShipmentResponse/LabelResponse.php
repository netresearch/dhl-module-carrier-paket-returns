<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse;

use Magento\Framework\DataObject;

/**
 * The return shipment label response.
 */
class LabelResponse extends DataObject
{
    public const REQUEST_INDEX = 'request_index';
    public const TRACKING_NUMBER = 'tracking_number';
    public const SHIPPING_LABEL_CONTENT = 'shipping_label_content';
    public const SHIPPING_LABEL_DATA = 'shipping_label_data';
    public const QR_LABEL_DATA = 'qr_label_data';

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
     * Get tracking number from response.
     *
     * @return string
     */
    public function getTrackingNumber(): string
    {
        return $this->getData(self::TRACKING_NUMBER);
    }

    /**
     * Get (combined) PDF label binary from response.
     *
     * @return string Label, QR, or both.
     */
    public function getShippingLabelContent(): string
    {
        return $this->getData(self::SHIPPING_LABEL_CONTENT);
    }

    /**
     * Get b64 encoded PDF label data.
     *
     * @return string
     */
    public function getShippingLabelData(): string
    {
        return $this->getData(self::SHIPPING_LABEL_DATA);
    }

    /**
     * Get b64 encoded QR code image data.
     *
     * @return string
     */
    public function getQRLabelData(): string
    {
        return $this->getData(self::QR_LABEL_DATA);
    }
}
