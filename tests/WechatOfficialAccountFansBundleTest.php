<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\WechatOfficialAccountFansBundle\WechatOfficialAccountFansBundle;

/**
 * @internal
 */
#[CoversClass(WechatOfficialAccountFansBundle::class)]
#[RunTestsInSeparateProcesses]
final class WechatOfficialAccountFansBundleTest extends AbstractBundleTestCase
{
}
