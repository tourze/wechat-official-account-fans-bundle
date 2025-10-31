<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\Tag;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 获取用户身上的标签列表
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/User_Group_Management.html#%E8%8E%B7%E5%8F%96%E7%94%A8%E6%88%B7%E8%BA%AB%E4%B8%8A%E7%9A%84%E6%A0%87%E7%AD%BE%E5%88%97%E8%A1%A8
 */
class GetUserTagsRequest extends WithAccountRequest
{
    #[Assert\NotBlank(message: '用户openid不能为空')]
    #[Assert\Length(max: 128, maxMessage: 'openid长度不能超过128个字符')]
    private ?string $openid = null;

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/tags/getidlist';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    public function setOpenid(string $openid): void
    {
        $this->openid = $openid;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestOptions(): array
    {
        return [
            'query' => [
                'access_token' => $this->getAccount()->getAccessToken(),
            ],
            'json' => [
                'openid' => $this->openid,
            ],
        ];
    }
}
