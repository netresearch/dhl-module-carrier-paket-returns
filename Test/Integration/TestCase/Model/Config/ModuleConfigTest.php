<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Test\Integration\TestCase\Model\Config;

use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ModuleConfigTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Init object manager and test subject
     */
    #[\Override]
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->config = $this->objectManager->create(ModuleConfig::class);
        $this->encryptor = $this->objectManager->create(EncryptorInterface::class);
    }

    /**
     * Config fixtures are loaded before data fixtures. Config fixtures for
     * non-existent stores will fail. We need to set the stores up first manually.
     *
     * @link http://magento.stackexchange.com/a/93961
     */
    #[\Override]
    public static function setUpBeforeClass(): void
    {
        $realPath = realpath(TESTS_TEMP_DIR . '/../testsuite/Magento/Store/_files');

        include $realPath . '/core_fixturestore_rollback.php';
        include $realPath . '/core_fixturestore.php';
    }

    /**
     * Delete manually added stores.
     *
     * @see setUpBeforeClass()
     */
    #[\Override]
    public static function tearDownAfterClass(): void
    {
        $realPath = realpath(TESTS_TEMP_DIR . '/../testsuite/Magento/Store/_files');

        include $realPath . '/core_fixturestore_rollback.php';
    }

    /**
     * @magentoConfigFixture default/carriers/dhlpaketrma/version XXX
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function getModuleVersion()
    {
        self::assertSame('XXX', $this->config->getModuleVersion());
    }

    /**
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function isSandboxMode()
    {
        self::assertTrue($this->config->isSandboxMode());
    }

    /**
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/production/api_username USER1
     *
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandbox/api_username USER2
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function getUser()
    {
        self::assertSame('USER1', $this->config->getUser());
        self::assertSame('USER2', $this->config->getUser('fixturestore'));
    }

    /**
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/production/api_password PASS1
     *
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandbox/api_password PASS2
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function getSignature(): void
    {
//        self::markTestIncomplete('encryption/decryption does not work with config fixtures');

        self::assertSame('PASS1', $this->config->getPassword());
        self::assertSame('PASS2', $this->config->getPassword('fixturestore'));
    }

    /**
     * Assert that getter handles empty values properly.
     *
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 0
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function getReceiverIds()
    {
        $receiverIds = $this->config->getReceiverIds();
        self::assertTrue(\is_array($receiverIds));
        self::assertEmpty($receiverIds);
    }

    /**
     * Assert config defaults are available in sandbox mode.
     *
     * Exact values do not matter, just assert they are loaded properly and match expected format.
     *
     *
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function getSandboxReceiverIds()
    {
        $receiverIds = $this->config->getReceiverIds('fixturestore');
        self::assertTrue(\is_array($receiverIds));

        foreach ($receiverIds as $countryCode => $receiverId) {
            self::assertTrue(\is_string($countryCode));
            self::assertSame(2, strlen($countryCode));
            self::assertTrue(\is_string($receiverId));
            self::assertSame(3, strlen($receiverId));
        }
    }
}
