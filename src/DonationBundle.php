<?php

namespace ErgoSarapu\DonationBundle;

use ErgoSarapu\DonationBundle\DependencyInjection\Compiler\RegisterQueryCompilerPass;
use ErgoSarapu\DonationBundle\Repository\ResetPasswordRequestRepository;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Intl\Countries;

class DonationBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('payments')
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
                ->validate()
                    ->ifTrue(function (array $values): bool{
                        foreach($values as $key => $value) {
                            if (!Countries::exists($key)){
                                return true;
                            }
                        }
                        return false;
                    })
                    ->thenInvalid('Not a valid alpha-2 country code')
                ->end()
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
            ->arrayPrototype()->info("Name of a Payum gateway")
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

        $builder->getDefinition('donation_bundle.payum.payum_payment_provider')
            ->setArgument(1, $config['payments'] ?? null);
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('twig_component',[
            'defaults' => [
                'ErgoSarapu\DonationBundle\Twig\Components\\' => [
                    'template_directory' =>  '@Donation/components/',
                ]
            ]
        ]);

        $builder->prependExtensionConfig('twig',[
            'form_themes' => [
                '@TalesFromADevFlowbite/form/default.html.twig'
            ]
        ]);

        $builder->prependExtensionConfig('symfonycasts_reset_password', [
            'request_password_repository' => ResetPasswordRequestRepository::class
        ]);

    }

    public function build(ContainerBuilder $builder): void {
        parent::build($builder);
        $builder->addCompilerPass(new RegisterQueryCompilerPass());
    }
}
