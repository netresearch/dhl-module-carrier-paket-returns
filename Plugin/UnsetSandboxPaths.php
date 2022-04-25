<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Plugin;

use Magento\Config\App\Config\Source\DumpConfigSourceAggregated;

/**
 * Unset sandbox config paths.
 *
 * Sandbox config defaults are static values distributed between environments
 * via config.xml file. There is no need to dump them to the config.php or
 * env.php files. Doing so causes issues when importing them as the necessary
 * backend model is not declared in system.xml
 */
class UnsetSandboxPaths
{
    /**
     * Prevent `account/sandbox_*` settings from being dumped on `app:config:dump` command.
     *
     * @param DumpConfigSourceAggregated $subject
     * @param string[][][][] $result
     * @return string[][][][]
     */
    public function afterGet(DumpConfigSourceAggregated $subject, $result): array
    {
        unset($result['default']['dhlshippingsolutions']['dhlpaketrma']['account']['sandbox']['auth_username']);
        unset($result['default']['dhlshippingsolutions']['dhlpaketrma']['account']['sandbox']['auth_password']);
        unset($result['default']['dhlshippingsolutions']['dhlpaketrma']['account']['sandbox']['api_username']);
        unset($result['default']['dhlshippingsolutions']['dhlpaketrma']['account']['sandbox']['api_password']);
        unset($result['default']['dhlshippingsolutions']['dhlpaketrma']['account']['sandbox']['account_number']);
        unset($result['default']['dhlshippingsolutions']['dhlpaketrma']['account']['sandbox']['account_participations']);
        unset($result['default']['dhlshippingsolutions']['dhlpaketrma']['account']['sandbox']['receiver_ids']);

        return $result;
    }
}
