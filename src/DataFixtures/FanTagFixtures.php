<?php

namespace Tourze\WechatOfficialAccountFansBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\FanTag;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;

#[When(env: 'test')]
#[When(env: 'dev')]
final class FanTagFixtures extends Fixture implements DependentFixtureInterface
{
    public const TEST_FAN_TAG_VIP_REFERENCE = 'test-fan-tag-vip';
    public const TEST_FAN_TAG_NEWBIE_REFERENCE = 'test-fan-tag-newbie';
    public const TEST_FAN_TAG_ACTIVE_REFERENCE = 'test-fan-tag-active';

    public function load(ObjectManager $manager): void
    {
        // 获取引用
        $testFan = $this->getReference(FanFixtures::TEST_FAN_REFERENCE, Fan::class);
        $maleFan = $this->getReference(FanFixtures::TEST_FAN_MALE_REFERENCE, Fan::class);
        $femaleFan = $this->getReference(FanFixtures::TEST_FAN_FEMALE_REFERENCE, Fan::class);

        $vipTag = $this->getReference(TagFixtures::TEST_TAG_VIP_REFERENCE, Tag::class);
        $newbieTag = $this->getReference(TagFixtures::TEST_TAG_NEWBIE_REFERENCE, Tag::class);
        $activeTag = $this->getReference(TagFixtures::TEST_TAG_ACTIVE_REFERENCE, Tag::class);

        // 测试粉丝 - VIP标签
        $fanTagVip = new FanTag();
        $fanTagVip->setFan($testFan);
        $fanTagVip->setTag($vipTag);

        $manager->persist($fanTagVip);
        $this->addReference(self::TEST_FAN_TAG_VIP_REFERENCE, $fanTagVip);

        // 男性粉丝 - 新手标签
        $fanTagNewbie = new FanTag();
        $fanTagNewbie->setFan($maleFan);
        $fanTagNewbie->setTag($newbieTag);

        $manager->persist($fanTagNewbie);
        $this->addReference(self::TEST_FAN_TAG_NEWBIE_REFERENCE, $fanTagNewbie);

        // 女性粉丝 - 活跃用户标签
        $fanTagActive = new FanTag();
        $fanTagActive->setFan($femaleFan);
        $fanTagActive->setTag($activeTag);

        $manager->persist($fanTagActive);
        $this->addReference(self::TEST_FAN_TAG_ACTIVE_REFERENCE, $fanTagActive);

        // 测试粉丝也是活跃用户（多标签测试）
        $fanTagActive2 = new FanTag();
        $fanTagActive2->setFan($testFan);
        $fanTagActive2->setTag($activeTag);

        $manager->persist($fanTagActive2);

        $manager->flush();

        // 更新标签计数
        $vipTag->setCount(1);
        $newbieTag->setCount(1);
        $activeTag->setCount(2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            FanFixtures::class,
            TagFixtures::class,
        ];
    }
}
