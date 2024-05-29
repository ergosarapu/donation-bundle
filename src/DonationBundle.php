<?php

namespace ErgoSarapu\DonationBundle;

use ErgoSarapu\DonationBundle\Controller\IndexController;
use ErgoSarapu\DonationBundle\Payum\UpdatePaymentStatusExtension;
use ErgoSarapu\DonationBundle\Twig\Components\DonationForm;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DonationBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('payment_methods')
                    ->info('Payment methods configuration. Use configuraed Payum gateway as the name.')
                    ->validate()
                        ->ifEmpty()
                        ->thenInvalid('Configure at least one payment method')
                    ->end()
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('label')->isRequired()->cannotBeEmpty()->info('Payment method label as shown to the end user')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/../config/controller.yaml');

        $container->services()->set(UpdatePaymentStatusExtension::class, class: UpdatePaymentStatusExtension::class)->public()->tag('payum.extension', ['all' => true]);
        $container->services()->get(IndexController::class)->call('setPaymentMethods', [$config['payment_methods']]);
        $container->services()->get(DonationForm::class)->call('setPaymentMethods', [$config['payment_methods']]);
    }
}
