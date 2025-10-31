<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\FanTag;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;

/** @template-extends AbstractCrudController<FanTag> */
#[AdminCrud(routePath: '/wechat-fans/fan-tag', routeName: 'wechat_fans_fan_tag')]
final class FanTagCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FanTag::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('粉丝标签关系')
            ->setEntityLabelInPlural('粉丝标签关系管理')
            ->setPageTitle('index', '粉丝标签关系列表')
            ->setPageTitle('detail', '粉丝标签关系详情')
            ->setPageTitle('new', '创建粉丝标签关系')
            ->setPageTitle('edit', '编辑粉丝标签关系')
            ->setHelp('index', '管理粉丝与标签的关联关系')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['fan.nickname', 'fan.openid', 'tag.name'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        yield AssociationField::new('fan', '粉丝')
            ->setHelp('关联的粉丝')
            ->setRequired(true)
            ->autocomplete()
            ->formatValue(function ($value) {
                if (!$value instanceof Fan) {
                    return '';
                }

                $nickname = $value->getNickname();
                if (null !== $nickname) {
                    return $nickname;
                }

                $openid = $value->getOpenid();
                if (null !== $openid) {
                    return $openid;
                }

                return (string) $value->getId();
            })
        ;

        yield AssociationField::new('tag', '标签')
            ->setHelp('关联的标签')
            ->setRequired(true)
            ->autocomplete()
            ->formatValue(function ($value) {
                if (!$value instanceof Tag) {
                    return '';
                }

                $name = $value->getName();
                if (null !== $name) {
                    return $name;
                }

                return (string) $value->getId();
            })
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('关系创建时间')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnDetail()
            ->setHelp('关系更新时间')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        // 安全地更新EDIT action（如果存在）
        try {
            $actions->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('编辑关系');
            });
        } catch (\InvalidArgumentException) {
            // EDIT action 不存在，添加它
            $actions->add(Crud::PAGE_INDEX, Action::EDIT)
                ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                    return $action->setLabel('编辑关系');
                })
            ;
        }

        // 安全地更新DELETE action（如果存在）
        try {
            $actions->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('删除关系');
            });
        } catch (\InvalidArgumentException) {
            // DELETE action 不存在，添加它
            $actions->add(Crud::PAGE_INDEX, Action::DELETE)
                ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                    return $action->setLabel('删除关系');
                })
            ;
        }

        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('fan', '粉丝'))
            ->add(EntityFilter::new('tag', '标签'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}
