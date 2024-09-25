<?php

namespace ErgoSarapu\DonationBundle\Controller;

use DateInterval;
use DateTime;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use ErgoSarapu\DonationBundle\Dto\SummaryFilterDto;
use ErgoSarapu\DonationBundle\Entity\Campaign;
use ErgoSarapu\DonationBundle\Entity\Payment;
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
        yield MenuItem::linkToCrud('Payments', 'fa fa-report', Payment::class);
        yield MenuItem::linkToCrud('Campaigns', 'fa fa-report', Campaign::class);
    }
}
