<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\WechatOfficialAccountFansBundle\DependencyInjection\WechatOfficialAccountFansExtension;

/**
 * @internal
 */
#[CoversClass(WechatOfficialAccountFansExtension::class)]
final class WechatOfficialAccountFansExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function getExtensionClass(): string
    {
        return WechatOfficialAccountFansExtension::class;
    }

    protected function getExpectedAlias(): string
    {
        return 'wechat_official_account_fans';
    }

    /**
     * EasyAdmin controllers are auto-discovered, so exclude Controller directory
     */
    protected function provideServiceDirectories(): iterable
    {
        yield 'Command';
        yield 'Service';
        yield 'Repository';
        yield 'EventSubscriber';
        yield 'MessageHandler';
        yield 'Procedure';
    }

    public function testGetConfigDir(): void
    {
        $extension = new WechatOfficialAccountFansExtension();

        $reflection = new \ReflectionClass($extension);
        $method = $reflection->getMethod('getConfigDir');
        $method->setAccessible(true);

        $configDir = $method->invoke($extension);

        $this->assertIsString($configDir);
        $this->assertStringEndsWith('/Resources/config', $configDir);
    }
}
