<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @extends ServiceEntityRepository<Fan>
 */
#[AsRepository(entityClass: Fan::class)]
class FanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fan::class);
    }

    /**
     * @return array<Fan>
     */
    public function findByAccount(Account $account): array
    {
        return $this->findBy(['account' => $account]);
    }

    public function findByAccountAndOpenid(Account $account, string $openid): ?Fan
    {
        return $this->findOneBy([
            'account' => $account,
            'openid' => $openid,
        ]);
    }

    /**
     * @return array<Fan>
     */
    public function findSubscribedByAccount(Account $account): array
    {
        return $this->findBy([
            'account' => $account,
            'status' => FanStatus::Subscribed,
        ]);
    }

    /**
     * @return array<Fan>
     */
    public function findBlockedByAccount(Account $account): array
    {
        return $this->findBy([
            'account' => $account,
            'status' => FanStatus::Blocked,
        ]);
    }

    public function countByAccountAndStatus(Account $account, FanStatus $status): int
    {
        return $this->count([
            'account' => $account,
            'status' => $status,
        ]);
    }

    /**
     * 查找指定标签下的粉丝
     * @return array<Fan>
     */
    public function findByAccountAndTagId(Account $account, int $tagId): array
    {
        /** @var array<Fan> */
        return $this->createQueryBuilder('f')
            ->innerJoin('f.fanTags', 'ft')
            ->innerJoin('ft.tag', 't')
            ->where('f.account = :account')
            ->andWhere('t.tagid = :tagId')
            ->setParameter('account', $account)
            ->setParameter('tagId', $tagId)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找没有任何标签的粉丝
     * @return array<Fan>
     */
    public function findUntaggedByAccount(Account $account): array
    {
        /** @var array<Fan> */
        return $this->createQueryBuilder('f')
            ->leftJoin('f.fanTags', 'ft')
            ->where('f.account = :account')
            ->andWhere('ft.id IS NULL')
            ->setParameter('account', $account)
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(Fan $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Fan $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
