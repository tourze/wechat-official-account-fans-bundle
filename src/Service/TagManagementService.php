<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;
use Tourze\WechatOfficialAccountFansBundle\Exception\TagAlreadyExistsException;
use Tourze\WechatOfficialAccountFansBundle\Exception\TagNotFoundException;
use Tourze\WechatOfficialAccountFansBundle\Repository\FanRepository;
use Tourze\WechatOfficialAccountFansBundle\Repository\TagRepository;
use WechatOfficialAccountBundle\Entity\Account;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'wechat_official_account_fans')]
readonly class TagManagementService
{
    public function __construct(
        private TagRepository $tagRepository,
        private FanRepository $fanRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return Tag[]
     */
    public function getTagsByAccount(Account $account): array
    {
        return $this->tagRepository->findByAccount($account);
    }

    public function getTagById(Account $account, int $tagId): ?Tag
    {
        return $this->tagRepository->findByAccountAndTagid($account, $tagId);
    }

    public function createTag(Account $account, string $name): Tag
    {
        $existingTag = $this->tagRepository->findByAccountAndName($account, $name);
        if (null !== $existingTag) {
            throw TagAlreadyExistsException::forTagName($name);
        }

        // 生成新的tagid，找出账号下最大的tagid然后+1
        $maxTagid = $this->getMaxTagidForAccount($account);
        $newTagid = $maxTagid + 1;

        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setName($name);
        $tag->setTagid($newTagid);
        $tag->setCount(0);

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        $this->logger->info('Created new tag', [
            'account_id' => $account->getId(),
            'tag_name' => $name,
            'tag_id' => $tag->getId(),
            'tagid' => $newTagid,
        ]);

        return $tag;
    }

    public function updateTag(Account $account, int $tagId, string $newName): bool
    {
        $tag = $this->tagRepository->findByAccountAndTagid($account, $tagId);
        if (null === $tag) {
            return false;
        }

        $existingTag = $this->tagRepository->findByAccountAndName($account, $newName);
        if (null !== $existingTag && $existingTag->getId() !== $tag->getId()) {
            throw TagAlreadyExistsException::forTagName($newName);
        }

        $oldName = $tag->getName();
        $tag->setName($newName);
        $this->entityManager->flush();

        $this->logger->info('Updated tag name', [
            'account_id' => $account->getId(),
            'tag_id' => $tagId,
            'old_name' => $oldName,
            'new_name' => $newName,
        ]);

        return true;
    }

    public function deleteTag(Account $account, int $tagId): bool
    {
        $tag = $this->tagRepository->findByAccountAndTagid($account, $tagId);
        if (null === $tag) {
            return false;
        }

        $fanCount = $tag->getFanTags()->count();

        $this->entityManager->remove($tag);
        $this->entityManager->flush();

        $this->logger->info('Deleted tag', [
            'account_id' => $account->getId(),
            'tag_id' => $tagId,
            'tag_name' => $tag->getName(),
            'fan_count' => $fanCount,
        ]);

        return true;
    }

    /**
     * @return array{
     *     tag_id: int,
     *     tag_name: string,
     *     fan_count: int,
     *     fans: array{openid: string, nickname: ?string}[]
     * }[]
     */
    public function getTagsWithFans(Account $account): array
    {
        $tags = $this->tagRepository->findByAccount($account);
        $result = [];

        foreach ($tags as $tag) {
            $fans = [];
            foreach ($tag->getFans() as $fan) {
                $openid = $fan->getOpenid();
                if (null === $openid) {
                    continue;
                }
                $fans[] = [
                    'openid' => $openid,
                    'nickname' => $fan->getNickname(),
                ];
            }

            $result[] = [
                'tag_id' => $tag->getTagid() ?? 0,
                'tag_name' => $tag->getName() ?? '',
                'fan_count' => count($fans),
                'fans' => $fans,
            ];
        }

        return $result;
    }

    /**
     * @return array{tag_id: int, tag_name: string, fan_count: int}[]
     */
    public function getTagStatistics(Account $account): array
    {
        $tags = $this->tagRepository->findByAccount($account);
        $result = [];

        foreach ($tags as $tag) {
            $result[] = [
                'tag_id' => $tag->getTagid() ?? 0,
                'tag_name' => $tag->getName() ?? '',
                'fan_count' => $tag->getFanTags()->count(),
            ];
        }

        usort($result, fn ($a, $b) => $b['fan_count'] <=> $a['fan_count']);

        return $result;
    }

    /**
     * @return array{openid: string, nickname: ?string, subscribe_time: ?string}[]
     */
    public function getFansByTag(Account $account, int $tagId): array
    {
        $fans = $this->fanRepository->findByAccountAndTagId($account, $tagId);
        $result = [];

        foreach ($fans as $fan) {
            $openid = $fan->getOpenid();
            if (null === $openid) {
                continue;
            }
            $result[] = [
                'openid' => $openid,
                'nickname' => $fan->getNickname(),
                'subscribe_time' => $fan->getSubscribeTime()?->format('Y-m-d H:i:s'),
            ];
        }

        return $result;
    }

    public function syncTagCount(Account $account, int $tagId): bool
    {
        $tag = $this->tagRepository->findByAccountAndTagid($account, $tagId);
        if (null === $tag) {
            return false;
        }

        $actualCount = $tag->getFanTags()->count();
        $tag->setCount($actualCount);
        $this->entityManager->flush();

        $this->logger->info('Synced tag count', [
            'account_id' => $account->getId(),
            'tag_id' => $tagId,
            'count' => $actualCount,
        ]);

        return true;
    }

    public function syncAllTagCounts(Account $account): int
    {
        $tags = $this->tagRepository->findByAccount($account);
        $updatedCount = 0;

        foreach ($tags as $tag) {
            $actualCount = $tag->getFanTags()->count();
            if ($tag->getCount() !== $actualCount) {
                $tag->setCount($actualCount);
                ++$updatedCount;
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $this->logger->info('Synced all tag counts', [
                'account_id' => $account->getId(),
                'updated_count' => $updatedCount,
            ]);
        }

        return $updatedCount;
    }

    private function getMaxTagidForAccount(Account $account): int
    {
        $maxTagid = $this->tagRepository->getMaxTagidByAccount($account);

        return $maxTagid ?? 0;
    }
}
