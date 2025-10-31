<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;
use Tourze\WechatOfficialAccountFansBundle\Enum\Gender;
use Tourze\WechatOfficialAccountFansBundle\Repository\FanRepository;
use Tourze\WechatOfficialAccountFansBundle\Request\User\BatchGetUserInfoRequest;
use WechatOfficialAccountBundle\Entity\Account;
use WechatOfficialAccountBundle\Repository\AccountRepository;
use WechatOfficialAccountBundle\Service\OfficialAccountClient;

/**
 * 同步粉丝详细信息
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/Getting_a_User_List.html#%E6%89%B9%E9%87%8F%E8%8E%B7%E5%8F%96%E7%94%A8%E6%88%B7%E5%9F%BA%E6%9C%AC%E4%BF%A1%E6%81%AF
 */
#[WithMonologChannel(channel: 'wechat_official_account_fans')]
#[AsCronTask(expression: '30 2 * * *')]
#[AsCommand(name: self::NAME, description: '同步粉丝详细信息')]
class SyncUserInfoCommand extends Command
{
    public const NAME = 'wechat:official-account:sync-user-info';

    private const BATCH_SIZE = 80; // 微信API最多100个，保留安全余量

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly OfficialAccountClient $client,
        private readonly FanRepository $fanRepository,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->accountRepository->findBy(['valid' => true]) as $account) {
            $this->processAccount($account);
        }

        return Command::SUCCESS;
    }

    private function processAccount(Account $account): void
    {
        try {
            $this->logger->info('开始同步粉丝详细信息', ['account' => $account->getId()]);

            $subscribedFans = $this->getSubscribedFans($account);
            if ([] === $subscribedFans) {
                return;
            }

            $result = $this->syncFansInfo($account, $subscribedFans);

            $this->logger->info('同步粉丝详细信息完成', [
                'account' => $account->getId(),
                'total_fans' => $result['total'],
                'processed_count' => $result['processed'],
                'error_count' => $result['errors'],
                'success_rate' => $result['success_rate'],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('同步粉丝详细信息时发生错误', [
                'account' => $account->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * @return array<int, Fan>
     */
    private function getSubscribedFans(Account $account): array
    {
        /** @var array<int, Fan> $subscribedFans */
        $subscribedFans = $this->fanRepository->findSubscribedByAccount($account);
        if ([] === $subscribedFans) {
            $this->logger->info('该公众号暂无已关注粉丝', ['account' => $account->getId()]);
        }

        return $subscribedFans;
    }

    /**
     * @param array<int, Fan> $subscribedFans
     * @return array{total: int, processed: int, errors: int, success_rate: string}
     */
    private function syncFansInfo(Account $account, array $subscribedFans): array
    {
        $totalFans = \count($subscribedFans);
        $processedCount = 0;
        $errorCount = 0;

        $fanBatches = array_chunk($subscribedFans, self::BATCH_SIZE);

        foreach ($fanBatches as $batchIndex => $fanBatch) {
            $result = $this->processFanBatch($account, $fanBatch, $batchIndex);
            $processedCount += $result['processed'];
            $errorCount += $result['errors'];

            $this->addBatchDelay($batchIndex, $fanBatches);
        }

        return [
            'total' => $totalFans,
            'processed' => $processedCount,
            'errors' => $errorCount,
            'success_rate' => round(($processedCount / $totalFans) * 100, 2) . '%',
        ];
    }

    /**
     * @param array<int, Fan> $fanBatch
     * @return array{processed: int, errors: int}
     */
    private function processFanBatch(Account $account, array $fanBatch, int $batchIndex): array
    {
        try {
            $openids = $this->extractOpenids($fanBatch);
            $userInfoList = $this->fetchUserInfoBatch($account, $openids, $batchIndex);

            if (null === $userInfoList) {
                return ['processed' => 0, 'errors' => \count($fanBatch)];
            }

            $processedCount = $this->updateFansFromUserInfo($fanBatch, $userInfoList);
            $this->entityManager->flush();

            $this->logBatchResult($account, $batchIndex, $fanBatch, $processedCount);

            return ['processed' => $processedCount, 'errors' => 0];
        } catch (\Throwable $e) {
            $this->logger->error('批次处理失败', [
                'account' => $account->getId(),
                'batch' => $batchIndex + 1,
                'error' => $e->getMessage(),
            ]);

            return ['processed' => 0, 'errors' => \count($fanBatch)];
        }
    }

    /**
     * @param array<int, Fan> $fanBatch
     * @return string[]
     */
    private function extractOpenids(array $fanBatch): array
    {
        return array_values(array_filter(
            array_map(static fn (Fan $fan): ?string => $fan->getOpenid(), $fanBatch),
            static fn (?string $openid): bool => null !== $openid
        ));
    }

    /**
     * @param string[] $openids
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchUserInfoBatch(Account $account, array $openids, int $batchIndex): ?array
    {
        $request = new BatchGetUserInfoRequest();
        $request->setAccount($account);
        $request->setOpenids($openids);

        /** @var array<string, mixed> $response */
        $response = $this->client->request($request);

        if (!isset($response['user_info_list']) || !\is_array($response['user_info_list'])) {
            $this->logger->warning('批量获取用户信息响应格式异常', [
                'account' => $account->getId(),
                'batch' => $batchIndex + 1,
                'openids_count' => \count($openids),
                'response' => $response,
            ]);

            return null;
        }

        /** @var array<int, array<string, mixed>> */
        return $response['user_info_list'];
    }

    /**
     * @param array<int, Fan> $fanBatch
     * @param array<int, array<string, mixed>> $userInfoList
     */
    private function updateFansFromUserInfo(array $fanBatch, array $userInfoList): int
    {
        $fanMap = $this->createFanMap($fanBatch);
        $processedCount = 0;

        foreach ($userInfoList as $userInfo) {
            if (!isset($userInfo['openid']) || !\is_string($userInfo['openid'])) {
                continue;
            }
            if (isset($fanMap[$userInfo['openid']])) {
                $this->updateFanInfo($fanMap[$userInfo['openid']], $userInfo);
                ++$processedCount;
            }
        }

        return $processedCount;
    }

    /**
     * @param array<int, Fan> $fanBatch
     * @return array<string, Fan>
     */
    private function createFanMap(array $fanBatch): array
    {
        $fanMap = [];
        foreach ($fanBatch as $fan) {
            $openid = $fan->getOpenid();
            if (null !== $openid) {
                $fanMap[$openid] = $fan;
            }
        }

        return $fanMap;
    }

    /**
     * @param array<int, Fan> $fanBatch
     */
    private function logBatchResult(Account $account, int $batchIndex, array $fanBatch, int $processedCount): void
    {
        $this->logger->debug('批次处理完成', [
            'account' => $account->getId(),
            'batch' => $batchIndex + 1,
            'batch_size' => \count($fanBatch),
            'processed_count' => $processedCount,
        ]);
    }

    /**
     * @param array<int, array<int, Fan>> $fanBatches
     */
    private function addBatchDelay(int $batchIndex, array $fanBatches): void
    {
        if ($batchIndex < \count($fanBatches) - 1) {
            usleep(200000); // 200ms
        }
    }

    /**
     * @param array<string, mixed> $userInfo
     */
    private function updateFanInfo(Fan $fan, array $userInfo): void
    {
        $this->updateBasicInfo($fan, $userInfo);
        $this->updateLocationInfo($fan, $userInfo);
        $this->updateSubscriptionInfo($fan, $userInfo);
    }

    /**
     * @param array<string, mixed> $userInfo
     */
    private function updateBasicInfo(Fan $fan, array $userInfo): void
    {
        $this->updateStringField($fan, $userInfo, 'unionid', fn (Fan $f, string $val) => $f->setUnionid($val));
        $this->updateStringField($fan, $userInfo, 'nickname', fn (Fan $f, string $val) => $f->setNickname($val));
        $this->updateStringField($fan, $userInfo, 'headimgurl', fn (Fan $f, string $val) => $f->setHeadimgurl($val));
        $this->updateStringField($fan, $userInfo, 'language', fn (Fan $f, string $val) => $f->setLanguage($val));
        $this->updateStringField($fan, $userInfo, 'remark', fn (Fan $f, string $val) => $f->setRemark($val));

        if (isset($userInfo['sex']) && (\is_int($userInfo['sex']) || \is_string($userInfo['sex']))) {
            $fan->setSex(Gender::tryFrom($userInfo['sex']) ?? Gender::Unknown);
        }
    }

    /**
     * @param array<string, mixed> $userInfo
     * @param callable(Fan, string): void $setter
     */
    private function updateStringField(Fan $fan, array $userInfo, string $field, callable $setter): void
    {
        if (isset($userInfo[$field]) && \is_string($userInfo[$field])) {
            $setter($fan, $userInfo[$field]);
        }
    }

    /**
     * @param array<string, mixed> $userInfo
     */
    private function updateLocationInfo(Fan $fan, array $userInfo): void
    {
        if (isset($userInfo['city']) && \is_string($userInfo['city'])) {
            $fan->setCity($userInfo['city']);
        }

        if (isset($userInfo['province']) && \is_string($userInfo['province'])) {
            $fan->setProvince($userInfo['province']);
        }

        if (isset($userInfo['country']) && \is_string($userInfo['country'])) {
            $fan->setCountry($userInfo['country']);
        }
    }

    /**
     * @param array<string, mixed> $userInfo
     */
    private function updateSubscriptionInfo(Fan $fan, array $userInfo): void
    {
        if (isset($userInfo['subscribe_time']) && \is_int($userInfo['subscribe_time']) && $userInfo['subscribe_time'] > 0) {
            $fan->setSubscribeTime(new \DateTimeImmutable('@' . (string) $userInfo['subscribe_time']));
        }

        if (isset($userInfo['subscribe']) && \is_int($userInfo['subscribe']) && 0 === $userInfo['subscribe']) {
            $fan->setStatus(FanStatus::Unsubscribed);
            $account = $fan->getAccount();
            $this->logger->warning('发现已取关用户', [
                'openid' => $fan->getOpenid(),
                'account' => $account->getId(),
            ]);
        }
    }
}
