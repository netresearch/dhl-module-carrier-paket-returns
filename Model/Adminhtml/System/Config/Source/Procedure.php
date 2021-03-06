<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Model\Adminhtml\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Procedure implements OptionSourceInterface
{
    public const PROCEDURE_RETURNSHIPMENT_NATIONAL = '07';
    public const PROCEDURE_RETURNSHIPMENT_INTERNATIONAL = '53';

    /**
     * Options getter.
     *
     * @return mixed[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::PROCEDURE_RETURNSHIPMENT_NATIONAL,
                'label' => __('DHL Paket Returns National'),
            ],
            [
                'value' => self::PROCEDURE_RETURNSHIPMENT_INTERNATIONAL,
                'label' => __('DHL Paket Returns International'),
            ],
        ];
    }
}
