<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline;

use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse\ErrorResponse;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse\ErrorResponseFactory;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse\LabelResponse;
use Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse\LabelResponseFactory;
use Dhl\Sdk\Paket\Retoure\Api\Data\ConfirmationInterface;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Phrase;
use Netresearch\ShippingCore\Api\Util\PdfCombinatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Response mapper.
 *
 * Convert API response into the carrier response format that the shipping module understands.
 *
 * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::requestToShipment
 */
class ResponseDataMapper
{
    /**
     * @var PdfCombinatorInterface
     */
    private $pdfCombinator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LabelResponseFactory
     */
    private $labelResponseFactory;

    /**
     * @var ErrorResponseFactory
     */
    private $errorResponseFactory;

    public function __construct(
        PdfCombinatorInterface $pdfCombinator,
        LabelResponseFactory $labelResponseFactory,
        ErrorResponseFactory $errorResponseFactory
    ) {
        $this->pdfCombinator = $pdfCombinator;
        $this->labelResponseFactory = $labelResponseFactory;
        $this->errorResponseFactory = $errorResponseFactory;
    }

    /**
     * Map created return shipment into response object as required by the shipping module.
     *
     * @param string $requestIndex
     * @param ConfirmationInterface $confirmation
     *
     * @return LabelResponse
     */
    public function createLabelResponse(string $requestIndex, ConfirmationInterface $confirmation): LabelResponse
    {
        try {
            // Merge all labels together
            $shippingLabelContent = $this->pdfCombinator->combineB64PdfPages([
                $confirmation->getLabelData(),
                $confirmation->getQrLabelData(),
            ]);
        } catch (RuntimeException $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            $shippingLabelContent = '';
        }

        return $this->labelResponseFactory->create([
            'data' => [
                LabelResponse::REQUEST_INDEX => $requestIndex,
                LabelResponse::TRACKING_NUMBER => $confirmation->getShipmentNumber(),
                LabelResponse::SHIPPING_LABEL_CONTENT => $shippingLabelContent,
                LabelResponse::SHIPPING_LABEL_DATA => $confirmation->getLabelData(),
                LabelResponse::QR_LABEL_DATA => $confirmation->getQrLabelData(),
            ]
        ]);
    }

    /**
     * Map error message into response object as required by the shipping module.
     *
     * @param string $requestIndex
     * @param Phrase $message
     *
     * @return ErrorResponse
     */
    public function createErrorResponse(string $requestIndex, Phrase $message): ErrorResponse
    {
        return $this->errorResponseFactory->create([
            'data' => [
                ErrorResponse::REQUEST_INDEX => $requestIndex,
                ErrorResponse::ERRORS => $message,
            ]
        ]);
    }
}
