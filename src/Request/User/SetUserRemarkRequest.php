<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\User;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 设置用户备注名
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/Configuring_user_remarks.html
 */
class SetUserRemarkRequest extends WithAccountRequest
{
    #[Assert\NotBlank(message: '用户openid不能为空')]
    #[Assert\Length(max: 128, maxMessage: 'openid长度不能超过128个字符')]
    private ?string $openid = null;

    #[Assert\Length(max: 30, maxMessage: '备注名长度不能超过30个字符')]
    private ?string $remark = null;

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/user/info/updateremark';
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

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
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
                'remark' => $this->remark ?? '',
            ],
        ];
    }
}
