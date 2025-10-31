<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\FanTag;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;
use Tourze\WechatOfficialAccountFansBundle\Repository\FanRepository;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @template-extends AbstractRepositoryTestCase<Fan>
 * @internal
 */
#[CoversClass(FanRepository::class)]
#[RunTestsInSeparateProcesses]
final class FanRepositoryTest extends AbstractRepositoryTestCase
{
    private FanRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(FanRepository::class);
        $this->cleanupTestData();

        // 确保测试数据存在以满足基础测试类的要求
        $this->ensureFixtureDataExists();
    }

    protected function onTearDown(): void
    {
        if (self::getEntityManager()->isOpen() && self::getEntityManager()->getConnection()->isConnected()) {
            $this->cleanupTestData();
        }
    }

    private function getFanRepository(): FanRepository
    {
        return $this->repository;
    }

    public function testFindByAccount(): void
    {
        $account = $this->createTestAccount('repo_test_app_id');

        $fan1 = $this->createTestFan($account, 'repo_fan_001', FanStatus::Subscribed);
        $fan2 = $this->createTestFan($account, 'repo_fan_002', FanStatus::Blocked);

        self::getEntityManager()->flush();

        $fanRepository = $this->getFanRepository();
        $fans = $fanRepository->findByAccount($account);

        $this->assertCount(2, $fans);
        $openids = array_map(static fn ($fan) => $fan->getOpenid(), $fans);
        $this->assertContains('repo_fan_001', $openids);
        $this->assertContains('repo_fan_002', $openids);
    }

    public function testFindSubscribedByAccount(): void
    {
        $account = $this->createTestAccount('repo_subscribed_test');

        $this->createTestFan($account, 'subscribed_fan', FanStatus::Subscribed);
        $this->createTestFan($account, 'unsubscribed_fan', FanStatus::Unsubscribed);
        $this->createTestFan($account, 'blocked_fan', FanStatus::Blocked);

        self::getEntityManager()->flush();

        $fanRepository = $this->getFanRepository();
        $subscribedFans = $fanRepository->findSubscribedByAccount($account);

        $this->assertCount(1, $subscribedFans);
        $this->assertSame('subscribed_fan', $subscribedFans[0]->getOpenid());
        $this->assertSame(FanStatus::Subscribed, $subscribedFans[0]->getStatus());
    }

    public function testFindBlockedByAccount(): void
    {
        $account = $this->createTestAccount('repo_blocked_test');

        $this->createTestFan($account, 'subscribed_fan', FanStatus::Subscribed);
        $this->createTestFan($account, 'blocked_fan', FanStatus::Blocked);

        self::getEntityManager()->flush();

        $fanRepository = $this->getFanRepository();
        $blockedFans = $fanRepository->findBlockedByAccount($account);

        $this->assertCount(1, $blockedFans);
        $this->assertSame('blocked_fan', $blockedFans[0]->getOpenid());
        $this->assertSame(FanStatus::Blocked, $blockedFans[0]->getStatus());
    }

    public function testFindByAccountAndTagId(): void
    {
        $account = $this->createTestAccount('repo_tag_test');

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(1001);
        $tag->setName('测试标签');
        $tag->setCount(0);

        $fan1 = $this->createTestFan($account, 'tagged_fan_001', FanStatus::Subscribed);
        $fan2 = $this->createTestFan($account, 'tagged_fan_002', FanStatus::Subscribed);
        $fan3 = $this->createTestFan($account, 'untagged_fan', FanStatus::Subscribed);

        $fanTag1 = new FanTag();
        $fanTag1->setFan($fan1);
        $fanTag1->setTag($tag);

        $fanTag2 = new FanTag();
        $fanTag2->setFan($fan2);
        $fanTag2->setTag($tag);

        self::getEntityManager()->persist($tag);
        self::getEntityManager()->persist($fanTag1);
        self::getEntityManager()->persist($fanTag2);
        self::getEntityManager()->flush();

        $fanRepository = $this->getFanRepository();
        $taggedFans = $fanRepository->findByAccountAndTagId($account, 1001);

        $this->assertCount(2, $taggedFans);
        $openids = array_map(static fn ($fan) => $fan->getOpenid(), $taggedFans);
        $this->assertContains('tagged_fan_001', $openids);
        $this->assertContains('tagged_fan_002', $openids);
        $this->assertNotContains('untagged_fan', $openids);
    }

    public function testFindUntaggedByAccount(): void
    {
        $account = $this->createTestAccount('repo_untagged_test');

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(1002);
        $tag->setName('测试标签');
        $tag->setCount(0);

        $taggedFan = $this->createTestFan($account, 'tagged_fan', FanStatus::Subscribed);
        $untaggedFan = $this->createTestFan($account, 'untagged_fan', FanStatus::Subscribed);

        $fanTag = new FanTag();
        $fanTag->setFan($taggedFan);
        $fanTag->setTag($tag);

        self::getEntityManager()->persist($tag);
        self::getEntityManager()->persist($fanTag);
        self::getEntityManager()->flush();

        $fanRepository = $this->getFanRepository();
        $untaggedFans = $fanRepository->findUntaggedByAccount($account);

        $this->assertCount(1, $untaggedFans);
        $this->assertSame('untagged_fan', $untaggedFans[0]->getOpenid());
    }

    public function testCountByAccountAndStatus(): void
    {
        $account = $this->createTestAccount('repo_count_test');

        $this->createTestFan($account, 'count_subscribed_1', FanStatus::Subscribed);
        $this->createTestFan($account, 'count_subscribed_2', FanStatus::Subscribed);
        $this->createTestFan($account, 'count_blocked_1', FanStatus::Blocked);

        self::getEntityManager()->flush();

        $fanRepository = $this->getFanRepository();
        $subscribedCount = $fanRepository->countByAccountAndStatus($account, FanStatus::Subscribed);
        $blockedCount = $fanRepository->countByAccountAndStatus($account, FanStatus::Blocked);
        $unsubscribedCount = $fanRepository->countByAccountAndStatus($account, FanStatus::Unsubscribed);

        $this->assertSame(2, $subscribedCount);
        $this->assertSame(1, $blockedCount);
        $this->assertSame(0, $unsubscribedCount);
    }

    public function testFindByAccountAndOpenid(): void
    {
        $account = $this->createTestAccount('repo_openid_test');

        $fan = $this->createTestFan($account, 'specific_openid', FanStatus::Subscribed);

        self::getEntityManager()->flush();

        $fanRepository = $this->getFanRepository();
        $foundFan = $fanRepository->findByAccountAndOpenid($account, 'specific_openid');
        $notFoundFan = $fanRepository->findByAccountAndOpenid($account, 'nonexistent_openid');

        $this->assertNotNull($foundFan);
        $this->assertSame($fan->getId(), $foundFan->getId());
        $this->assertNull($notFoundFan);
    }

    public function testSave(): void
    {
        $account = $this->createTestAccount('repo_save_test');
        $fanRepository = $this->getFanRepository();

        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid('save_test_openid');
        $fan->setStatus(FanStatus::Subscribed);

        // 测试不立即flush
        $fanRepository->save($fan, false);
        $this->assertTrue(self::getEntityManager()->contains($fan), 'Entity should be managed but not yet flushed');

        // 测试立即flush（默认行为）
        $fanRepository->save($fan);

        // 验证数据被保存到数据库
        $savedFan = $fanRepository->findByAccountAndOpenid($account, 'save_test_openid');
        $this->assertNotNull($savedFan);
        $this->assertSame($fan->getId(), $savedFan->getId());
    }

    public function testRemove(): void
    {
        $account = $this->createTestAccount('repo_remove_test');
        $fanRepository = $this->getFanRepository();

        $fan = $this->createTestFan($account, 'remove_test_openid', FanStatus::Subscribed);
        self::getEntityManager()->flush();

        // 确认数据存在
        $existingFan = $fanRepository->findByAccountAndOpenid($account, 'remove_test_openid');
        $this->assertNotNull($existingFan);

        // 测试删除（默认立即flush）
        $fanRepository->remove($fan);

        // 验证数据被删除
        $removedFan = $fanRepository->findByAccountAndOpenid($account, 'remove_test_openid');
        $this->assertNull($removedFan);
    }

    private function createTestAccount(string $appId): Account
    {
        $account = new Account();
        $account->setName('测试账号');
        $account->setAppId($appId);
        $account->setAppSecret('test_secret');
        $account->setValid(true);

        self::getEntityManager()->persist($account);

        return $account;
    }

    private function createTestFan(Account $account, string $openid, FanStatus $status): Fan
    {
        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid($openid);
        $fan->setStatus($status);

        self::getEntityManager()->persist($fan);

        return $fan;
    }

    private function cleanupTestData(): void
    {
        if (!self::getEntityManager()->isOpen() || !self::getEntityManager()->getConnection()->isConnected()) {
            return;
        }

        try {
            $this->removeTestFans();
            $this->removeTestAccounts();
            self::getEntityManager()->flush();
        } catch (\Exception $e) {
            // 忽略数据库清理失败的情况
        }
    }

    private function removeTestFans(): void
    {
        $testFans = self::getEntityManager()->createQuery(
            'SELECT f FROM Tourze\WechatOfficialAccountFansBundle\Entity\Fan f JOIN f.account a WHERE a.appId LIKE :pattern'
        )
            ->setParameter('pattern', '%test%')
            ->getResult()
        ;

        if (!is_iterable($testFans)) {
            return;
        }

        foreach ($testFans as $fan) {
            if ($fan instanceof Fan) {
                self::getEntityManager()->remove($fan);
            }
        }
    }

    private function removeTestAccounts(): void
    {
        $testAccounts = self::getEntityManager()->createQuery(
            'SELECT a FROM WechatOfficialAccountBundle\Entity\Account a WHERE a.appId LIKE :pattern'
        )
            ->setParameter('pattern', '%test%')
            ->getResult()
        ;

        if (!is_iterable($testAccounts)) {
            return;
        }

        foreach ($testAccounts as $account) {
            if ($account instanceof Account) {
                self::getEntityManager()->remove($account);
            }
        }
    }

    protected function createNewEntity(): object
    {
        $account = $this->createTestAccount('test_' . uniqid());
        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid('test_openid_' . uniqid());
        $fan->setStatus(FanStatus::Subscribed);

        return $fan;
    }

    /**
     * @return ServiceEntityRepository<Fan>
     */
    #[\ReturnTypeWillChange]
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    private function ensureFixtureDataExists(): void
    {
        // 检查是否已有数据，如果没有则创建基础数据以满足基础测试类的要求
        if (0 === $this->repository->count([])) {
            $account = $this->createTestAccount('fixture_account');
            $fan = $this->createTestFan($account, 'fixture_fan', FanStatus::Subscribed);
            self::getEntityManager()->flush();
        }
    }
}
