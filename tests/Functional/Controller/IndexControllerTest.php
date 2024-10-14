<?php

namespace ErgoSarapu\DonationBundle\Tests\Functional\Controller;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManager;
use ErgoSarapu\DonationBundle\DonationBundle;
use ErgoSarapu\DonationBundle\Entity\Campaign;
use Gedmo\Timestampable\TimestampableListener;
use Payum\Bundle\PayumBundle\PayumBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Chartjs\ChartjsBundle;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use SymfonyCasts\Bundle\ResetPassword\SymfonyCastsResetPasswordBundle;

class IndexControllerTest extends TestCase
{

    private ?Kernel $kernel;

    private ?EntityManager $entityManager;

    protected function setUp(): void {
        $this->kernel = new DonationBundleControllerKernel();
        $this->kernel->boot();
        $this->entityManager = $this->kernel->getContainer()->get('doctrine')->getManager();
        $this->entityManager->getEventManager()->addEventSubscriber(new TimestampableListener());
    }

    protected function tearDown(): void {
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testIndex() : void {
        $campaign = (new Campaign())
            ->setName('Campaign Name')
            ->setDefault(true)
            ->setPublicTitle('Public Title')
            ->setPublicId(100);
        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        $client = new KernelBrowser($this->kernel);
        $crawler = $client->request('GET', '/');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSame('Public Title', $crawler->filterXPath('//head/title')->text());
    }

    public function testIndexNoDefaultCampaign() : void {
        $campaign = (new Campaign())
            ->setName('Campaign Name')
            ->setDefault(false)
            ->setPublicTitle('Public Title')
            ->setPublicId(100);
        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        $client = new KernelBrowser($this->kernel);
        $client->request('GET', '/');
        $this->assertSame(500, $client->getResponse()->getStatusCode());
    }

    public function testIndexMultipleDefaultCampaign() : void {
        $this->entityManager->persist((new Campaign())
            ->setName('Campaign 1')
            ->setDefault(true)
            ->setPublicTitle('Public Title 1')
            ->setPublicId(100));
        
        $this->entityManager->persist((new Campaign())
            ->setName('Campaign 2')
            ->setDefault(true)
            ->setPublicTitle('Public Title 2')
            ->setPublicId(101));
        $this->entityManager->flush();

        $client = new KernelBrowser($this->kernel);
        $client->request('GET', '/');
        $this->assertSame(500, $client->getResponse()->getStatusCode());
    }
}

class DonationBundleControllerKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct()
    {
        parent::__construct($_ENV['APP_ENV'], $_ENV['APP_DEBUG']);
    }

    public function registerBundles(): iterable {
        return [
            new DonationBundle(),
            new FrameworkBundle(),
            new PayumBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new LiveComponentBundle(),
            new StimulusBundle(),
            new DoctrineBundle(),
            new DAMADoctrineTestBundle(),
            new SymfonyCastsResetPasswordBundle(),
            new ChartjsBundle(),
        ];
    }
    
    protected function configureRoutes(RoutingConfigurator $routes): void{
        $routes->import(__DIR__.'/../../../config/routes.xml')->prefix('/');
        $routes->import(__DIR__.'/../../../config/routes_campaign.xml')->prefix('/');
        $routes->import('@LiveComponentBundle/config/routes.php');
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
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
                ]
            ]);

        $loader->load(__DIR__.'/../Fixtures/config/full.yaml', 'yaml');

        $builder->loadFromExtension('framework', [
            'secret' => 'FOO',
            'asset_mapper' => [
                'paths' => ['assets/']
            ],
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
        ]);
    }

    public function getCacheDir(): string
    {
        // Ensure each kernel instance generates its own cache allowing different test cases do not reuse the cache
        return parent::getCacheDir().'/'.spl_object_hash($this);
    }
}