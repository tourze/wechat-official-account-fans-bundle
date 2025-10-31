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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\WechatOfficialAccountFansBundle\Entity\Fan;
use Tourze\WechatOfficialAccountFansBundle\Entity\FanTag;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;
use Tourze\WechatOfficialAccountFansBundle\Enum\Gender;

/** @template-extends AbstractCrudController<Fan> */
#[AdminCrud(routePath: '/wechat-fans/fan', routeName: 'wechat_fans_fan')]
final class FanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Fan::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('粉丝')
            ->setEntityLabelInPlural('粉丝管理')
            ->setPageTitle('index', '粉丝列表')
            ->setPageTitle('detail', '粉丝详情')
            ->setPageTitle('edit', '编辑粉丝')
            ->setHelp('index', '管理微信公众号粉丝信息')
            ->setDefaultSort(['subscribeTime' => 'DESC'])
            ->setSearchFields(['nickname', 'openid', 'remark'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->hideOnForm();

        yield AssociationField::new('account', '公众号')
            ->setHelp('所属微信公众号')
        ;

        yield TextField::new('openid', 'OpenID')
            ->setRequired(true)
            ->setHelp('微信用户OpenID')
        ;

        yield TextField::new('unionid', 'UnionID')
            ->setHelp('微信用户UnionID')
            ->hideOnIndex()
        ;

        yield TextField::new('nickname', '昵称')
            ->setHelp('用户昵称')
        ;

        yield ImageField::new('headimgurl', '头像')
            ->setHelp('用户头像URL')
            ->hideOnIndex()
            ->hideOnForm()
        ;

        yield UrlField::new('headimgurl', '头像链接')
            ->setHelp('用户头像URL')
            ->onlyOnDetail()
        ;

        yield ChoiceField::new('sex', '性别')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => Gender::class])
            ->formatValue($this->formatGenderValue(...))
            ->hideOnIndex()
        ;

        yield TextField::new('language', '语言')
            ->setHelp('用户语言')
            ->hideOnIndex()
        ;

        yield TextField::new('city', '城市')
            ->setHelp('用户所在城市')
            ->hideOnIndex()
        ;

        yield TextField::new('province', '省份')
            ->setHelp('用户所在省份')
            ->hideOnIndex()
        ;

        yield TextField::new('country', '国家')
            ->setHelp('用户所在国家')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('subscribeTime', '关注时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('用户关注公众号的时间')
            ->hideOnForm()
        ;

        yield TextField::new('remark', '备注名')
            ->setHelp('为用户设置的备注名')
        ;

        yield ChoiceField::new('status', '状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => FanStatus::class])
            ->formatValue($this->formatStatusValue(...))
        ;

        yield AssociationField::new('fanTags', '标签')
            ->setHelp('用户所拥有的标签')
            ->hideOnIndex()
            ->formatValue($this->formatFanTagsValue(...))
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('updatedAt', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnDetail()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        $actions->disable(Action::NEW, Action::DELETE);

        // 安全地更新EDIT action（如果不存在则先添加）
        try {
            $actions->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('编辑备注');
            });
        } catch (\InvalidArgumentException) {
            // EDIT action 不存在，添加它
            $actions->add(Crud::PAGE_INDEX, Action::EDIT)
                ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                    return $action->setLabel('编辑备注');
                })
            ;
        }

        $actions->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT]);

        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $statusChoices = [];
        foreach (FanStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        $genderChoices = [];
        foreach (Gender::cases() as $case) {
            $genderChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(EntityFilter::new('account', '公众号'))
            ->add(TextFilter::new('nickname', '昵称'))
            ->add(TextFilter::new('openid', 'OpenID'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices($statusChoices))
            ->add(ChoiceFilter::new('sex', '性别')->setChoices($genderChoices))
            ->add(TextFilter::new('city', '城市'))
            ->add(TextFilter::new('province', '省份'))
            ->add(DateTimeFilter::new('subscribeTime', '关注时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    private function formatGenderValue(mixed $value): string
    {
        return $value instanceof Gender ? $value->getLabel() : '未知';
    }

    private function formatStatusValue(mixed $value): string
    {
        return $value instanceof FanStatus ? $value->getLabel() : '';
    }

    private function formatFanTagsValue(mixed $value): string
    {
        if (!is_iterable($value)) {
            return '';
        }

        $tags = [];
        foreach ($value as $fanTag) {
            if (!$fanTag instanceof FanTag) {
                continue;
            }
            $tag = $fanTag->getTag();
            if (null !== $tag) {
                $tagName = $tag->getName();
                if (null !== $tagName) {
                    $tags[] = $tagName;
                }
            }
        }

        return implode(', ', $tags);
    }
}
