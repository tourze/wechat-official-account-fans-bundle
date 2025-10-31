<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Request\Tag;

use HttpClientBundle\Tests\Request\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\WechatOfficialAccountFansBundle\Request\Tag\DeleteTagRequest;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(DeleteTagRequest::class)]
final class DeleteTagRequestTest extends RequestTestCase
{
    public function testGetRequestPath(): void
    {
        $request = new DeleteTagRequest();

        $this->assertSame('https://api.weixin.qq.com/cgi-bin/tags/delete', $request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $request = new DeleteTagRequest();

        $this->assertSame('POST', $request->getRequestMethod());
    }

    public function testIdGetterSetter(): void
    {
        $request = new DeleteTagRequest();

        $this->assertNull($request->getId());

        $request->setId(123);
        $this->assertSame(123, $request->getId());
    }

    public function testGetRequestOptions(): void
    {
        $account = new Account();
        $account->setAccessToken('test_access_token');

        $request = new DeleteTagRequest();
        $request->setAccount($account);
        $request->setId(123);

        $options = $request->getRequestOptions();

        $this->assertArrayHasKey('query', $options);
        $this->assertArrayHasKey('json', $options);
        $this->assertSame([
            'access_token' => 'test_access_token',
        ], $options['query']);
        $this->assertSame([
            'tag' => [
                'id' => 123,
            ],
        ], $options['json']);
    }
}
