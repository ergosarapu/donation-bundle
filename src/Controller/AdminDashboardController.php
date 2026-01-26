<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller;

use DateInterval;
use DateTime;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Campaign as CampaignReadModel;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Donation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\RecurringPlan as RecurringPlanReadModel;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment as PaymentReadModel;
use ErgoSarapu\DonationBundle\Dto\SummaryFilterDto;
use ErgoSarapu\DonationBundle\Entity\Campaign;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Subscription;
use ErgoSarapu\DonationBundle\Enum\Period;
use Symfony\Component\HttpFoundation\Response;

class AdminDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $chartFilter = new SummaryFilterDto();
        $chartFilter->setGroupByPeriod(Period::Month);
        $endDate = new DateTime();
        $startDate = clone $endDate;
        $startDate->sub(new DateInterval('P1Y'));

        $chartFilter->setStartDate($startDate);
        $chartFilter->setEndDate($endDate);

        return $this->render('@Donation/admin/dashboard.html.twig', [
            'filter' => $chartFilter,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Donation App');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Payments', 'fa fa-money-bill', Payment::class);

        yield MenuItem::linkToCrud('Campaigns', 'fa fa-rocket', Campaign::class);
        yield MenuItem::linkToCrud('Subscriptions', 'fa fa-arrow-rotate-right', Subscription::class);

        yield MenuItem::section('Payments');
        yield MenuItem::linkToCrud('Payments', 'fa fa-money-bill', PaymentReadModel::class);

        yield MenuItem::section('Donations');
        yield MenuItem::linkToCrud('Campaigns', 'fa fa-rocket', CampaignReadModel::class);
        yield MenuItem::linkToCrud('Donations', 'fa fa-hand-holding-heart', Donation::class);
        yield MenuItem::linkToCrud('Recurring', 'fa fa-arrow-rotate-right', RecurringPlanReadModel::class);
    }
}
