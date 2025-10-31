<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Exception;

class TagAlreadyExistsException extends \InvalidArgumentException
{
    public static function forTagName(string $name): self
    {
        return new self("Tag with name '{$name}' already exists");
    }
}
