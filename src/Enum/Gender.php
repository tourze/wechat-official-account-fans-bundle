<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 性别枚举
 */
enum Gender: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case Unknown = 0;
    case Male = 1;
    case Female = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::Unknown => '未知',
            self::Male => '男',
            self::Female => '女',
        };
    }

    /**
     * 从整数值获取枚举实例
     */
    public static function fromInt(int $value): self
    {
        return match ($value) {
            0 => self::Unknown,
            1 => self::Male,
            2 => self::Female,
            default => self::Unknown,
        };
    }

    /**
     * 获取所有枚举的选项数组（用于下拉列表等）
     *
     * @return array<int, array{value: int, label: string}>
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
