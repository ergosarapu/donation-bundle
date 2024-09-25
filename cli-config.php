<?php

require 'vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;

$driver = new MappingDriverChain();
$driver->addDriver(new SimplifiedXmlDriver([__DIR__.'/vendor/payum/core/Payum/Core/Bridge/Doctrine/Resources/mapping' => 'Payum\Core\Model']), 'Payum\Core\Model');
$driver->addDriver(new AttributeDriver([__DIR__.'/src/Entity']), 'ErgoSarapu\DonationBundle\Entity');

$ORMConfig = new Configuration();
$ORMConfig->setProxyDir(sys_get_temp_dir());
$ORMConfig->setProxyNamespace('Proxies');
$ORMConfig->setMetadataDriverImpl($driver);
$ORMConfig->setNamingStrategy(new UnderscoreNamingStrategy());

if (!getenv('DATABASE_URL')) {
    throw new InvalidArgumentException('DATABASE_URL not available as environment variable');
}
$dsnParser = new DsnParser();
$connectionParams = $dsnParser->parse(getenv('DATABASE_URL'));
var_dump($connectionParams);
$connection = DriverManager::getConnection($connectionParams, $ORMConfig);

$entityManager = new EntityManager($connection, $ORMConfig);

return DependencyFactory::fromEntityManager(new PhpFile('migrations.php'), new ExistingEntityManager($entityManager));
