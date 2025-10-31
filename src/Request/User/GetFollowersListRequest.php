<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\User;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 获取用户列表
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/Getting_a_User_List.html#%E8%8E%B7%E5%8F%96%E7%94%A8%E6%88%B7%E5%88%97%E8%A1%A8
 */
class GetFollowersListRequest extends WithAccountRequest
{
    #[Assert\Length(max: 128, maxMessage: 'next_openid长度不能超过128个字符')]
    private ?string $nextOpenid = null;

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/user/get';
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
    }

    public function getNextOpenid(): ?string
    {
        return $this->nextOpenid;
    }

    public function setNextOpenid(?string $nextOpenid): void
    {
        $this->nextOpenid = $nextOpenid;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestOptions(): array
    {
        $query = [
            'access_token' => $this->getAccount()->getAccessToken(),
        ];

        if (null !== $this->nextOpenid) {
            $query['next_openid'] = $this->nextOpenid;
        }

        return [
            'query' => $query,
        ];
    }
}
