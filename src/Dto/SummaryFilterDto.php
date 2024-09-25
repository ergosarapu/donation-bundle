<?php

namespace ErgoSarapu\DonationBundle\Dto;

use DateTime;
use ErgoSarapu\DonationBundle\Enum\Period;

class SummaryFilterDto
{
    private ?Period $groupByPeriod = null;

    private ?DateTime $startDate = null;

    private ?DateTime $endDate = null;

    public function getGroupByPeriod():?Period{
        return $this->groupByPeriod;
    }

    public function setGroupByPeriod(?Period $period):void{
        $this->groupByPeriod = $period;
    }

    public function getStartDate():?DateTime{
        return $this->startDate;
    }

    public function setStartDate(?DateTime $startDate):void{
        $this->startDate = $startDate;
    }

    public function getEndDate():?DateTime{
        return $this->endDate;
    }

    public function setEndDate(?DateTime $endDate):void{
        $this->endDate = $endDate;
    }
}
