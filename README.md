# DonationBundle

Donation Bundle allows creating developer friendly donation Symfony based websites easily

The bundle currently supports Symfony 6.4

## Installation

Open a command console, enter your project directory and execute:

```console
composer require ergosarapu/donation-bundle
```

To initialize database, first generate migrations files ...

```console
bin/console doctrine:migrations:diff
```

... then run migrations to create database tables:

```console
bin/console doctrine:migrations:migrate
```

Register bundle routes:
```yaml
// config/routes.yaml

donation_bundle_routes:
    # loads routes from the given routing file stored in bundle
    resource: '@DonationBundle/config/routes.xml'
```

Create admin user:
```sh
php bin/console donation:add-user [email] [givenname] [familyname] --admin
```

If you run your app in localhost, then the admin panel can be accessed at http://localhost/admin.

## Register Payum gateway factories

The bundle uses Payum for payment gateway abstraction. In order to use a gateway, register Payum gateway factory, e.g:

```yaml
// config/services.yaml

app.montonio_gateway_factory:
    class: Payum\Core\Bridge\Symfony\Builder\GatewayFactoryBuilder
    arguments: [ErgoSarapu\PayumMontonio\MontonioGatewayFactory]
    tags:
        - { name: payum.gateway_factory_builder, factory: montonio }
```

Then configure [PayumBundle](https://github.com/Payum/PayumBundle) and gateways.

## Configuration

The following configuration options are available for the Donation Bundle:

```yaml
# config/packages/donation.yaml

donation:

    # Payments configuration.
    payments:
        onetime:
            bank:

                # Prototype
                country_code:
                    gateways:

                        # Prototype: Name of a Payum gateway
                        name:

                            # Payment method label as shown to the end user
                            label:                ~ # Required

                            # Payment method icon shown to the end user
                            image:                ~
            card:
                gateways:

                    # Prototype: Name of a Payum gateway
                    name:

                        # Payment method label as shown to the end user
                        label:                ~ # Required

                        # Payment method icon shown to the end user
                        image:                ~
        monthly:
            bank:

                # Prototype
                country_code:
                    gateways:

                        # Prototype: Name of a Payum gateway
                        name:

                            # Payment method label as shown to the end user
                            label:                ~ # Required

                            # Payment method icon shown to the end user
                            image:                ~
            card:
                gateways:

                    # Prototype: Name of a Payum gateway
                    name:

                        # Payment method label as shown to the end user
                        label:                ~ # Required

                        # Payment method icon shown to the end user
                        image:                ~
```

# Development

## Set up dev environment using DDEV
```sh
ddev start
```

## Set up app integrated dev environment
TODO: Describe how to set up dev environment with Symfony app using this bundle. While it is possible to develop bundle without setting up app itself, it is useful to verify things properly work as expected. Also it gives possibility to use Symfony console commands, e.g. to generate needed database migration files.

## Initialize test database
Create missing directories and database file:
```console
mkdir var && mkdir migrations
touch var/testdb.sqlite
```

Create and run migrations:
```sh
./vendor/bin/doctrine-migrations migrations:diff
./vendor/bin/doctrine-migrations migrations:migrate
```

## Testing
Use following command to run tests:
```sh
./vendor/bin/simple-phpunit
```
