<?php

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\BCDonations\Application\Command\InitiateDonation;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentGatewayInterface;
use ErgoSarapu\DonationBundle\SharedApplication\Port\Bus\CommandBusInterface;
use ErgoSarapu\DonationBundle\BCDonations\Domain\Campaign\ValueObject\CampaignId;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Currency;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Money;
use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Entity\Campaign;
use ErgoSarapu\DonationBundle\Form\DonationFormStep1Type;
use ErgoSarapu\DonationBundle\Form\DonationFormStep2Type;
use ErgoSarapu\DonationBundle\Form\DonationFormStep3Type;
use ErgoSarapu\DonationBundle\Form\FormOptionsProvider;
use ErgoSarapu\DonationBundle\Repository\CampaignRepository;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Gateway;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{

    public function __construct(
        private FormOptionsProvider $formOptions,
        private readonly CampaignRepository $campaignRepository,
        private readonly CommandBusInterface $commandBus)
    {
    }

    public function __invoke(
        Request $request,
        string $campaign,
        string $template,
        int $step = 1): Response
    {
        $campaign = $this->getDefaultCampaign();
        $donation = $this->getDonationData($request);

        $form = $this->getDonationFormStep($step, $donation, $request);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DonationDto $donation */
            $donation = $form->getData();
            
            if ($step === 3) {
                /** @var DonationDto $donation */
                $donation = $form->getData();
                $gateway = new Gateway($donation->getGateway());

                $amount = new Money($donation->getAmount(), new Currency($donation->getCurrencyCode()));
                
                $redirectParams = [];
                $redirectRoute = null;
                $campaignId = CampaignId::generate(); // TODO: use active campaign id
                
                // TODO: Recurring donations
                // if ($donation->getFrequency() === null){
                    $initiateDonation = new InitiateDonation(
                        $amount,
                        $campaignId,
                        $gateway,
                        // TODO: donor info if available
                    );
                    $redirectParams['donationId'] = $initiateDonation->donationId->toString();
                    $redirectRoute = 'donation_redirect';
                // } else {
                //     $interval = new RecurringInterval($donation->getFrequency());
                //     $createRecurringDonation = new CreateRecurringDonationByDonor(
                //         $campaignId,
                //         $amount,
                //         $interval,
                //         // TODO: donor info if available
                //     );
                //     $redirectParams['recurringDonationId'] = $createRecurringDonation->recurringDonationId->toString();
                // }
                
                $this->commandBus->dispatch($initiateDonation);
                
                $request->getSession()->remove('donation');
                
                return $this->redirectToRoute($redirectRoute, $redirectParams);
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

    private function getFormUrl(string $template, int $step): string {
        return $this->generateUrl('donation_' . $template, ['template' => $template, 'step' => $step]);
    }

    private function getDonationFormStep(int $step, DonationDto $donation, Request $request): FormInterface {
        if ($step === 1){
            return $this->createForm(
                DonationFormStep1Type::class,
                $donation,
                [
                    'currencies' => $this->formOptions->getCurrencies(),
                    'locale' => $request->getLocale(),
                    'frequencies' => $this->formOptions->getFrequencies(),
                ]);
        } else if ($step === 2){
            return $this->createForm(DonationFormStep2Type::class, $donation);
        } else if ($step === 3){
            $frequency = $donation->getFrequency();
            return $this->createForm(
                DonationFormStep3Type::class,
                $donation, 
                [
                    'frequency' => $frequency,
                    'gateways' => $this->formOptions->getGateways($frequency),
                ]);
        }
        throw new InvalidArgumentException('Unsupported form step ' . $step);
    }

    private function getDonationData(Request $request): DonationDto {
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

    private function getDefaultCampaign(): Campaign {
        $campaigns = $this->campaignRepository->findBy(['default' => true]);
        if (count($campaigns) === 0) {
            throw new InvalidArgumentException('No default campaign found');
        }
        if (count($campaigns) > 1) {
            throw new InvalidArgumentException('Multiple default campaigns found');
        }
        return $campaigns[0];
    }
}
