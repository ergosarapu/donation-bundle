<?php

namespace ErgoSarapu\DonationBundle\Entity;

use Payum\Core\Model\Payment as BasePayment;
use Doctrine\ORM\Mapping as ORM;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;

#[ORM\Entity]
#[ORM\Table]
class Payment extends BasePayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    protected int $id;

    #[Orm\Column(length: 16, enumType: Status::class)]
    protected Status $status;

    public function setStatus(Status $status) {
        $this->status = $status;
    }

    public function getStatus(): Status {
        return $this->status;
    }
}
