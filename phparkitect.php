<?php

declare(strict_types=1);

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\Implement;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\EventSourcing\Attribute\Id;
use Patchlevel\Hydrator\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Attribute\PersonalData;
use Patchlevel\Hydrator\Normalizer\ObjectNormalizer;
use Ramsey\Uuid\Uuid;

return static function (Config $config): void {
    $classSet = ClassSet::fromDir(__DIR__.'/src');

    $rules = [];

    // Domain layer
    $domainDepsToExclude = [
        BasicAggregateRoot::class,
        Aggregate::class,
        Apply::class,
        Id::class,
        ObjectNormalizer::class,
        PersonalData::class,
        DataSubjectId::class,
        Event::class,
        AggregateRootId::class,
        Uuid::class,
        'ErgoSarapu\DonationBundle\SharedKernel'
    ];

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\BCDonations\Domain'))
        ->should(new NotHaveDependencyOutsideNamespace('*\BCDonations\Domain\*', $domainDepsToExclude, true))
        ->because('we want to keep the domain layer independent')
    ;

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\BCPayments\Domain'))
        ->should(new NotHaveDependencyOutsideNamespace('*\BCPayments\Domain\*', $domainDepsToExclude, true))
        ->because('we want to keep the domain layer independent')
    ;

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\BCIdentities\Domain'))
        ->should(new NotHaveDependencyOutsideNamespace('*\BCIdentities\Domain\*', $domainDepsToExclude, true))
        ->because('we want to keep the domain layer independent')
    ;

    // Application layer
    $applicationDepsToExclude = [
        BasicAggregateRoot::class,
        'ErgoSarapu\DonationBundle\SharedKernel',
        'ErgoSarapu\DonationBundle\SharedApplication',
        'ErgoSarapu\DonationBundle\IntegrationContracts',
        'Psr\Clock'
    ];

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces(
            '*\BCDonations\Application\*',
        ))
        ->should(new NotHaveDependencyOutsideNamespace(
            '*\BCDonations\Application\*',
            array_merge(
                $applicationDepsToExclude,
                ['ErgoSarapu\DonationBundle\BCDonations\Domain']
            ),
            true
        ))
        ->because('we want to keep the application layer clean from infrastructure details')
    ;

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces(
            '*\BCPayments\Application\*',
        ))
        ->should(new NotHaveDependencyOutsideNamespace(
            '*\BCPayments\Application\*',
            array_merge(
                $applicationDepsToExclude,
                ['ErgoSarapu\DonationBundle\BCPayments\Domain']
            ),
            true
        ))
        ->because('we want to keep the application layer clean from infrastructure details');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces(
            '*\BCIdentities\Application\*',
        ))
        ->should(new NotHaveDependencyOutsideNamespace(
            '*\BCIdentities\Application\*',
            array_merge(
                $applicationDepsToExclude,
                ['ErgoSarapu\DonationBundle\BCIdentities\Domain']
            ),
            true
        ))
        ->because('we want to keep the application layer clean from infrastructure details');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\BCDonations\Application\Command\*'))
        ->should(new Implement(CommandInterface::class))
        ->because('we want all commands to implement the marker interface');
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\BCPayments\Application\Command\*'))
        ->should(new Implement(CommandInterface::class))
        ->because('we want all commands to implement the marker interface');
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\BCIdentities\Application\Command\*'))
        ->should(new Implement(CommandInterface::class))
        ->because('we want all commands to implement the marker interface');

    // Integration contracts
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\IntegrationContracts\Donations\Command'))
        ->should(new Implement(IntegrationCommandInterface::class))
        ->because('we want all integration commands to implement the marker interface');
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\IntegrationContracts\Payments\Command'))
        ->should(new Implement(IntegrationCommandInterface::class))
        ->because('we want all integration commands to implement the marker interface');
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\IntegrationContracts\Identities\Command'))
        ->should(new Implement(IntegrationCommandInterface::class))
        ->because('we want all integration commands to implement the marker interface');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\IntegrationContracts\Donations\Event'))
        ->should(new Implement(IntegrationEventInterface::class))
        ->because('we want all integration events to implement the marker interface');
    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\IntegrationContracts\Payments\Event'))
        ->should(new Implement(IntegrationEventInterface::class))
        ->because('we want all integration events to implement the marker interface');

    $rules[] = Rule::allClasses()
        ->that(new ResideInOneOfTheseNamespaces('*\IntegrationContracts'))
        ->should(new NotHaveDependencyOutsideNamespace('*\IntegrationContracts\*', ['ErgoSarapu\DonationBundle\SharedKernel'], true))
        ->because('we want to keep the integration contracts without dependencies');

    $config->add($classSet, ...$rules);
};
