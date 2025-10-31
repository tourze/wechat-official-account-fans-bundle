<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\WechatOfficialAccountFansBundle\Exception\TagAlreadyExistsException;

/**
 * @internal
 */
#[CoversClass(TagAlreadyExistsException::class)]
class TagAlreadyExistsExceptionTest extends AbstractExceptionTestCase
{
    public function testForTagName(): void
    {
        $tagName = 'Test Tag';
        $exception = TagAlreadyExistsException::forTagName($tagName);

        $this->assertInstanceOf(TagAlreadyExistsException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertSame("Tag with name 'Test Tag' already exists", $exception->getMessage());
    }
}
