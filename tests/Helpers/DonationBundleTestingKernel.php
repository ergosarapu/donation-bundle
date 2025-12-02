<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Tests\Helpers;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use ErgoSarapu\DonationBundle\DonationBundle;
use ErgoSarapu\DonationBundle\Entity\Payment;
use Patchlevel\EventSourcingBundle\PatchlevelEventSourcingBundle;
use Payum\Bundle\PayumBundle\PayumBundle;
use Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Zenstruck\Messenger\Test\ZenstruckMessengerTestBundle;

class DonationBundleTestingKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @param array<string, mixed>|string|null $bundleConfig
     */
    public function __construct(string $environment, bool $debug, private array|string|null $bundleConfig = null, private readonly ?string $cachePath = null)
    {
        $this->bundleConfig = $bundleConfig;
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        return [
            new DonationBundle(),
            new FrameworkBundle(),
            new DoctrineBundle(),
            new DAMADoctrineTestBundle(),
            new PayumBundle(),
            new ZenstruckMessengerTestBundle(),
            new PatchlevelEventSourcingBundle(),
            new StofDoctrineExtensionsBundle(),
        ];
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../../config/routes.xml')->prefix('/');
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        // Load test-specific services
        $loader->load(__DIR__.'/../../config/services_test.php');

        if ($this->bundleConfig !== null) {
            if (is_string($this->bundleConfig)) {
                $loader->load($this->bundleConfig);
            } else {
                /** @var array<string, mixed> $config */
                $config = $this->bundleConfig;
                $builder->loadFromExtension('donation', $config);
            }
        }

        $builder->loadFromExtension('framework', [
            'test' => true,
            'messenger' => [
                'transports' => [
                    'delayed_command' => [
                        'retry_strategy' => [
                            'max_retries' => 0,
                        ]
                    ],
                ],
            ]
        ]);

        $builder->loadFromExtension('payum', [
            'gateways' => [
                'my_gateway' => [
                    'factory' => 'offline',
                    'username' => 'test',
                    'password' => 'test',
                    'sandbox' => true,
                ]
            ],
            'storages' => [
                Payment::class => [
                    'doctrine' => 'orm'
                ]
            ]
        ]);

        $builder->loadFromExtension('patchlevel_event_sourcing', [
            'subscription' => [
                'throw_on_error' => ['enabled' => true],
                // If new domain events are generated then they are not part of current subscription engine and don't
                // get processed. Enable catching up these when testing.
                // https://event-sourcing-bundle.patchlevel.io/latest/configuration/#catch-up
                'catch_up' => [
                    'enabled' => true,
                ],
                'run_after_aggregate_save' => [
                    'enabled' => true,
                ],
            ]
        ]);

        $builder->loadFromExtension('stof_doctrine_extensions', [
            'orm' => [
                'default' => [
                    'timestampable' => true,
                ],
            ],
        ]);
    }

    public function getCacheDir(): string
    {
        if ($this->cachePath) {
            // In case cachePath is explicitly provided, use that
            $path = $this->cachePath;
        } else {
            // Ensure each kernel instance generates its own cache based on the configuration
            // This allows different configurations to have separate caches
            $path = md5(serialize($this->bundleConfig));
        }
        return parent::getCacheDir().'/'.$path;
    }
}
