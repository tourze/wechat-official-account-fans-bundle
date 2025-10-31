<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\WechatOfficialAccountFansBundle\Repository\TagRepository;
use WechatOfficialAccountBundle\Entity\Account;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'wechat_official_account_tag', options: ['comment' => '公众号标签'])]
#[ORM\UniqueConstraint(name: 'tag_account_tagid_uniq', columns: ['account_id', 'tagid'])]
#[ORM\UniqueConstraint(name: 'tag_account_name_uniq', columns: ['account_id', 'name'])]
class Tag implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[Assert\NotNull(message: '标签ID不能为空')]
    #[Assert\PositiveOrZero(message: '标签ID必须是非负整数')]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '微信标签ID'])]
    private ?int $tagid = null;

    #[Assert\NotBlank(message: '标签名称不能为空')]
    #[Assert\Length(max: 30, maxMessage: '标签名称长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 30, options: ['comment' => '标签名称'])]
    private ?string $name = null;

    #[Assert\PositiveOrZero(message: '粉丝数量必须是非负整数')]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '此标签下粉丝数', 'default' => 0])]
    private int $count = 0;

    /**
     * @var Collection<int, FanTag>
     */
    #[ORM\OneToMany(targetEntity: FanTag::class, mappedBy: 'tag', cascade: ['persist'], orphanRemoval: true)]
    private Collection $fanTags;

    public function __construct()
    {
        $this->fanTags = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? (string) ($this->tagid ?? $this->id);
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

    public function getTagid(): ?int
    {
        return $this->tagid;
    }

    public function setTagid(int $tagid): void
    {
        $this->tagid = $tagid;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
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
            $fanTag->setTag($this);
        }
    }

    public function removeFanTag(FanTag $fanTag): void
    {
        if ($this->fanTags->removeElement($fanTag)) {
            if ($fanTag->getTag() === $this) {
                $fanTag->setTag(null);
            }
        }
    }

    /**
     * @return Collection<int, Fan>
     */
    public function getFans(): Collection
    {
        $fans = new ArrayCollection();
        foreach ($this->fanTags as $fanTag) {
            $fan = $fanTag->getFan();
            if (null !== $fan) {
                $fans->add($fan);
            }
        }

        return $fans;
    }
}
