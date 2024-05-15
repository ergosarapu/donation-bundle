Installation
============

The bundle currently supports Symfony 6.4

Step 1: Configure composer.json repository, e.g. add following if donation-bundle is mounted in local file system at `/mnt/donation-bundle`
---------------------------
```json
    "repositories":[
        {
            "type": "path",
            "url": "/mnt/donation-bundle"
        }
    ],
```


Step 2: Install the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require ergosarapu/donation-bundle @dev
```

Step 3: Initialize database
---------------------------

The Docker configuration of symfony/doctrine-bundle repository is extensible thanks to Flex recipes. By default, the recipe installs PostgreSQL.
If you prefer to work with MySQL, update the project configuration accordingly. In case you are using [symfony-docker](https://github.com/dunglas/symfony-docker) for your app, you can follow [these instructions](https://github.com/dunglas/symfony-docker/blob/main/docs/mysql.md).

Step 4: Register bundle routes
---------------------------

Add following to `config/routes.yaml`:

```yaml
donation_bundle_routes:
    # loads routes from the given routing file stored in bundle
    resource: '@DonationBundle/config/routes.yaml'
```

Step 5: Register bundle services
---------------------------

Add following to `config/services.yaml`:

```yaml
ErgoSarapu\DonationBundle\Controller\AdminDashboardController:
```

Step 6: Register bundle templates
---------------------------

Add following to `config/packages/twig.yaml`:

```yaml
twig:
    paths:
        'vendor/ergosarapu/donation-bundle/templates/': 'DonationBundle'
```

Step 7: Register DonationForm
---------------------------

Create file `src/Twig/Components/DonationForm.php`:

```php
namespace App\Twig\Components;

use ErgoSarapu\DonationBundle\Traits\DonationFormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent(template:'@DonationBundle/components/DonationForm.html.twig')]
class DonateForm extends AbstractController
{
    use DonationFormTrait;
}
```
