# Installation

The bundle currently supports Symfony 6.4

## Step 1: Configure composer.json repository, e.g. add following if donation-bundle is mounted in local file system at `/mnt/donation-bundle`

```json
    "repositories":[
        {
            "type": "path",
            "url": "/mnt/donation-bundle"
        }
    ],
```

## Step 2: Install the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require ergosarapu/donation-bundle @dev
```

## Step 3: Initialize database

Add following to `config/packages/doctrine_migrations.yaml`:

```json
doctrine_migrations:
    migrations_paths:
        'DonationBundle\Migrations': '@DonationBundle/migrations'
```

The Docker configuration of symfony/doctrine-bundle repository is extensible thanks to Flex recipes. By default, the recipe installs PostgreSQL.
If you prefer to work with MySQL, update the project configuration accordingly. In case you are using [symfony-docker](https://github.com/dunglas/symfony-docker) for your app, you can follow [these instructions](https://github.com/dunglas/symfony-docker/blob/main/docs/mysql.md).

## Step 4: Register bundle routes

Add following to `config/routes.yaml`:

```yaml
donation_bundle_routes:
    # loads routes from the given routing file stored in bundle
    resource: '@DonationBundle/config/routes.xml'
```

## Step 5: Register Payum gateway factories

Add following to `config/services.yaml`:

```yaml
app.montonio_gateway_factory:
    class: Payum\Core\Bridge\Symfony\Builder\GatewayFactoryBuilder
    arguments: [ErgoSarapu\PayumMontonio\MontonioGatewayFactory]
    tags:
        - { name: payum.gateway_factory_builder, factory: montonio }
```

# Development

## Set up dev environment using DDEV
```sh
ddev start
```

## Set up app integrated dev environment
TODO: Describe how to set up dev environment with Symfony app using this bundle. While it is possible to develop bundle without setting up app itself, it is useful to verify things properly work as expected. Also it gives possibility to use Symfony console commands, e.g. to generate needed database migration files.

## Initialize test database
Create migrations:
```sh
./vendor/bin/doctrine-migrations migrations:diff
```

Migrate database
```sh
./vendor/bin/doctrine-migrations migrations:migrate
```

## Testing
Use following command to run tests:
```sh
./vendor/bin/simple-phpunit
```
