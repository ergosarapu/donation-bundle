<?php

namespace ErgoSarapu\DonationBundle\Query;

use DateTime;
use ErgoSarapu\DonationBundle\Dto\Query\DtoResultSetMapping;
use ErgoSarapu\DonationBundle\Dto\Query\PaymentSummaryEntryDto;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use RuntimeException;

class PaymentSummaryMysqlQuery extends AbstractQuery implements PaymentSummaryQueryInterface
{
    private const SQL_DAY_SERIES_CTE = <<<EOT
    WITH RECURSIVE day_series AS (
        SELECT DATE('%s') AS date_value
        UNION ALL
        SELECT DATE_ADD(date_value, INTERVAL 1 DAY)
        FROM day_series
        WHERE date_value < DATE('%s')
    ),
    period_series AS (
        SELECT
            date_value AS start_date,
            date_value AS end_date,
            date_value AS period_key
        FROM day_series
    )
    EOT;

    private const SQL_WEEK_SERIES_CTE = <<<EOT
    WITH RECURSIVE week_series AS (
        SELECT 
            DATE('%s') + INTERVAL ( - WEEKDAY(DATE('%s'))) DAY AS start_date
        UNION ALL
        SELECT 
            DATE_ADD(start_date, INTERVAL 1 WEEK)
        FROM 
            week_series
        WHERE 
            start_date < DATE('%s') + INTERVAL ( - WEEKDAY(DATE('%s'))) DAY
    ),
    period_series AS (
        SELECT 
            CASE
                WHEN start_date < '%s' THEN DATE('%s') -- Cap start date to given start date
                ELSE start_date
            END
            AS start_date, 
            CASE
                WHEN DATE_ADD(start_date, INTERVAL 6 DAY) > '%s' THEN DATE('%s') -- Cap end date to given end date
                ELSE DATE_ADD(start_date, INTERVAL 6 DAY)
            END
            AS end_date,
            DATE_FORMAT(start_date,'%%Y-%%u') AS period_key
        FROM 
            week_series
    )
    EOT;

    private const SQL_MONTH_SERIES_CTE = <<<EOT
    WITH RECURSIVE month_series AS (
        SELECT 
            DATE(DATE_FORMAT('%s', '%%Y-%%m-01')) AS month_start,
            LAST_DAY('%s') AS month_end
        UNION ALL
        SELECT 
            DATE_ADD(month_start, INTERVAL 1 MONTH),
            LAST_DAY(DATE_ADD(month_start, INTERVAL 1 MONTH))
        FROM 
            month_series
        WHERE 
            DATE_ADD(month_start, INTERVAL 1 MONTH) <= '%s'  -- Ensure the next month_start is within the end date
    ),
    period_series AS (
        SELECT 
            CASE
                WHEN month_start < '%s' THEN DATE('%s') -- Cap start date to given start date
                ELSE month_start
            END
            AS start_date,
            CASE
                WHEN month_end > '%s' THEN DATE('%s') -- Cap end date to given end date
                ELSE month_end
            END
            AS end_date,
            DATE_FORMAT(month_start,'%%Y-%%m') AS period_key
        FROM 
            month_series
    )
    EOT;

    private const SQL = <<<EOT
    SELECT campaigns.campaign_id campaignId, campaigns.campaign_name campaignName, start_date startDate, end_date endDate, period_key periodKey, COALESCE(SUM(p.total_amount), 0) amount, ? currency FROM period_series
    LEFT JOIN
    (
        SELECT DISTINCT c.id campaign_id, c.name campaign_name FROM payment p LEFT JOIN campaign c ON p.campaign_id = c.id WHERE p.created_at >= ? AND p.created_at <= ?
    ) campaigns ON 1=1 -- Multiply period series for every campaign
    LEFT JOIN payment p
    ON p.campaign_id <=> campaigns.campaign_id
    AND DATE(p.created_at) >= period_series.start_date
    AND DATE(p.created_at) <= period_series.end_date
    AND p.status = ?
    AND p.currency_code = ?
    GROUP BY campaigns.campaign_id, start_date
    ORDER BY campaigns.campaign_id, start_date
    EOT;

    /**
     * @return array<PaymentSummaryEntryDto>
     */
    public function query(DateTime $startDate, DateTime $endDate, string $groupByPeriod = 'month'): array {
        $rsm = new DtoResultSetMapping(PaymentSummaryEntryDto::class);
        $query = $this->em->createNativeQuery($this->getSeriesCTE($groupByPeriod, $startDate, $endDate) . ' ' . self::SQL, $rsm->getMapping());
        $query->setParameter(1, 'EUR');
        $query->setParameter(2, $startDate->format('Y-m-d'));
        $query->setParameter(3, $endDate->format('Y-m-d'));
        $query->setParameter(4, Status::Captured);
        $query->setParameter(5, 'EUR');

        return $query->getResult();
    }

    private function getSeriesCTE(string $groupByPeriod, DateTime $startDate, DateTime $endDate): string {
        $start = $startDate->format('Y-m-d');
        $end = $endDate->format('Y-m-d');
        
        switch ($groupByPeriod) {
            case 'day':
                return sprintf(self::SQL_DAY_SERIES_CTE, $start, $end);
            case 'week':
                return sprintf(self::SQL_WEEK_SERIES_CTE, $start, $start, $end, $end, $start, $start, $end, $end);
            case 'month':
                return sprintf(self::SQL_MONTH_SERIES_CTE, $start, $start, $end, $start, $start, $end, $end);
            default:
                throw new RuntimeException(sprintf('Unsupported group by period \'%s\'', $groupByPeriod));
        }
    }
}
