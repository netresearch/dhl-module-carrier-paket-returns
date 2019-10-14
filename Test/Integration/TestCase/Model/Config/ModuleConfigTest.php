<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Test\Integration\TestCase\Model\Config;

use Dhl\PaketReturns\Model\Config\ModuleConfig;
use Magento\Framework\App\Config;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ModuleConfigTest
 *
 * @package Dhl\PaketReturns\Test\Integration
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
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
    protected function setUp()
    {
        parent::setUp();

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
    public static function setUpBeforeClass()
    {
        $realPath = realpath(TESTS_TEMP_DIR . '/../testsuite/Magento/Store/_files');

        include $realPath . '/core_fixturestore_rollback.php';
        include $realPath . '/core_fixturestore.php';

        parent::setUpBeforeClass();
    }

    /**
     * Delete manually added stores.
     *
     * @see setUpBeforeClass()
     */
    public static function tearDownAfterClass()
    {
        $realPath = realpath(TESTS_TEMP_DIR . '/../testsuite/Magento/Store/_files');

        include $realPath . '/core_fixturestore_rollback.php';

        parent::tearDownAfterClass();
    }

    /**
     * @test
     *
     * @magentoConfigFixture default/carriers/dhlpaketrma/version XXX
     */
    public function getModuleVersion()
    {
        self::assertSame('XXX', $this->config->getModuleVersion());
    }

    /**
     * @test
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     */
    public function isSandboxMode()
    {
        self::assertTrue($this->config->isSandboxMode());
    }

    /**
     * @test
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/production/auth_username USERNAME1
     *
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandbox/auth_username USERNAME2
     */
    public function getAuthUsername()
    {
        self::assertSame('USERNAME1', $this->config->getAuthUsername());
        self::assertSame('USERNAME2', $this->config->getAuthUsername('fixturestore'));
    }

    /**
     * @test
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/production/auth_password SECRET1
     *
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandbox/auth_password SECRET2
     */
    public function getAuthPassword()
    {
        self::assertSame($this->encryptor->decrypt('SECRET1'), $this->config->getAuthPassword());
        self::assertSame('SECRET2', $this->config->getAuthPassword('fixturestore'));
    }

    /**
     * @test
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/production/api_username USER1
     *
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandbox/api_username USER2
     */
    public function getUser()
    {
        self::assertSame('USER1', $this->config->getUser());
        self::assertSame('USER2', $this->config->getUser('fixturestore'));
    }

    /**
     * @test
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/production/api_password PASS1
     *
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandbox/api_password PASS2
     */
    public function getSignature()
    {
        self::assertSame($this->encryptor->decrypt('PASS1'), $this->config->getSignature());
        self::assertSame('PASS2', $this->config->getSignature('fixturestore'));
    }

    /**
     * @test
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/production/account_number EKP1
     *
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandbox/account_number EKP2
     */
    public function getEkp()
    {
        self::assertSame('EKP1', $this->config->getEkp());
        self::assertSame('EKP2', $this->config->getEkp('fixturestore'));
    }

    /**
     * Assert config defaults are valid.
     *
     * Exact values do not matter, just assert they are loaded properly.
     *
     * @test
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 0
     */
    public function getParticipations()
    {
        $participations = $this->config->getParticipations();

        self::assertInternalType('array', $participations);
        self::assertNotEmpty($participations);

        foreach ($participations as $procedure => $participation) {
            self::assertInternalType('string', $participation);
            self::assertSame(2, strlen($participation));
        }
    }

    /**
     * Assert config defaults are valid in sandbox mode.
     *
     * Exact values do not matter, just assert they are loaded properly and match expected format.
     *
     * @test
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     */
    public function getSandboxParticipations()
    {
        $participations = $this->config->getParticipations();

        self::assertInternalType('array', $participations);
        self::assertNotEmpty($participations);

        foreach ($participations as $procedure => $participation) {
            self::assertInternalType('string', $participation);
            self::assertSame(2, strlen($participation));
        }
    }

    /**
     * Assert that getter handles empty values properly.
     *
     * @test
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 0
     */
    public function getReceiverIds()
    {
        $receiverIds = $this->config->getReceiverIds();
        self::assertInternalType('array', $receiverIds);
        self::assertEmpty($receiverIds);
    }

    /**
     * Assert config defaults are available in sandbox mode.
     *
     * Exact values do not matter, just assert they are loaded properly and match expected format.
     *
     * @test
     *
     * @magentoConfigFixture fixturestore_store dhlshippingsolutions/dhlpaketrma/account/sandboxmode 1
     */
    public function getSandboxReceiverIds()
    {
        $receiverIds = $this->config->getReceiverIds('fixturestore');
        self::assertInternalType('array', $receiverIds);

        foreach ($receiverIds as $countryCode => $receiverId) {
            self::assertInternalType('string', $countryCode);
            self::assertSame(2, strlen($countryCode));
            self::assertInternalType('string', $receiverId);
            self::assertSame(2, strlen($receiverId));
        }
    }
}
