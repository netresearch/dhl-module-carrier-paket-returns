<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\ViewModel\Sales\Rma;

use Dhl\PaketReturns\Model\Carrier\Paket;
use Dhl\PaketReturns\Model\ReturnShipmentResponse\LabelDataProvider;
use Dhl\PaketReturns\Model\Sales\OrderProvider;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * View model class for displaying return shipment label data.
 *
 * @package Dhl\PaketReturns\Controller
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Label implements ArgumentInterface
{
    /**
     * @var OrderProvider
     */
    private $orderProvider;

    /**
     * @var LabelDataProvider
     */
    private $labelDataProvider;

    /**
     * Label constructor.
     *
     * @param OrderProvider $orderProvider
     * @param LabelDataProvider $labelDataProvider
     */
    public function __construct(OrderProvider $orderProvider, LabelDataProvider $labelDataProvider)
    {
        $this->orderProvider = $orderProvider;
        $this->labelDataProvider = $labelDataProvider;
    }

    /**
     * @param string $fileExt
     * @return string
     */
    public function getFileName(string $fileExt): string
    {
        $filename = sprintf(
            '%s-%s-(%s).%s',
            $this->orderProvider->getOrder()->getStore()->getFrontendName(),
            $this->orderProvider->getOrder()->getRealOrderId(),
            $this->getTrackingNumber(),
            $fileExt
        );

        return str_replace(' ', '_', $filename);
    }

    /**
     * @return string
     */
    public function getTrackingNumber(): string
    {
        $labelResponse = $this->labelDataProvider->getLabelResponse();
        return $labelResponse ? $labelResponse->getTrackingNumber() : '';
    }

    /**
     * @return string
     */
    public function getTrackingUrl(): string
    {
        $trackingNumber = $this->getTrackingNumber();
        if (!$trackingNumber) {
            return '';
        }

        return sprintf(Paket::TRACKING_URL_TEMPLATE, $trackingNumber);
    }

    /**
     * @return string
     */
    public function getShippingLabel(): string
    {
        $labelResponse = $this->labelDataProvider->getLabelResponse();
        return $labelResponse ? $labelResponse->getShippingLabelData() : '';
    }

    /**
     * @return string
     */
    public function getQrLabel()
    {
        $labelResponse = $this->labelDataProvider->getLabelResponse();
        return $labelResponse ? $labelResponse->getQRLabelData() : '';
    }
}
