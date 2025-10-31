<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tourze\WechatOfficialAccountFansBundle\Entity\Tag;

/** @template-extends AbstractCrudController<Tag> */
#[AdminCrud(routePath: '/wechat-fans/tag', routeName: 'wechat_fans_tag')]
final class WechatFansTagCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Tag::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('标签')
            ->setEntityLabelInPlural('标签管理')
            ->setPageTitle('index', '标签列表')
            ->setPageTitle('detail', '标签详情')
            ->setPageTitle('new', '创建标签')
            ->setPageTitle('edit', '编辑标签')
            ->setHelp('index', '管理微信公众号粉丝标签')
            ->setDefaultSort(['tagid' => 'ASC'])
            ->setSearchFields(['name'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        yield AssociationField::new('account', '公众号')
            ->setHelp('所属微信公众号')
        ;

        if (Crud::PAGE_EDIT === $pageName) {
            yield IntegerField::new('tagid', '微信标签ID')
                ->setHelp('微信系统中的标签ID')
                ->setFormTypeOption('disabled', true)
            ;
        } elseif (Crud::PAGE_INDEX === $pageName || Crud::PAGE_DETAIL === $pageName) {
            yield IntegerField::new('tagid', '微信标签ID')
                ->setHelp('微信系统中的标签ID')
            ;
        }

        yield TextField::new('name', '标签名称')
            ->setRequired(true)
            ->setMaxLength(30)
            ->setHelp('标签名称，最多30个字符')
        ;

        yield IntegerField::new('count', '粉丝数量')
            ->setHelp('拥有此标签的粉丝数量')
            ->hideOnForm()
            ->formatValue(function ($value) {
                if (!is_int($value)) {
                    return '0';
                }

                return number_format($value);
            })
        ;

        yield AssociationField::new('fanTags', '关联粉丝')
            ->setHelp('拥有此标签的粉丝')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                if (!$value instanceof \Countable) {
                    return '暂无粉丝';
                }

                $count = $value->count();

                return $count > 0 ? "共 {$count} 个粉丝" : '暂无粉丝';
            })
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnDetail()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $syncAction = Action::new('sync', '同步标签', 'fas fa-sync')
            ->linkToCrudAction('syncTags')
            ->displayAsButton()
            ->addCssClass('btn btn-info')
        ;

        $syncCountAction = Action::new('syncCount', '同步粉丝数', 'fas fa-users')
            ->linkToCrudAction('syncCount')
            ->displayAsButton()
            ->addCssClass('btn btn-warning')
        ;

        $actions->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $syncAction)
            ->add(Crud::PAGE_INDEX, $syncCountAction)
        ;

        // 安全地更新NEW action（如果存在）
        try {
            $actions->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('创建标签');
            });
        } catch (\InvalidArgumentException) {
            // NEW action 不存在，添加它
            $actions->add(Crud::PAGE_INDEX, Action::NEW)
                ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                    return $action->setLabel('创建标签');
                })
            ;
        }

        // 安全地更新EDIT action（如果存在）
        try {
            $actions->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('编辑标签');
            });
        } catch (\InvalidArgumentException) {
            // EDIT action 不存在，添加它
            $actions->add(Crud::PAGE_INDEX, Action::EDIT)
                ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                    return $action->setLabel('编辑标签');
                })
            ;
        }

        // 安全地更新DELETE action（如果存在）
        try {
            $actions->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('删除标签');
            });
        } catch (\InvalidArgumentException) {
            // DELETE action 不存在，添加它
            $actions->add(Crud::PAGE_INDEX, Action::DELETE)
                ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                    return $action->setLabel('删除标签');
                })
            ;
        }

        $actions->reorder(Crud::PAGE_INDEX, [Action::NEW, Action::DETAIL, Action::EDIT, Action::DELETE, 'sync', 'syncCount']);

        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('account', '公众号'))
            ->add(TextFilter::new('name', '标签名称'))
            ->add(NumericFilter::new('tagid', '微信标签ID'))
            ->add(NumericFilter::new('count', '粉丝数量'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    #[AdminAction(routeName: 'admin_wechat_fans_tag_sync', routePath: '/wechat-fans/tag/sync')]
    public function syncTags(): RedirectResponse
    {
        // 这里可以调用同步标签的服务方法
        // $this->tagManagementService->syncAllTags();
        $this->addFlash('success', '标签同步功能待实现');

        return $this->redirectToRoute('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]);
    }

    #[AdminAction(routeName: 'admin_wechat_fans_tag_sync_count', routePath: '/wechat-fans/tag/sync-count')]
    public function syncCount(): RedirectResponse
    {
        // 这里可以调用同步粉丝数的服务方法
        // $this->tagManagementService->syncAllTagCounts();
        $this->addFlash('success', '粉丝数同步功能待实现');

        return $this->redirectToRoute('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]);
    }
}
