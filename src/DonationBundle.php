<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle;

use DateInterval;
use ErgoSarapu\DonationBundle\DependencyInjection\Compiler\RegisterQueryCompilerPass;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationCommandInterface;
use ErgoSarapu\DonationBundle\IntegrationContracts\IntegrationEventInterface;
use ErgoSarapu\DonationBundle\Repository\ResetPasswordRequestRepository;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandInterface;
use ErgoSarapu\DonationBundle\SharedKernel\Event\DomainEventInterface;
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
                    'event' => 'sync://',
                    'command' => 'sync://',
                    'delayed' => [
                        'dsn' => 'doctrine://messenger',
                        'options' => [
                            'queue_name' => 'delayed',
                        ],
                    ],
                ],
                'routing' => [
                    CommandInterface::class => 'command',
                    IntegrationCommandInterface::class => 'command',
                    DomainEventInterface::class => 'event',
                    IntegrationEventInterface::class => 'event',
                ],
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
            ],
            'events' => [
                __DIR__ . '/BCDonations/Domain',
                __DIR__ . '/BCPayments/Domain',
            ],
            'headers' => [
                __DIR__ . '/SharedInfrastructure/Messenger/Stamp',
            ],
        ]);

        $this->prependAssetMapperConfig($builder);
    }

    public function build(ContainerBuilder $builder): void
    {
        parent::build($builder);
        $builder->addCompilerPass(new RegisterQueryCompilerPass());
    }
}
