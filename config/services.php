<?php

declare(strict_types=1);

use ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\EntityWriteInterceptor;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

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

    // ******************************
    // *** Admin CQRS Controllers ***
    // ******************************

    $services->set(\ErgoSarapu\DonationBundle\Controller\Admin\CQRS\PaymentController::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set(\ErgoSarapu\DonationBundle\Controller\Admin\CQRS\PaymentImportController::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set(\ErgoSarapu\DonationBundle\Controller\Admin\CQRS\DonationController::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set(\ErgoSarapu\DonationBundle\Controller\Admin\CQRS\CampaignController::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set(\ErgoSarapu\DonationBundle\Controller\Admin\CQRS\RecurringPlanController::class)
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

    // ***********************
    // *** API Controllers ***
    // ***********************

    $services->set('donation_bundle.controller.command_status_controller', \ErgoSarapu\DonationBundle\Controller\CommandStatusController::class)
        ->autoconfigure(true)
        ->autowire(true);

    // ***********************
    // *** GUI Controllers ***
    // ***********************

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

    // Command
    $services->set('donation_bundle.add_user_command', \ErgoSarapu\DonationBundle\Command\AddUserCommand::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.user_validator', \ErgoSarapu\DonationBundle\Utils\UserValidator::class);
    $services->alias(\ErgoSarapu\DonationBundle\Utils\UserValidator::class, 'donation_bundle.user_validator');

    // Repository
    $services->set(\ErgoSarapu\DonationBundle\Repository\UserRepository::class)
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

    $services->set('donation_bundle.infrastructure.payment.patchlevel_payment_method_repository', \ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter\PatchlevelPaymentMethodRepository::class)
        ->arg(0, (new Definition(\Patchlevel\EventSourcing\Repository\Repository::class))
            ->setFactory([new Reference(\Patchlevel\EventSourcing\Repository\RepositoryManager::class), 'get'])
            ->addArgument(\ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethod::class));
    $services->alias(\ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMethodRepositoryInterface::class, 'donation_bundle.infrastructure.payment.patchlevel_payment_method_repository');

    $services->set('donation_bundle.infrastructure.donations.repository.adapter.patchlevel_donation_repository', \ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Adapter\PatchlevelDonationRepository::class)
        ->arg(0, (new Definition(\Patchlevel\EventSourcing\Repository\Repository::class))
            ->setFactory([new Reference(\Patchlevel\EventSourcing\Repository\RepositoryManager::class), 'get'])
            ->addArgument(\ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\Donation::class));
    $services->alias(\ErgoSarapu\DonationBundle\BCDonations\Application\Port\DonationRepositoryInterface::class, 'donation_bundle.infrastructure.donations.repository.adapter.patchlevel_donation_repository');

    $services->set('donation_bundle.infrastructure.donations.repository.adapter.patchlevel_recurring_plan_repository', \ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Adapter\PatchlevelRecurringPlanRepository::class)
        ->arg(0, (new Definition(\Patchlevel\EventSourcing\Repository\Repository::class))
            ->setFactory([new Reference(\Patchlevel\EventSourcing\Repository\RepositoryManager::class), 'get'])
            ->addArgument(\ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlan::class));
    $services->alias(\ErgoSarapu\DonationBundle\BCDonations\Application\Port\RecurringPlanRepositoryInterface::class, 'donation_bundle.infrastructure.donations.repository.adapter.patchlevel_recurring_plan_repository');

    $services->set('donation_bundle.infrastructure.donations.repository.adapter.patchlevel_campaign_repository', \ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Adapter\PatchlevelCampaignRepository::class)
        ->arg(0, (new Definition(\Patchlevel\EventSourcing\Repository\Repository::class))
            ->setFactory([new Reference(\Patchlevel\EventSourcing\Repository\RepositoryManager::class), 'get'])
            ->addArgument(\ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\Campaign::class));
    $services->alias(\ErgoSarapu\DonationBundle\BCDonations\Application\Port\CampaignRepositoryInterface::class, 'donation_bundle.infrastructure.donations.repository.adapter.patchlevel_campaign_repository');

    // ***********************
    // *** Payment Imports ***
    // ***********************

    $services->set('donation_bundle.infrastructure.payment.camt_import_decoder', \ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter\CamtImportDecoder::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->alias(\ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentImportDecoderInterface::class, 'donation_bundle.infrastructure.payment.camt_import_decoder');

    $services->set('donation_bundle.infrastructure.payment.projection_payments_matcher', \ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter\ProjectionPaymentsMatcher::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->alias(\ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentsMatcherInterface::class, 'donation_bundle.infrastructure.payment.projection_payments_matcher');

    $services->set('donation_bundle.infrastructure.payment.pending_payment_import_listener', \ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Listener\PendingPaymentImportListener::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->tag('doctrine.orm.entity_listener', ['event' => 'postLoad', 'entity' => \ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment::class]);

    // ************************
    // *** Command Handlers ***
    // ************************

    // Register command handlers on command bus
    $services->instanceof(\ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\CommandHandlerInterface::class)
        ->tag('messenger.message_handler', ['bus' => 'command.bus']);

    // Donations
    $services->set('donation_bundle.donations.application.donation.command_handler.initiate_donation', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\InitiateDonationHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.create_donation', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\CreateDonationHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.initiate_donation_integration', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\Integration\InitiateDonationHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.mark_donation_as_accepted', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\AcceptDonationHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.mark_donation_as_failed', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\FailDonationHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.initiate_recurring_plan', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\InitiateRecurringPlanHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.create_recurring_plan', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\CreateRecurringPlanHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.activate_recurring_plan', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\ActivateRecurringPlanHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.fail_recurring_plan', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\FailRecurringPlanHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.complete_recurring_donation_attempt', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\CompleteRecurringDonationAttemptHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.initiate_recurring_plan_renewal', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\InitiateRecurringPlanRenewalHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.reactivate_recurring_plan', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\ReActivateRecurringPlanHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.donation.command_handler.reactivate_recurring_plan_integration', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\Integration\ReActivateRecurringPlanHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // Campaigns
    $services->set('donation_bundle.donations.application.campaign.command_handler.create_campaign', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\CreateCampaignHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.campaign.command_handler.update_campaign_name', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\UpdateCampaignNameHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.campaign.command_handler.update_campaign_public_title', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\UpdateCampaignPublicTitleHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.campaign.command_handler.update_campaign_donation_description', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\UpdateCampaignDonationDescriptionHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.campaign.command_handler.activate_campaign', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\ActivateCampaignHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.donations.application.campaign.command_handler.archive_campaign', \ErgoSarapu\DonationBundle\BCDonations\Application\CommandHandler\ArchiveCampaignHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // Payments
    $services->set('donation_bundle.payments.application.payment.command_handler.initiate_payment', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\InitiatePaymentHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.initiate_payment_integration', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\Integration\InitiatePaymentHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.generate_redirect_capture_url', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\GenerateRedirectCaptureURLHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.mark_payment_as_captured', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\MarkPaymentAsCapturedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.mark_payment_as_failed', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\MarkPaymentAsFailedHandler::class)
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
    $services->set('donation_bundle.payments.application.payment.command_handler.use_payment_method', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\UsePaymentMethodHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.create_payment', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\CreatePaymentHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.store_payment_method', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\CreatePaymentMethodHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.update_payment_method', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\UpdatePaymentMethodHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.capture_payment', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\CapturePaymentHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.accept_payment_import', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\AcceptPaymentImportHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.reject_payment_import', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\RejectPaymentImportHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.reconcile_payment_import', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\ReconcilePaymentImportHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.create_pending_payment_import', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\CreatePendingPaymentImportHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.payments.application.payment.command_handler.import_payments_from_file', \ErgoSarapu\DonationBundle\BCPayments\Application\CommandHandler\ImportPaymentsFromFileHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // **********************
    // *** Query Handlers ***
    // **********************

    // Register query handlers on query bus
    $services->instanceof(\ErgoSarapu\DonationBundle\SharedApplication\Port\Handler\QueryHandlerInterface::class)
        ->tag('messenger.message_handler', ['bus' => 'query.bus']);

    // Donations
    $services->set('donation_bundle.application.donations.query_handler.get_donation', \ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler\GetDonationHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.query_handler.get_donations', \ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler\GetDonationsHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.query_handler.get_pending_donation', \ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler\GetPendingDonationHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.query_handler.get_recurring_plan', \ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler\GetRecurringPlanHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.query_handler.get_recurring_plan_by_payment_method', \ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler\GetRecurringPlanByPaymentMethodHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.query_handler.get_pending_recurring_plan', \ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler\GetPendingRecurringPlanHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // Campaigns
    $services->set('donation_bundle.application.campaigns.query_handler.get_campaign', \ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler\GetCampaignHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.campaigns.query_handler.get_active_campaigns', \ErgoSarapu\DonationBundle\BCDonations\Application\Query\Handler\GetActiveCampaignsHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // Payments
    $services->set('donation_bundle.application.payments.query_handler.get_payment', \ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler\GetPaymentHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.query_handler.get_pending_payment', \ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler\GetPendingPaymentHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.query_handler.get_matching_payments', \ErgoSarapu\DonationBundle\BCPayments\Application\Query\Handler\GetMatchingPaymentsHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // Shared
    $services->set('donation_bundle.application.shared.query_handler.get_command_statuses', \ErgoSarapu\DonationBundle\SharedApplication\Query\Handler\GetCommandStatusesHandler::class)
        ->autoconfigure(true)
        ->autowire(true);

    // **********************
    // *** Event Handlers ***
    // **********************

    // Register event handlers on event bus
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
    $services->set('donation_bundle.application.donations.integration_event_handler.payment_method_usable', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration\UsablePaymentMethodCreatedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.integration_event_handler.payment_method_unusable', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration\UnusablePaymentMethodCreatedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.integration_event_handler.payment_method_became_unusable', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Integration\PaymentMethodUnusableHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.domain_event_handler.recurring_plan_initiated', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain\RecurringPlanInitiatedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.domain_event_handler.recurring_plan_activated', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain\RecurringPlanActivatedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.domain_event_handler.donation_accepted', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain\DonationAcceptedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.domain_event_handler.donation_failed', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain\DonationFailedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.domain_event_handler.recurring_plan_renewal_initiated', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain\RecurringPlanRenewalInitiatedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.donations.domain_event_handler.recurring_plan_renewal_completed', \ErgoSarapu\DonationBundle\BCDonations\Application\EventHandler\Domain\RecurringPlanRenewalCompletedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);


    // Payments
    $services->set('donation_bundle.application.payments.domain_event_handler.payment_initiated', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentInitiatedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.domain_event_handler.payment_succeeded', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentSucceededHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.domain_event_handler.payment_did_not_succeed', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentDidNotSucceedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.domain_event_handler.payment_method_use_permitted', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentMethodUsePermittedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.domain_event_handler.payment_method_use_rejected', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentMethodUseRejectedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.domain_event_handler.payment_captured', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentCapturedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.domain_event_handler.payment_failed', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentFailedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.domain_event_handler.usable_payment_method_created', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\UsablePaymentMethodCreatedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.domain_event_handler.unusable_payment_method_created', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\UnusablePaymentMethodCreatedHandler::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->set('donation_bundle.application.payments.domain_event_handler.payment_method_unusable', \ErgoSarapu\DonationBundle\BCPayments\Application\EventHandler\Domain\PaymentMethodUnusableHandler::class)
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

    $services->set('donation_bundle.infrastructure.projector.recurring_plan', \ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Projection\RecurringPlanProjector::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->tag('event_sourcing.subscriber');
    $services->alias(\ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\RecurringPlanProjectionRepositoryInterface::class, 'donation_bundle.infrastructure.projector.recurring_plan');

    $services->set('donation_bundle.infrastructure.projector.campaign', \ErgoSarapu\DonationBundle\BCDonations\Infrastructure\Projection\CampaignProjector::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->tag('event_sourcing.subscriber');
    $services->alias(\ErgoSarapu\DonationBundle\BCDonations\Application\Query\Port\CampaignProjectionRepositoryInterface::class, 'donation_bundle.infrastructure.projector.campaign');

    // Payments

    $services->set('donation_bundle.infrastructure.projector.payment', \ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Projection\PaymentProjector::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->tag('event_sourcing.subscriber');
    $services->alias(\ErgoSarapu\DonationBundle\BCPayments\Application\Query\Port\PaymentProjectionRepositoryInterface::class, 'donation_bundle.infrastructure.projector.payment');

    // Shared
    $services->set('donation_bundle.infrastructure.shared.command_status_projection_repository', \ErgoSarapu\DonationBundle\SharedInfrastructure\Adapter\CommandStatusProjectionRepository::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->alias(\ErgoSarapu\DonationBundle\SharedApplication\Query\Port\CommandStatusProjectionRepositoryInterface::class, 'donation_bundle.infrastructure.shared.command_status_projection_repository');

    // ******************
    // *** Processors ***
    // ******************

    $services->set('donation_bundle.infrastructure.subscriber.patchlevel_all_events_processor', \ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\PatchlevelAllDomainEventsProcessor::class)
        ->autoconfigure(true)
        ->autowire(true);

    // ************
    // *** Misc ***
    // ************

    // Message Decorator
    $services->set('donation_bundle.infrastructure.message.patchlevel_message_decorator', \ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\MessageMetadataDecorator::class)
        ->autoconfigure(true)
        ->autowire(true);
    $services->alias(\Patchlevel\EventSourcing\Repository\MessageDecorator\MessageDecorator::class, 'donation_bundle.infrastructure.message.patchlevel_message_decorator');

    // Messenger Context and Middleware
    $services->set('donation_bundle.infrastructure.messenger.message_context', \ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\MessageContext::class);
    $services->alias(\ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\MessageContext::class, 'donation_bundle.infrastructure.messenger.message_context');

    $services->set('donation_bundle.infrastructure.messenger.message_metadata_middleware', \ErgoSarapu\DonationBundle\SharedInfrastructure\Messenger\Middleware\MessageMetadataMiddleware::class)
        ->arg(0, new Reference('donation_bundle.infrastructure.messenger.message_context'))
        ->autoconfigure(false);

    // Default allowed classes for write (bundle internal - not meant to be overridden)
    $container->parameters()->set('donation_bundle.entity_write_interceptor.default_classes', [
        \ErgoSarapu\DonationBundle\Entity\User::class,
        \ErgoSarapu\DonationBundle\Entity\ResetPasswordRequest::class,
    ]);

    // Additional allowed classes (host app can set this to add more classes)
    // Example in host app's config/services.yaml:
    //   parameters:
    //     donation_bundle.entity_write_interceptor.additional_classes:
    //       - App\Entity\CustomEntity
    $container->parameters()->set('donation_bundle.entity_write_interceptor.additional_classes', []);

    // EntityWriteInterceptor - uses factory to merge default and additional allowed classes
    // Host apps can add more allowed classes via 'donation_bundle.entity_write_interceptor.additional_classes'
    // Example in host app's config/services.yaml:
    //   parameters:
    //     donation_bundle.entity_write_interceptor.additional_classes:
    //       - App\Entity\CustomEntity
    $services->set(EntityWriteInterceptor::class)
        ->factory([EntityWriteInterceptor::class, 'create'])
        ->args([
            '%donation_bundle.entity_write_interceptor.default_classes%',
            '%donation_bundle.entity_write_interceptor.additional_classes%',
        ])
        ->tag('doctrine.event_listener', [
            'event' => 'prePersist',
            'connection' => 'default',
        ])
        ->tag('doctrine.event_listener', [
            'event' => 'preUpdate',
            'connection' => 'default',
        ])
        ->tag('doctrine.event_listener', [
            'event' => 'preRemove',
            'connection' => 'default',
        ])
    ;
};
