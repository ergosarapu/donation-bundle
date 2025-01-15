<?php

namespace ErgoSarapu\DonationBundle\Tests\Integration;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use ErgoSarapu\DonationBundle\DonationBundle;
use Payum\Bundle\PayumBundle\PayumBundle;
use Payum\Offline\OfflineGatewayFactory;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Zenstruck\Messenger\Test\ZenstruckMessengerTestBundle;

class IntegrationTestingKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable {
        return [
            new DonationBundle(),
            new FrameworkBundle(),
            new DoctrineBundle(),
            new DAMADoctrineTestBundle(),
            new PayumBundle(),
            new ZenstruckMessengerTestBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $builder->loadFromExtension('framework', [
            'test' => true,
            'messenger' => [
                'transports' => [
                    'async' => 'test://'
                ],
            ]
        ]);

        $builder->loadFromExtension('doctrine', [
            'dbal' => [
                'url' => $_ENV['DATABASE_URL'],
                'use_savepoints' => true,
            ],
            'orm' => [
                'auto_mapping' => true,
                'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
            ]
        ]);

        $builder->loadFromExtension('payum', 
        ['security' => 
            ['token_storage' => 
                ['Payum\Core\Model\Token' => 
                    ['filesystem' => [
                        'storage_dir' => __DIR__.'/../../../var/cache/gateways',
                        'id_property' => 'hash',
                    ]]]
                    ],
        'gateways' => [
            'my_gateway' => [
                'factory' => 'offline',
                'username' => 'test',
                'password' => 'test',
                'sandbox' => true,
            ]
        ]
        ]);
    }
    
    public function getCacheDir(): string
    {
        // Ensure each kernel instance generates its own cache allowing different test cases do not reuse the cache
        return parent::getCacheDir().'/'.spl_object_hash($this);
    }
}
