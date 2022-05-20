<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline;

use Dhl\Sdk\Paket\Retoure\Api\Data\ConfirmationInterface;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ReturnShipmentDocumentInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ReturnShipmentDocumentInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentDocumentInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentResponseInterface;
use Netresearch\ShippingCore\Api\Util\PdfCombinatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Response mapper.
 *
 * Convert API response into the carrier response format that the NR shipping core understands.
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
     * @var ReturnShipmentDocumentInterfaceFactory
     */
    private $shipmentDocumentFactory;

    /**
     * @var LabelResponseInterfaceFactory
     */
    private $labelResponseFactory;

    /**
     * @var ShipmentErrorResponseInterfaceFactory
     */
    private $errorResponseFactory;

    public function __construct(
        PdfCombinatorInterface $pdfCombinator,
        LoggerInterface $logger,
        ReturnShipmentDocumentInterfaceFactory $shipmentDocumentFactory,
        LabelResponseInterfaceFactory $labelResponseFactory,
        ShipmentErrorResponseInterfaceFactory $errorResponseFactory
    ) {
        $this->pdfCombinator = $pdfCombinator;
        $this->logger = $logger;
        $this->shipmentDocumentFactory = $shipmentDocumentFactory;
        $this->labelResponseFactory = $labelResponseFactory;
        $this->errorResponseFactory = $errorResponseFactory;
    }

    /**
     * Collect documents from response.
     *
     * @param ConfirmationInterface $confirmation
     * @return ReturnShipmentDocumentInterface[]
     */
    private function getDocuments(ConfirmationInterface $confirmation): array
    {
        $documents = [];

        if ($confirmation->getLabelData()) {
            $documents[] = $this->shipmentDocumentFactory->create(
                [
                    'data' => [
                        ShipmentDocumentInterface::TITLE => 'PDF Label',
                        ShipmentDocumentInterface::MIME_TYPE => 'application/pdf',
                        ShipmentDocumentInterface::LABEL_DATA => base64_decode($confirmation->getLabelData()),
                        ReturnShipmentDocumentInterface::TRACKING_NUMBER => $confirmation->getShipmentNumber(),
                    ]
                ]
            );
        }

        if ($confirmation->getQrLabelData()) {
            $documents[] = $this->shipmentDocumentFactory->create(
                [
                    'data' => [
                        ShipmentDocumentInterface::TITLE => 'QR Code',
                        ShipmentDocumentInterface::MIME_TYPE => 'image/png',
                        ShipmentDocumentInterface::LABEL_DATA => base64_decode($confirmation->getQrLabelData()),
                        ReturnShipmentDocumentInterface::TRACKING_NUMBER => $confirmation->getShipmentNumber(),
                    ]
                ]
            );
        }

        return $documents;
    }

    /**
     * Extract label binary from response.
     *
     * Returned file can include
     * - either the shipping label PDF,
     * - or the QR image,
     * - or both combined into a multi-page PDF.
     *
     * @param ConfirmationInterface $confirmation
     * @return string
     */
    private function getShippingLabelContent(ConfirmationInterface $confirmation): string
    {
        if ($confirmation->getLabelData() && $confirmation->getQrLabelData()) {
            try {
                return $this->pdfCombinator->combineB64PdfPages([
                    $confirmation->getLabelData(),
                    $confirmation->getQrLabelData(),
                ]);
            } catch (RuntimeException $exception) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }
        } elseif ($confirmation->getLabelData()) {
            return base64_decode($confirmation->getLabelData());
        } elseif ($confirmation->getQrLabelData()) {
            return base64_decode($confirmation->getQrLabelData());
        }

        return '';
    }

    /**
     * Map created return shipment into response object as required by the shipping module.
     *
     * @param string $requestIndex
     * @param ConfirmationInterface $confirmation
     * @param ShipmentInterface|\Magento\Rma\Model\Shipping $salesShipment
     * @return LabelResponseInterface
     */
    public function createLabelResponse(
        string $requestIndex,
        ConfirmationInterface $confirmation,
        $salesShipment
    ): LabelResponseInterface {
        $documents = $this->getDocuments($confirmation);
        $shippingLabelContent = $this->getShippingLabelContent($confirmation);

        return $this->labelResponseFactory->create([
            'data' => [
                ShipmentResponseInterface::REQUEST_INDEX => $requestIndex,
                ShipmentResponseInterface::SALES_SHIPMENT => $salesShipment,
                LabelResponseInterface::TRACKING_NUMBER => $confirmation->getShipmentNumber(),
                LabelResponseInterface::SHIPPING_LABEL_CONTENT => $shippingLabelContent,
                LabelResponseInterface::DOCUMENTS => $documents,
            ]
        ]);
    }

    /**
     * Map error message into response object as required by the shipping module.
     *
     * @param string $requestIndex
     * @param Phrase $message
     * @param ShipmentInterface|\Magento\Rma\Model\Shipping $salesShipment
     * @return ShipmentErrorResponseInterface
     */
    public function createErrorResponse(
        string $requestIndex,
        Phrase $message,
        $salesShipment
    ): ShipmentErrorResponseInterface {
        return $this->errorResponseFactory->create([
            'data' => [
                ShipmentResponseInterface::REQUEST_INDEX => $requestIndex,
                ShipmentResponseInterface::SALES_SHIPMENT => $salesShipment,
                ShipmentErrorResponseInterface::ERRORS => $message,
            ]
        ]);
    }
}
