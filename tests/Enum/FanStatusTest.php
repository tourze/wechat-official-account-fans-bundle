<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;

/**
 * @internal
 */
#[CoversClass(FanStatus::class)]
final class FanStatusTest extends AbstractEnumTestCase
{
    public function testValues(): void
    {
        $this->assertEquals('subscribed', FanStatus::Subscribed->value);
        $this->assertEquals('unsubscribed', FanStatus::Unsubscribed->value);
        $this->assertEquals('blocked', FanStatus::Blocked->value);
    }

    public function testLabels(): void
    {
        $this->assertEquals('已关注', FanStatus::Subscribed->getLabel());
        $this->assertEquals('已取消关注', FanStatus::Unsubscribed->getLabel());
        $this->assertEquals('已拉黑', FanStatus::Blocked->getLabel());
    }

    public function testCasesCount(): void
    {
        $this->assertCount(3, FanStatus::cases());
    }

    public function testToSelectItems(): void
    {
        $items = FanStatus::toSelectItems();

        $this->assertCount(3, $items);

        $this->assertEquals([
            'value' => 'subscribed',
            'label' => '已关注',
        ], $items[0]);

        $this->assertEquals([
            'value' => 'unsubscribed',
            'label' => '已取消关注',
        ], $items[1]);

        $this->assertEquals([
            'value' => 'blocked',
            'label' => '已拉黑',
        ], $items[2]);
    }

    public function testGenOptions(): void
    {
        $options = FanStatus::genOptions();

        $this->assertCount(3, $options);
        $this->assertArrayHasKey('value', $options[0]);
        $this->assertArrayHasKey('label', $options[0]);
    }

    public function testToArray(): void
    {
        $subscribedArray = FanStatus::Subscribed->toArray();
        $this->assertArrayHasKey('value', $subscribedArray);
        $this->assertArrayHasKey('label', $subscribedArray);
        $this->assertEquals('subscribed', $subscribedArray['value']);
        $this->assertEquals('已关注', $subscribedArray['label']);

        $unsubscribedArray = FanStatus::Unsubscribed->toArray();
        $this->assertEquals('unsubscribed', $unsubscribedArray['value']);
        $this->assertEquals('已取消关注', $unsubscribedArray['label']);

        $blockedArray = FanStatus::Blocked->toArray();
        $this->assertEquals('blocked', $blockedArray['value']);
        $this->assertEquals('已拉黑', $blockedArray['label']);
    }
}
