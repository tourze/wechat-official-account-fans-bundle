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
use Tourze\WechatOfficialAccountFansBundle\Request\User\GetFollowersListRequest;
use WechatOfficialAccountBundle\Entity\Account;
use WechatOfficialAccountBundle\Repository\AccountRepository;
use WechatOfficialAccountBundle\Service\OfficialAccountClient;

/**
 * 同步公众号粉丝列表（仅OpenID）
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/Getting_a_User_List.html#%E8%8E%B7%E5%8F%96%E7%94%A8%E6%88%B7%E5%88%97%E8%A1%A8
 */
#[WithMonologChannel(channel: 'wechat_official_account_fans')]
#[AsCronTask(expression: '10 2 * * *')]
#[AsCommand(name: self::NAME, description: '同步公众号粉丝列表')]
class SyncFollowersCommand extends Command
{
    public const NAME = 'wechat:official-account:sync-followers';

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
            $this->logger->info('开始同步粉丝列表', ['account' => $account->getId()]);

            $allOpenids = $this->fetchAllFollowerOpenids($account);
            $this->syncFollowersStatus($account, $allOpenids);

            $this->logger->info('同步粉丝列表完成', [
                'account' => $account->getId(),
                'total_followers' => \count($allOpenids),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('同步粉丝列表时发生错误', [
                'account' => $account->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * @return string[]
     */
    private function fetchAllFollowerOpenids(Account $account): array
    {
        /** @var string[] $allOpenids */
        $allOpenids = [];
        $nextOpenid = null;

        do {
            $response = $this->fetchFollowersPage($account, $nextOpenid);

            if (!$this->isValidFollowersResponse($response, $account)) {
                break;
            }

            /** @var array<string, mixed> $data */
            $data = $response['data'] ?? [];
            /** @var string[] $openids */
            $openids = $data['openid'] ?? [];
            $allOpenids = array_merge($allOpenids, $openids);
            /** @var string|null $nextOpenid */
            $nextOpenid = $response['next_openid'] ?? null;

            $this->logFollowersPageResult($account, $openids, $allOpenids, $nextOpenid);

            if (null !== $nextOpenid) {
                usleep(100000); // 100ms
            }
        } while (null !== $nextOpenid);

        return $allOpenids;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchFollowersPage(Account $account, ?string $nextOpenid): array
    {
        $request = new GetFollowersListRequest();
        $request->setAccount($account);
        if (null !== $nextOpenid) {
            $request->setNextOpenid($nextOpenid);
        }

        /** @var array<string, mixed> */
        return $this->client->request($request);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function isValidFollowersResponse(array $response, Account $account): bool
    {
        if (!isset($response['data']) || !\is_array($response['data']) || !isset($response['data']['openid']) || !\is_array($response['data']['openid'])) {
            if (isset($response['total']) && 0 === $response['total']) {
                $this->logger->info('该公众号暂无粉丝', ['account' => $account->getId()]);

                return false;
            }
            $this->logger->warning('获取粉丝列表响应格式异常', [
                'account' => $account->getId(),
                'response' => $response,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param string[] $openids
     * @param string[] $allOpenids
     */
    private function logFollowersPageResult(Account $account, array $openids, array $allOpenids, ?string $nextOpenid): void
    {
        $this->logger->debug('获取到一页粉丝数据', [
            'account' => $account->getId(),
            'current_page_count' => \count($openids),
            'accumulated_count' => \count($allOpenids),
            'has_next' => null !== $nextOpenid && '' !== $nextOpenid,
        ]);
    }

    /**
     * @param string[] $allOpenids
     */
    private function syncFollowersStatus(Account $account, array $allOpenids): void
    {
        $this->processFansInBatches($account, $allOpenids);
        $this->markUnsubscribedFans($account, $allOpenids);
    }

    /**
     * @param string[] $openids
     */
    private function processFansInBatches(Account $account, array $openids): void
    {
        $chunks = array_chunk($openids, self::BATCH_SIZE);

        foreach ($chunks as $chunk) {
            $this->processFollowerChunk($account, $chunk);
        }
    }

    /**
     * @param string[] $chunk
     */
    private function processFollowerChunk(Account $account, array $chunk): void
    {
        $existingFans = $this->getExistingFollowersMap($account, $chunk);

        foreach ($chunk as $openid) {
            if (isset($existingFans[$openid])) {
                $this->updateExistingFollower($existingFans[$openid]);
            } else {
                $this->createNewFollower($account, $openid);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param string[] $chunk
     * @return array<string, Fan>
     */
    private function getExistingFollowersMap(Account $account, array $chunk): array
    {
        $existingFans = [];
        foreach ($this->fanRepository->findBy(['account' => $account, 'openid' => $chunk]) as $fan) {
            $existingFans[$fan->getOpenid()] = $fan;
        }

        return $existingFans;
    }

    private function updateExistingFollower(Fan $fan): void
    {
        if (FanStatus::Subscribed !== $fan->getStatus()) {
            $fan->setStatus(FanStatus::Subscribed);
        }
    }

    private function createNewFollower(Account $account, string $openid): void
    {
        $fan = new Fan();
        $fan->setAccount($account);
        $fan->setOpenid($openid);
        $fan->setStatus(FanStatus::Subscribed);
        $this->entityManager->persist($fan);
    }

    /**
     * @param string[] $currentOpenids
     */
    private function markUnsubscribedFans(Account $account, array $currentOpenids): void
    {
        if ([] === $currentOpenids) {
            $this->markAllFollowersAsUnsubscribed($account);

            return;
        }

        $this->markRemovedFollowersAsUnsubscribed($account, $currentOpenids);
    }

    private function markAllFollowersAsUnsubscribed(Account $account): void
    {
        $this->fanRepository->createQueryBuilder('f')
            ->update()
            ->set('f.status', ':unsubscribed')
            ->where('f.account = :account')
            ->andWhere('f.status = :subscribed')
            ->setParameter('unsubscribed', FanStatus::Unsubscribed)
            ->setParameter('account', $account)
            ->setParameter('subscribed', FanStatus::Subscribed)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param string[] $currentOpenids
     */
    private function markRemovedFollowersAsUnsubscribed(Account $account, array $currentOpenids): void
    {
        $chunks = array_chunk($currentOpenids, self::BATCH_SIZE);
        foreach ($chunks as $chunk) {
            $this->fanRepository->createQueryBuilder('f')
                ->update()
                ->set('f.status', ':unsubscribed')
                ->where('f.account = :account')
                ->andWhere('f.status = :subscribed')
                ->andWhere('f.openid NOT IN (:openids)')
                ->setParameter('unsubscribed', FanStatus::Unsubscribed)
                ->setParameter('account', $account)
                ->setParameter('subscribed', FanStatus::Subscribed)
                ->setParameter('openids', $chunk)
                ->getQuery()
                ->execute()
            ;
        }
    }
}
