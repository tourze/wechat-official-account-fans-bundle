<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\Tag;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 编辑标签
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/User_Group_Management.html#%E7%BC%96%E8%BE%91%E6%A0%87%E7%AD%BE
 */
class UpdateTagRequest extends WithAccountRequest
{
    #[Assert\NotNull(message: '标签ID不能为空')]
    #[Assert\PositiveOrZero(message: '标签ID必须是非负整数')]
    private ?int $id = null;

    #[Assert\NotBlank(message: '标签名不能为空')]
    #[Assert\Length(max: 30, maxMessage: '标签名长度不能超过30个字符')]
    private ?string $name = null;

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/tags/update';
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
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
                    'name' => $this->name,
                ],
            ],
        ];
    }
}
