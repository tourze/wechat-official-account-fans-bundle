<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\WechatOfficialAccountFansBundle\Enum\Gender;

/**
 * @internal
 */
#[CoversClass(Gender::class)]
final class GenderTest extends AbstractEnumTestCase
{
    public function testValues(): void
    {
        $this->assertEquals(0, Gender::Unknown->value);
        $this->assertEquals(1, Gender::Male->value);
        $this->assertEquals(2, Gender::Female->value);
    }

    public function testLabels(): void
    {
        $this->assertEquals('未知', Gender::Unknown->getLabel());
        $this->assertEquals('男', Gender::Male->getLabel());
        $this->assertEquals('女', Gender::Female->getLabel());
    }

    public function testCasesCount(): void
    {
        $this->assertCount(3, Gender::cases());
    }

    public function testFromInt(): void
    {
        $this->assertEquals(Gender::Unknown, Gender::fromInt(0));
        $this->assertEquals(Gender::Male, Gender::fromInt(1));
        $this->assertEquals(Gender::Female, Gender::fromInt(2));

        // 测试默认值
        $this->assertEquals(Gender::Unknown, Gender::fromInt(999));
        $this->assertEquals(Gender::Unknown, Gender::fromInt(-1));
    }

    public function testToSelectItems(): void
    {
        $items = Gender::toSelectItems();

        $this->assertCount(3, $items);

        $this->assertEquals([
            'value' => 0,
            'label' => '未知',
        ], $items[0]);

        $this->assertEquals([
            'value' => 1,
            'label' => '男',
        ], $items[1]);

        $this->assertEquals([
            'value' => 2,
            'label' => '女',
        ], $items[2]);
    }

    public function testGenOptions(): void
    {
        $options = Gender::genOptions();

        $this->assertCount(3, $options);
        $this->assertArrayHasKey('value', $options[0]);
        $this->assertArrayHasKey('label', $options[0]);
    }

    public function testToArray(): void
    {
        $unknownArray = Gender::Unknown->toArray();
        $this->assertArrayHasKey('value', $unknownArray);
        $this->assertArrayHasKey('label', $unknownArray);
        $this->assertEquals(0, $unknownArray['value']);
        $this->assertEquals('未知', $unknownArray['label']);

        $maleArray = Gender::Male->toArray();
        $this->assertEquals(1, $maleArray['value']);
        $this->assertEquals('男', $maleArray['label']);

        $femaleArray = Gender::Female->toArray();
        $this->assertEquals(2, $femaleArray['value']);
        $this->assertEquals('女', $femaleArray['label']);
    }
}
