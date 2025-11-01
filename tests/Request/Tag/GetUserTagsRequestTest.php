<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Request\Tag;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\WechatOfficialAccountFansBundle\Request\Tag\GetUserTagsRequest;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(GetUserTagsRequest::class)]
final class GetUserTagsRequestTest extends RequestTestCase
{
    public function testGetRequestPath(): void
    {
        $request = new GetUserTagsRequest();

        $this->assertSame('https://api.weixin.qq.com/cgi-bin/tags/getidlist', $request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $request = new GetUserTagsRequest();

        $this->assertSame('POST', $request->getRequestMethod());
    }

    public function testOpenidGetterSetter(): void
    {
        $request = new GetUserTagsRequest();

        $this->assertNull($request->getOpenid());

        $request->setOpenid('test_openid');
        $this->assertSame('test_openid', $request->getOpenid());
    }

    public function testGetRequestOptions(): void
    {
        $account = new Account();
        $account->setAccessToken('test_access_token');

        $request = new GetUserTagsRequest();
        $request->setAccount($account);
        $request->setOpenid('test_openid');

        $options = $request->getRequestOptions();

        $this->assertArrayHasKey('query', $options);
        $this->assertArrayHasKey('json', $options);
        $this->assertSame([
            'access_token' => 'test_access_token',
        ], $options['query']);
        $this->assertSame([
            'openid' => 'test_openid',
        ], $options['json']);
    }
}
