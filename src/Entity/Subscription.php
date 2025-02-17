<?php

namespace ErgoSarapu\DonationBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ErgoSarapu\DonationBundle\Entity\Subscription\Status;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity]
#[ORM\Table]
class Subscription
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?Payment $initialPayment = null;

    #[ORM\Column(name: '`interval`', length: 255, nullable: false)]
    private ?string $interval = null;

    #[ORM\Column(nullable: false)]
    private ?int $amount = null;

    #[ORM\Column(length: 3, nullable: false)]
    private ?string $currencyCode = null;

    #[Orm\Column(length: 16, enumType: Status::class, nullable: false)]
    private ?Status $status;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $nextRenewalTime;

    #[ORM\OneToMany(mappedBy: 'subscription', targetEntity: Payment::class, cascade: ['persist'])]
    private Collection $payments;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getInitialPayment(): ?Payment
    {
        return $this->initialPayment;
    }

    public function setInitialPayment(?Payment $initialPayment): self
    {
        $this->initialPayment = $initialPayment;

        return $this;
    }

    public function getInterval(): ?string
    {
        return $this->interval;
    }

    public function setInterval(?string $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    public function setStatus(?Status $status): self {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?Status {
        return $this->status;
    }
    
    public function __toString(): string
    {
        return $this->id;
    }

    public function getNextRenewalTime(): DateTime
    {
        return $this->nextRenewalTime;
    }

    public function setNextRenewalTime($nextRenewalTime): self
    {
        $this->nextRenewalTime = $nextRenewalTime;

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }
}
