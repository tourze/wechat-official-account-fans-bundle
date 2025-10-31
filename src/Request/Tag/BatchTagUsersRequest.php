<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\Tag;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 批量为用户打标签
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/User_Group_Management.html#%E6%89%B9%E9%87%8F%E4%B8%BA%E7%94%A8%E6%88%B7%E6%89%93%E6%A0%87%E7%AD%BE
 */
class BatchTagUsersRequest extends WithAccountRequest
{
    #[Assert\NotNull(message: '标签ID不能为空')]
    #[Assert\PositiveOrZero(message: '标签ID必须是非负整数')]
    private ?int $tagid = null;

    /**
     * @var string[]
     */
    #[Assert\NotBlank(message: '用户openid列表不能为空')]
    #[Assert\Count(min: 1, max: 20, minMessage: '至少需要1个用户', maxMessage: '最多只能同时为20个用户打标签')]
    #[Assert\All(constraints: [
        new Assert\NotBlank(message: 'openid不能为空'),
        new Assert\Length(max: 128, maxMessage: 'openid长度不能超过128个字符'),
    ])]
    private array $openidList = [];

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging';
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
                'tagid' => $this->tagid,
                'openid_list' => $this->openidList,
            ],
        ];
    }
}
