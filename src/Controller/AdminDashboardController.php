<?php

namespace ErgoSarapu\DonationBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use ErgoSarapu\DonationBundle\Controller\Admin\PaymentCrudController;
use ErgoSarapu\DonationBundle\Entity\Campaign;
use ErgoSarapu\DonationBundle\Entity\Payment;
use Symfony\Component\HttpFoundation\Response;

class AdminDashboardController extends AbstractDashboardController
{

    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(PaymentCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('App');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Payments', 'fa fa-report', Payment::class);
        yield MenuItem::linkToCrud('Campaigns', 'fa fa-report', Campaign::class);
    }
}
