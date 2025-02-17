<?php

namespace ErgoSarapu\DonationBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Entity\Campaign;
use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status as PaymentStatus;
use ErgoSarapu\DonationBundle\Form\DonationFormStep1Type;
use ErgoSarapu\DonationBundle\Form\DonationFormStep2Type;
use ErgoSarapu\DonationBundle\Form\DonationFormStep3Type;
use ErgoSarapu\DonationBundle\Form\FormOptionsProvider;
use ErgoSarapu\DonationBundle\Repository\CampaignRepository;
use ErgoSarapu\DonationBundle\Subscription\SubscriptionManager;
use InvalidArgumentException;
use Payum\Core\Payum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{

    public function __construct(
        private FormOptionsProvider $formOptions,
        private readonly CampaignRepository $campaignRepository,
        private ?Payum $payum,
        private readonly EntityManagerInterface $entityManager,
        private readonly SubscriptionManager $subscriptionManager)
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

                $gatewayName = $donation->getGateway();
                
                $payment = $this->payum->getStorage(Payment::class)->create();
                $payment->setStatus(PaymentStatus::Created);
                $payment->setNumber(uniqid(true));
                $payment->setCurrencyCode($donation->getCurrencyCode());
                $payment->setTotalAmount($donation->getAmount());
                $payment->setDescription(sprintf('%s;%s', $payment->getNumber(), $campaign->getPublicId()));
                $payment->setClientId(null);
                $payment->setClientEmail($donation->getEmail());
                $payment->setGivenName($donation->getGivenName());
                $payment->setFamilyName($donation->getFamilyName());
                $payment->setNationalIdCode($donation->getNationalIdCode());
                $payment->setCampaign($campaign);
                $payment->setGateway($gatewayName);
                
                $this->payum->getStorage(Payment::class)->update($payment);
                
                $targetUrl = $this->payum->getTokenFactory()->createCaptureToken(
                    $gatewayName, 
                    $payment,
                    'donation_payment_done' // the route to redirect after capture
                )->getTargetUrl();
                
                // If frequency is set then create subscription
                if ($donation->getFrequency() !== null) {
                    $subscription = $this->subscriptionManager->createSubscription($payment, $donation->getFrequency());
                    $this->entityManager->persist($subscription);
                    $this->entityManager->flush();
                }

                $request->getSession()->remove('donation');
                
                return $this->redirectToRoute('donation_payment_redirect', ['targetUrl' => $targetUrl]);
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
