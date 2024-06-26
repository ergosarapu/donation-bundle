<?php

namespace ErgoSarapu\DonationBundle;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
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
                ->scalarNode('campaign_public_id')
                    ->isRequired()
                    ->info('Campaign public Id, will be encoded into payment description')
                    ->validate()
                        ->ifEmpty()
                        ->thenInvalid('Campaign public Id must be configured')
                    ->end()
                ->end()

                ->arrayNode('payments')
                    ->isRequired()
                    ->info('Payments configuration.')
                    ->validate()
                        ->ifEmpty()
                        ->thenInvalid('Configure at least one payment frequency type.')
                    ->end()

                    ->children()
                        ->arrayNode('onetime')
                            ->children()
                                ->append($this->addBankNode())
                                ->append($this->addCardNode())
                            ->end()
                        ->end()
                        ->arrayNode('monthly')
                            ->children()
                                ->append($this->addBankNode())
                                ->append($this->addCardNode())
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addBankNode(): NodeDefinition {

        $treeBuilder = new TreeBuilder('bank');
        return $treeBuilder->getRootNode()
            ->useAttributeAsKey('country_code')
            ->arrayPrototype()
                ->children()
                    ->append($this->addGatewaysNode())
                ->end()
            ->end();
    }

    private function addGatewaysNode(): NodeDefinition {
        $treeBuilder = new TreeBuilder('gateways');
        return $treeBuilder->getRootNode()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->scalarNode('label')->isRequired()->cannotBeEmpty()->info('Payment method label as shown to the end user')->end()
                    ->scalarNode('image')->cannotBeEmpty()->info('Payment method icon shown to the end user')->end()
                ->end()
            ->end();
    }

    private function addCardNode(): NodeDefinition {
        return (new TreeBuilder('card'))->getRootNode()->append($this->addGatewaysNode());
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/../config/services.xml');

        $builder->getDefinition('donation_bundle.controller.index_controller')
            ->setArgument(0, $config['payments'])
            ->setArgument(1, $config['campaign_public_id']);

        $builder->getDefinition('donation_bundle.twig.components.donation_form')
            ->setArgument(0, $config['payments'])
            ->setArgument(1, $config['campaign_public_id']);
    }    
}
