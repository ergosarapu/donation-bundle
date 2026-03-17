<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\QueryBuilder;
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
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ImportPaymentsFromFile;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentFileImportResult;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentsController extends AbstractPaymentController
{
    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('(entity.importStatus IS NULL OR entity.importStatus IN (:importStatuses))')
            ->setParameter('importStatuses', [
            PaymentImportStatus::Accepted->value,
            PaymentImportStatus::Reconciled->value,
            ]);
        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setDefaultSort([
                'effectiveDate' => SortOrder::DESC,
            ])
            ->setPageTitle(Crud::PAGE_INDEX, 'Payments')
        ;
    }
    public function configureActions(Actions $actions): Actions
    {
        $uploadFile = Action::new('uploadFile', 'Import Payments', 'fa fa-file-import')
            ->linkToCrudAction('uploadFile')
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $uploadFile)
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
}
