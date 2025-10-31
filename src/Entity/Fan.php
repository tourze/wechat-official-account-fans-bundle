<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\WechatOfficialAccountFansBundle\Enum\FanStatus;
use Tourze\WechatOfficialAccountFansBundle\Enum\Gender;
use Tourze\WechatOfficialAccountFansBundle\Repository\FanRepository;
use WechatOfficialAccountBundle\Entity\Account;

#[ORM\Entity(repositoryClass: FanRepository::class)]
#[ORM\Table(name: 'wechat_official_account_fan', options: ['comment' => '公众号粉丝'])]
#[ORM\UniqueConstraint(name: 'fan_account_openid_uniq', columns: ['account_id', 'openid'])]
class Fan implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[Assert\NotBlank(message: 'OpenID不能为空')]
    #[Assert\Length(max: 128, maxMessage: 'OpenID长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 128, options: ['comment' => '用户OpenID'])]
    private ?string $openid = null;

    #[Assert\Length(max: 128, maxMessage: 'UnionID长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 128, nullable: true, options: ['comment' => '用户UnionID'])]
    private ?string $unionid = null;

    #[Assert\Length(max: 64, maxMessage: '昵称长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '昵称'])]
    private ?string $nickname = null;

    #[Assert\Length(max: 255, maxMessage: '头像URL长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '头像URL'])]
    private ?string $headimgurl = null;

    #[Assert\Choice(callback: [Gender::class, 'cases'])]
    #[ORM\Column(enumType: Gender::class, nullable: true, options: ['comment' => '性别'])]
    private ?Gender $sex = null;

    #[Assert\Length(max: 32, maxMessage: '语言长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '语言'])]
    private ?string $language = null;

    #[Assert\Length(max: 64, maxMessage: '城市长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '城市'])]
    private ?string $city = null;

    #[Assert\Length(max: 64, maxMessage: '省份长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '省份'])]
    private ?string $province = null;

    #[Assert\Length(max: 64, maxMessage: '国家长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '国家'])]
    private ?string $country = null;

    #[Assert\Type(type: \DateTimeInterface::class)]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '关注时间'])]
    private ?\DateTimeImmutable $subscribeTime = null;

    #[Assert\Length(max: 64, maxMessage: '备注名长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '备注名'])]
    private ?string $remark = null;

    #[Assert\Choice(callback: [FanStatus::class, 'cases'])]
    #[ORM\Column(enumType: FanStatus::class, options: ['comment' => '粉丝状态', 'default' => 'subscribed'])]
    private FanStatus $status = FanStatus::Subscribed;

    /**
     * @var Collection<int, FanTag>
     */
    #[ORM\OneToMany(targetEntity: FanTag::class, mappedBy: 'fan', cascade: ['persist'], orphanRemoval: true)]
    private Collection $fanTags;

    public function __construct()
    {
        $this->fanTags = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nickname ?? $this->openid ?? (string) $this->id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    public function getOpenid(): ?string
    {
        return $this->openid;
    }

    public function setOpenid(string $openid): void
    {
        $this->openid = $openid;
    }

    public function getUnionid(): ?string
    {
        return $this->unionid;
    }

    public function setUnionid(?string $unionid): void
    {
        $this->unionid = $unionid;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): void
    {
        $this->nickname = $nickname;
    }

    public function getHeadimgurl(): ?string
    {
        return $this->headimgurl;
    }

    public function setHeadimgurl(?string $headimgurl): void
    {
        $this->headimgurl = $headimgurl;
    }

    public function getSex(): ?Gender
    {
        return $this->sex;
    }

    public function setSex(?Gender $sex): void
    {
        $this->sex = $sex;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function setProvince(?string $province): void
    {
        $this->province = $province;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getSubscribeTime(): ?\DateTimeImmutable
    {
        return $this->subscribeTime;
    }

    public function setSubscribeTime(?\DateTimeImmutable $subscribeTime): void
    {
        $this->subscribeTime = $subscribeTime;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getStatus(): FanStatus
    {
        return $this->status;
    }

    public function setStatus(FanStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * @return Collection<int, FanTag>
     */
    public function getFanTags(): Collection
    {
        return $this->fanTags;
    }

    public function addFanTag(FanTag $fanTag): void
    {
        if (!$this->fanTags->contains($fanTag)) {
            $this->fanTags->add($fanTag);
            $fanTag->setFan($this);
        }
    }

    public function removeFanTag(FanTag $fanTag): void
    {
        if ($this->fanTags->removeElement($fanTag)) {
            if ($fanTag->getFan() === $this) {
                $fanTag->setFan(null);
            }
        }
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        $tags = new ArrayCollection();
        foreach ($this->fanTags as $fanTag) {
            $tag = $fanTag->getTag();
            if (null !== $tag) {
                $tags->add($tag);
            }
        }

        return $tags;
    }
}
