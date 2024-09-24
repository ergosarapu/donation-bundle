<?php

namespace ErgoSarapu\DonationBundle\Tests\Integration\Query;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManager;
use ErgoSarapu\DonationBundle\DonationBundle;
use ErgoSarapu\DonationBundle\Entity\Campaign;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\Query\PaymentSummaryQueryInterface;
use Gedmo\Timestampable\TimestampableListener;
use Money\Money;
use Payum\Bundle\PayumBundle\PayumBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;


class PaymentSummaryQueryTest extends TestCase
{

    private ?Kernel $kernel;

    private ?EntityManager $entityManager;

    private PaymentSummaryQueryInterface $query;
    
    protected function setUp(): void {
        $this->kernel = new DatabaseTestingKernel();
        $this->kernel->boot();
        $this->entityManager = $this->kernel->getContainer()->get('doctrine')->getManager();
        $this->entityManager->getEventManager()->addEventSubscriber(new TimestampableListener());
        $this->query = $this->kernel->getContainer()->get(PaymentSummaryQueryInterface::class);
    }

    protected function tearDown(): void {
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testQuery():void{
        
        // Payments with no campaign
        $this->createPayment(100, Status::Captured, '2024-09-01');
        $this->createPayment(100, Status::Captured, '2024-09-30');
        $this->createPayment(100, Status::Captured, '2024-12-31'); // Outside the query date range
        $this->createPayment(100, Status::Created, '2024-12-31'); // Outside the query date range

        // Payments with campaign
        $campaignA = $this->createCampaign('Campaign A', 1, 'Public A');
        $this->createPayment(100, Status::Captured, '2024-05-01', $campaignA);
        $this->createPayment(100, Status::Captured, '2024-06-01', $campaignA);

        $campaignB = $this->createCampaign('Campaign B', 2, 'Public B');
        $this->createPayment(100, Status::Captured, '2024-06-01', $campaignB);
        $this->createPayment(100, Status::Captured, '2024-07-01', $campaignB);

        $this->entityManager->flush();
        
        // Query
        $result = $this->query->query(new DateTime('2024-01-01'), new DateTime('2024-12-15'));

        // Assert no campaign
        $this->assertStrEq('null, null, 2024-01-01, 2024-01-31, 2024-01, 0, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-02-01, 2024-02-29, 2024-02, 0, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-03-01, 2024-03-31, 2024-03, 0, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-04-01, 2024-04-30, 2024-04, 0, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-05-01, 2024-05-31, 2024-05, 0, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-06-01, 2024-06-30, 2024-06, 0, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-07-01, 2024-07-31, 2024-07, 0, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-08-01, 2024-08-31, 2024-08, 0, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-09-01, 2024-09-30, 2024-09, 200, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-10-01, 2024-10-31, 2024-10, 0, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-11-01, 2024-11-30, 2024-11, 0, EUR', array_shift($result));
        $this->assertStrEq('null, null, 2024-12-01, 2024-12-15, 2024-12, 0, EUR', array_shift($result));

        // Assert campaign A
        $cid = $campaignA->getId();
        $this->assertStrEq($cid.', Campaign A, 2024-01-01, 2024-01-31, 2024-01, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-02-01, 2024-02-29, 2024-02, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-03-01, 2024-03-31, 2024-03, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-04-01, 2024-04-30, 2024-04, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-05-01, 2024-05-31, 2024-05, 100, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-06-01, 2024-06-30, 2024-06, 100, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-07-01, 2024-07-31, 2024-07, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-08-01, 2024-08-31, 2024-08, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-09-01, 2024-09-30, 2024-09, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-10-01, 2024-10-31, 2024-10, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-11-01, 2024-11-30, 2024-11, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign A, 2024-12-01, 2024-12-15, 2024-12, 0, EUR', array_shift($result));

        // Assert campaign B
        $cid = $campaignB->getId();
        $this->assertStrEq($cid.', Campaign B, 2024-01-01, 2024-01-31, 2024-01, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-02-01, 2024-02-29, 2024-02, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-03-01, 2024-03-31, 2024-03, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-04-01, 2024-04-30, 2024-04, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-05-01, 2024-05-31, 2024-05, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-06-01, 2024-06-30, 2024-06, 100, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-07-01, 2024-07-31, 2024-07, 100, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-08-01, 2024-08-31, 2024-08, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-09-01, 2024-09-30, 2024-09, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-10-01, 2024-10-31, 2024-10, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-11-01, 2024-11-30, 2024-11, 0, EUR', array_shift($result));
        $this->assertStrEq($cid.', Campaign B, 2024-12-01, 2024-12-15, 2024-12, 0, EUR', array_shift($result));
    }

    private function createPayment(int $totalAmount, Status $status, string $createdAt, ?Campaign $campaign = null, string $currency = 'EUR'){
        $payment = new Payment();
        $payment->setTotalAmount($totalAmount);
        $payment->setStatus($status);
        $payment->setCreatedAt(new DateTime($createdAt));
        $payment->setCampaign($campaign);
        $payment->setCurrencyCode($currency);
        $this->entityManager->persist($payment);
    }

    private function createCampaign(string $name, int $publidId, string $publicTitle): Campaign{
        $campaign = new Campaign();
        $campaign->setName($name);
        $campaign->setDefault(false);
        $campaign->setPublicId($publidId);
        $campaign->setPublicTitle($publicTitle);
        $this->entityManager->persist($campaign);
        return $campaign;
    }
    
    private function assertStrEq(string $expected, object $actual): void {
        $this->assertSame($expected, (string)$actual);
    }
}
class DatabaseTestingKernel extends Kernel
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
            new DoctrineBundle(),
            new DAMADoctrineTestBundle(),
            new PayumBundle(),
        ];
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
    }
    
    public function getCacheDir(): string
    {
        // Ensure each kernel instance generates its own cache allowing different test cases do not reuse the cache
        return parent::getCacheDir().'/'.spl_object_hash($this);
    }
}