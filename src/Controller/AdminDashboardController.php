<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\CampaignController;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\DonationController;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\PaymentController;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\PaymentImportController;
use ErgoSarapu\DonationBundle\Controller\Admin\CQRS\RecurringPlanController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class AdminDashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(PaymentController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Donation App');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Payments');
        yield MenuItem::linkTo(PaymentController::class, 'Payments', 'fa fa-money-bill');
        yield MenuItem::linkTo(PaymentImportController::class, 'Import', 'fa fa-file-import');

        yield MenuItem::section('Donations');
        yield MenuItem::linkTo(CampaignController::class, 'Campaigns', 'fa fa-rocket');
        yield MenuItem::linkTo(DonationController::class, 'Donations', 'fa fa-hand-holding-heart');
        yield MenuItem::linkTo(RecurringPlanController::class, 'Recurring Plans', 'fa fa-arrow-rotate-right');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addAssetMapperEntry('app')
            ->addCssFile('@ergosarapu/donation-bundle/admin.css');
    }

}
