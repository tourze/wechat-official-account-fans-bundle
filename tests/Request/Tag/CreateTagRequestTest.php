<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Request\Tag;

use PHPUnit\Framework\Attributes\CoversClass;
use HttpClientBundle\Tests\Request\RequestTestCase;
use Tourze\WechatOfficialAccountFansBundle\Request\Tag\CreateTagRequest;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(CreateTagRequest::class)]
final class CreateTagRequestTest extends RequestTestCase
{
    public function testGetRequestPath(): void
    {
        $request = new CreateTagRequest();

        $this->assertSame('https://api.weixin.qq.com/cgi-bin/tags/create', $request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $request = new CreateTagRequest();

        $this->assertSame('POST', $request->getRequestMethod());
    }

    public function testNameGetterSetter(): void
    {
        $request = new CreateTagRequest();

        $this->assertNull($request->getName());

        $request->setName('test_tag_name');
        $this->assertSame('test_tag_name', $request->getName());
    }

    public function testGetRequestOptions(): void
    {
        $account = new Account();
        $account->setAccessToken('test_access_token');

        $request = new CreateTagRequest();
        $request->setAccount($account);
        $request->setName('test_tag_name');

        $options = $request->getRequestOptions();

        $this->assertArrayHasKey('query', $options);
        $this->assertArrayHasKey('json', $options);
        $this->assertSame([
            'access_token' => 'test_access_token',
        ], $options['query']);
        $this->assertSame([
            'tag' => [
                'name' => 'test_tag_name',
            ],
        ], $options['json']);
    }
}
