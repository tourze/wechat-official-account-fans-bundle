<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Tests\Request\User;

use HttpClientBundle\Tests\Request\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\WechatOfficialAccountFansBundle\Request\User\GetUserInfoRequest;
use WechatOfficialAccountBundle\Entity\Account;

/**
 * @internal
 */
#[CoversClass(GetUserInfoRequest::class)]
final class GetUserInfoRequestTest extends RequestTestCase
{
    public function testGetRequestPath(): void
    {
        $request = new GetUserInfoRequest();

        $this->assertSame('https://api.weixin.qq.com/cgi-bin/user/info', $request->getRequestPath());
    }

    public function testGetRequestMethod(): void
    {
        $request = new GetUserInfoRequest();

        $this->assertSame('GET', $request->getRequestMethod());
    }

    public function testOpenidGetterSetter(): void
    {
        $request = new GetUserInfoRequest();

        $this->assertNull($request->getOpenid());

        $request->setOpenid('test_openid');
        $this->assertSame('test_openid', $request->getOpenid());
    }

    public function testLangGetterSetter(): void
    {
        $request = new GetUserInfoRequest();

        $this->assertSame('zh_CN', $request->getLang());

        $request->setLang('en');
        $this->assertSame('en', $request->getLang());
    }

    public function testGetRequestOptions(): void
    {
        $account = new Account();
        $account->setAccessToken('test_access_token');

        $request = new GetUserInfoRequest();
        $request->setAccount($account);
        $request->setOpenid('test_openid');
        $request->setLang('en');

        $options = $request->getRequestOptions();

        $this->assertArrayHasKey('query', $options);
        $this->assertSame([
            'access_token' => 'test_access_token',
            'openid' => 'test_openid',
            'lang' => 'en',
        ], $options['query']);
    }
}
