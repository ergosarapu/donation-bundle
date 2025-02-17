<?php

namespace ErgoSarapu\DonationBundle\Payum;

use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Subscription\Status;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetHumanStatus;

class ActivateSubscriptionExtension implements ExtensionInterface
{

    public function onPreExecute(Context $context) { }

    public function onExecute(Context $context) {
    }

    public function onPostExecute(Context $context) {
        $request = $context->getRequest();
        if (!$request instanceof Generic) {
            return;
        }

        $model = $request->getModel();
        if (!$model instanceof ArrayObject) {
            return;
        }
        
        $payment = $request->getFirstModel();
        if (!$payment instanceof Payment) {
            return;
        }

        $subscription = $payment->getSubscription();
        if ($subscription === null) {
            return;
        }

        // TODO: Safe to compare by reference?
        if ($subscription->getInitialPayment() !== $payment){
            return;
        }

        if ($subscription->getStatus() !== Status::Created){
            // Only allow activating subscription when it is in Created status
            return;
        }

        $context->getGateway()->execute($status = new GetHumanStatus($model));
        if ($status->isCaptured()) {
            // Only activate subscription when payment is captured
            $subscription->setStatus(Status::Active);
        }
    }

}
