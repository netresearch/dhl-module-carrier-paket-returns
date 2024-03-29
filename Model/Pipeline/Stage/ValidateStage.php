<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Pipeline\Stage;

use Dhl\PaketReturns\Model\Pipeline\ArtifactsContainer;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Shipment\Request;
use Magento\Shipping\Model\Shipment\ReturnShipment;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class ValidateStage implements CreateShipmentsStageInterface
{
    /**
     * Validate shipment requests.
     *
     * Invalid requests are removed from return requests and instantly added as label failures.
     *
     * @param DataObject[]|ReturnShipment[]|Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return ReturnShipment[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $callback = static function ($request, $requestIndex) use ($artifactsContainer) {
            if ($request instanceof ReturnShipment && $request->getData('is_return')) {
                return true;
            }

            /** @var ReturnShipment|Request $request */
            $message = __('Only return shipments are supported.')->render();
            $artifactsContainer->addError((string) $requestIndex, $request->getOrderShipment(), $message);
            return false;
        };

        // pass on only the shipment requests that validate
        return array_filter($requests, $callback, ARRAY_FILTER_USE_BOTH);
    }
}
