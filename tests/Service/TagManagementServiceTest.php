<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;
use Tourze\WechatOfficialAccountFansBundle\Exception\TagAlreadyExistsException;
use Tourze\WechatOfficialAccountFansBundle\Service\TagManagementService;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(TagManagementService::class)]
#[RunTestsInSeparateProcesses]
class TagManagementServiceTest extends AbstractIntegrationTestCase
{
    private TagManagementService $service;

    private Account $account;

    private Tag $testTag;

    /**
     * @return array<string>
     */
    protected function getFixtures(): array
    {
        return [];
    }

    protected function onSetUp(): void
    {
        $this->service = self::getService(TagManagementService::class);

        // 直接创建测试数据
        $this->account = new Account();
        $this->account->setAppId('test_app_id');
        $this->account->setAppSecret('test_app_secret');
        $this->account->setName('Test Account');

        $this->testTag = new Tag();
        $this->testTag->setAccount($this->account);
        $this->testTag->setTagid(1);
        $this->testTag->setName('VIP');
        $this->testTag->setCount(10);

        $em = self::getEntityManager();
        $em->persist($this->account);
        $em->persist($this->testTag);
        $em->flush();
    }

    public function testGetTagsByAccount(): void
    {
        $tags = $this->service->getTagsByAccount($this->account);

        $this->assertNotEmpty($tags);
        $this->assertCount(1, $tags);
        // Verify tag entity integrity with specific assertions
        $firstTag = $tags[0];
        $this->assertInstanceOf(Tag::class, $firstTag);
        $this->assertSame($this->testTag->getTagid(), $firstTag->getTagid());
        $this->assertSame('VIP', $firstTag->getName());
    }

    public function testGetTagById(): void
    {
        $tagid = $this->testTag->getTagid();
        $this->assertNotNull($tagid, 'Test tag should have a tagid');

        $result = $this->service->getTagById($this->account, $tagid);

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals($tagid, $result->getTagid());
    }

    public function testCreateTag(): void
    {
        $tagName = 'Test New Tag ' . uniqid();
        $result = $this->service->createTag($this->account, $tagName);

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals($tagName, $result->getName());
        $this->assertEquals(0, $result->getCount());
    }

    public function testCreateTagWithExistingName(): void
    {
        $this->expectException(TagAlreadyExistsException::class);

        $tagName = $this->testTag->getName();
        $this->assertNotNull($tagName, 'Test tag should have a name');

        $this->service->createTag($this->account, $tagName);
    }

    public function testUpdateTag(): void
    {
        $tagid = $this->testTag->getTagid();
        $this->assertNotNull($tagid, 'Test tag should have a tagid');

        $newName = 'Updated Tag Name ' . uniqid();
        $result = $this->service->updateTag($this->account, $tagid, $newName);

        $this->assertTrue($result);

        self::getEntityManager()->refresh($this->testTag);
        $this->assertEquals($newName, $this->testTag->getName());
    }

    public function testUpdateTagWithNonExistentTag(): void
    {
        $result = $this->service->updateTag($this->account, 99999, 'Non Existent');

        $this->assertFalse($result);
    }

    public function testDeleteTag(): void
    {
        $tagId = $this->testTag->getTagid();
        $this->assertNotNull($tagId, 'Test tag should have a tagid');

        $result = $this->service->deleteTag($this->account, $tagId);

        $this->assertTrue($result);

        $deletedTag = $this->service->getTagById($this->account, $tagId);
        $this->assertNull($deletedTag);
    }

    public function testDeleteTagWithNonExistentTag(): void
    {
        $result = $this->service->deleteTag($this->account, 99999);

        $this->assertFalse($result);
    }

    public function testSyncAllTagCounts(): void
    {
        // 测试同步所有标签计数功能
        $this->service->syncAllTagCounts($this->account);

        // 验证方法执行完成（具体同步逻辑需要根据业务实现）
        $this->expectNotToPerformAssertions();
    }

    public function testSyncTagCount(): void
    {
        // 测试同步单个标签计数功能
        $tagid = $this->testTag->getTagid();
        $this->assertNotNull($tagid, 'Test tag should have a tagid');

        $result = $this->service->syncTagCount($this->account, $tagid);

        // 验证方法执行完成，应该返回布尔值表示同步是否成功
        $this->assertTrue($result);
    }
}
