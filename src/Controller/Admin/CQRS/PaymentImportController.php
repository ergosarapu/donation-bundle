<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SortOrder;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
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

class PaymentImportController extends AbstractPaymentController
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
            ->setPageTitle(Crud::PAGE_INDEX, 'Pending Imports')
            ->overrideTemplate('crud/index', '@Donation/admin/crud/pending_payment_import_index.html.twig')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $uploadFile = Action::new('uploadFile', 'Import File', 'fa fa-file-import')
            ->linkToCrudAction('uploadFile')
            ->createAsGlobalAction();

        $accept = $this->newInterceptedAction(
            'accept',
            'Accept',
            'fa fa-check',
            htmlAttributes: [
                'data-reconcile-target' => 'acceptAction',
                'data-action-interceptor-confirmation-param' => '⚠️ This payment has potential matching payments. Accepting it may create a duplicate. Continue?',
                'hidden' => 'hidden',
            ]
        )
            ->linkToCrudAction('accept')
            ->asSuccessAction()
            ->displayIf(static fn ($entity) => $entity instanceof Payment && !empty($entity->getMatchingPayments()));

        $acceptNoMatches = $this->newInterceptedAction(
            'acceptNoMatches',
            'Accept',
            'fa fa-check',
            htmlAttributes: [
                'data-reconcile-target' => 'acceptAction',
                'hidden' => 'hidden',
            ]
        )
            ->linkToCrudAction('accept')
            ->asSuccessAction()
            ->displayIf(static fn ($entity) => $entity instanceof Payment && empty($entity->getMatchingPayments()));

        $reject = $this->newInterceptedAction('reject', 'Reject', 'fa fa-times')
            ->linkToCrudAction('reject')
            ->asDangerAction()
        ;

        $reconcile = $this->newInterceptedAction(
            'reconcile',
            'Reconcile',
            'fa fa-link',
            htmlAttributes: [
                'data-reconcile-target' => 'reconcileAction',
                'hidden' => 'hidden',
            ]
        )
            ->linkToCrudAction('reconcile')
            ->asSuccessAction()
            ->addCssClass('btn-reconcile')
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $uploadFile)
            ->add(Crud::PAGE_INDEX, $reconcile)
            ->add(Crud::PAGE_INDEX, $accept)
            ->add(Crud::PAGE_INDEX, $acceptNoMatches)
            ->add(Crud::PAGE_INDEX, $reject)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
        ;
    }

    /**
     * @param AdminContext<Payment> $context
     */
    #[AdminRoute(path: '/upload')]
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
                $result = $this->dispatch(new ImportPaymentsFromFile($uploadedFile->getPathname()))->result;
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
    #[AdminAction(methods: ['POST'])]
    #[AdminRoute(path: '/accept')]
    public function accept(AdminContext $context): Response
    {
        /** @var Payment $payment */
        $payment = $context->getEntity()->getInstance();
        $command = new AcceptPaymentImport(PaymentId::fromString($payment->getPaymentId()));
        return $this->dispatchAndReturnCorrelationId($command);
    }

    /**
     * @param AdminContext<Payment> $context
     */
    #[AdminAction(methods: ['POST'])]
    #[AdminRoute(path: '/reject')]
    public function reject(AdminContext $context): Response
    {
        /** @var Payment $payment */
        $payment = $context->getEntity()->getInstance();
        $command = new RejectPaymentImport(PaymentId::fromString($payment->getPaymentId()));
        return $this->dispatchAndReturnCorrelationId($command);
    }

    /**
    * @param AdminContext<Payment> $context
    */
    #[AdminAction(methods: ['POST'])]
    #[AdminRoute(path: '/reconcile')]
    public function reconcile(AdminContext $context): Response
    {
        /** @var Payment $payment */
        $payment = $context->getEntity()->getInstance();

        $request = $context->getRequest();
        $reconcileWith = $request->request->get('reconcileWith');

        if (!is_string($reconcileWith)) {
            throw new RuntimeException('Missing reconcileWith parameter');
        }

        $command = new ReconcilePaymentImport(
            PaymentId::fromString($payment->getPaymentId()),
            PaymentId::fromString($reconcileWith)
        );
        return $this->dispatchAndReturnCorrelationId($command);
    }

}
