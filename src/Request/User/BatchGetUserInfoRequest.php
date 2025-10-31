<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Request\User;

use Symfony\Component\Validator\Constraints as Assert;
use WechatOfficialAccountBundle\Request\WithAccountRequest;

/**
 * 批量获取用户基本信息
 * @see https://developers.weixin.qq.com/doc/offiaccount/User_Management/Getting_a_User_List.html#%E6%89%B9%E9%87%8F%E8%8E%B7%E5%8F%96%E7%94%A8%E6%88%B7%E5%9F%BA%E6%9C%AC%E4%BF%A1%E6%81%AF
 */
class BatchGetUserInfoRequest extends WithAccountRequest
{
    /**
     * @var array<array{openid: string, lang?: string}>
     */
    #[Assert\NotBlank(message: '用户列表不能为空')]
    #[Assert\Count(min: 1, max: 100, minMessage: '至少需要1个用户', maxMessage: '最多只能同时获取100个用户的信息')]
    private array $userList = [];

    public function getRequestPath(): string
    {
        return 'https://api.weixin.qq.com/cgi-bin/user/info/batchget';
    }

    public function getRequestMethod(): ?string
    {
        return 'POST';
    }

    /**
     * @return array<array{openid: string, lang?: string}>
     */
    public function getUserList(): array
    {
        return $this->userList;
    }

    /**
     * @param array<array{openid: string, lang?: string}> $userList
     */
    public function setUserList(array $userList): void
    {
        $this->userList = $userList;
    }

    public function addUser(string $openid, string $lang = 'zh_CN'): void
    {
        $this->userList[] = [
            'openid' => $openid,
            'lang' => $lang,
        ];
    }

    /**
     * @param string[] $openids
     */
    public function setOpenids(array $openids, string $lang = 'zh_CN'): void
    {
        $this->userList = [];
        foreach ($openids as $openid) {
            $this->addUser($openid, $lang);
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
                'user_list' => $this->userList,
            ],
        ];
    }
}
