<?php

namespace ErgoSarapu\DonationBundle\Entity;

use Payum\Core\Model\Payment as BasePayment;
use Doctrine\ORM\Mapping as ORM;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;

#[ORM\Entity]
#[ORM\Table]
class Payment extends BasePayment
{
    use TimestampableEntity;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;

    #[Orm\Column(length: 16, enumType: Status::class)]
    protected Status $status;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $givenName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $familyName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nationalIdCode = null;

    #[ORM\ManyToOne]
    private ?Campaign $campaign = null;

    #[ORM\ManyToOne]
    private ?Subscription $subscription = null;

    public function getId(): int {
        return $this->id;
    }

    public function setStatus(Status $status) {
        $this->status = $status;
    }

    public function getStatus(): Status {
        return $this->status;
    }

    public function getGivenName():?string{
        return $this->givenName;
    }

    public function setGivenName(?string $givenName):void{
        $this->givenName = $givenName;
    }

    public function getFamilyName():?string{
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName):void{
        $this->familyName = $familyName;
    }

    public function getNationalIdCode():?string{
        return $this->nationalIdCode;
    }

    public function setNationalIdCode(?string $nationalIdCode):void{
        $this->nationalIdCode = $nationalIdCode;
    }

    public function getDetailsString():string{
        return json_encode($this->getDetails());
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): static
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): static
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function __toString(): string
    {
        $money = new Money($this->totalAmount, new Currency($this->currencyCode));
        $moneyFormatter = new DecimalMoneyFormatter(new ISOCurrencies());

        return sprintf(
            '%s %s %s (%s)',
            $this->createdAt->format('Y-m-d'),
            $moneyFormatter->format($money),
            $this->currencyCode,
            $this->id,
        );
    }
}
