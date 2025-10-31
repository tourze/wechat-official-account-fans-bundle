<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Request\Tag;

use HttpClientBundle\Tests\Request\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\WechatOfficialAccountFansBundle\Request\Tag\UpdateTagRequest;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(UpdateTagRequest::class)]
final class UpdateTagRequestTest extends RequestTestCase
{
    public function testGetRequestPath(): void
    {
        $request = new UpdateTagRequest();

        $this->assertSame('https://api.weixin.qq.com/cgi-bin/tags/update', $request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $request = new UpdateTagRequest();

        $this->assertSame('POST', $request->getRequestMethod());
    }

    public function testIdGetterSetter(): void
    {
        $request = new UpdateTagRequest();

        $this->assertNull($request->getId());

        $request->setId(123);
        $this->assertSame(123, $request->getId());
    }

    public function testNameGetterSetter(): void
    {
        $request = new UpdateTagRequest();

        $this->assertNull($request->getName());

        $request->setName('test_tag_name');
        $this->assertSame('test_tag_name', $request->getName());
    }

    public function testGetRequestOptions(): void
    {
        $account = new Account();
        $account->setAccessToken('test_access_token');

        $request = new UpdateTagRequest();
        $request->setAccount($account);
        $request->setId(123);
        $request->setName('test_tag_name');

        $options = $request->getRequestOptions();

        $this->assertArrayHasKey('query', $options);
        $this->assertArrayHasKey('json', $options);
        $this->assertSame([
            'access_token' => 'test_access_token',
        ], $options['query']);
        $this->assertSame([
            'tag' => [
                'id' => 123,
                'name' => 'test_tag_name',
            ],
        ], $options['json']);
    }
}
