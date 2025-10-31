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
use Tourze\WechatOfficialAccountFansBundle\Repository\TagRepository;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @template-extends AbstractRepositoryTestCase<Tag>
 * @internal
 */
#[CoversClass(TagRepository::class)]
#[RunTestsInSeparateProcesses]
final class TagRepositoryTest extends AbstractRepositoryTestCase
{
    private TagRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(TagRepository::class);
    }

    protected function onTearDown(): void
    {
        if (self::getEntityManager()->isOpen() && self::getEntityManager()->getConnection()->isConnected()) {
            $this->cleanupTestData();
        }
    }

    public function testFindByAccount(): void
    {
        $account = new Account();
        $account->setName('测试账号');
        $account->setAppId('tag_repo_test');
        $account->setAppSecret('test_secret');
        $account->setValid(true);

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(100);
        $tag->setName('测试标签');
        $tag->setCount(5);

        self::getEntityManager()->persist($account);
        self::getEntityManager()->persist($tag);
        self::getEntityManager()->flush();

        $tags = $this->repository->findByAccount($account);

        $this->assertCount(1, $tags);
        $this->assertSame('测试标签', $tags[0]->getName());
    }

    public function testFindByAccountAndTagid(): void
    {
        $account = new Account();
        $account->setName('测试账号');
        $account->setAppId('tag_repo_tagid_test');
        $account->setAppSecret('test_secret');
        $account->setValid(true);

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(200);
        $tag->setName('特定标签');
        $tag->setCount(3);

        self::getEntityManager()->persist($account);
        self::getEntityManager()->persist($tag);
        self::getEntityManager()->flush();

        $foundTag = $this->repository->findByAccountAndTagid($account, 200);
        $notFoundTag = $this->repository->findByAccountAndTagid($account, 999);

        $this->assertNotNull($foundTag);
        $this->assertSame('特定标签', $foundTag->getName());
        $this->assertNull($notFoundTag);
    }

    public function testFindByAccountAndName(): void
    {
        $account = $this->createTestAccount('tag_repo_name_test');

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(300);
        $tag->setName('名称标签');
        $tag->setCount(7);

        self::getEntityManager()->persist($tag);
        self::getEntityManager()->flush();

        $foundTag = $this->repository->findByAccountAndName($account, '名称标签');
        $notFoundTag = $this->repository->findByAccountAndName($account, '不存在的标签');

        $this->assertNotNull($foundTag);
        $this->assertSame(300, $foundTag->getTagid());
        $this->assertNull($notFoundTag);
    }

    public function testGetMaxTagidByAccount(): void
    {
        $account = $this->createTestAccount('tag_repo_max_test');

        $tag1 = new Tag();
        $tag1->setAccount($account);
        $tag1->setTagid(100);
        $tag1->setName('标签1');
        $tag1->setCount(1);

        $tag2 = new Tag();
        $tag2->setAccount($account);
        $tag2->setTagid(300);
        $tag2->setName('标签2');
        $tag2->setCount(2);

        $tag3 = new Tag();
        $tag3->setAccount($account);
        $tag3->setTagid(200);
        $tag3->setName('标签3');
        $tag3->setCount(3);

        self::getEntityManager()->persist($tag1);
        self::getEntityManager()->persist($tag2);
        self::getEntityManager()->persist($tag3);
        self::getEntityManager()->flush();

        $maxTagid = $this->repository->getMaxTagidByAccount($account);

        $this->assertSame(300, $maxTagid);
    }

    public function testGetMaxTagidByAccountWithNoTags(): void
    {
        $account = $this->createTestAccount('tag_repo_empty_test');

        $maxTagid = $this->repository->getMaxTagidByAccount($account);

        $this->assertNull($maxTagid);
    }

    public function testSave(): void
    {
        $account = $this->createTestAccount('tag_repo_save_test');

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(500);
        $tag->setName('保存测试标签');
        $tag->setCount(0);

        // 测试不立即flush
        $this->repository->save($tag, false);
        $this->assertTrue(self::getEntityManager()->contains($tag), 'Entity should be managed but not yet flushed');

        // 测试立即flush（默认行为）
        $this->repository->save($tag);

        // 验证数据被保存到数据库
        $savedTag = $this->repository->findByAccountAndTagid($account, 500);
        $this->assertNotNull($savedTag);
        $this->assertSame($tag->getId(), $savedTag->getId());
    }

    public function testRemove(): void
    {
        $account = $this->createTestAccount('tag_repo_remove_test');

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(600);
        $tag->setName('删除测试标签');
        $tag->setCount(0);

        self::getEntityManager()->persist($tag);
        self::getEntityManager()->flush();

        // 确认数据存在
        $existingTag = $this->repository->findByAccountAndTagid($account, 600);
        $this->assertNotNull($existingTag);

        // 测试删除（默认立即flush）
        $this->repository->remove($tag);

        // 验证数据被删除
        $removedTag = $this->repository->findByAccountAndTagid($account, 600);
        $this->assertNull($removedTag);
    }

    public function testFindByAccountAndFanOpenid(): void
    {
        $account = $this->createTestAccount('tag_repo_fan_openid_test');

        // 创建粉丝和标签
        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid('test_fan_openid');
        $fan->setStatus(FanStatus::Subscribed);

        $tag1 = new Tag();
        $tag1->setAccount($account);
        $tag1->setTagid(1001);
        $tag1->setName('粉丝标签1');
        $tag1->setCount(1);

        $tag2 = new Tag();
        $tag2->setAccount($account);
        $tag2->setTagid(1002);
        $tag2->setName('粉丝标签2');
        $tag2->setCount(1);

        // 创建关联关系
        $fanTag1 = new FanTag();
        $fanTag1->setFan($fan);
        $fanTag1->setTag($tag1);

        $fanTag2 = new FanTag();
        $fanTag2->setFan($fan);
        $fanTag2->setTag($tag2);

        self::getEntityManager()->persist($fan);
        self::getEntityManager()->persist($tag1);
        self::getEntityManager()->persist($tag2);
        self::getEntityManager()->persist($fanTag1);
        self::getEntityManager()->persist($fanTag2);
        self::getEntityManager()->flush();

        // 测试根据账号和粉丝openid查找标签
        $tags = $this->repository->findByAccountAndFanOpenid($account, 'test_fan_openid');

        $this->assertCount(2, $tags);
        $tagNames = array_map(static fn ($tag) => $tag->getName(), $tags);
        $this->assertContains('粉丝标签1', $tagNames);
        $this->assertContains('粉丝标签2', $tagNames);

        // 测试找不到的情况
        $noTags = $this->repository->findByAccountAndFanOpenid($account, 'nonexistent_openid');
        $this->assertCount(0, $noTags);
    }

    private function cleanupTestData(): void
    {
        if (!self::getEntityManager()->isOpen() || !self::getEntityManager()->getConnection()->isConnected()) {
            return;
        }

        try {
            $this->removeTestTags();
            $this->removeTestAccounts();
            self::getEntityManager()->flush();
        } catch (\Exception $e) {
            // 忽略数据库清理失败的情况
        }
    }

    private function removeTestTags(): void
    {
        $testTags = self::getEntityManager()->createQuery(
            'SELECT t FROM Tourze\WechatOfficialAccountFansBundle\Entity\Tag t JOIN t.account a WHERE a.appId LIKE :pattern'
        )
            ->setParameter('pattern', '%test%')
            ->getResult()
        ;

        if (!is_iterable($testTags)) {
            return;
        }

        foreach ($testTags as $tag) {
            if ($tag instanceof Tag) {
                self::getEntityManager()->remove($tag);
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

    protected function createNewEntity(): object
    {
        $account = $this->createTestAccount('test_' . uniqid());
        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid(rand(1000, 9999));
        $tag->setName('test_tag_' . uniqid());
        $tag->setCount(0);

        return $tag;
    }

    /**
     * @return ServiceEntityRepository<Tag>
     */
    #[\ReturnTypeWillChange]
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
