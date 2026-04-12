<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Command\CommandResult;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\DeleteEntityInterceptedException;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\PersistEntityInterceptedException;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Doctrine\UpdateEntityInterceptedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @template TEntity of object
 *
 * @extends  AbstractCrudController<TEntity>
 */
abstract class AbstractCQRSController extends AbstractCrudController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::updateEntity($entityManager, $entityInstance);
        } catch (UpdateEntityInterceptedException $e) {
            /** @var TEntity $entity */
            $entity = $e->getEntity();
            $this->dispatchCommandsForUpdate($entity, $e->getUpdateEvent());
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::persistEntity($entityManager, $entityInstance);
        } catch (PersistEntityInterceptedException $e) {
            /** @var TEntity $entity */
            $entity = $e->getEntity();
            $this->dispatchCommandsForPersist($entity);
        }
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::deleteEntity($entityManager, $entityInstance);
        } catch (DeleteEntityInterceptedException $e) {
            /** @var TEntity $entity */
            $entity = $e->getEntity();
            $this->dispatchCommandsForDelete($entity);
        }
    }

    /**
     * @param TEntity $entity
     */
    abstract public function dispatchCommandsForUpdate(object $entity, PreUpdateEventArgs $updateEvent): void;

    /**
     * @param TEntity $entity
     */
    abstract public function dispatchCommandsForPersist(object $entity): void;

    /**
     * @param TEntity $entity
     */
    abstract public function dispatchCommandsForDelete(object $entity): void;

    public function dispatchAndReturnTrackingId(object $command): JsonResponse
    {
        $result = $this->commandBus->dispatch($command);
        return new JsonResponse(['trackingId' => $result->trackingId]);
    }

    public function dispatch(object $command): CommandResult
    {
        return $this->commandBus->dispatch($command);
    }

    protected function redirectToIndex(): Response
    {
        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $urlGenerator = $adminUrlGenerator
            ->unsetAllExcept('page', 'sort')
            ->setController(get_class($this))
            ->setAction(Action::INDEX);

        $url = $urlGenerator->generateUrl();
        return $this->redirect($url);
    }

    /**
     * @param array<string, string> $htmlAttributes
     */
    protected function newInterceptedAction(string $name, TranslatableInterface|string|callable|false|null $label = null, ?string $icon = null, array $htmlAttributes = []): Action
    {
        $htmlAttributes['data-action'] = 'click->action-interceptor#intercept';

        return Action::new($name, $label, $icon)
            ->renderAsForm()
            ->setHtmlAttributes($htmlAttributes);
    }
}
