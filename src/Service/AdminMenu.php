<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\FanTag;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;

readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('微信公众号')) {
            $item->addChild('微信公众号');
        }

        $wechatMenu = $item->getChild('微信公众号');
        if (null === $wechatMenu) {
            return;
        }

        // 直接在微信公众号下添加菜单项，不要再嵌套一层
        // 粉丝列表菜单
        $wechatMenu->addChild('粉丝列表')
            ->setUri($this->linkGenerator->getCurdListPage(Fan::class))
            ->setAttribute('icon', 'fas fa-users')
        ;

        // 标签管理菜单
        $wechatMenu->addChild('标签管理')
            ->setUri($this->linkGenerator->getCurdListPage(Tag::class))
            ->setAttribute('icon', 'fas fa-tags')
        ;

        // 粉丝标签关系菜单
        $wechatMenu->addChild('粉丝标签关系')
            ->setUri($this->linkGenerator->getCurdListPage(FanTag::class))
            ->setAttribute('icon', 'fas fa-link')
        ;
    }
}
