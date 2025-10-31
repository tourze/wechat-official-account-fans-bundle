<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @extends ServiceEntityRepository<Tag>
 */
#[AsRepository(entityClass: Tag::class)]
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @return array<Tag>
     */
    public function findByAccount(Account $account): array
    {
        return $this->findBy(['account' => $account], ['tagid' => 'ASC']);
    }

    public function findByAccountAndTagid(Account $account, int $tagid): ?Tag
    {
        return $this->findOneBy([
            'account' => $account,
            'tagid' => $tagid,
        ]);
    }

    public function findByAccountAndName(Account $account, string $name): ?Tag
    {
        return $this->findOneBy([
            'account' => $account,
            'name' => $name,
        ]);
    }

    /**
     * 获取指定用户的所有标签
     * @return array<Tag>
     */
    public function findByAccountAndFanOpenid(Account $account, string $openid): array
    {
        /** @var array<Tag> */
        return $this->createQueryBuilder('t')
            ->innerJoin('t.fanTags', 'ft')
            ->innerJoin('ft.fan', 'f')
            ->where('t.account = :account')
            ->andWhere('f.openid = :openid')
            ->setParameter('account', $account)
            ->setParameter('openid', $openid)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getMaxTagidByAccount(Account $account): ?int
    {
        $result = $this->createQueryBuilder('t')
            ->select('MAX(t.tagid)')
            ->where('t.account = :account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return null !== $result ? (int) $result : null;
    }

    public function save(Tag $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Tag $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
