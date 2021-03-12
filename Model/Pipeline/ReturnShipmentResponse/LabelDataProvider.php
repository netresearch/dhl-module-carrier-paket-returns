<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline\ReturnShipmentResponse;

/**
 * Registry for passing a web service response through the application.
 */
class LabelDataProvider
{
    /**
     * @var LabelResponse
     */
    private $labelResponse;

    public function setLabelResponse(LabelResponse $labelResponse)
    {
        $this->labelResponse = $labelResponse;
    }

    public function getLabelResponse(): ?LabelResponse
    {
        return $this->labelResponse;
    }
}
