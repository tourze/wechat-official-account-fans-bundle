<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 粉丝状态枚举
 */
enum FanStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';
    case Blocked = 'blocked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Subscribed => '已关注',
            self::Unsubscribed => '已取消关注',
            self::Blocked => '已拉黑',
        };
    }

    /**
     * 获取所有枚举的选项数组（用于下拉列表等）
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function toSelectItems(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[] = [
                'value' => $case->value,
                'label' => $case->getLabel(),
            ];
        }

        return $result;
    }
}
