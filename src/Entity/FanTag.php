<?php

declare(strict_types=1);

namespace Tourze\WechatOfficialAccountFansBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\WechatOfficialAccountFansBundle\Repository\FanTagRepository;

#[ORM\Entity(repositoryClass: FanTagRepository::class)]
#[ORM\Table(name: 'wechat_official_account_fan_tag', options: ['comment' => '粉丝标签关系'])]
#[ORM\UniqueConstraint(name: 'fan_tag_uniq', columns: ['fan_id', 'tag_id'])]
class FanTag implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[Assert\NotNull(message: '粉丝不能为空')]
    #[ORM\ManyToOne(targetEntity: Fan::class, inversedBy: 'fanTags')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Fan $fan = null;

    #[Assert\NotNull(message: '标签不能为空')]
    #[ORM\ManyToOne(targetEntity: Tag::class, inversedBy: 'fanTags')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Tag $tag = null;

    public function __toString(): string
    {
        return "Fan({$this->fan}) - Tag({$this->tag})";
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFan(): ?Fan
    {
        return $this->fan;
    }

    public function setFan(?Fan $fan): void
    {
        $this->fan = $fan;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): void
    {
        $this->tag = $tag;
    }
}
