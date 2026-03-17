<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\GetPaymentsCount;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\CampaignController;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\DonationController;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\PaymentsController;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\PendingPaymentImportsController;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\RecurringPlanController;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\ReviewPaymentImportsController;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class AdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus
    ) {
    }

    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(PaymentsController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Donation App');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Donations');
        yield MenuItem::linkTo(DonationController::class, 'Donations', 'fa fa-hand-holding-heart');
        yield MenuItem::linkTo(RecurringPlanController::class, 'Recurring Plans', 'fa fa-arrow-rotate-right');
        yield MenuItem::linkTo(CampaignController::class, 'Campaigns', 'fa fa-rocket');


        yield MenuItem::section('Payments');
        yield MenuItem::linkTo(PaymentsController::class, 'Payments', 'fa fa-money-bill');
        $pendingImportsCount = $this->queryBus->ask(new GetPaymentsCount(PaymentImportStatus::Pending));
        $reviewImportsCount = $this->queryBus->ask(new GetPaymentsCount(PaymentImportStatus::Review));
        if ($pendingImportsCount > 0) {
            yield MenuItem::linkTo(PendingPaymentImportsController::class, 'Pending Imports', 'fa fa-clock')
                ->setBadge((string) $pendingImportsCount);
        }
        if ($reviewImportsCount > 0) {
            yield MenuItem::linkTo(ReviewPaymentImportsController::class, 'Review Imports', 'fa fa-check-circle')
                ->setBadge((string) $reviewImportsCount);
        }
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addAssetMapperEntry('app')
            ->addCssFile('@ergosarapu/donation-bundle/admin.css');
    }
}
