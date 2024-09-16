<?php

namespace ErgoSarapu\DonationBundle\Payum;

use ErgoSarapu\DonationBundle\Entity\Payment;
use ErgoSarapu\DonationBundle\Entity\Payment\Status;
use Payum\Core\Extension\Context;
use Payum\Core\Extension\ExtensionInterface;
use Payum\Core\Request\BaseGetStatus;

class UpdatePaymentStatusExtension implements ExtensionInterface
{

    public function onPreExecute(Context $context) { }

    public function onExecute(Context $context) { }

    public function onPostExecute(Context $context) {
        $request = $context->getRequest();
        
        if (!$request instanceof BaseGetStatus) {
            return;
        }

        $payment = $request->getFirstModel();

        if (!$payment instanceof Payment) {
            return;
        }

        // TODO: handle state machine properly
        $status = $this->getStatus($request->getValue());
        if ($payment->getStatus() !== $status){
            $payment->setStatus($status);
        }
    }

    private function getStatus(string $status): Status {
        if ($status === 'new') {
            return Status::Created;
        }
        if ($status === 'payedout') {
            return Status::Paid;
        }
        return Status::from($status);
    }
}
