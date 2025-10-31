<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\Tag;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 删除标签
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/User_Group_Management.html#%E5%88%A0%E9%99%A4%E6%A0%87%E7%AD%BE
 */
class DeleteTagRequest extends WithAccountRequest
{
    #[Assert\NotNull(message: '标签ID不能为空')]
    #[Assert\PositiveOrZero(message: '标签ID必须是非负整数')]
    private ?int $id = null;

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/tags/delete';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
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
                'tag' => [
                    'id' => $this->id,
                ],
            ],
        ];
    }
}
