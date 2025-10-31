<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\WechatOfficialAccountFansBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    protected function onSetUp(): void
    {
        // 从容器中获取服务实例，而不是直接实例化
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
    }

    public function testInvokeBasicFunctionality(): void
    {
        $rootItem = $this->createMock(ItemInterface::class);

        $rootItem
            ->expects($this->atLeastOnce())
            ->method('getChild')
            ->willReturn(null)
        ;

        $rootItem
            ->expects($this->atLeastOnce())
            ->method('addChild')
        ;

        ($this->adminMenu)($rootItem);

        // 添加一个显式断言来避免risky警告
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
    }
}
