<?php

use ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\EntityWriteInterceptor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

return function (ContainerConfigurator $container) {
    $services = $container->services();
    $services->defaults()
        ->autowire(false)
        ->autoconfigure(false);

    // Controller
    $services->set('donation_bundle.controller.admin.login_controller', \ErgoSarapu\DonationBundle\Controller\Admin\LoginController::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set(\ErgoSarapu\DonationBundle\Controller\AdminDashboardController::class)
        ->autoconfigure(true)
        ->autowire(true);

    // ***************************************
    // *** Admin CRUD Controllers (legacy) ***
    // ***************************************

    $services->set(\ErgoSarapu\DonationBundle\Controller\Admin\PaymentCrudController::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set(\ErgoSarapu\DonationBundle\Controller\Admin\CampaignCrudController::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set(\ErgoSarapu\DonationBundle\Controller\Admin\SubscriptionCrudController::class)
        ->autoconfigure(true)
        ->autowire(true);

    // ******************************
    // *** Admin CQRS Controllers ***
    // ******************************

    $services->set(\ErgoSarapu\DonationBundle\Controller\Admin\CQRS\PaymentCQRSController::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set(\ErgoSarapu\DonationBundle\Controller\Admin\CQRS\DonationCQRSController::class)
        ->autoconfigure(true)
        ->autowire(true);

    $services->set('donation_bundle.controller.index_controller', \ErgoSarapu\DonationBundle\Controller\IndexController::class)
        ->autoconfigure(true)
        ->autowire(true)
    ->call('setContainer', [new Reference(\Psr\Container\ContainerInterface::class)]);

    $services->set('donation_bundle.controller.redirect_controller', \ErgoSarapu\DonationBundle\Controller\RedirectController::class)
        ->autoconfigure(true)
        ->autowire(true)
    ->call('setContainer', [new Reference(\Psr\Container\ContainerInterface::class)]);

    // Form
    $services->set('donation_bundle.twig.components.donation_form', \ErgoSarapu\DonationBundle\Twig\Components\DonationForm::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->call('setContainer', [new Reference(\Psr\Container\ContainerInterface::class)]);
    $services->set('donation_bundle.twig.components.donation_form_step_1', \ErgoSarapu\DonationBundle\Twig\Components\DonationFormStep1::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->call('setContainer', [new Reference(\Psr\Container\ContainerInterface::class)]);
    $services->set('donation_bundle.twig.components.donation_form_step_2', \ErgoSarapu\DonationBundle\Twig\Components\DonationFormStep2::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->call('setContainer', [new Reference(\Psr\Container\ContainerInterface::class)]);
    $services->set('donation_bundle.twig.components.donation_form_step_3', \ErgoSarapu\DonationBundle\Twig\Components\DonationFormStep3::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->call('setContainer', [new Reference(\Psr\Container\ContainerInterface::class)]);
    $services->set('donation_bundle.twig.components.payment_summary_chart_form', \ErgoSarapu\DonationBundle\Twig\Components\PaymentSummaryChartForm::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->call('setContainer', [new Reference(\Psr\Container\ContainerInterface::class)]);
    $services->set('donation_bundle.controller.payment_done_controller', \ErgoSarapu\DonationBundle\Controller\PaymentDoneController::class)
        ->autoconfigure(true)
        ->call('setContainer', [new Reference(\Psr\Container\ContainerInterface::class)]);
    $services->set('donation_bundle.twig.components.flag', \ErgoSarapu\DonationBundle\Twig\Components\Flag::class)
        ->autoconfigure(true)
        ->autowire(true);

    // Password Reset
    $services->set('donation_bundle.controller.reset_password_controller', \ErgoSarapu\DonationBundle\Controller\Admin\ResetPasswordController::class)
        ->autoconfigure(true)
        ->autowire(true);

    // Config/Options
    $services->set('donation_bundle.form.form_options_provider', \ErgoSarapu\DonationBundle\Form\FormOptionsProvider::class)
        ->public()
        ->autowire(true);
    $services->alias(\ErgoSarapu\DonationBundle\Form\FormOptionsProvider::class, 'donation_bundle.form.form_options_provider');

    // Payum
    $services->set('donation_bundle.payum.payment_status_extension', \ErgoSarapu\DonationBundle\Payum\UpdatePaymentStatusExtension::class)
        ->autoconfigure(true)
        ->public()
        ->autowire(true)
        ->tag('payum.extension', ['all' => true]);
    $services->set('donation_bundle.payum.activate_subscription_extension', \ErgoSarapu\DonationBundle\Payum\ActivateSubscriptionExtension::class)
        ->autoconfigure(true)
        ->public()
        ->tag('payum.extension', ['all' => true]);
    $services->set('donation_bundle.application.payment.port.payment_gateway', \ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter\PayumPaymentGateway::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->alias(\ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentGatewayInterface::class, 'donation_bundle.application.payment.port.payment_gateway');

    // Subscription
    $services->set('donation_bundle.subscription.subscription_manager', \ErgoSarapu\DonationBundle\Subscription\SubscriptionManager::class)
        ->autowire(true)
        ->public();
    $services->alias(\ErgoSarapu\DonationBundle\Subscription\SubscriptionManager::class, 'donation_bundle.subscription.subscription_manager');

    // Command
    $services->set('donation_bundle.add_user_command', \ErgoSarapu\DonationBundle\Command\AddUserCommand::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.subscription_process_command', \ErgoSarapu\DonationBundle\Command\SubscriptionProcessCommand::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.user_validator', \ErgoSarapu\DonationBundle\Utils\UserValidator::class);
    $services->alias(\ErgoSarapu\DonationBundle\Utils\UserValidator::class, 'donation_bundle.user_validator');

    // Repository
    $services->set(\ErgoSarapu\DonationBundle\Repository\UserRepository::class)
        ->autowire(true)
        ->tag('doctrine.repository_service');
    $services->set(\ErgoSarapu\DonationBundle\Repository\CampaignRepository::class)
        ->autowire(true)
        ->tag('doctrine.repository_service');
    $services->set(\ErgoSarapu\DonationBundle\Repository\PaymentRepository::class)
        ->autowire(true)
        ->tag('doctrine.repository_service');
    $services->set(\ErgoSarapu\DonationBundle\Repository\SubscriptionRepository::class)
        ->autowire(true)
        ->tag('doctrine.repository_service');
    $services->set(\ErgoSarapu\DonationBundle\Repository\ResetPasswordRequestRepository::class)
        ->autowire(true)
        ->tag('doctrine.repository_service');

    // *************************************
    // *** Aggregate Repository Adapters ***
    // *************************************

    $services->set('donation_bundle.infrastructure.payment.patchlevel_payment_repository', \ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter\PatchlevelPaymentRepository::class)
        ->arg(0, (new Definition(\Patchlevel\EventSourcing\Repository\Repository::class))
            ->setFactory([new Reference(\Patchlevel\EventSourcing\Repository\RepositoryManager::class), 'get'])
            ->addArgument(\ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\Payment::class));
    $services->alias(\ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentRepositoryInterface::class, 'donation_bundle.infrastructure.payment.patchlevel_payment_repository');

    $services->set('donation_bundle.infrastructure.donations.repository.adapter.patchlevel_donation_repository', \ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Adapter\PatchlevelDonationRepository::class)
        ->arg(0, (new Definition(\Patchlevel\EventSourcing\Repository\Repository::class))
            ->setFactory([new Reference(\Patchlevel\EventSourcing\Repository\RepositoryManager::class), 'get'])
            ->addArgument(\ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation::class));
    $services->alias(\ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface::class, 'donation_bundle.infrastructure.donations.repository.adapter.patchlevel_donation_repository');

    // ************************
    // *** Command Handlers ***
    // ************************

    // All services implementing the CommandHandlerInterface will be registered on the command.bus bus
    $services->instanceof(\ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface::class)
        ->tag('messenger.message_handler', ['bus' => 'command.bus']);
    
    // Donations
    $services->set('donation_bundle.donations.application.donation.command_handler.initiate_donation', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\InitiateDonationHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.mark_donation_as_accepted', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\MarkDonationAsAcceptedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.mark_donation_as_failed', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\MarkDonationAsFailedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // Payments 
    $services->set('donation_bundle.payments.application.payment.command_handler.initiate_payment', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\InitiatePaymentHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.mark_payment_as_captured', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\MarkPaymentAsCapturedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.mark_payment_as_failed', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\MarkPaymentAsFailedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.mark_payment_as_pending', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\MarkPaymentAsPendingHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.mark_payment_as_canceled', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\MarkPaymentAsCanceledHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.mark_payment_as_authorized', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\MarkPaymentAsAuthorizedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.mark_payment_as_refunded', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\MarkPaymentAsRefundedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // **********************
    // *** Query Handlers ***
    // **********************

    // All services implementing the QueryHandlerInterface will be registered on the query.bus bus
    $services->instanceof(\ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface::class)
        ->tag('messenger.message_handler', ['bus' => 'query.bus']);

    // Donations
    $services->set('donation_bundle.application.donations.query_handler.get_donation', \ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler\GetDonationHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.query_handler.get_pending_donation', \ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler\GetPendingDonationHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // Payments
    $services->set('donation_bundle.application.payments.query_handler.get_payment', \ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler\GetPaymentHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.query_handler.get_pending_payment', \ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler\GetPendingPaymentHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // **********************
    // *** Event Handlers ***
    // **********************

    // All services implementing the EventHandlerInterface will be registered on the event.bus bus
    $services->instanceof(\ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\EventHandlerInterface::class)
        ->tag('messenger.message_handler', ['bus' => 'event.bus']);
    
    // Donations
    $services->set('donation_bundle.application.donations.domain_event_handler.donation_initiated', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain\DonationInitiatedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.integration_event_handler.payment_succeeded', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration\PaymentSucceededHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.integration_event_handler.payment_did_not_succeed', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration\PaymentDidNotSucceedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // Payments
    $services->set('donation_bundle.application.payments.domain_event_handler.payment_succeeded', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentSucceededHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.domain_event_handler.payment_did_not_succeed', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentDidNotSucceedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // *************
    // *** Buses ***
    // *************

    // Command Bus
    $services->set('donation_bundle.infrastructure.bus.command_bus', \ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus\SymfonyMessengerCommandBus::class)
        ->autowire(true)
        ->autoconfigure(true);
    $services->alias(\ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface::class, 'donation_bundle.infrastructure.bus.command_bus')
        ->public();

    // Query Bus
    $services->set('donation_bundle.infrastructure.bus.query_bus', \ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus\SymfonyMessengerQueryBus::class)
        ->autowire(true)
        ->autoconfigure(true);
    $services->alias(\ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface::class, 'donation_bundle.infrastructure.bus.query_bus')
        ->public();

    // Event Bus
    $services->set('donation_bundle.infrastructure.bus.event_bus', \ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\Bus\SymfonyMessengerEventBus::class)
        ->autowire(true)
        ->autoconfigure(true);
    $services->alias(\ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\EventBusInterface::class, 'donation_bundle.infrastructure.bus.event_bus')
        ->public();

    // **********************************************
    // *** Projectors and Projection Repositories ***
    // **********************************************

    // Donations
    $services->set('donation_bundle.infrastructure.projector.donation', \ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Projection\DonationProjector::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->tag('event_sourcing.subscriber');
    $services->alias(\ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\DonationProjectionRepositoryInterface::class, 'donation_bundle.infrastructure.projector.donation');

    // Payments

    $services->set('donation_bundle.infrastructure.projector.payment', \ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Projection\PaymentProjector::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->tag('event_sourcing.subscriber');
    $services->alias(\ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface::class, 'donation_bundle.infrastructure.projector.payment');

    // ******************
    // *** Processors ***
    // ******************

    $services->set('donation_bundle.infrastructure.subscriber.patchlevel_all_events_processor', \ErgoSarapu\DonationBundle\SharedInfrastructure\Processor\PatchlevelAllDomainEventsProcessor::class)
        ->autoconfigure(true)
        ->autowire(true);

    // ************
    // *** Misc ***
    // ************

    $services->set(EntityWriteInterceptor::class)
        ->tag('doctrine.event_listener', [
            'event' => 'prePersist',
            # you can also restrict listeners to a specific Doctrine connection
            'connection' => 'default',
        ])
        ->tag('doctrine.event_listener', [
            'event' => 'preUpdate',
            # you can also restrict listeners to a specific Doctrine connection
            'connection' => 'default',
        ])
        ->tag('doctrine.event_listener', [
            'event' => 'preRemove',
            # you can also restrict listeners to a specific Doctrine connection
            'connection' => 'default',
        ])
    ;

    // Legacy message handler
    $services->set('donation_bundle.message_handler.capture_payment')->class(ErgoSarapu\DonationBundle\MessageHandler\CapturePaymentHandler::class)->autoconfigure()->autowire();
};
