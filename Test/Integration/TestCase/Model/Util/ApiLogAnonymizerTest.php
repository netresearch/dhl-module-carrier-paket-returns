<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Test\Integration\TestCase\Model\Util;

use Netresearch\ShippingCore\Model\Util\ApiLogAnonymizer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ApiLogAnonymizerTest extends TestCase
{
    /**
     * @return string[][][]
     */
    public function getLogs(): array
    {
        return [
            'return' => [
                ['message' => file_get_contents(__DIR__ . '/../../../Provider/_files/return_log_orig.txt')],
                ['message' => file_get_contents(__DIR__ . '/../../../Provider/_files/return_log_anon.txt')],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getLogs
     *
     * @param string[] $originalRecord
     * @param string[] $expectedRecord
     */
    public function stripSensitiveData(array $originalRecord, array $expectedRecord)
    {
        /** @var ApiLogAnonymizer $anonymizer */
        $anonymizer = Bootstrap::getObjectManager()->create(ApiLogAnonymizer::class, ['replacement' => '[test]']);
        $actualRecord = $anonymizer($originalRecord);
        self::assertSame($expectedRecord, $actualRecord);
    }
}
