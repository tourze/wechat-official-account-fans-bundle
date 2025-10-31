<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\FanTag;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;
use Tourze\WechatOfficialAccountFansBundle\Exception\TagNotFoundException;
use Tourze\WechatOfficialAccountFansBundle\Repository\FanRepository;
use Tourze\WechatOfficialAccountFansBundle\Repository\FanTagRepository;
use Tourze\WechatOfficialAccountFansBundle\Repository\TagRepository;
use WechatOfficialAccountBundle\Entity\Account;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'wechat_official_account_fans')]
readonly class FanManagementService
{
    public function __construct(
        private FanRepository $fanRepository,
        private TagRepository $tagRepository,
        private FanTagRepository $fanTagRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{
     *     fans: array<Fan>,
     *     total: int,
     *     page: int,
     *     limit: int,
     *     hasNext: bool
     * }
     */
    public function getFansPaginated(Account $account, int $page = 1, int $limit = 20, ?FanStatus $status = null, ?int $tagId = null): array
    {
        $qb = $this->fanRepository->createQueryBuilder('f')
            ->where('f.account = :account')
            ->setParameter('account', $account)
            ->orderBy('f.subscribeTime', 'DESC')
        ;

        if (null !== $status) {
            $qb->andWhere('f.status = :status')
                ->setParameter('status', $status)
            ;
        }

        if (null !== $tagId) {
            $qb->innerJoin('f.fanTags', 'ft')
                ->innerJoin('ft.tag', 't')
                ->andWhere('t.tagid = :tagId')
                ->setParameter('tagId', $tagId)
            ;
        }

        $totalQuery = clone $qb;
        $total = (int) $totalQuery->select('COUNT(f.id)')->getQuery()->getSingleScalarResult();

        $offset = ($page - 1) * $limit;
        /** @var array<Fan> $fans */
        $fans = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        return [
            'fans' => $fans,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'hasNext' => $total > $offset + $limit,
        ];
    }

    public function getFanByOpenid(Account $account, string $openid): ?Fan
    {
        return $this->fanRepository->findByAccountAndOpenid($account, $openid);
    }

    /**
     * @return array{
     *     subscribed: int,
     *     unsubscribed: int,
     *     blocked: int,
     *     total: int
     * }
     */
    public function getFanStatistics(Account $account): array
    {
        $subscribed = $this->fanRepository->countByAccountAndStatus($account, FanStatus::Subscribed);
        $unsubscribed = $this->fanRepository->countByAccountAndStatus($account, FanStatus::Unsubscribed);
        $blocked = $this->fanRepository->countByAccountAndStatus($account, FanStatus::Blocked);

        return [
            'subscribed' => $subscribed,
            'unsubscribed' => $unsubscribed,
            'blocked' => $blocked,
            'total' => $subscribed + $unsubscribed + $blocked,
        ];
    }

    /**
     * @param string[] $openids
     */
    public function batchAddTagToFans(Account $account, array $openids, int $tagId): int
    {
        $tag = $this->tagRepository->findByAccountAndTagid($account, $tagId);
        if (null === $tag) {
            throw TagNotFoundException::forTagId($tagId);
        }

        $addedCount = 0;

        foreach ($openids as $openid) {
            $fan = $this->fanRepository->findByAccountAndOpenid($account, $openid);
            if (null === $fan) {
                continue;
            }

            $existingFanTag = $this->fanTagRepository->findByFanAndTag($fan, $tag);
            if (null !== $existingFanTag) {
                continue;
            }

            $fanTag = new FanTag();
            $fanTag->setFan($fan);
            $fanTag->setTag($tag);

            $this->entityManager->persist($fanTag);
            ++$addedCount;
        }

        if ($addedCount > 0) {
            $this->entityManager->flush();
            $this->logger->info('Batch added tag to fans', [
                'account_id' => $account->getId(),
                'tag_id' => $tagId,
                'openids_count' => count($openids),
                'added_count' => $addedCount,
            ]);
        }

        return $addedCount;
    }

    /**
     * @param string[] $openids
     */
    public function batchRemoveTagFromFans(Account $account, array $openids, int $tagId): int
    {
        $tag = $this->tagRepository->findByAccountAndTagid($account, $tagId);
        if (null === $tag) {
            throw TagNotFoundException::forTagId($tagId);
        }

        $removedCount = 0;

        foreach ($openids as $openid) {
            $fan = $this->fanRepository->findByAccountAndOpenid($account, $openid);
            if (null === $fan) {
                continue;
            }

            $fanTag = $this->fanTagRepository->findByFanAndTag($fan, $tag);
            if (null === $fanTag) {
                continue;
            }

            $this->entityManager->remove($fanTag);
            ++$removedCount;
        }

        if ($removedCount > 0) {
            $this->entityManager->flush();
            $this->logger->info('Batch removed tag from fans', [
                'account_id' => $account->getId(),
                'tag_id' => $tagId,
                'openids_count' => count($openids),
                'removed_count' => $removedCount,
            ]);
        }

        return $removedCount;
    }

    public function updateFanRemark(Account $account, string $openid, string $remark): bool
    {
        $fan = $this->fanRepository->findByAccountAndOpenid($account, $openid);
        if (null === $fan) {
            return false;
        }

        $fan->setRemark($remark);
        $this->entityManager->flush();

        $this->logger->info('Updated fan remark', [
            'account_id' => $account->getId(),
            'openid' => $openid,
            'remark' => $remark,
        ]);

        return true;
    }

    /**
     * @return array<array{openid: string, nickname: ?string, status: string, tags: array<string>}>
     */
    public function exportFansData(Account $account, ?FanStatus $status = null): array
    {
        $qb = $this->fanRepository->createQueryBuilder('f')
            ->where('f.account = :account')
            ->setParameter('account', $account)
            ->orderBy('f.subscribeTime', 'DESC')
        ;

        if (null !== $status) {
            $qb->andWhere('f.status = :status')
                ->setParameter('status', $status)
            ;
        }

        /** @var array<Fan> $fans */
        $fans = $qb->getQuery()->getResult();
        $result = [];

        foreach ($fans as $fan) {
            $tags = [];
            foreach ($fan->getTags() as $tag) {
                $tagName = $tag->getName();
                if (null !== $tagName) {
                    $tags[] = $tagName;
                }
            }

            $openid = $fan->getOpenid();
            if (null === $openid) {
                continue;
            }

            $result[] = [
                'openid' => $openid,
                'nickname' => $fan->getNickname(),
                'status' => $fan->getStatus()->value,
                'tags' => $tags,
            ];
        }

        return $result;
    }
}
