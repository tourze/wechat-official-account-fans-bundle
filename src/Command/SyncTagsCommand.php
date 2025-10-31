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
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;
use Tourze\WechatOfficialAccountFansBundle\Repository\TagRepository;
use Tourze\WechatOfficialAccountFansBundle\Request\Tag\GetTagsRequest;
use WechatOfficialAccountBundle\Entity\Account;
use WechatOfficialAccountBundle\Repository\AccountRepository;
use WechatOfficialAccountBundle\Service\OfficialAccountClient;

/**
 * 同步公众号标签列表
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/User_Group_Management.html#%E8%8E%B7%E5%8F%96%E5%85%AC%E4%BC%97%E5%8F%B7%E5%B7%B2%E5%88%9B%E5%BB%BA%E7%9A%84%E6%A0%87%E7%AD%BE
 */
#[WithMonologChannel(channel: 'wechat_official_account_fans')]
#[AsCronTask(expression: '5 2 * * *')]
#[AsCommand(name: self::NAME, description: '同步公众号标签列表')]
class SyncTagsCommand extends Command
{
    public const NAME = 'wechat:official-account:sync-tags';

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly OfficialAccountClient $client,
        private readonly TagRepository $tagRepository,
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
            $this->logger->info('开始同步标签列表', ['account' => $account->getId()]);

            $tagsData = $this->fetchTagsData($account);
            if (null === $tagsData) {
                return;
            }

            $this->syncTags($account, $tagsData);

            $this->logger->info('同步标签完成', [
                'account' => $account->getId(),
                'total_tags' => \count($tagsData),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('同步标签时发生错误', [
                'account' => $account->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * @return array<int, array{id: int, name: string, count: int}>|null
     */
    private function fetchTagsData(Account $account): ?array
    {
        $request = new GetTagsRequest();
        $request->setAccount($account);
        /** @var array<string, mixed> $response */
        $response = $this->client->request($request);

        if (!isset($response['tags']) || !\is_array($response['tags'])) {
            $this->logger->warning('获取标签列表响应格式异常', [
                'account' => $account->getId(),
                'response' => $response,
            ]);

            return null;
        }

        /** @var array<int, array{id: int, name: string, count: int}> */
        return $response['tags'];
    }

    /**
     * @param array<int, array{id: int, name: string, count: int}> $tagsData
     */
    private function syncTags(Account $account, array $tagsData): void
    {
        $existingTags = $this->getExistingTagsMap($account);
        $processedTagIds = $this->processApiTags($account, $tagsData, $existingTags);
        $this->removeObsoleteTags($account, $existingTags, $processedTagIds);

        $this->entityManager->flush();
    }

    /**
     * @return array<int, Tag>
     */
    private function getExistingTagsMap(Account $account): array
    {
        $existingTags = [];
        foreach ($this->tagRepository->findByAccount($account) as $tag) {
            /** @var int $tagid */
            $tagid = $tag->getTagid();
            $existingTags[$tagid] = $tag;
        }

        return $existingTags;
    }

    /**
     * @param array<int, array{id: int, name: string, count: int}> $tagsData
     * @param array<int, Tag> $existingTags
     * @return int[]
     */
    private function processApiTags(Account $account, array $tagsData, array $existingTags): array
    {
        $processedTagIds = [];

        foreach ($tagsData as $tagData) {
            $tagid = $tagData['id'];
            $processedTagIds[] = $tagid;

            if (isset($existingTags[$tagid])) {
                $this->updateExistingTag($existingTags[$tagid], $tagData);
            } else {
                $this->createNewTag($account, $tagData);
            }
        }

        return $processedTagIds;
    }

    /**
     * @param array{id: int, name: string, count: int} $tagData
     */
    private function updateExistingTag(Tag $tag, array $tagData): void
    {
        $tag->setName($tagData['name']);
        $tag->setCount($tagData['count']);
    }

    /**
     * @param array{id: int, name: string, count: int} $tagData
     */
    private function createNewTag(Account $account, array $tagData): void
    {
        $tag = new Tag();
        $tag->setAccount($account);
        $tag->setTagid($tagData['id']);
        $tag->setName($tagData['name']);
        $tag->setCount($tagData['count']);
        $this->entityManager->persist($tag);
    }

    /**
     * @param array<int, Tag> $existingTags
     * @param int[] $processedTagIds
     */
    private function removeObsoleteTags(Account $account, array $existingTags, array $processedTagIds): void
    {
        foreach ($existingTags as $tagid => $tag) {
            if (!\in_array($tagid, $processedTagIds, true)) {
                $this->entityManager->remove($tag);
                $this->logger->info('删除已不存在的标签', [
                    'account' => $account->getId(),
                    'tagid' => $tagid,
                    'tag_name' => $tag->getName(),
                ]);
            }
        }
    }
}
