<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Webservice\Pipeline\ReturnShipment;

use Dhl\PaketReturns\Model\ReturnShipmentResponse\ErrorResponse;
use Dhl\PaketReturns\Model\ReturnShipmentResponse\LabelResponse;
use Dhl\Sdk\Paket\Retoure\Api\Data\ConfirmationInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;

/**
 * Class ArtifactsContainer
 *
 * @package Dhl\PaketReturns\Webservice
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
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
     * @var LabelResponse[]
     */
    private $labelResponses = [];

    /**
     * Error response suitable for processing by the core. Contains request id / sequence number.
     *
     * @var ErrorResponse[]
     */
    private $errorResponses = [];

    /**
     * Set store id for the pipeline.
     *
     * @param int $storeId
     * @return void
     */
    public function setStoreId(int $storeId)
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
     * @param string $errorMessage
     *
     * @return void
     */
    public function addError(string $requestIndex, string $errorMessage)
    {
        $this->errors[$requestIndex] = $errorMessage;
    }

    /**
     * Add a prepared request object, ready for the web service call.
     *
     * @param string $requestIndex
     * @param \JsonSerializable $returnOrder
     * @return void
     */
    public function addApiRequest(string $requestIndex, \JsonSerializable $returnOrder)
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
    public function addApiResponse(string $requestIndex, ConfirmationInterface $apiResponse)
    {
        $this->apiResponses[$requestIndex] = $apiResponse;
    }

    /**
     * Add positive label response.
     *
     * @param string $requestIndex
     * @param LabelResponse $labelResponse
     * @return void
     */
    public function addLabelResponse(string $requestIndex, LabelResponse $labelResponse)
    {
        $this->labelResponses[$requestIndex] = $labelResponse;
    }

    /**
     * Add label error.
     *
     * @param string $requestIndex
     * @param ErrorResponse $errorResponse
     * @return void
     */
    public function addErrorResponse(string $requestIndex, ErrorResponse $errorResponse)
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
     * @return LabelResponse[]
     */
    public function getLabelResponses(): array
    {
        return $this->labelResponses;
    }

    /**
     * Obtain the label errors occurred during web service call.
     *
     * @return ErrorResponse[]
     */
    public function getErrorResponses(): array
    {
        return $this->errorResponses;
    }
}
