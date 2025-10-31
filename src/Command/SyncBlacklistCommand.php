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
use Tourze\WechatOfficialAccountFansBundle\Repository\FanRepository;
use Tourze\WechatOfficialAccountFansBundle\Request\User\GetBlacklistRequest;
use WechatOfficialAccountBundle\Entity\Account;
use WechatOfficialAccountBundle\Repository\AccountRepository;
use WechatOfficialAccountBundle\Service\OfficialAccountClient;

/**
 * 同步黑名单列表
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/Blacklisting_a_user.html#%E8%8E%B7%E5%8F%96%E5%85%AC%E4%BC%97%E5%8F%B7%E7%9A%84%E9%BB%91%E5%90%8D%E5%8D%95%E5%88%97%E8%A1%A8
 */
#[WithMonologChannel(channel: 'wechat_official_account_fans')]
#[AsCronTask(expression: '50 2 * * *')]
#[AsCommand(name: self::NAME, description: '同步黑名单列表')]
class SyncBlacklistCommand extends Command
{
    public const NAME = 'wechat:official-account:sync-blacklist';

    private const BATCH_SIZE = 100; // 批量处理大小

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
            $this->logger->info('开始同步黑名单列表', ['account' => $account->getId()]);

            $allBlacklistedOpenids = $this->fetchAllBlacklistedOpenids($account);
            $this->syncBlacklistStatus($account, $allBlacklistedOpenids);

            $this->logger->info('同步黑名单列表完成', [
                'account' => $account->getId(),
                'blacklisted_count' => \count($allBlacklistedOpenids),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('同步黑名单列表时发生错误', [
                'account' => $account->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * @return string[]
     */
    private function fetchAllBlacklistedOpenids(Account $account): array
    {
        /** @var string[] $allBlacklistedOpenids */
        $allBlacklistedOpenids = [];
        $beginOpenid = null;

        do {
            $response = $this->fetchBlacklistPage($account, $beginOpenid);

            if (!$this->isValidResponse($response, $account)) {
                break;
            }

            /** @var array<string, mixed> $data */
            $data = $response['data'] ?? [];
            /** @var string[] $openids */
            $openids = $data['openid'] ?? [];
            $allBlacklistedOpenids = array_merge($allBlacklistedOpenids, $openids);
            /** @var string|null $beginOpenid */
            $beginOpenid = $response['next_openid'] ?? null;

            $this->logPageResult($account, $openids, $allBlacklistedOpenids, $beginOpenid);

            if (null !== $beginOpenid) {
                usleep(100000); // 100ms
            }
        } while (null !== $beginOpenid);

        return $allBlacklistedOpenids;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchBlacklistPage(Account $account, ?string $beginOpenid): array
    {
        $request = new GetBlacklistRequest();
        $request->setAccount($account);
        if (null !== $beginOpenid) {
            $request->setBeginOpenid($beginOpenid);
        }

        /** @var array<string, mixed> */
        return $this->client->request($request);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function isValidResponse(array $response, Account $account): bool
    {
        if (!isset($response['data']) || !\is_array($response['data']) || !isset($response['data']['openid']) || !\is_array($response['data']['openid'])) {
            if (isset($response['total']) && 0 === $response['total']) {
                $this->logger->info('该公众号暂无黑名单用户', ['account' => $account->getId()]);

                return false;
            }
            $this->logger->warning('获取黑名单响应格式异常', [
                'account' => $account->getId(),
                'response' => $response,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param string[] $openids
     * @param string[] $allBlacklistedOpenids
     */
    private function logPageResult(Account $account, array $openids, array $allBlacklistedOpenids, ?string $beginOpenid): void
    {
        $this->logger->debug('获取到一页黑名单数据', [
            'account' => $account->getId(),
            'current_page_count' => \count($openids),
            'accumulated_count' => \count($allBlacklistedOpenids),
            'has_next' => null !== $beginOpenid && '' !== $beginOpenid,
        ]);
    }

    /**
     * 同步黑名单状态
     *
     * @param array<string> $blacklistedOpenids
     */
    private function syncBlacklistStatus(Account $account, array $blacklistedOpenids): void
    {
        if ([] === $blacklistedOpenids) {
            $this->clearAllBlockedUsers($account);

            return;
        }

        $this->updateBlacklistedUsers($account, $blacklistedOpenids);
        $this->unblockRemovedUsers($account, $blacklistedOpenids);
    }

    private function clearAllBlockedUsers(Account $account): void
    {
        $this->fanRepository->createQueryBuilder('f')
            ->update()
            ->set('f.status', ':unsubscribed')
            ->where('f.account = :account')
            ->andWhere('f.status = :blocked')
            ->setParameter('unsubscribed', FanStatus::Unsubscribed)
            ->setParameter('account', $account)
            ->setParameter('blocked', FanStatus::Blocked)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param string[] $blacklistedOpenids
     */
    private function updateBlacklistedUsers(Account $account, array $blacklistedOpenids): void
    {
        $chunks = array_chunk($blacklistedOpenids, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $this->processBlacklistChunk($account, $chunk);
        }
    }

    /**
     * @param string[] $chunk
     */
    private function processBlacklistChunk(Account $account, array $chunk): void
    {
        $existingFans = $this->getExistingFansMap($account, $chunk);

        foreach ($chunk as $openid) {
            if (isset($existingFans[$openid])) {
                $this->updateExistingFan($existingFans[$openid]);
            } else {
                $this->createBlockedFan($account, $openid);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param string[] $chunk
     * @return array<string, Fan>
     */
    private function getExistingFansMap(Account $account, array $chunk): array
    {
        $existingFans = [];
        foreach ($this->fanRepository->findBy(['account' => $account, 'openid' => $chunk]) as $fan) {
            $existingFans[$fan->getOpenid()] = $fan;
        }

        return $existingFans;
    }

    private function updateExistingFan(Fan $fan): void
    {
        if (FanStatus::Blocked !== $fan->getStatus()) {
            $fan->setStatus(FanStatus::Blocked);
        }
    }

    private function createBlockedFan(Account $account, string $openid): void
    {
        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid($openid);
        $fan->setStatus(FanStatus::Blocked);
        $this->entityManager->persist($fan);
    }

    /**
     * @param string[] $blacklistedOpenids
     */
    private function unblockRemovedUsers(Account $account, array $blacklistedOpenids): void
    {
        $chunks = array_chunk($blacklistedOpenids, self::BATCH_SIZE);
        foreach ($chunks as $chunk) {
            $this->fanRepository->createQueryBuilder('f')
                ->update()
                ->set('f.status', ':unsubscribed')
                ->where('f.account = :account')
                ->andWhere('f.status = :blocked')
                ->andWhere('f.openid NOT IN (:openids)')
                ->setParameter('unsubscribed', FanStatus::Unsubscribed)
                ->setParameter('account', $account)
                ->setParameter('blocked', FanStatus::Blocked)
                ->setParameter('openids', $chunk)
                ->getQuery()
                ->execute()
            ;
        }
    }
}
