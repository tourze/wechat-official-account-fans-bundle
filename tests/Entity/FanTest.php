<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\FanTag;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;
use Tourze\WechatOfficialAccountFansBundle\Enum\Gender;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(Fan::class)]
final class FanTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Fan();
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'openid' => ['openid', 'test_openid_123'];
        yield 'unionid' => ['unionid', 'test_unionid_456'];
        yield 'nickname' => ['nickname', '测试用户昵称'];
        yield 'headimgurl' => ['headimgurl', 'http://example.com/avatar.jpg'];
        yield 'language' => ['language', 'zh_CN'];
        yield 'city' => ['city', '深圳市'];
        yield 'province' => ['province', '广东省'];
        yield 'country' => ['country', '中国'];
        yield 'remark' => ['remark', 'VIP用户备注'];
    }

    public function testBasicGettersAndSetters(): void
    {
        $account = new Account();
        $account->setName('测试账号');

        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid('test_openid_123');
        $fan->setNickname('测试用户');
        $fan->setStatus(FanStatus::Subscribed);
        $fan->setSex(Gender::Male);
        $fan->setCity('深圳');
        $fan->setProvince('广东');
        $fan->setCountry('中国');
        $fan->setLanguage('zh_CN');
        $fan->setHeadimgurl('http://example.com/avatar.jpg');
        $fan->setUnionid('test_union_id');
        $fan->setRemark('VIP用户');

        // 验证所有getter方法
        $this->assertSame($account, $fan->getAccount());
        $this->assertSame('test_openid_123', $fan->getOpenid());
        $this->assertSame('测试用户', $fan->getNickname());
        $this->assertSame(FanStatus::Subscribed, $fan->getStatus());
        $this->assertSame(Gender::Male, $fan->getSex());
        $this->assertSame('深圳', $fan->getCity());
        $this->assertSame('广东', $fan->getProvince());
        $this->assertSame('中国', $fan->getCountry());
        $this->assertSame('zh_CN', $fan->getLanguage());
        $this->assertSame('http://example.com/avatar.jpg', $fan->getHeadimgurl());
        $this->assertSame('test_union_id', $fan->getUnionid());
        $this->assertSame('VIP用户', $fan->getRemark());
    }

    public function testSubscribeTimeGetterAndSetter(): void
    {
        $fan = new Fan();
        $subscribeTime = new \DateTimeImmutable('2024-01-01 12:00:00');
        $fan->setSubscribeTime($subscribeTime);

        $this->assertSame($subscribeTime, $fan->getSubscribeTime());
        $this->assertSame('2024-01-01 12:00:00', $fan->getSubscribeTime()->format('Y-m-d H:i:s'));
    }

    public function testFanTagRelationships(): void
    {
        $fan = new Fan();
        $fan->setOpenid('test_fan');

        $tag1 = new Tag();
        $tag1->setTagid(1);
        $tag1->setName('VIP用户');

        $tag2 = new Tag();
        $tag2->setTagid(2);
        $tag2->setName('活跃用户');

        $fanTag1 = new FanTag();
        $fanTag1->setFan($fan);
        $fanTag1->setTag($tag1);

        $fanTag2 = new FanTag();
        $fanTag2->setFan($fan);
        $fanTag2->setTag($tag2);

        // 测试添加FanTag关系
        $fan->addFanTag($fanTag1);
        $fan->addFanTag($fanTag2);

        $fanTags = $fan->getFanTags();
        $this->assertCount(2, $fanTags);
        $this->assertTrue($fanTags->contains($fanTag1));
        $this->assertTrue($fanTags->contains($fanTag2));

        // 测试getTags方法（通过FanTag获取Tag）
        $tags = $fan->getTags();
        $this->assertCount(2, $tags);
        $tagNames = [];
        foreach ($tags as $tag) {
            $tagNames[] = $tag->getName();
        }
        $this->assertContains('VIP用户', $tagNames);
        $this->assertContains('活跃用户', $tagNames);
    }

    public function testRemoveFanTag(): void
    {
        $fan = new Fan();
        $tag = new Tag();
        $tag->setName('临时标签');

        $fanTag = new FanTag();
        $fanTag->setFan($fan);
        $fanTag->setTag($tag);

        // 添加关系
        $fan->addFanTag($fanTag);
        $this->assertCount(1, $fan->getFanTags());
        $this->assertTrue($fan->getFanTags()->contains($fanTag));

        // 移除关系
        $fan->removeFanTag($fanTag);
        $this->assertCount(0, $fan->getFanTags());
        $this->assertFalse($fan->getFanTags()->contains($fanTag));
    }

    public function testToStringWithNickname(): void
    {
        $fan = new Fan();
        $fan->setOpenid('test_openid');
        $fan->setNickname('测试昵称');

        $this->assertSame('测试昵称', (string) $fan);
    }

    public function testToStringWithoutNickname(): void
    {
        $fan = new Fan();
        $fan->setOpenid('test_openid_only');

        $this->assertSame('test_openid_only', (string) $fan);
    }

    public function testToStringWithBothEmpty(): void
    {
        $fan = new Fan();
        // 当nickname和openid都为null时，会返回id的字符串形式
        // 新创建的实体id默认为0
        $this->assertSame('0', (string) $fan);
    }
}
