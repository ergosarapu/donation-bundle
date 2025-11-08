<?php

/**
 * Doctrine CLI Configuration for ORM Migrations
 * 
 * This configuration uses IntegrationTestingKernel to load all required bundles and extensions,
 * including Patchlevel EventSourcing with 'merge_orm_schema' => true configuration.
 * This ensures that the EventStore schema is merged with the ORM schema for migrations.
 */
require 'vendor/autoload.php';

use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use ErgoSarapu\DonationBundle\Tests\Helpers\DonationBundleTestingKernel;

if (!getenv('DATABASE_URL')) {
    throw new InvalidArgumentException('DATABASE_URL not available as environment variable');
}

// Boot the IntegrationTestingKernel to load all bundles and configuration
$kernel = new DonationBundleTestingKernel('dev', true);
$kernel->boot();

// Get the EntityManager from the kernel container
$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

// Create and return the DependencyFactory for Doctrine Migrations
return DependencyFactory::fromEntityManager(
    new PhpFile('migrations.php'), 
    new ExistingEntityManager($entityManager)
);
