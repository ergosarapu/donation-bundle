<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SortOrder;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\AcceptPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ImportPaymentsFromFile;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ReconcilePaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\RejectPaymentImport;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentFileImportResult;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PendingPaymentImportCQRSController extends AbstractPaymentCQRSController
{
    public function dispatchCommandsForPersist(object $entity): void
    {
    }

    public function dispatchCommandsForDelete(object $entity): void
    {
    }

    public function dispatchCommandsForUpdate(object $entity, PreUpdateEventArgs $updateEvent): void
    {
    }

    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.importStatus = :importStatus')->setParameter('importStatus', PaymentImportStatus::Pending->value);
        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setDefaultSort([
                'effectiveDate' => SortOrder::ASC,
            ])
            ->setEntityLabelInSingular('Pending Import')
            ->setPageTitle(Crud::PAGE_INDEX, 'Pending Imports')
            ->overrideTemplate('crud/index', '@Donation/admin/crud/pending_payment_import_index.html.twig')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $uploadFile = Action::new('uploadFile', 'Import File', 'fa fa-file-import')
            ->linkToCrudAction('uploadFile')
            ->createAsGlobalAction();

        $accept = Action::new('accept', 'Accept', 'fa fa-check')
            ->linkToCrudAction('accept')
            ->setCssClass('btn btn-success')
            ->displayIf(static fn ($entity) => $entity instanceof Payment && !empty($entity->getMatchingPayments()))
            ->askConfirmation('⚠️ This payment has potential matching payments. Accepting it may create a duplicate. Continue?');

        $acceptNoMatches = Action::new('acceptNoMatches', 'Accept', 'fa fa-check')
            ->linkToCrudAction('accept')
            ->setCssClass('btn btn-success')
            ->displayIf(static fn ($entity) => $entity instanceof Payment && empty($entity->getMatchingPayments()));

        $reject = Action::new('reject', 'Reject', 'fa fa-times')
            ->linkToCrudAction('reject')
            ->setCssClass('btn btn-danger');

        $reconcile = Action::new('reconcile', 'Reconcile', 'fa fa-link')
            ->linkToCrudAction('reconcile')
            ->displayAsButton()
            ->setCssClass('btn btn-primary btn-sm')
            ->displayIf(static fn () => false); // Hidden from automatic rendering

        return $actions
            ->add(Crud::PAGE_INDEX, $uploadFile)
            ->add(Crud::PAGE_INDEX, $accept)
            ->add(Crud::PAGE_INDEX, $acceptNoMatches)
            ->add(Crud::PAGE_INDEX, $reject)
            ->add(Crud::PAGE_INDEX, $reconcile)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW);
    }

    /**
     * @param AdminContext<Payment> $context
     */
    public function uploadFile(AdminContext $context, Request $request): Response
    {
        if (!$request->isMethod('POST')) {
            // Render the upload form
            return $this->render('@Donation/admin/upload_form.html.twig', [
                'upload_action' => $context->getRequest()->getUri(),
            ]);
        }

        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('upload_file');

        if ($uploadedFile) {
            try {
                $result = $this->commandBus->dispatch(new ImportPaymentsFromFile($uploadedFile->getPathname()));
                if ($result instanceof PaymentFileImportResult === false) {
                    throw new RuntimeException('Unexpected result type from '.ImportPaymentsFromFile::class.' command');
                }
                if (count($result->pendingPaymentIds) > 0) {
                    $this->addFlash('success', sprintf('%d payment(s) pending for import.', count($result->pendingPaymentIds)));
                }
                if ($result->skippedCount > 0) {
                    $this->addFlash('warning', sprintf('%d payment(s) were skipped.', $result->skippedCount));
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to process file: ' . $e->getMessage());
                throw $e;
            }
        } else {
            $this->addFlash('error', 'No file was uploaded.');
        }

        return $this->redirectToIndex();
    }

    /**
     * @param AdminContext<Payment> $context
     */
    public function accept(AdminContext $context): Response
    {
        /** @var Payment $payment */
        $payment = $context->getEntity()->getInstance();

        $this->commandBus->dispatch(new AcceptPaymentImport(PaymentId::fromString($payment->getPaymentId())));
        $this->addFlash('success', sprintf('Payment %s accepted.', $payment->getPaymentId()));

        return $this->redirectToIndex();
    }

    /**
     * @param AdminContext<Payment> $context
     */
    public function reject(AdminContext $context): Response
    {
        /** @var Payment $payment */
        $payment = $context->getEntity()->getInstance();

        $this->commandBus->dispatch(new RejectPaymentImport(PaymentId::fromString($payment->getPaymentId())));
        $this->addFlash('warning', sprintf('Payment %s rejected.', $payment->getPaymentId()));

        return $this->redirectToIndex();
    }

    /**
    * @param AdminContext<Payment> $context
    */
    public function reconcile(AdminContext $context): Response
    {
        $request = $context->getRequest();
        $pendingPaymentId = $request->query->get('pendingId');
        $matchingPaymentId = $request->query->get('matchingId');

        if (!$pendingPaymentId || !$matchingPaymentId) {
            $this->addFlash('error', 'Missing payment IDs for reconciliation.');
            return $this->redirectToRoute('admin', [
                'crudAction' => Action::INDEX,
                'crudControllerFqcn' => self::class,
            ]);
        }

        $this->commandBus->dispatch(new ReconcilePaymentImport(PaymentId::fromString($pendingPaymentId), PaymentId::fromString($matchingPaymentId)));
        $this->addFlash('success', sprintf('Payments %s and %s reconciled.', $pendingPaymentId, $matchingPaymentId));

        return $this->redirectToIndex();
    }

    private function redirectToIndex(): Response
    {
        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
        return $this->redirect($url);
    }

}
