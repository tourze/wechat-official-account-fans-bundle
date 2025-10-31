<?php

namespace Tourze\WechatOfficialAccountFansBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;
use WechatOfficialAccountBundle\DataFixtures\AccountFixtures;
use WechatOfficialAccountBundle\Entity\Account;

#[When(env: 'test')]
#[When(env: 'dev')]
final class TagFixtures extends Fixture
{
    public const TEST_TAG_VIP_REFERENCE = 'test-tag-vip';
    public const TEST_TAG_NEWBIE_REFERENCE = 'test-tag-newbie';
    public const TEST_TAG_ACTIVE_REFERENCE = 'test-tag-active';

    public function load(ObjectManager $manager): void
    {
        // 尝试获取外部Account，如果不存在则创建一个测试用的Account
        $account = null;
        try {
            $account = $this->getReference(AccountFixtures::ACCOUNT_REFERENCE, Account::class);
        } catch (\OutOfBoundsException $e) {
            // 如果外部Account不存在，创建一个测试用的Account
            $account = new Account();
            $account->setAppId('test_app_id_' . uniqid());
            $account->setAppSecret('test_app_secret_' . uniqid());
            $account->setName('测试公众号');
            $account->setToken('test_token_' . uniqid());
            $account->setEncodingAesKey('test_encoding_aes_key_' . uniqid() . uniqid() . uniqid());
            $account->setValid(true);

            $manager->persist($account);
            $this->addReference(AccountFixtures::ACCOUNT_REFERENCE, $account);
        }

        // 创建VIP标签
        $vipTag = new Tag();
        $vipTag->setAccount($account);
        $vipTag->setTagid(1001);
        $vipTag->setName('VIP会员');
        $vipTag->setCount(0); // 初始为0，后面会通过关联更新

        $manager->persist($vipTag);
        $this->addReference(self::TEST_TAG_VIP_REFERENCE, $vipTag);

        // 创建新手标签
        $newbieTag = new Tag();
        $newbieTag->setAccount($account);
        $newbieTag->setTagid(1002);
        $newbieTag->setName('新手用户');
        $newbieTag->setCount(0);

        $manager->persist($newbieTag);
        $this->addReference(self::TEST_TAG_NEWBIE_REFERENCE, $newbieTag);

        // 创建活跃用户标签
        $activeTag = new Tag();
        $activeTag->setAccount($account);
        $activeTag->setTagid(1003);
        $activeTag->setName('活跃用户');
        $activeTag->setCount(0);

        $manager->persist($activeTag);
        $this->addReference(self::TEST_TAG_ACTIVE_REFERENCE, $activeTag);

        $manager->flush();
    }
}
