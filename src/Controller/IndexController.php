<?php

namespace ErgoSarapu\DonationBundle\Controller;

use ErgoSarapu\DonationBundle\Dto\DonationDto;
use ErgoSarapu\DonationBundle\Dto\MoneyDto;
use ErgoSarapu\DonationBundle\Entity\Campaign;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use ErgoSarapu\DonationBundle\Form\DonationFormStep1Type;
use ErgoSarapu\DonationBundle\Form\DonationFormStep2Type;
use ErgoSarapu\DonationBundle\Form\DonationFormStep3Type;
use ErgoSarapu\DonationBundle\Payum\PayumPaymentProvider;
use ErgoSarapu\DonationBundle\Repository\CampaignRepository;
use InvalidArgumentException;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends AbstractController
{

    public function __construct(private PayumPaymentProvider $provider, private readonly CampaignRepository $campaignRepository)
    {
    }

    public function __invoke(
        Request $request, int $step = 1): Response
    {
        $campaign = $this->getDefaultCampaign();
        $donation = $this->getDonationData($request);
        $formAction  = $this->getFormAction($step);

        $form = $this->getDonationFormStep($step, $donation, $formAction);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DonationDto $donation */
            $donation = $form->getData();
            
            if ($step === 3) {
                /** @var DonationDto $donation */
                $donation = $form->getData();

                $gatewayName = $donation->getGateway();
                
                $payment = $this->provider->createPayment();
                $payment->setStatus(Status::Created);
                $payment->setNumber(uniqid());
                $payment->setCurrencyCode($donation->getAmount()->currency);
                $payment->setTotalAmount($donation->getAmount()->amount);
                $payment->setDescription(sprintf('%s;%s', $payment->getNumber(), $campaign->getPublicId()));
                $payment->setClientId(null);
                $payment->setClientEmail($donation->getEmail());
                $payment->setGivenName($donation->getGivenName());
                $payment->setFamilyName($donation->getFamilyName());
                $payment->setNationalIdCode($donation->getNationalIdCode());
                $payment->setCampaign($campaign);
                
                $this->provider->updatePayment($payment);
                            
                $targetUrl = $this->provider->createCaptureTargetUrl($gatewayName, $payment, 'donation_payment_done');
                
                $request->getSession()->remove('donation');

                return $this->redirect($targetUrl);
            }

            $request->getSession()->set('donation', $donation);

            return $this->redirectToRoute('donation_index', ['step' => $step + 1]);
        }


        return $this->render('@Donation/landing.html.twig', [
            'form' => $form,
            'donationData' => $donation,
            'campaign' => $campaign,
            'step' => $step,
            'action' => $formAction
        ]);
    }

    private function getFormAction(int $step): string {
        return  $this->generateUrl('donation_index', ['step' => $step]);
    }

    private function getDonationFormStep(int $step, DonationDto $donation, string $formAction): FormInterface {
        $options = [
            'action' => $formAction,
            'payments_config' => $this->provider->getPaymentsConfig(),
        ];
        if ($step === 1){
            return $this->createForm(DonationFormStep1Type::class, $donation, $options);
        } else if ($step === 2){
            return $this->createForm(DonationFormStep2Type::class, $donation, $options);
        } else if ($step === 3){
            return $this->createForm(DonationFormStep3Type::class, $donation, $options);
        }
        throw new InvalidArgumentException('Unsupported form step ' . $step);
    }
    private function getDonationData(Request $request): DonationDto {
        $session = $request->getSession();
        $donation = $session->get('donation');
        if ($donation === null) {
            $donation = new DonationDto();

            // Set initial default value
            $donation->setAmount(MoneyDto::fromMoney(Money::EUR(2500)));
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
