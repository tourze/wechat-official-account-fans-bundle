<?php

namespace Tourze\WechatOfficialAccountFansBundle\DataFixtures;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;
use Tourze\WechatOfficialAccountFansBundle\Enum\Gender;
use WechatOfficialAccountBundle\DataFixtures\AccountFixtures;
use WechatOfficialAccountBundle\Entity\Account;

#[When(env: 'test')]
#[When(env: 'dev')]
final class FanFixtures extends Fixture
{
    public const TEST_FAN_REFERENCE = 'test-fan';
    public const TEST_FAN_MALE_REFERENCE = 'test-fan-male';
    public const TEST_FAN_FEMALE_REFERENCE = 'test-fan-female';
    public const TEST_FAN_BLOCKED_REFERENCE = 'test-fan-blocked';

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

        // 创建订阅粉丝
        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid('test_fan_openid_001');
        $fan->setUnionid('test_fan_unionid_001');
        $fan->setNickname('测试粉丝');
        $fan->setHeadimgurl('https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=100&h=100');
        $fan->setSex(Gender::Unknown);
        $fan->setLanguage('zh_CN');
        $fan->setCity('深圳');
        $fan->setProvince('广东');
        $fan->setCountry('中国');
        $fan->setSubscribeTime(CarbonImmutable::now()->subDays(30));
        $fan->setRemark('第一个测试粉丝');
        $fan->setStatus(FanStatus::Subscribed);

        $manager->persist($fan);
        $this->addReference(self::TEST_FAN_REFERENCE, $fan);

        // 创建男性粉丝
        $maleFan = new Fan();
        $maleFan->setAccount($account);
        $maleFan->setOpenid('test_fan_openid_002');
        $maleFan->setUnionid('test_fan_unionid_002');
        $maleFan->setNickname('男性测试粉丝');
        $maleFan->setHeadimgurl('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100');
        $maleFan->setSex(Gender::Male);
        $maleFan->setLanguage('zh_CN');
        $maleFan->setCity('北京');
        $maleFan->setProvince('北京');
        $maleFan->setCountry('中国');
        $maleFan->setSubscribeTime(CarbonImmutable::now()->subDays(15));
        $maleFan->setRemark('男性测试粉丝');
        $maleFan->setStatus(FanStatus::Subscribed);

        $manager->persist($maleFan);
        $this->addReference(self::TEST_FAN_MALE_REFERENCE, $maleFan);

        // 创建女性粉丝
        $femaleFan = new Fan();
        $femaleFan->setAccount($account);
        $femaleFan->setOpenid('test_fan_openid_003');
        $femaleFan->setUnionid('test_fan_unionid_003');
        $femaleFan->setNickname('女性测试粉丝');
        $femaleFan->setHeadimgurl('https://images.unsplash.com/photo-1494790108755-2616c4e2caaf?w=100&h=100');
        $femaleFan->setSex(Gender::Female);
        $femaleFan->setLanguage('zh_CN');
        $femaleFan->setCity('上海');
        $femaleFan->setProvince('上海');
        $femaleFan->setCountry('中国');
        $femaleFan->setSubscribeTime(CarbonImmutable::now()->subDays(7));
        $femaleFan->setRemark('女性测试粉丝');
        $femaleFan->setStatus(FanStatus::Subscribed);

        $manager->persist($femaleFan);
        $this->addReference(self::TEST_FAN_FEMALE_REFERENCE, $femaleFan);

        // 创建被拉黑粉丝
        $blockedFan = new Fan();
        $blockedFan->setAccount($account);
        $blockedFan->setOpenid('test_fan_openid_004');
        $blockedFan->setUnionid('test_fan_unionid_004');
        $blockedFan->setNickname('被拉黑粉丝');
        $blockedFan->setHeadimgurl('https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100');
        $blockedFan->setSex(Gender::Male);
        $blockedFan->setLanguage('zh_CN');
        $blockedFan->setCity('广州');
        $blockedFan->setProvince('广东');
        $blockedFan->setCountry('中国');
        $blockedFan->setSubscribeTime(CarbonImmutable::now()->subDays(60));
        $blockedFan->setRemark('被拉黑的测试粉丝');
        $blockedFan->setStatus(FanStatus::Blocked);

        $manager->persist($blockedFan);
        $this->addReference(self::TEST_FAN_BLOCKED_REFERENCE, $blockedFan);

        $manager->flush();
    }
}
