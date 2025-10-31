<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\FanTag;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;

/**
 * @extends ServiceEntityRepository<FanTag>
 */
#[AsRepository(entityClass: FanTag::class)]
class FanTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FanTag::class);
    }

    /**
     * @return array<FanTag>
     */
    public function findByFan(Fan $fan): array
    {
        return $this->findBy(['fan' => $fan]);
    }

    /**
     * @return array<FanTag>
     */
    public function findByTag(Tag $tag): array
    {
        return $this->findBy(['tag' => $tag]);
    }

    public function findByFanAndTag(Fan $fan, Tag $tag): ?FanTag
    {
        return $this->findOneBy([
            'fan' => $fan,
            'tag' => $tag,
        ]);
    }

    /**
     * 批量删除指定粉丝的所有标签关系
     */
    public function removeAllTagsFromFan(Fan $fan): int
    {
        $result = $this->createQueryBuilder('ft')
            ->delete()
            ->where('ft.fan = :fan')
            ->setParameter('fan', $fan)
            ->getQuery()
            ->execute()
        ;
        assert(is_int($result));

        return $result;
    }

    /**
     * 批量删除指定标签的所有粉丝关系
     */
    public function removeAllFansFromTag(Tag $tag): int
    {
        $result = $this->createQueryBuilder('ft')
            ->delete()
            ->where('ft.tag = :tag')
            ->setParameter('tag', $tag)
            ->getQuery()
            ->execute()
        ;
        assert(is_int($result));

        return $result;
    }

    public function save(FanTag $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FanTag $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
