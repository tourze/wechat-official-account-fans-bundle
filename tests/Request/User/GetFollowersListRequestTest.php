<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Request\User;

use HttpClientBundle\Test\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\WechatOfficialAccountFansBundle\Request\User\GetFollowersListRequest;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(GetFollowersListRequest::class)]
final class GetFollowersListRequestTest extends RequestTestCase
{
    public function testGetRequestPath(): void
    {
        $request = new GetFollowersListRequest();

        $this->assertSame('https://api.weixin.qq.com/cgi-bin/user/get', $request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $request = new GetFollowersListRequest();

        $this->assertSame('GET', $request->getRequestMethod());
    }

    public function testNextOpenidGetterSetter(): void
    {
        $request = new GetFollowersListRequest();

        $this->assertNull($request->getNextOpenid());

        $request->setNextOpenid('test_next_openid');
        $this->assertSame('test_next_openid', $request->getNextOpenid());
    }

    public function testNextOpenidSetterWithNull(): void
    {
        $request = new GetFollowersListRequest();

        $request->setNextOpenid(null);
        $this->assertNull($request->getNextOpenid());
    }

    public function testGetRequestOptions(): void
    {
        $account = new Account();
        $account->setAccessToken('test_access_token');

        $request = new GetFollowersListRequest();
        $request->setAccount($account);
        $request->setNextOpenid('test_next_openid');

        $options = $request->getRequestOptions();

        $this->assertArrayHasKey('query', $options);
        $this->assertSame([
            'access_token' => 'test_access_token',
            'next_openid' => 'test_next_openid',
        ], $options['query']);
    }

    public function testGetRequestOptionsWithoutNextOpenid(): void
    {
        $account = new Account();
        $account->setAccessToken('test_access_token');

        $request = new GetFollowersListRequest();
        $request->setAccount($account);

        $options = $request->getRequestOptions();

        $this->assertArrayHasKey('query', $options);
        $this->assertSame([
            'access_token' => 'test_access_token',
        ], $options['query']);
    }
}
