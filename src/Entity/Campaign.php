<?php

namespace ErgoSarapu\DonationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ErgoSarapu\DonationBundle\Repository\CampaignRepository;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\UniqueConstraint(columns: ['public_id'])]
class Campaign
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $name = null;

    #[ORM\Column(name: '`default`')]
    private ?bool $default = null;

    #[ORM\Column]
    private ?int $publicId = null;

    #[ORM\Column]
    private ?string $publicTitle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function getPublicId(): ?int
    {
        return $this->publicId;
    }

    public function setPublicId(int $publicId): static
    {
        $this->publicId = $publicId;

        return $this;
    }

    public function getPublicTitle(): ?string
    {
        return $this->publicTitle;
    }

    public function setPublicTitle(string $publicTitle): static
    {
        $this->publicTitle = $publicTitle;

        return $this;
    }
}
