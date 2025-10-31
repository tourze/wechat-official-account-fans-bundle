<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Exception;

class TagNotFoundException extends \RuntimeException
{
    public static function forTagId(int $tagId): self
    {
        return new self("Tag with ID {$tagId} not found");
    }

    public static function forTagName(string $name): self
    {
        return new self("Tag with name '{$name}' not found");
    }
}
