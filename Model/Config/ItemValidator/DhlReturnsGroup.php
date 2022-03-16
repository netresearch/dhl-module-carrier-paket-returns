<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Config\ItemValidator;

use Magento\Framework\Phrase;

trait DhlReturnsGroup
{
    public function getGroupCode(): string
    {
        return Group::CODE;
    }

    public function getGroupName(): Phrase
    {
        return __('DHL Parcel Germany Returns');
    }
}
