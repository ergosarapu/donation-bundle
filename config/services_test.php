<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return function (ContainerConfigurator $container) {
    $services = $container->services();

    // Register Behat contexts for autowiring
    $services->set(ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\Behat\PaymentsContext::class)
        ->class(ErgoSarapu\DonationBundle\Tests\Acceptance\Payments\Behat\PaymentsContext::class)
        ->public(true)->autowire();
    $services->set(ErgoSarapu\DonationBundle\Tests\Acceptance\Donations\Behat\DonationsContext::class)
        ->class(ErgoSarapu\DonationBundle\Tests\Acceptance\Donations\Behat\DonationsContext::class)
        ->public(true)->autowire();
    $services->set(ErgoSarapu\DonationBundle\Tests\Acceptance\Identities\Behat\IdentitiesContext::class)
        ->class(ErgoSarapu\DonationBundle\Tests\Acceptance\Identities\Behat\IdentitiesContext::class)
        ->public(true)->autowire();

    // For message metadata testing ...

    // First Command Handler
    $services->set('donation_bundle.test.infrastructure.first_command_handler', ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers\FirstCommandHandler::class)
        ->autoconfigure(true)
        ->arg(0, (new Definition(\Patchlevel\EventSourcing\Repository\Repository::class))
            ->setFactory([new Reference(\Patchlevel\EventSourcing\Repository\RepositoryManager::class), 'get'])
            ->addArgument(ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers\TestAggregate::class))
        ->tag('messenger.message_handler', ['bus' => 'command.bus']);

    // First Event Handler
    $services->set('donation_bundle.test.infrastructure.first_event_handler', ErgoSarapu\DonationBundle\Tests\Integration\Messenger\Helpers\FirstEventHandler::class)
        ->autoconfigure(true)
        ->autowire(true)
        ->tag('messenger.message_handler', ['bus' => 'event.bus']);
};
