<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\Tag;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 获取标签下粉丝列表
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/User_Group_Management.html#%E8%8E%B7%E5%8F%96%E6%A0%87%E7%AD%BE%E4%B8%8B%E7%B2%89%E4%B8%9D%E5%88%97%E8%A1%A8
 */
class GetTagUsersRequest extends WithAccountRequest
{
    #[Assert\NotNull(message: '标签ID不能为空')]
    #[Assert\PositiveOrZero(message: '标签ID必须是非负整数')]
    private ?int $tagid = null;

    #[Assert\Length(max: 128, maxMessage: 'next_openid长度不能超过128个字符')]
    private ?string $nextOpenid = null;

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/user/tag/get';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    public function getTagid(): ?int
    {
        return $this->tagid;
    }

    public function setTagid(int $tagid): void
    {
        $this->tagid = $tagid;
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
        $data = [
            'tagid' => $this->tagid,
        ];

        if (null !== $this->nextOpenid) {
            $data['next_openid'] = $this->nextOpenid;
        }

        return [
            'query' => [
                'access_token' => $this->getAccount()->getAccessToken(),
            ],
            'json' => $data,
        ];
    }
}
