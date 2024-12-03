<?php

namespace ErgoSarapu\DonationBundle\Twig\Components;

use ErgoSarapu\DonationBundle\Dto\Query\PaymentSummaryEntryDto;
use ErgoSarapu\DonationBundle\Dto\SummaryFilterDto;
use ErgoSarapu\DonationBundle\Form\PaymentSummaryChartType;
use ErgoSarapu\DonationBundle\Query\PaymentSummaryQueryInterface;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\MoneyFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(route: 'live_component_admin')]
final class PaymentSummaryChartForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?SummaryFilterDto $filter = null;

    private MoneyFormatter $formatter;
    
    public function __construct(private ?ChartBuilderInterface $chartBuilder, private PaymentSummaryQueryInterface $query)
    {
        $this->formatter = new DecimalMoneyFormatter(new ISOCurrencies());
    }
    
    protected function instantiateForm(): FormInterface {
        return $this->createForm(PaymentSummaryChartType::class, $this->filter);
    }

    public function getChart(): Chart {
        $result = $this->query->query($this->filter->getStartDate(), $this->filter->getEndDate(), $this->filter->getGroupByPeriod()->value);
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $this->getTimeSeriesPeriods($result),
            'datasets' => $this->getTimeSeriesDatasets($result),
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 100,
                    'stacked' => true,
                ],
            ],
        ]);

        return $chart;
    }

    /**
     * @param array<PaymentSummaryEntryDto> $seriesData
     * @return array<string>
     */
    private function getTimeSeriesPeriods(array $seriesData): array {
        $periods = array_unique(array_map(function($e){
            return $e->periodKey;
        }, $seriesData));
        return $periods;
    }

    /**
     * @param array<PaymentSummaryEntryDto> $seriesData
     * @return array<array<string, mixed>>
     */
    private function getTimeSeriesDatasets(array $seriesData): array {
        $datasets = [];
        foreach($seriesData as $item) {
            $dataset = end($datasets);
            if ($dataset === false || $dataset['campaign_id'] !== $item->campaignId){
                // Create new dataset
                $dataset = [
                    'campaign_id' => $item->campaignId,
                    'label' => $item->campaignName,
                    'data' => [],
                    'fill' => 'origin',
                    'tension' => 0.3,
                ];
                array_push($datasets, $dataset);
            }
            $data = $dataset['data'];
            $amount = new Money($item->amount, new Currency($item->currency));
            $data[] = $this->formatter->format($amount);
            $datasets[count($datasets) - 1]['data'] = $data;
        }
        return $datasets;
    }
}
