<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {

    $routes->import('.', \EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteLoader::ROUTE_LOADER_TYPE);

    $routes->add('live_component_admin', '/admin/_components/{_live_component}/{_live_action}')
        ->defaults(['_live_action' => 'get']);

    $routes->add('donation_admin_login', '/login')
        ->controller('donation_bundle.controller.admin.login_controller');

    $routes->add('donation_redirect', '/donate/{donationId}')
        ->controller('donation_bundle.controller.redirect_controller')
        ->requirements(['donationId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}']);

    $routes->add('donation_command_status', '/api/command-status')
        ->controller('donation_bundle.controller.command_status_controller')
        ->methods(['GET']);

    $routes->add('donation_thank_you', '/thank-you')
        ->controller('Symfony\Bundle\FrameworkBundle\Controller\TemplateController')
        ->defaults(['template' => '@Donation/thankyou.html.twig']);

    // Password reset routes
    $routes->add('donation_forgot_password_request', '/reset-password')
        ->controller('donation_bundle.controller.reset_password_controller::request');

    $routes->add('donation_reset_password', '/reset-password/reset/{token?}')
        ->controller('donation_bundle.controller.reset_password_controller::reset');

    $routes->add('donation_check_email', '/reset-password/check-email')
        ->controller('donation_bundle.controller.reset_password_controller::checkEmail');
};
