<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\User;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 取消拉黑用户
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/Blacklisting_a_user.html#%E5%8F%96%E6%B6%88%E6%8B%89%E9%BB%91%E7%94%A8%E6%88%B7
 */
class BatchUnblacklistRequest extends WithAccountRequest
{
    /**
     * @var string[]
     */
    #[Assert\NotBlank(message: '用户openid列表不能为空')]
    #[Assert\Count(min: 1, max: 20, minMessage: '至少需要1个用户', maxMessage: '最多只能同时取消拉黑20个用户')]
    #[Assert\All(constraints: [
        new Assert\NotBlank(message: 'openid不能为空'),
        new Assert\Length(max: 128, maxMessage: 'openid长度不能超过128个字符'),
    ])]
    private array $openidList = [];

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/tags/members/batchunblacklist';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    /**
     * @return string[]
     */
    public function getOpenidList(): array
    {
        return $this->openidList;
    }

    /**
     * @param string[] $openidList
     */
    public function setOpenidList(array $openidList): void
    {
        $this->openidList = $openidList;
    }

    public function addOpenid(string $openid): void
    {
        if (!\in_array($openid, $this->openidList, true)) {
            $this->openidList[] = $openid;
        }
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
                'openid_list' => $this->openidList,
            ],
        ];
    }
}
