<?php

namespace ErgoSarapu\DonationBundle\DependencyInjection\Compiler;

use ErgoSarapu\DonationBundle\Query\PaymentSummaryMysqlQuery;
use ErgoSarapu\DonationBundle\Query\PaymentSummaryQueryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterQueryCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void {
        // TODO: Implement query service registration based on database engine used, this just supports Mysql only
        if (!$container->has('doctrine.orm.entity_manager')){
            return;
        }
        
        $definition = $container->register('donation_bundle.query.payment_summary', PaymentSummaryMysqlQuery::class);
        $definition->addArgument(new Reference('doctrine.orm.entity_manager'));
        
        $alias = $container->setAlias(PaymentSummaryQueryInterface::class, 'donation_bundle.query.payment_summary');
        if ($container->getParameter('kernel.environment') === 'test') {
            // Make the service alias available for tests
            $alias->setPublic(true);
        }
    }

}
