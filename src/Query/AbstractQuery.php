<?php

namespace ErgoSarapu\DonationBundle\Query;

use Doctrine\ORM\EntityManagerInterface;

class AbstractQuery
{
    public function __construct(protected EntityManagerInterface $em) {}
}
