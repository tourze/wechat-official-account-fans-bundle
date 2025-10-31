<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\WechatOfficialAccountFansBundle\Exception\TagNotFoundException;

/**
 * @internal
 */
#[CoversClass(TagNotFoundException::class)]
final class TagNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testForTagId(): void
    {
        $exception = TagNotFoundException::forTagId(123);

        $this->assertInstanceOf(TagNotFoundException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('Tag with ID 123 not found', $exception->getMessage());
    }

    public function testForTagName(): void
    {
        $exception = TagNotFoundException::forTagName('duplicate-tag');

        $this->assertInstanceOf(TagNotFoundException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame("Tag with name 'duplicate-tag' not found", $exception->getMessage());
    }
}
