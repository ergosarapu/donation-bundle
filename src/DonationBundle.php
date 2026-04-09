<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle;

use DateInterval;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\AcceptDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ArchiveCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CompleteRecurringDonationAttempt;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CompleteRecurringPlanRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateCampaign;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\CreateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\FailDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\FailRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateRecurringPlanRenewal;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\MarkRecurringPlanAsFailing;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\ReActivateRecurringPlan;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignDonationDescription;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignName;
use ErgoSarapu\DonationBundle\BCDonations\Application\Command\UpdateCampaignPublicTitle;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignArchived;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignDonationDescriptionUpdated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignNameUpdated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignPublicTitleUpdated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationAccepted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanActivated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanCanceled;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanCreated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanExpired;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailed;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanFailing;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanInitiated;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalCompleted;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringPlanRenewalInitiated;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\CreateIdentity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\PresentClaimEvidence;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Command\ResolveClaim;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimCreated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimInReview;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForEmail;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForIban;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForNationalIdCode;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForPersonName;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForRawName;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimResolved;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\ClaimMerged;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityCreated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityEmailAdded;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityIbanAdded;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityNationalIdCodeChanged;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityPersonNameChanged;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityRawNameAdded;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\AcceptPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CapturePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\CreatePendingPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\GenerateRedirectCaptureUrl;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ImportPaymentsFromFile;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\InitiatePayment;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsFailed;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MarkPaymentAsRefunded;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\MovePaymentImportToReview;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ReconcilePaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\RejectPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\UpdatePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\UsePaymentMethod;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentAuthorized;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCanceled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCaptured;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentDidNotSucceed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentFailed;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportAccepted;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportInReview;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportPending;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportReconciled;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportRejected;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentInitiated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUnusable;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUsePermitted;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentMethodUseRejected;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentRedirectUrlSetUp;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReleasedForGatewayCall;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentReservedForGatewayCall;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentSucceeded;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UnusablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\UsablePaymentMethodCreated;
use ErgoSarapu\DonationBundle\DependencyInjection\Compiler\RegisterQueryCompilerPass;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\InitiateDonationIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\ReActivateRecurringPlanIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Identities\Event\ClaimPresentedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Command\InitiatePaymentIntegrationCommand;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentDidNotSucceedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentMethodUnusableIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\PaymentSucceededIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UnusablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\IntegrationContracts\Payments\Event\UsablePaymentMethodCreatedIntegrationEvent;
use ErgoSarapu\DonationBundle\Repository\ResetPasswordRequestRepository;
use Exception;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;

class DonationBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                // Form
                ->arrayNode('form')
                    ->info('Form configuration.')
                    ->children()
                        ->append($this->addCurrencyNode())
                    ->end()
                ->end()

                ->arrayNode('gateways')
                    // ->isRequired()
                    ->info('Gateways configuration')
                    ->validate()
                        ->ifEmpty()
                        ->thenInvalid('Configure at least one gateway')
                    ->end()
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('group')
                                ->isRequired()->info('The label of the gateway group shown to the end user')
                            ->end()
                            ->scalarNode('label')
                                ->isRequired()->cannotBeEmpty()->info('The label of payment gateway shown to the end user')
                            ->end()
                            ->scalarNode('image')
                                ->cannotBeEmpty()->info('The icon of payment gateway shown to the end user')
                            ->end()
                            ->arrayNode('frequencies')
                                ->info('Available recurring frequencies, null for one-time (default) or date interval string, e.g. P1M for monthly, P1W for weekly, etc')
                                ->defaultValue([null])
                                ->scalarPrototype()->end()
                                ->validate()
                                    ->ifTrue(function (array $values): bool {
                                        foreach ($values as $value) {
                                            if ($value === null) {
                                                // Null value marks one-time payment frequency, allow this
                                                continue;
                                            }
                                            // Try to construct date interval, this throws in case of bad interval string
                                            try {
                                                new DateInterval($value);
                                            } catch (Exception $e) {
                                                throw new Exception(sprintf('Invalid frequency date interval format (%s)', $value), previous: $e);
                                            }
                                        }
                                        return false;
                                    })
                                    ->thenInvalid('Not valid frequencies')
                                ->end()
                            ->end()
                            ->scalarNode('country')
                                ->info('Marks gateway as country specific so user can quickly filter gateways with same country. Must be valid alpha-2 country code.')
                                ->validate()->ifTrue(function (string $value): bool {
                                    return !Countries::exists($value);
                                })
                                ->thenInvalid('Not a valid alpha-2 country code')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addCurrencyNode(): NodeDefinition
    {
        $notPositiveInt = function ($value) {
            return !is_numeric($value) || (int)$value != $value || !((int)$value > 0);
        };

        $treeBuilder = new TreeBuilder('currencies');
        return $treeBuilder->getRootNode()
        ->isRequired()
        ->useAttributeAsKey('currency_code')
        ->validate()
            ->ifTrue(function (array $values): bool {
                foreach ($values as $key => $value) {
                    if (!Currencies::exists($key)) {
                        return true;
                    }
                }
                return false;
            })
            ->thenInvalid('Not a valid currency code.')
        ->end()
        ->arrayPrototype()
        ->info("Currency code")
            ->children()
                ->scalarNode('amount_default')
                    ->info('Default amount pre-filled for the end user.')
                    ->isRequired()
                    ->validate()
                        ->ifTrue($notPositiveInt)
                        ->thenInvalid('Value must be valid integer greater than 0.')
                    ->end()
                ->end()
                ->arrayNode('amount_choices')
                    ->scalarPrototype()
                        ->validate()
                            ->ifTrue($notPositiveInt)
                            ->thenInvalid('Each value must be valid integer greater than 0.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/../config/services.php');

        $builder->getDefinition('donation_bundle.form.form_options_provider')
            ->setArgument(0, $config['gateways'])
            ->setArgument(1, $config['form']['currencies'] ?? []);
    }

    private function prependAssetMapperConfig(ContainerBuilder $builder): void
    {
        if (!$this->isAssetMapperAvailable($builder)) {
            return;
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets/dist' => '@ergosarapu/donation-bundle',
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $builder): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $builder->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'] . '/Resources/config/asset_mapper.php');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('twig', [
            'paths' => [
                __DIR__ . '/../templates/bundles/EasyAdminBundle' => 'EasyAdmin',
            ],
        ]);

        $builder->prependExtensionConfig('twig_component', [
            'defaults' => [
                'ErgoSarapu\DonationBundle\Twig\Components\\' => [
                    'template_directory' =>  '@Donation/components/',
                ]
            ]
        ]);

        $builder->prependExtensionConfig('symfonycasts_reset_password', [
            'request_password_repository' => ResetPasswordRequestRepository::class
        ]);

        $builder->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    'datetime_immutable' => \ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\Type\UTCDateTimeImmutableType::class,
                ],
                'default_connection' => 'default',
                'connections' => [
                    'default' => [
                        'url' => '%env(DATABASE_URL)%', // Used by the application (legacy)
                    ],
                    // Different connection for projections to enable write access to read models
                    'projection' => [
                        'url' => '%env(DATABASE_URL)%',
                    ],
                    // Different connection for messenger transport
                    'messenger' => [
                        'url' => '%env(DATABASE_URL)%',
                    ]
                ],
            ],
            'orm' => [
                'entity_managers' => [
                    'default' => [
                        'connection' => 'default',
                        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
                        'mappings' => [
                            'DonationBundle' => [
                                'type' => 'attribute',
                                'dir' => __DIR__ . '/Entity',
                                'prefix' => 'ErgoSarapu\DonationBundle\Entity',
                                'alias' => 'DonationBundle',
                                'is_bundle' => false,
                            ],
                            'DonationsReadModel' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../config/doctrine/donations',
                                'prefix' => 'ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model',
                                'alias' => 'DonationsReadModel',
                                'is_bundle' => false,
                            ],
                            'PaymentsReadModel' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../config/doctrine/payments',
                                'prefix' => 'ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model',
                                'alias' => 'PaymentsReadModel',
                                'is_bundle' => false,
                            ],
                            'IdentitiesReadModel' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../config/doctrine/identities',
                                'prefix' => 'ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model',
                                'alias' => 'IdentitiesReadModel',
                                'is_bundle' => false,
                            ],
                            'SharedReadModel' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../config/doctrine/shared',
                                'prefix' => 'ErgoSarapu\DonationBundle\SharedApplication\Query\Model',
                                'alias' => 'SharedReadModel',
                                'is_bundle' => false,
                            ]
                        ],
                    ],
                    'projection' => [
                        'connection' => 'projection',
                        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
                        'auto_mapping' => false,
                        'mappings' => [
                            'DonationsReadModel' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../config/doctrine/donations',
                                'prefix' => 'ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model',
                                'alias' => 'DonationsReadModel',
                                'is_bundle' => false,
                            ],
                            'PaymentsReadModel' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../config/doctrine/payments',
                                'prefix' => 'ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model',
                                'alias' => 'PaymentsReadModel',
                                'is_bundle' => false,
                            ],
                            'IdentitiesReadModel' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../config/doctrine/identities',
                                'prefix' => 'ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model',
                                'alias' => 'IdentitiesReadModel',
                                'is_bundle' => false,
                            ],
                            'SharedReadModel' => [
                                'type' => 'xml',
                                'dir' => __DIR__ . '/../config/doctrine/shared',
                                'prefix' => 'ErgoSarapu\DonationBundle\SharedApplication\Query\Model',
                                'alias' => 'SharedReadModel',
                                'is_bundle' => false,
                            ]
                        ],
                    ],
                ],
            ]
        ]);

        $builder->prependExtensionConfig('framework', [
            'messenger' => [
                'default_bus' => 'message.bus',
                'buses' => [
                    'message.bus' => null,
                    'command.bus' => [
                        'middleware' => [
                            'donation_bundle.infrastructure.messenger.message_metadata_middleware',
                        ],
                    ],
                    'query.bus' => null,
                    'event.bus' => [
                        'default_middleware' => [
                            'allow_no_handlers' => true,
                        ],
                        'middleware' => [
                            'donation_bundle.infrastructure.messenger.message_metadata_middleware',
                        ],
                    ],
                ],
                'transports' => [
                    'command' => 'sync://',
                    'integration_command' => 'sync://',
                    'event_low_priority' => 'sync://',
                    'event' => 'sync://',
                    'integration_event' => 'sync://',
                    'delayed' => [
                        'dsn' => 'doctrine://messenger',
                        'options' => [
                            'queue_name' => 'delayed',
                        ],
                    ],
                ],
                'routing' => $this->getMessengerRouting(),
            ]
        ]);

        $builder->prependExtensionConfig('patchlevel_event_sourcing', [
            'connection' => [
                'service' => 'doctrine.dbal.default_connection'
            ],
            'store' => [
                'type' => 'dbal_stream',
                'merge_orm_schema' => true,
            ],
            'aggregates' => [
                __DIR__ . '/BCDonations/Domain',
                __DIR__ . '/BCPayments/Domain',
                __DIR__ . '/BCIdentities/Domain',
            ],
            'events' => [
                __DIR__ . '/BCDonations/Domain',
                __DIR__ . '/BCPayments/Domain',
                __DIR__ . '/BCIdentities/Domain',
            ],
            'headers' => [
                __DIR__ . '/SharedInfrastructure/Messenger/Stamp',
            ],
            'cryptography' => [
                'enabled' => true,
                'algorithm' => 'aes-256-cbc',
                'use_encrypted_field_name' => true,
            ],
        ]);

        $this->prependAssetMapperConfig($builder);
    }

    private function getMessengerRouting(): array
    {
        return [
            // Commands
            ...array_fill_keys([
                // BCDonations
                AcceptDonation::class,
                ActivateCampaign::class,
                ActivateRecurringPlan::class,
                ArchiveCampaign::class,
                CompleteRecurringDonationAttempt::class,
                CompleteRecurringPlanRenewal::class,
                CreateCampaign::class,
                CreateDonation::class,
                CreateRecurringPlan::class,
                FailDonation::class,
                FailRecurringPlan::class,
                InitiateDonation::class,
                InitiateRecurringPlan::class,
                InitiateRecurringPlanRenewal::class,
                MarkRecurringPlanAsFailing::class,
                ReActivateRecurringPlan::class,
                UpdateCampaignDonationDescription::class,
                UpdateCampaignName::class,
                UpdateCampaignPublicTitle::class,
                // BCIdentities
                CreateIdentity::class,
                PresentClaimEvidence::class,
                ResolveClaim::class,
                // BCPayments
                AcceptPaymentImport::class,
                CapturePayment::class,
                CreatePayment::class,
                CreatePaymentMethod::class,
                CreatePendingPaymentImport::class,
                GenerateRedirectCaptureUrl::class,
                ImportPaymentsFromFile::class,
                InitiatePayment::class,
                MarkPaymentAsAuthorized::class,
                MarkPaymentAsCanceled::class,
                MarkPaymentAsCaptured::class,
                MarkPaymentAsFailed::class,
                MarkPaymentAsRefunded::class,
                MovePaymentImportToReview::class,
                ReconcilePaymentImport::class,
                RejectPaymentImport::class,
                UpdatePaymentMethod::class,
                UsePaymentMethod::class,
            ], 'command'),
            // Integration Commands
            ...array_fill_keys([
                // BCDonations
                InitiateDonationIntegrationCommand::class,
                ReActivateRecurringPlanIntegrationCommand::class,
                // BCPayments
                InitiatePaymentIntegrationCommand::class,
            ], 'integration_command'),
            // Integration Events
            ...array_fill_keys([
                // BCIdentities
                ClaimPresentedIntegrationEvent::class,
                // BCPayments
                PaymentDidNotSucceedIntegrationEvent::class,
                PaymentMethodUnusableIntegrationEvent::class,
                PaymentSucceededIntegrationEvent::class,
                UnusablePaymentMethodCreatedIntegrationEvent::class,
                UsablePaymentMethodCreatedIntegrationEvent::class,
            ], 'integration_event'),
            // Domain Events
            // Payment import pending goes to low priority queue to avoid blocking during bulk imports
            PaymentImportPending::class => 'event_low_priority',
            // All other domain events go to standard event transport
            ...array_fill_keys([
                // BCDonations
                CampaignActivated::class,
                CampaignArchived::class,
                CampaignCreated::class,
                CampaignDonationDescriptionUpdated::class,
                CampaignNameUpdated::class,
                CampaignPublicTitleUpdated::class,
                DonationAccepted::class,
                DonationCreated::class,
                DonationFailed::class,
                DonationInitiated::class,
                RecurringPlanActivated::class,
                RecurringPlanCanceled::class,
                RecurringPlanCreated::class,
                RecurringPlanExpired::class,
                RecurringPlanFailed::class,
                RecurringPlanFailing::class,
                RecurringPlanInitiated::class,
                RecurringPlanRenewalCompleted::class,
                RecurringPlanRenewalInitiated::class,
                // BCIdentities
                ClaimCreated::class,
                ClaimInReview::class,
                ClaimPresentedForEmail::class,
                ClaimPresentedForIban::class,
                ClaimPresentedForNationalIdCode::class,
                ClaimPresentedForPersonName::class,
                ClaimPresentedForRawName::class,
                ClaimResolved::class,
                ClaimMerged::class,
                IdentityCreated::class,
                IdentityEmailAdded::class,
                IdentityIbanAdded::class,
                IdentityRawNameAdded::class,
                IdentityNationalIdCodeChanged::class,
                IdentityPersonNameChanged::class,
                // BCPayments
                PaymentAuthorized::class,
                PaymentCanceled::class,
                PaymentCaptured::class,
                PaymentCreated::class,
                PaymentDidNotSucceed::class,
                PaymentFailed::class,
                PaymentImportAccepted::class,
                PaymentImportInReview::class,
                PaymentImportReconciled::class,
                PaymentImportRejected::class,
                PaymentInitiated::class,
                PaymentMethodUnusable::class,
                PaymentMethodUsePermitted::class,
                PaymentMethodUseRejected::class,
                PaymentRedirectUrlSetUp::class,
                PaymentReleasedForGatewayCall::class,
                PaymentReservedForGatewayCall::class,
                PaymentSucceeded::class,
                UnusablePaymentMethodCreated::class,
                UsablePaymentMethodCreated::class,
            ], 'event'),
        ];
    }

    public function build(ContainerBuilder $builder): void
    {
        parent::build($builder);
        $builder->addCompilerPass(new RegisterQueryCompilerPass());
    }
}
