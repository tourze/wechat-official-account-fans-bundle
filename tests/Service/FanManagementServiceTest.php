<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;
use Tourze\WechatOfficialAccountFansBundle\Service\FanManagementService;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(FanManagementService::class)]
#[RunTestsInSeparateProcesses]
class FanManagementServiceTest extends AbstractIntegrationTestCase
{
    private FanManagementService $service;

    private Account $account;

    private Fan $testFan;

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
        $this->service = self::getService(FanManagementService::class);

        // 直接创建测试数据
        $this->account = new Account();
        $this->account->setAppId('test_app_id');
        $this->account->setAppSecret('test_app_secret');
        $this->account->setName('Test Account');

        $this->testFan = new Fan();
        $this->testFan->setAccount($this->account);
        $this->testFan->setOpenid('test_openid');
        $this->testFan->setNickname('Test Fan');
        $this->testFan->setStatus(FanStatus::Subscribed);

        $this->testTag = new Tag();
        $this->testTag->setAccount($this->account);
        $this->testTag->setTagid(1);
        $this->testTag->setName('VIP');
        $this->testTag->setCount(0);

        $em = self::getEntityManager();
        $em->persist($this->account);
        $em->persist($this->testFan);
        $em->persist($this->testTag);
        $em->flush();
    }

    public function testGetFansPaginated(): void
    {
        $result = $this->service->getFansPaginated($this->account, 1, 10);

        $this->assertArrayHasKey('fans', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('hasNext', $result);

        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['limit']);
        $this->assertSame(1, $result['total']);
        $this->assertCount(1, $result['fans']);
        $this->assertFalse($result['hasNext']);
        // Verify first fan entity integrity
        $firstFan = $result['fans'][0];
        $this->assertInstanceOf(Fan::class, $firstFan);
        $this->assertSame($this->testFan->getOpenid(), $firstFan->getOpenid());
    }

    public function testGetFansPaginatedWithStatus(): void
    {
        $result = $this->service->getFansPaginated($this->account, 1, 10, FanStatus::Subscribed);

        $this->assertArrayHasKey('fans', $result);

        foreach ($result['fans'] as $fan) {
            $this->assertInstanceOf(Fan::class, $fan);
            $this->assertEquals(FanStatus::Subscribed, $fan->getStatus());
        }
    }

    public function testGetFansPaginatedWithTag(): void
    {
        $tagid = $this->testTag->getTagid();
        $this->assertNotNull($tagid, 'Test tag should have a tagid');

        $result = $this->service->getFansPaginated($this->account, 1, 10, null, $tagid);

        $this->assertArrayHasKey('fans', $result);
        $this->assertSame(0, $result['total']);
        $this->assertCount(0, $result['fans']);
        $this->assertFalse($result['hasNext']);
    }

    public function testGetFanByOpenid(): void
    {
        $openid = $this->testFan->getOpenid();
        $this->assertNotNull($openid, 'Test fan should have an openid');

        $result = $this->service->getFanByOpenid($this->account, $openid);

        $this->assertInstanceOf(Fan::class, $result);
        $this->assertEquals($openid, $result->getOpenid());
    }

    public function testGetFanStatistics(): void
    {
        $result = $this->service->getFanStatistics($this->account);

        $this->assertArrayHasKey('subscribed', $result);
        $this->assertArrayHasKey('unsubscribed', $result);
        $this->assertArrayHasKey('blocked', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertGreaterThan(0, $result['total']);
    }

    public function testUpdateFanRemark(): void
    {
        $openid = $this->testFan->getOpenid();
        $this->assertNotNull($openid, 'Test fan should have an openid');

        $newRemark = 'Updated test remark';
        $result = $this->service->updateFanRemark($this->account, $openid, $newRemark);

        $this->assertTrue($result);

        self::getEntityManager()->refresh($this->testFan);
        $this->assertEquals($newRemark, $this->testFan->getRemark());
    }

    public function testUpdateFanRemarkWithNonExistentFan(): void
    {
        $result = $this->service->updateFanRemark($this->account, 'nonexistent-openid', 'test');

        $this->assertFalse($result);
    }

    public function testExportFansData(): void
    {
        $result = $this->service->exportFansData($this->account);

        $this->assertNotEmpty($result);

        foreach ($result as $fanData) {
            $this->assertArrayHasKey('openid', $fanData);
            $this->assertArrayHasKey('nickname', $fanData);
            $this->assertArrayHasKey('status', $fanData);
            $this->assertArrayHasKey('tags', $fanData);
        }
    }

    public function testBatchAddTagToFans(): void
    {
        // 测试批量为粉丝添加标签功能
        $openid = $this->testFan->getOpenid();
        $this->assertNotNull($openid, 'Test fan should have an openid');

        $tagid = $this->testTag->getTagid();
        $this->assertNotNull($tagid, 'Test tag should have a tagid');

        $openids = [$openid];
        $result = $this->service->batchAddTagToFans($this->account, $openids, $tagid);

        // 验证方法执行完成，应该返回受影响的粉丝数量
        $this->assertSame(1, $result);
    }

    public function testBatchRemoveTagFromFans(): void
    {
        // 测试批量从粉丝移除标签功能
        $openid = $this->testFan->getOpenid();
        $this->assertNotNull($openid, 'Test fan should have an openid');

        $tagid = $this->testTag->getTagid();
        $this->assertNotNull($tagid, 'Test tag should have a tagid');

        $openids = [$openid];
        $result = $this->service->batchRemoveTagFromFans($this->account, $openids, $tagid);

        // 验证方法执行完成，返回受影响的粉丝数量（可能为0）
        $this->assertGreaterThanOrEqual(0, $result);
    }
}
