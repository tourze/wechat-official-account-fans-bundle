<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\Tag;

use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 获取公众号已创建的标签
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/User_Group_Management.html#%E8%8E%B7%E5%8F%96%E5%85%AC%E4%BC%97%E5%8F%B7%E5%B7%B2%E5%88%9B%E5%BB%BA%E7%9A%84%E6%A0%87%E7%AD%BE
 */
class GetTagsRequest extends WithAccountRequest
{
    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/tags/get';
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
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
        ];
    }
}
