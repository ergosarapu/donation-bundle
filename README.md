# DonationBundle

Donation Bundle allows creating Symfony based donation websites easily

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

# Define or override your routes here

donation_bundle_routes_campaign:
    # loads rest of loosely matching routes as last so they will not match before the ones defined before
    resource: '@DonationBundle/config/routes_campaign.xml'
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

    # Form configuration.
    form:
        currencies:           # Required

            # Prototype: Currency code
            currency_code:

                # Default amount pre-filled for the end user.
                amount_default:       ~ # Required
                amount_choices:       []

    # Gateways configuration
    gateways:

        # Prototype
        name:

            # The label of the gateway group shown to the end user
            group:                ~ # Required

            # The label of payment gateway shown to the end user
            label:                ~ # Required

            # The icon of payment gateway shown to the end user
            image:                ~

            # Available recurring frequencies, null for one-time (default) or date interval string, e.g. P1M for monthly, P1W for weekly, etc
            frequencies:

                # Default:
                - 

            # Marks gateway as country specific so user can quickly filter gateways with same country. Must be valid alpha-2 country code.
            country:              ~
```
## Process Subscription payments

To create new payments for subscriptions (renew) run following command periodically. This dispatches created payments to messenger transport for capturing:
```sh
bin/console donation:subscription:process
```

To handle subscription payment capture asynchronously, you may create following Messenger configuration. Adjust according to your needs:

```yaml
# config/packages/messenger.yaml

framework:
    messenger:
        transports:
            async: 'doctrine://default'

            subscription:
                dsn: 'doctrine://default?queue_name=subscription'
                failure_transport: subscription_failed
                retry_strategy:
                    max_retries: 0 // Do not retry subscription capture automatically, it will land in failure transport for manual processing

            subscription_failed:
                dsn: 'doctrine://default?queue_name=subscription_failed'
                retry_strategy:
                    max_retries: 10
                    delay: 0

        routing:
            'ErgoSarapu\DonationBundle\Message\CapturePayment': subscription
```

## Reset password feature
The password reset feature uses [SymfonyCastsResetPasswordBundle](https://github.com/symfonycasts/reset-password-bundle), check its configuration to modify its behavior.

In order to use reset password feature, install [Mailer](https://symfony.com/doc/current/mailer.html) component in your application and configure [sender globally](https://symfony.com/doc/current/mailer.html#configuring-emails-globally):
```yaml
# config/packages/mailer.yaml

mailer:
    envelope:
        sender: 'donations@example.com'
    headers:
        From: 'Donations <donations@example.com>'
```


# Development

## Set up dev environment using DDEV
```sh
ddev start
```

## Install dependencies

To restrict packages install to specific Symfony version, install symfony/flex globally and specify your desired Symfony version: 

```console
composer global config --no-plugins allow-plugins.symfony/flex true
composer global require --no-interaction --no-progress symfony/flex:^2.4
composer config extra.symfony.require "7.1"
```

## Testing
Use following script to run database migrations and tests:
```sh
./run_tests.sh
```

## Set up app integrated dev environment
TODO: Describe how to set up dev environment with Symfony app using this bundle. While it is possible to develop bundle without setting up app itself, it is useful to verify things work properly as expected.