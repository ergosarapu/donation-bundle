<?php

namespace ErgoSarapu\DonationBundle\Form;

use ErgoSarapu\DonationBundle\Dto\SummaryFilterDto;
use ErgoSarapu\DonationBundle\Enum\Period;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentTimeSeriesSummaryChartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options):void
    {
        $builder->add('startDate', DateType::class, ['widget' => 'single_text']);
        $builder->add('endDate', DateType::class, ['widget' => 'single_text']);
        $builder->add('groupByPeriod', EnumType::class, ['class' => Period::class, 'expanded' => true]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SummaryFilterDto::class
        ]);
    }
}
