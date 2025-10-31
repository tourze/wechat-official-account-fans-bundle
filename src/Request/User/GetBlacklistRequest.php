<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\User;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 获取公众号的黑名单列表
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/Blacklisting_a_user.html#%E8%8E%B7%E5%8F%96%E5%85%AC%E4%BC%97%E5%8F%B7%E7%9A%84%E9%BB%91%E5%90%8D%E5%8D%95%E5%88%97%E8%A1%A8
 */
class GetBlacklistRequest extends WithAccountRequest
{
    #[Assert\Length(max: 128, maxMessage: 'begin_openid长度不能超过128个字符')]
    private ?string $beginOpenid = null;

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/tags/members/getblacklist';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    public function getBeginOpenid(): ?string
    {
        return $this->beginOpenid;
    }

    public function setBeginOpenid(?string $beginOpenid): void
    {
        $this->beginOpenid = $beginOpenid;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestOptions(): array
    {
        $json = [];

        if (null !== $this->beginOpenid) {
            $json['begin_openid'] = $this->beginOpenid;
        }

        return [
            'query' => [
                'access_token' => $this->getAccount()->getAccessToken(),
            ],
            'json' => $json,
        ];
    }
}
