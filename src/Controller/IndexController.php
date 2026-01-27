<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\BCDonations\Application\Query\GetActiveCampaigns;
use ErgoSarapu\DonationBundle\BCDonations\Application\Query\Model\Campaign;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\CampaignId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationId;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonationRequest;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Donation\DonorIdentity;
use ErgoSarapu\DonationBundle\BCDonations\Domain\RecurringPlan\RecurringInterval;
use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Form\DonationFormStep1Type;
use ErgoSarapu\DonationBundle\Form\DonationFormStep2Type;
use ErgoSarapu\DonationBundle\Form\DonationFormStep3Type;
use ErgoSarapu\DonationBundle\Form\FormOptionsProvider;
use ErgoSarapu\DonationBundle\IntegrationContracts\Donations\Command\InitiateDonationIntegrationCommand;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\QueryBusInterface;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\ShortDescription;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{
    public function __construct(
        private FormOptionsProvider $formOptions,
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus
    ) {
    }

    public function __invoke(
        Request $request,
        string $campaignSlug,
        string $template,
        int $step = 1
    ): Response {
        $campaign = $this->getSingleActiveCampaign();
        $donation = $this->getDonationData($request);

        $form = $this->getDonationFormStep($step, $donation, $request);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DonationDto $donation */
            $donation = $form->getData();

            if ($step === 3) {
                /** @var DonationDto $donation */
                $donation = $form->getData();
                if ($donation->getGateway() === null) {
                    throw new InvalidArgumentException('Gateway must be set at this point');
                }
                $gateway = new Gateway($donation->getGateway());

                if ($donation->getAmount() === null) {
                    throw new InvalidArgumentException('Amount must be set at this point');
                }
                if ($donation->getCurrencyCode() === null) {
                    throw new InvalidArgumentException('Currency code must be set at this point');
                }
                $amount = new Money($donation->getAmount(), new Currency($donation->getCurrencyCode()));

                $donationRequest = new DonationRequest(
                    donationId: DonationId::generate(),
                    campaignId: CampaignId::fromString($campaign->getCampaignId()),
                    amount: $amount,
                    gateway: $gateway,
                    donorIdentity: new DonorIdentity(
                        $this->getDomainEmail($donation),
                        $this->getDomainPersonName($donation),
                        $this->getDomainNationalIdCode($donation),
                    ),
                    description: new ShortDescription($campaign->getPublicTitle()),
                );

                $interval = null;
                if ($donation->getFrequency() !== null) {
                    $interval = new RecurringInterval($donation->getFrequency());
                }
                $command = new InitiateDonationIntegrationCommand($donationRequest, $interval);
                $this->commandBus->dispatch($command);
                $request->getSession()->remove('donation');
                return $this->redirectToRoute('donation_redirect', ['donationId' => $command->donationRequest->donationId->toString()]);
            }

            $request->getSession()->set('donation', $donation);

            return $this->redirectToRoute('donation_' . $template, ['template' => $template, 'step' => $step + 1]);
        }

        return $this->render('@Donation/' . $template . '.html.twig', [
            'form' => $form,
            'donationData' => $donation,
            'campaign' => $campaign,
            'step' => $step,
            'currentUrl' => $this->getFormUrl($template, $step),
            'previousUrl' => $step === 1 ? null : $this->getFormUrl($template, $step - 1),
        ]);
    }

    private function getFormUrl(string $template, int $step): string
    {
        return $this->generateUrl('donation_' . $template, ['template' => $template, 'step' => $step]);
    }

    private function getDonationFormStep(int $step, DonationDto $donation, Request $request): FormInterface
    {
        if ($step === 1) {
            return $this->createForm(
                DonationFormStep1Type::class,
                $donation,
                [
                    'currencies' => $this->formOptions->getCurrencies(),
                    'locale' => $request->getLocale(),
                    'frequencies' => $this->formOptions->getFrequencies(),
                ]
            );
        } elseif ($step === 2) {
            return $this->createForm(DonationFormStep2Type::class, $donation);
        } elseif ($step === 3) {
            $frequency = $donation->getFrequency();
            return $this->createForm(
                DonationFormStep3Type::class,
                $donation,
                [
                    'frequency' => $frequency,
                    'gateways' => $this->formOptions->getGateways($frequency),
                ]
            );
        }
        throw new InvalidArgumentException('Unsupported form step ' . $step);
    }

    private function getDonationData(Request $request): DonationDto
    {
        $session = $request->getSession();
        $donation = $session->get('donation');
        if ($donation === null) {
            $donation = new DonationDto();

            $options = $this->formOptions->getCurrencies();

            // Set initial default values
            $currencyCode = 'EUR';
            $defaultAmount = $options[$currencyCode]['amount_default'];
            $donation->setAmount($defaultAmount);
            $donation->setChosenAmount($defaultAmount);
            $donation->setCurrencyCode($currencyCode);
        }
        return $donation;
    }

    private function getSingleActiveCampaign(): Campaign
    {
        /** @var array<Campaign> $campaigns */
        $campaigns = $this->queryBus->ask(new GetActiveCampaigns());

        if (count($campaigns) === 0) {
            throw new InvalidArgumentException('No active campaign found');
        }
        if (count($campaigns) > 1) {
            throw new InvalidArgumentException('Multiple active campaigns found');
        }
        return $campaigns[0];
    }


    private function getDomainPersonName(DonationDto $donation): ?PersonName
    {
        if ($donation->getGivenName() === null && $donation->getFamilyName() === null) {
            return null;
        }
        return new PersonName($donation->getGivenName(), $donation->getFamilyName());
    }

    private function getDomainEmail(DonationDto $donation): ?Email
    {
        if ($donation->getEmail() === null) {
            return null;
        }
        return new Email($donation->getEmail());
    }

    private function getDomainNationalIdCode(DonationDto $donation): ?NationalIdCode
    {
        if ($donation->getNationalIdCode() === null) {
            return null;
        }
        return new NationalIdCode($donation->getNationalIdCode());
    }
}
