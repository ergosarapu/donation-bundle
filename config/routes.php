<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    // Import PayumBundle routes
    // Note: PayumBundle provides XML routes, but since XmlFileLoader is not available in Symfony 7.4+,
    // we need to manually define the essential Payum routes that are required for payment processing

    // Essential PayumBundle routes for token-based payment flows
    // Note: We cannot use $routes->import("@PayumBundle/Resources/config/routing/all.xml")
    // because XmlFileLoader is not available in Symfony 7.4+
    // These are the essential routes from PayumBundle that we need for payment processing
    $routes->add('payum_capture_do', '/payment/capture/{payum_token}')
        ->controller('payum.action.capture_controller');

    $routes->add('payum_notify_do', '/payment/notify/{payum_token}')
        ->controller('payum.action.notify_controller');

    $routes->add('payum_authorize_do', '/payment/authorize/{payum_token}')
        ->controller('payum.action.authorize_controller');

    $routes->add('payum_refund_do', '/payment/refund/{payum_token}')
        ->controller('payum.action.refund_controller');

    // Application routes
    $routes->add('donation_dashboard', '/admin')
        ->controller('ErgoSarapu\DonationBundle\Controller\AdminDashboardController::index');

    $routes->add('live_component_admin', '/admin/_components/{_live_component}/{_live_action}')
        ->defaults(['_live_action' => 'get']);

    $routes->add('donation_admin_login', '/login')
        ->controller('donation_bundle.controller.admin.login_controller');

    $routes->add('donation_redirect', '/donate/{donationId}')
        ->controller('donation_bundle.controller.redirect_controller')
        ->requirements(['donationId' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}']);

    $routes->add('donation_payment_done', '/done')
        ->controller('donation_bundle.controller.payment_done_controller');

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
