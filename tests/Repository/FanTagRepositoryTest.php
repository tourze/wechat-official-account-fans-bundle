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
use Tourze\WechatOfficialAccountFansBundle\Repository\FanTagRepository;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @template-extends AbstractRepositoryTestCase<FanTag>
 * @internal
 */
#[CoversClass(FanTagRepository::class)]
#[RunTestsInSeparateProcesses]
final class FanTagRepositoryTest extends AbstractRepositoryTestCase
{
    private FanTagRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(FanTagRepository::class);
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

    public function testFindByFan(): void
    {
        $account = new Account();
        $account->setName('测试账号');
        $account->setAppId('fantag_repo_test');
        $account->setAppSecret('test_secret');
        $account->setValid(true);

        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid('test_fan_fantag');
        $fan->setStatus(FanStatus::Subscribed);

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(300);
        $tag->setName('测试关系标签');
        $tag->setCount(1);

        $fanTag = new FanTag();
        $fanTag->setFan($fan);
        $fanTag->setTag($tag);

        self::getEntityManager()->persist($account);
        self::getEntityManager()->persist($fan);
        self::getEntityManager()->persist($tag);
        self::getEntityManager()->persist($fanTag);
        self::getEntityManager()->flush();

        $fanTags = $this->repository->findByFan($fan);

        $this->assertCount(1, $fanTags);
        $this->assertSame($tag, $fanTags[0]->getTag());
    }

    public function testFindByTag(): void
    {
        $account = new Account();
        $account->setName('测试账号');
        $account->setAppId('fantag_repo_tag_test');
        $account->setAppSecret('test_secret');
        $account->setValid(true);

        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid('test_fan_for_tag');
        $fan->setStatus(FanStatus::Subscribed);

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(400);
        $tag->setName('按标签查找测试');
        $tag->setCount(1);

        $fanTag = new FanTag();
        $fanTag->setFan($fan);
        $fanTag->setTag($tag);

        self::getEntityManager()->persist($account);
        self::getEntityManager()->persist($fan);
        self::getEntityManager()->persist($tag);
        self::getEntityManager()->persist($fanTag);
        self::getEntityManager()->flush();

        $fanTags = $this->repository->findByTag($tag);

        $this->assertCount(1, $fanTags);
        $this->assertSame($fan, $fanTags[0]->getFan());
    }

    public function testFindByFanAndTag(): void
    {
        $account = $this->createTestAccount('fantag_find_test');

        $fan = $this->createTestFan($account, 'test_fan_find');
        $tag = $this->createTestTag($account, 500, '查找标签');

        $fanTag = new FanTag();
        $fanTag->setFan($fan);
        $fanTag->setTag($tag);

        self::getEntityManager()->persist($fanTag);
        self::getEntityManager()->flush();

        // 测试找到的情况
        $foundFanTag = $this->repository->findByFanAndTag($fan, $tag);
        $this->assertNotNull($foundFanTag);
        $this->assertSame($fan, $foundFanTag->getFan());
        $this->assertSame($tag, $foundFanTag->getTag());

        // 测试找不到的情况
        $otherTag = $this->createTestTag($account, 501, '其他标签');
        self::getEntityManager()->persist($otherTag);
        self::getEntityManager()->flush();

        $notFoundFanTag = $this->repository->findByFanAndTag($fan, $otherTag);
        $this->assertNull($notFoundFanTag);
    }

    public function testRemoveAllTagsFromFan(): void
    {
        $account = $this->createTestAccount('fantag_remove_tags_test');

        $fan = $this->createTestFan($account, 'test_fan_remove_tags');
        $tag1 = $this->createTestTag($account, 600, '标签1');
        $tag2 = $this->createTestTag($account, 601, '标签2');

        $fanTag1 = new FanTag();
        $fanTag1->setFan($fan);
        $fanTag1->setTag($tag1);

        $fanTag2 = new FanTag();
        $fanTag2->setFan($fan);
        $fanTag2->setTag($tag2);

        self::getEntityManager()->persist($fanTag1);
        self::getEntityManager()->persist($fanTag2);
        self::getEntityManager()->flush();

        // 验证关系存在
        $fanTags = $this->repository->findByFan($fan);
        $this->assertCount(2, $fanTags);

        // 删除该粉丝的所有标签关系
        $removedCount = $this->repository->removeAllTagsFromFan($fan);
        $this->assertSame(2, $removedCount);

        // 验证关系已删除
        $fanTagsAfterRemoval = $this->repository->findByFan($fan);
        $this->assertCount(0, $fanTagsAfterRemoval);
    }

    public function testRemoveAllFansFromTag(): void
    {
        $account = $this->createTestAccount('fantag_remove_fans_test');

        $fan1 = $this->createTestFan($account, 'test_fan_1');
        $fan2 = $this->createTestFan($account, 'test_fan_2');
        $tag = $this->createTestTag($account, 700, '删除粉丝标签');

        $fanTag1 = new FanTag();
        $fanTag1->setFan($fan1);
        $fanTag1->setTag($tag);

        $fanTag2 = new FanTag();
        $fanTag2->setFan($fan2);
        $fanTag2->setTag($tag);

        self::getEntityManager()->persist($fanTag1);
        self::getEntityManager()->persist($fanTag2);
        self::getEntityManager()->flush();

        // 验证关系存在
        $fanTags = $this->repository->findByTag($tag);
        $this->assertCount(2, $fanTags);

        // 删除该标签的所有粉丝关系
        $removedCount = $this->repository->removeAllFansFromTag($tag);
        $this->assertSame(2, $removedCount);

        // 验证关系已删除
        $fanTagsAfterRemoval = $this->repository->findByTag($tag);
        $this->assertCount(0, $fanTagsAfterRemoval);
    }

    public function testSave(): void
    {
        $account = $this->createTestAccount('fantag_save_test');
        $fan = $this->createTestFan($account, 'save_test_fan');
        $tag = $this->createTestTag($account, 800, '保存测试标签');

        $fanTag = new FanTag();
        $fanTag->setFan($fan);
        $fanTag->setTag($tag);

        // 测试不立即flush
        $this->repository->save($fanTag, false);
        $this->assertTrue(self::getEntityManager()->contains($fanTag), 'Entity should be managed but not yet flushed');

        // 测试立即flush（默认行为）
        $this->repository->save($fanTag);

        // 验证数据被保存到数据库
        $savedFanTag = $this->repository->findByFanAndTag($fan, $tag);
        $this->assertNotNull($savedFanTag);
        $this->assertSame($fanTag->getId(), $savedFanTag->getId());
    }

    public function testRemove(): void
    {
        $account = $this->createTestAccount('fantag_remove_test');
        $fan = $this->createTestFan($account, 'remove_test_fan');
        $tag = $this->createTestTag($account, 900, '删除测试标签');

        $fanTag = new FanTag();
        $fanTag->setFan($fan);
        $fanTag->setTag($tag);

        self::getEntityManager()->persist($fanTag);
        self::getEntityManager()->flush();

        // 确认数据存在
        $existingFanTag = $this->repository->findByFanAndTag($fan, $tag);
        $this->assertNotNull($existingFanTag);

        // 测试删除（默认立即flush）
        $this->repository->remove($fanTag);

        // 验证数据被删除
        $removedFanTag = $this->repository->findByFanAndTag($fan, $tag);
        $this->assertNull($removedFanTag);
    }

    private function cleanupTestData(): void
    {
        if (!self::getEntityManager()->isOpen() || !self::getEntityManager()->getConnection()->isConnected()) {
            return;
        }

        try {
            // 清理测试数据
            $testFanTags = self::getEntityManager()->createQuery(
                'SELECT ft FROM Tourze\WechatOfficialAccountFansBundle\Entity\FanTag ft JOIN ft.fan f JOIN f.account a WHERE a.appId LIKE :pattern'
            )
                ->setParameter('pattern', '%test%')
                ->getResult()
            ;

            // 确保返回值是可迭代的实体数组
            if (is_iterable($testFanTags)) {
                foreach ($testFanTags as $fanTag) {
                    if ($fanTag instanceof FanTag) {
                        self::getEntityManager()->remove($fanTag);
                    }
                }
            }

            self::getEntityManager()->flush();
        } catch (\Exception $e) {
            // 忽略数据库清理失败的情况
        }
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

    private function createTestFan(Account $account, string $openid): Fan
    {
        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid($openid);
        $fan->setStatus(FanStatus::Subscribed);

        self::getEntityManager()->persist($fan);

        return $fan;
    }

    private function createTestTag(Account $account, int $tagId, string $name): Tag
    {
        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid($tagId);
        $tag->setName($name);
        $tag->setCount(0);

        self::getEntityManager()->persist($tag);

        return $tag;
    }

    protected function createNewEntity(): object
    {
        $account = $this->createTestAccount('test_' . uniqid());
        $fan = $this->createTestFan($account, 'test_openid_' . uniqid());
        $tag = $this->createTestTag($account, rand(1000, 9999), 'test_tag_' . uniqid());

        $fanTag = new FanTag();
        $fanTag->setFan($fan);
        $fanTag->setTag($tag);

        return $fanTag;
    }

    /**
     * @return ServiceEntityRepository<FanTag>
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
            $fan = $this->createTestFan($account, 'fixture_fan');
            $tag = $this->createTestTag($account, 999, 'fixture_tag');

            $fanTag = new FanTag();
            $fanTag->setFan($fan);
            $fanTag->setTag($tag);

            self::getEntityManager()->persist($fanTag);
            self::getEntityManager()->flush();
        }
    }
}
