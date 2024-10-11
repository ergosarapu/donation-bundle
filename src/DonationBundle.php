<?php

namespace ErgoSarapu\DonationBundle;

use ErgoSarapu\DonationBundle\DependencyInjection\Compiler\RegisterQueryCompilerPass;
use ErgoSarapu\DonationBundle\Repository\ResetPasswordRequestRepository;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Currencies;

class DonationBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                // Form
                ->arrayNode('form')
                    ->info('Form configuration.')
                    ->children()
                        ->append($this->addCurrencyNode())
                    ->end()
                ->end()

                // Payments
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

    private function addCurrencyNode(): NodeDefinition {
        $notPositiveInt = function ($value){
            return !is_numeric($value) || (int)$value != $value || !((int)$value > 0);
        };

        $treeBuilder = new TreeBuilder('currencies');
        return $treeBuilder->getRootNode()
        ->isRequired()
        ->useAttributeAsKey('currency_code')
        ->validate()
            ->ifTrue(function (array $values): bool{
                foreach($values as $key => $value) {
                    if (!Currencies::exists($key)){
                        return true;
                    }
                }
                return false;
            })
            ->thenInvalid('Not a valid currency code.')
        ->end()
        ->arrayPrototype()
        ->info("Currency code")
            ->children()
                ->scalarNode('amount_default')
                    ->info('Default amount pre-filled for the end user.')
                    ->isRequired()
                    ->validate()
                        ->ifTrue($notPositiveInt)
                        ->thenInvalid('Value must be valid integer greater than 0.')
                    ->end()
                ->end()
                ->arrayNode('amount_choices')
                    ->scalarPrototype()
                        ->validate()
                            ->ifTrue($notPositiveInt)
                            ->thenInvalid('Each value must be valid integer greater than 0.')
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

        $builder->getDefinition('donation_bundle.form.form_options_provider')
            ->setArgument(0, $config['payments'] ?? null)
            ->setArgument(1, $config['form']['currencies'] ?? null);
    }

    private function prependAssetMapperConfig(ContainerBuilder $builder): void{
        if (!$this->isAssetMapperAvailable($builder)) {
            return;
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../assets/dist' => '@ergosarapu/donation-bundle',
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $builder): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $builder->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'] . '/Resources/config/asset_mapper.php');
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

        $builder->prependExtensionConfig('symfonycasts_reset_password', [
            'request_password_repository' => ResetPasswordRequestRepository::class
        ]);

        $this->prependAssetMapperConfig($builder);
    }

    public function build(ContainerBuilder $builder): void {
        parent::build($builder);
        $builder->addCompilerPass(new RegisterQueryCompilerPass());
    }
}
