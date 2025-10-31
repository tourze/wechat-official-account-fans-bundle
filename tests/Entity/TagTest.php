<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\FanTag;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(Tag::class)]
final class TagTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Tag();
    }

    /**
     * @return \Generator<string, array{string, int|string}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'tagid' => ['tagid', 500];
        yield 'name' => ['name', '测试标签名称'];
        yield 'count' => ['count', 10];
    }

    public function testBasicGettersAndSetters(): void
    {
        $account = new Account();
        $account->setName('测试账号');

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(100);
        $tag->setName('VIP会员');
        $tag->setCount(50);

        // 验证所有getter方法
        $this->assertSame($account, $tag->getAccount());
        $this->assertSame(100, $tag->getTagid());
        $this->assertSame('VIP会员', $tag->getName());
        $this->assertSame(50, $tag->getCount());
    }

    public function testCountUpdate(): void
    {
        $tag = new Tag();
        $tag->setCount(0);
        $this->assertSame(0, $tag->getCount());

        // 更新计数
        $tag->setCount(25);
        $this->assertSame(25, $tag->getCount());

        // 更新为更大的数值
        $tag->setCount(100);
        $this->assertSame(100, $tag->getCount());
    }

    public function testFanTagRelationships(): void
    {
        $account = new Account();
        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(200);
        $tag->setName('活跃用户');

        $fan1 = new Fan();
        $fan1->setOpenid('fan_001');
        $fan1->setStatus(FanStatus::Subscribed);

        $fan2 = new Fan();
        $fan2->setOpenid('fan_002');
        $fan2->setStatus(FanStatus::Subscribed);

        $fanTag1 = new FanTag();
        $fanTag1->setFan($fan1);
        $fanTag1->setTag($tag);

        $fanTag2 = new FanTag();
        $fanTag2->setFan($fan2);
        $fanTag2->setTag($tag);

        // 测试添加FanTag关系
        $tag->addFanTag($fanTag1);
        $tag->addFanTag($fanTag2);

        $fanTags = $tag->getFanTags();
        $this->assertCount(2, $fanTags);
        $this->assertTrue($fanTags->contains($fanTag1));
        $this->assertTrue($fanTags->contains($fanTag2));

        // 测试getFans方法（通过FanTag获取Fan）
        $fans = $tag->getFans();
        $this->assertCount(2, $fans);
        $openids = [];
        foreach ($fans as $fan) {
            $openids[] = $fan->getOpenid();
        }
        $this->assertContains('fan_001', $openids);
        $this->assertContains('fan_002', $openids);
    }

    public function testRemoveFanTag(): void
    {
        $tag = new Tag();
        $tag->setName('临时标签');

        $fan = new Fan();
        $fan->setOpenid('test_fan');

        $fanTag = new FanTag();
        $fanTag->setFan($fan);
        $fanTag->setTag($tag);

        // 添加关系
        $tag->addFanTag($fanTag);
        $this->assertCount(1, $tag->getFanTags());
        $this->assertTrue($tag->getFanTags()->contains($fanTag));

        // 移除关系
        $tag->removeFanTag($fanTag);
        $this->assertCount(0, $tag->getFanTags());
        $this->assertFalse($tag->getFanTags()->contains($fanTag));
    }

    public function testToStringWithName(): void
    {
        $tag = new Tag();
        $tag->setName('测试标签');
        $tag->setTagid(999);

        $this->assertSame('测试标签', (string) $tag);
    }

    public function testToStringWithoutName(): void
    {
        $tag = new Tag();
        $tag->setTagid(999);

        $this->assertSame('999', (string) $tag);
    }

    public function testToStringWithBothEmpty(): void
    {
        $tag = new Tag();
        // 当name和tagid都为null时，会返回id的字符串形式
        // 新创建的实体id默认为0
        $this->assertSame('0', (string) $tag);
    }
}
