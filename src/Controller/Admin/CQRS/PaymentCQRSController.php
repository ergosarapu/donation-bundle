<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller\Admin\CQRS;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use ErgoSarapu\DonationBundle\BCPayments\Application\Command\ImportPaymentsFromFile;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentFileImportResult;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentStatus;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCQRSController<Payment>
 */
class PaymentCQRSController extends AbstractCQRSController
{
    public function __construct(
        public readonly CommandBusInterface $commandBus,
    ) {
    }

    public function dispatchCommandsForPersist(object $entity): void
    {
    }

    public function dispatchCommandsForDelete(object $entity): void
    {
    }

    /**
     * @param Payment $entity
     */
    public function dispatchCommandsForUpdate(object $entity, PreUpdateEventArgs $updateEvent): void
    {
        $changes = $updateEvent->getEntityChangeSet();
        /** @var string $field */
        foreach ($changes as $field => $change) {
            /** @var string $oldValue */
            $oldValue = $updateEvent->getOldValue($field);
            /** @var string $newValue */
            $newValue = $updateEvent->getNewValue($field);
            $this->addFlash('warning', sprintf('No command was dispatched for "%s" field change, old value "%s", new value "%s"', $field, $oldValue, $newValue));
        }
    }

    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->setDisabled()->hideOnIndex(),
            IdField::new('id')->setDisabled()->formatValue(function (string $value): string {
                return substr($value, -12);
            })->hideOnDetail()->hideOnForm(),
            MoneyField::new('amount')->setCurrencyPropertyPath('currency')->setDisabled(),
            ChoiceField::new('status')
                ->setDisabled()
                ->formatValue(function (PaymentStatus $value) {
                    $colors = [
                        PaymentStatus::Pending->value => 'warning',
                        PaymentStatus::Authorized->value => 'info',
                        PaymentStatus::Captured->value => 'success',
                        PaymentStatus::Settled->value => 'success',
                        PaymentStatus::Failed->value => 'danger',
                        PaymentStatus::Canceled->value => 'secondary',
                        PaymentStatus::Refunded->value => 'secondary',
                    ];
                    $color = $colors[$value->value];
                    return sprintf('<span class="badge badge-%s">%s</span>', $color, ucfirst($value->name));
                }),
            ChoiceField::new('importStatus')
                ->setDisabled()
                ->formatValue(function (?PaymentImportStatus $value) {
                    if ($value === null) {
                        return '';
                    }
                    $colors = [
                        PaymentImportStatus::Pending->value => 'warning',
                        PaymentImportStatus::Reconciled->value => 'info',
                        PaymentImportStatus::Accepted->value => 'success',
                        PaymentImportStatus::Rejected->value => 'danger',
                    ];
                    $color = $colors[$value->value];
                    return sprintf('<span class="badge badge-%s">%s</span>', $color, ucfirst($value->name));
                }),
            TextField::new('description')->setDisabled(),
            TextField::new('givenName')->setDisabled(),
            TextField::new('familyName')->setDisabled(),
            TextField::new('accountHolderName')->setDisabled(),
            TextField::new('nationalIdCode')->setDisabled(),
            TextField::new('organizationRegCode')->setDisabled(),
            TextField::new('referenceNumber')->setDisabled(),
            TextField::new('iban')->setDisabled(),
            TextField::new('bic')->setDisabled(),
            DateTimeField::new('initiatedAt')->setDisabled(),
            DateTimeField::new('capturedAt')->setDisabled(),
            DateTimeField::new('authorizedAt')->setDisabled(),
            DateTimeField::new('bookingDate')->setDisabled(),
            DateTimeField::new('createdAt')->setDisabled(),
            DateTimeField::new('updatedAt')->setDisabled(),
        ];
    }


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setDefaultSort(['createdAt' => 'DESC'])
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $uploadFile = Action::new('uploadFile', 'Import', 'fa fa-file-import')
            ->linkToCrudAction('uploadFile')
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $uploadFile)
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
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

        // Redirect back to index page
        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

}
