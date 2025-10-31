<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\User;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 获取用户基本信息
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/Getting_a_User_List.html#%E8%8E%B7%E5%8F%96%E7%94%A8%E6%88%B7%E5%9F%BA%E6%9C%AC%E4%BF%A1%E6%81%AF%EF%BC%88%E5%8C%85%E6%8B%AC UnionID%E6%9C%BA%E5%88%B6%EF%BC%89
 */
class GetUserInfoRequest extends WithAccountRequest
{
    #[Assert\NotBlank(message: '用户openid不能为空')]
    #[Assert\Length(max: 128, maxMessage: 'openid长度不能超过128个字符')]
    private ?string $openid = null;

    #[Assert\Choice(choices: ['zh_CN', 'zh_TW', 'en'], message: '语言必须是 zh_CN, zh_TW, en 中的一个')]
    private string $lang = 'zh_CN';

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/user/info';
    }

    public function getRequestMethod(): ?string
    {
        return 'GET';
    }

    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    public function setOpenid(string $openid): void
    {
        $this->openid = $openid;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): void
    {
        $this->lang = $lang;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestOptions(): array
    {
        return [
            'query' => [
                'access_token' => $this->getAccount()->getAccessToken(),
                'openid' => $this->openid,
                'lang' => $this->lang,
            ],
        ];
    }
}
