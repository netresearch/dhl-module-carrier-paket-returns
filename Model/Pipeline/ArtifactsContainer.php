<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline;

use Dhl\Sdk\ParcelDe\Returns\Api\Data\ConfirmationInterface;
use Magento\Rma\Model\Shipping;
use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\Order\Shipment;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;

class ArtifactsContainer implements ArtifactsContainerInterface
{
    /**
     * Store id the pipeline runs for.
     *
     * @var int|null
     */
    private $storeId;

    /**
     * Error messages occurred during pipeline execution.
     *
     * @var string[]
     */
    private $errors = [];

    /**
     * API (SDK) request objects.
     *
     * @var \JsonSerializable[]
     */
    private $apiRequests = [];

    /**
     * API (SDK) response objects.
     *
     * @var ConfirmationInterface[]
     */
    private $apiResponses = [];

    /**
     * Label response suitable for processing by the core.
     *
     * @var LabelResponseInterface[]
     */
    private $labelResponses = [];

    /**
     * Error response suitable for processing by the core. Contains request id / sequence number.
     *
     * @var ShipmentErrorResponseInterface[]
     */
    private $errorResponses = [];

    /**
     * Set store id for the pipeline.
     *
     * @param int $storeId
     * @return void
     */
    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * Add error message for a shipment request.
     *
     * Text errors must only be added if the web service call did not return
     * a response for the particular request item. For errors returned from the
     * web service, use an error object.
     *
     * @see addErrorResponse
     *
     * @param string $requestIndex
     * @param Shipment|Shipping $shipment
     * @param string $errorMessage
     */
    public function addError(string $requestIndex, AbstractModel $shipment, string $errorMessage): void
    {
        $this->errors[$requestIndex] = [
            'shipment' => $shipment,
            'message' => $errorMessage,
        ];
    }

    /**
     * Add a prepared request object, ready for the web service call.
     *
     * @param string $requestIndex
     * @param \JsonSerializable $returnOrder
     * @return void
     */
    public function addApiRequest(string $requestIndex, \JsonSerializable $returnOrder): void
    {
        $this->apiRequests[$requestIndex] = $returnOrder;
    }

    /**
     * Add a received response object.
     *
     * @param string $requestIndex
     * @param ConfirmationInterface $apiResponse
     * @return void
     */
    public function addApiResponse(string $requestIndex, ConfirmationInterface $apiResponse): void
    {
        $this->apiResponses[$requestIndex] = $apiResponse;
    }

    /**
     * Add positive label response.
     *
     * @param string $requestIndex
     * @param LabelResponseInterface $labelResponse
     * @return void
     */
    public function addLabelResponse(string $requestIndex, LabelResponseInterface $labelResponse): void
    {
        $this->labelResponses[$requestIndex] = $labelResponse;
    }

    /**
     * Add label error.
     *
     * @param string $requestIndex
     * @param ShipmentErrorResponseInterface $errorResponse
     * @return void
     */
    public function addErrorResponse(string $requestIndex, ShipmentErrorResponseInterface $errorResponse): void
    {
        $this->errorResponses[$requestIndex] = $errorResponse;
    }

    /**
     * Get store id for the pipeline.
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return (int) $this->storeId;
    }

    /**
     * Obtain the error messages which occurred during pipeline execution.
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtain the prepared request objects, ready for the web service call.
     *
     * @return \JsonSerializable[]
     */
    public function getApiRequests(): array
    {
        return $this->apiRequests;
    }

    /**
     * Obtain the response objects as received from the web service.
     *
     * @return ConfirmationInterface[]
     */
    public function getApiResponses(): array
    {
        return $this->apiResponses;
    }

    /**
     * Obtain the labels retrieved from the web service.
     *
     * @return LabelResponseInterface[]
     */
    public function getLabelResponses(): array
    {
        return $this->labelResponses;
    }

    /**
     * Obtain the label errors occurred during web service call.
     *
     * @return ShipmentErrorResponseInterface[]
     */
    public function getErrorResponses(): array
    {
        return $this->errorResponses;
    }
}
