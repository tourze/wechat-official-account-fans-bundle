<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\FanTag;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;

/**
 * @internal
 */
#[CoversClass(FanTag::class)]
final class FanTagTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new FanTag();
    }

    /**
     * @return \Generator<string, array{string, \DateTimeImmutable}>
     */
    public static function propertiesProvider(): iterable
    {
        // FanTag uses TimestampableAware trait which provides createTime and updateTime
        // These are DateTime properties that get set automatically
        $dateTime = new \DateTimeImmutable();
        yield 'createTime' => ['createTime', $dateTime];
        yield 'updateTime' => ['updateTime', $dateTime];
    }

    public function testBasicGettersAndSetters(): void
    {
        $fan = new Fan();
        $fan->setOpenid('test_fan_123');
        $fan->setNickname('测试粉丝');

        $tag = new Tag();
        $tag->setTagid(500);
        $tag->setName('关系测试标签');

        $fanTag = new FanTag();
        $fanTag->setFan($fan);
        $fanTag->setTag($tag);

        // 验证getter方法
        $this->assertSame($fan, $fanTag->getFan());
        $this->assertSame($tag, $fanTag->getTag());
        $this->assertSame('test_fan_123', $fanTag->getFan()->getOpenid());
        $this->assertSame('测试粉丝', $fanTag->getFan()->getNickname());
        $this->assertSame(500, $fanTag->getTag()->getTagid());
        $this->assertSame('关系测试标签', $fanTag->getTag()->getName());
    }

    public function testSetNullRelationships(): void
    {
        $fanTag = new FanTag();

        // 初始状态应为null
        $this->assertNull($fanTag->getFan());
        $this->assertNull($fanTag->getTag());

        // 设置实体后再设置为null
        $fan = new Fan();
        $tag = new Tag();

        $fanTag->setFan($fan);
        $fanTag->setTag($tag);
        $this->assertNotNull($fanTag->getFan());
        $this->assertNotNull($fanTag->getTag());

        $fanTag->setFan(null);
        $fanTag->setTag(null);
        $this->assertNull($fanTag->getFan());
        $this->assertNull($fanTag->getTag());
    }

    public function testToStringWithFullData(): void
    {
        $fan = new Fan();
        $fan->setOpenid('string_test_fan');
        $fan->setNickname('测试粉丝');

        $tag = new Tag();
        $tag->setTagid(600);
        $tag->setName('测试标签');

        $fanTag = new FanTag();
        $fanTag->setFan($fan);
        $fanTag->setTag($tag);

        $expected = 'Fan(测试粉丝) - Tag(测试标签)';
        $this->assertSame($expected, (string) $fanTag);
    }

    public function testToStringWithNullFan(): void
    {
        $tag = new Tag();
        $tag->setName('测试标签');

        $fanTag = new FanTag();
        $fanTag->setTag($tag);

        $expected = 'Fan() - Tag(测试标签)';
        $this->assertSame($expected, (string) $fanTag);
    }

    public function testToStringWithNullTag(): void
    {
        $fan = new Fan();
        $fan->setNickname('测试粉丝');

        $fanTag = new FanTag();
        $fanTag->setFan($fan);

        $expected = 'Fan(测试粉丝) - Tag()';
        $this->assertSame($expected, (string) $fanTag);
    }

    public function testToStringWithBothNull(): void
    {
        $fanTag = new FanTag();

        $expected = 'Fan() - Tag()';
        $this->assertSame($expected, (string) $fanTag);
    }

    public function testUniqueConstraintScenario(): void
    {
        // 这个测试模拟唯一约束的场景
        // 在实际数据库中，相同的fan和tag组合应该是唯一的
        $fan = new Fan();
        $fan->setOpenid('unique_fan');

        $tag = new Tag();
        $tag->setTagid(700);
        $tag->setName('唯一性测试标签');

        $fanTag1 = new FanTag();
        $fanTag1->setFan($fan);
        $fanTag1->setTag($tag);

        $fanTag2 = new FanTag();
        $fanTag2->setFan($fan);
        $fanTag2->setTag($tag);

        // 验证两个FanTag对象虽然引用相同的Fan和Tag，但它们是不同的对象
        $this->assertNotSame($fanTag1, $fanTag2);
        $this->assertSame($fanTag1->getFan(), $fanTag2->getFan());
        $this->assertSame($fanTag1->getTag(), $fanTag2->getTag());
    }
}
