<?php

namespace ErgoSarapu\DonationBundle\Entity;

use Payum\Core\Model\Token;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table]
class PaymentToken extends Token
{
}
