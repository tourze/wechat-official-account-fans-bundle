<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\Tag;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 创建标签
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/User_Group_Management.html#%E5%88%9B%E5%BB%BA%E6%A0%87%E7%AD%BE
 */
class CreateTagRequest extends WithAccountRequest
{
    #[Assert\NotBlank(message: '标签名不能为空')]
    #[Assert\Length(max: 30, maxMessage: '标签名长度不能超过30个字符')]
    private ?string $name = null;

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/tags/create';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
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
                    'name' => $this->name,
                ],
            ],
        ];
    }
}
