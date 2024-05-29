<?php

use ErgoSarapu\DonationBundle\Controller\AdminDashboardController;
use ErgoSarapu\DonationBundle\Controller\IndexController;
use ErgoSarapu\DonationBundle\Controller\PaymentDoneController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->import('@PayumBundle/Resources/config/routing/all.xml');

    $routes->add('dashboard', '/admin')->controller([AdminDashboardController::class, 'index']);

    $routes->add('index', '/')->controller(IndexController::class);
    $routes->add('payment_done', '/done')->controller(PaymentDoneController::class);
};