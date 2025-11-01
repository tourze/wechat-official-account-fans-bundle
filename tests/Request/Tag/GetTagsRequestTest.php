<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Request\Tag;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\WechatOfficialAccountFansBundle\Request\Tag\GetTagsRequest;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(GetTagsRequest::class)]
final class GetTagsRequestTest extends RequestTestCase
{
    public function testRequestCanBeInstantiated(): void
    {
        $request = new GetTagsRequest();

        $this->assertInstanceOf(GetTagsRequest::class, $request);
    }

    public function testGetRequestPath(): void
    {
        $request = new GetTagsRequest();

        $this->assertSame('https://api.weixin.qq.com/cgi-bin/tags/get', $request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $request = new GetTagsRequest();

        $this->assertSame('GET', $request->getRequestMethod());
    }

    public function testGetRequestOptions(): void
    {
        $account = new Account();
        $account->setName('测试账号');
        $account->setAccessToken('test_access_token');

        $request = new GetTagsRequest();
        $request->setAccount($account);

        $options = $request->getRequestOptions();

        $this->assertArrayHasKey('query', $options);

        // 明确类型断言以避免mixed类型错误
        $query = $options['query'];
        $this->assertIsArray($query);
        $this->assertArrayHasKey('access_token', $query);
        $this->assertSame('test_access_token', $query['access_token']);
    }
}
