<?php

namespace Memeoirs\PaymillBundle\Controller;

use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\PluginController\Result;

class WebhooksController extends Controller
{
    private $supportedEvents = array(
        'transaction.succeeded',
        'refund.succeeded'
    );

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function hookAction(Request $request)
    {
        $log = $this->get('logger');
        $ppc = $this->get('payment.plugin_controller');
        $response = new Response;

        $data = $request->getContent();
        if (empty($data)) {
            $log->error('Empty Paymill event. Ignoring.');
            return $response;
        }

        $data = json_decode($data, true);
        if (!isset($data['event']) || !isset($data['event']['event_type']) ||
            !isset($data['event']['event_resource']['id'])) {
            $log->error('Invalid Paymill event. Ignoring.');
            return $response;
        }

        $event     = $data['event'];
        $eventType = $event['event_type'];

        if (!in_array($eventType, $this->supportedEvents)) {
            $log->warning("Unsupported event type $eventType. Ignoring.");
            return $response;
        }

        $instruction = null;
        $oldName = null;
        try {
            $payment = $this->getPayment($event);
            $instruction = $payment->getPaymentInstruction();

            // We temporarily change the value of "payment_system_name" in the
            // PaymentInstruction to 'paymill_event'. The original value is reset
            // once the event has been handled. This is done in order to workaround
            // the internal architecture of JMSPaymentCore. For information on why
            // this is needed see
            // https://github.com/schmittjoh/JMSPaymentPaypalBundle/issues/56
            $oldName = $instruction->getPaymentSystemName();
            $this->setPaymentSystemName($instruction, 'paymill_event');

            $result = null;
            switch ($event['event_type']) {
            // The payment has been completed and the funds have been added to
            // the merchants's account balance.
            case 'transaction.succeeded':
                if ($payment->getState() === PaymentInterface::STATE_NEW ||
                    $payment->getState() === PaymentInterface::STATE_APPROVING) {
                    $result = $ppc->approveAndDeposit($payment->getId(), $event['amount']);
                }
                break;

            // The seller refunded the payment
            case 'refund.succeeded':
                // The Payment must have state APPROVED in order for JMSPaymentCore
                // to accept a credit. Since at this point the payment has state
                // DEPOSITED, we set it to APPROVED and re-set it back after the
                // credit was created.
                $oldState = $payment->getState();
                $this->setPaymentState($payment, PaymentInterface::STATE_APPROVED);

                try {
                    $amount = $event['event_resource']['amount']/100;
                    $refundId = $event['event_resource']['id'];
                    $this->createRefund($payment, $amount, $refundId);
                } catch (Exception $e) {
                    $this->setPaymentState($payment, $oldState);
                    throw $e;
                }

                $this->setPaymentState($payment, $oldState);
                break;
            }

            if ($result && $result->getStatus() !== Result::STATUS_SUCCESS) {
                $log->error('Transaction was not successful: '.$result->getReasonCode());
            }

        } catch (\Exception $e) {
            $log->error("Failed to process event: ". $e->getMessage());
        }

        // Reset the original value
        $this->setPaymentSystemName($instruction, $oldName);

        return $response;
    }

    private function getPayment($event)
    {
        switch ($event['event_type']) {
        case 'transaction.succeeded':
            $transactionId = $event['event_resource']['id'];
            break;
        case 'refund.succeeded':
            $transactionId = $event['event_resource']['transaction']['id'];
            break;
        }

        $repository = $this->getDoctrine()->getManager()
            ->getRepository('JMS\Payment\CoreBundle\Entity\FinancialTransaction');
        $transaction = $repository->findOneBy(array(
            'referenceNumber' => $transactionId
        ));

        if (!$transaction) {
            throw new \Exception("Transaction not found: $transactionId");
        }

        $payment = $transaction->getPayment();
        if (!$payment) {
            throw new \Exception("No Payment is associated with the Transaction: $resourceId");
        }

        return $payment;
    }

    private function createRefund($payment, $amount, $refundId)
    {
        $em = $this->getDoctrine()->getManager();
        $ppc = $this->get('payment.plugin_controller');
        $repository = $em->getRepository('JMS\Payment\CoreBundle\Entity\FinancialTransaction');

        $credit = $ppc->createDependentCredit($payment->getId(), $amount);
        $result = $ppc->credit($credit->getId(), $amount);

        // set the reference number in the newly created transaction
        $newTransaction = $repository->findOneBy(array('credit' => $credit));
        if ($newTransaction) {
            $newTransaction->setReferenceNumber($refundId);
            $em->flush($newTransaction);
        }
    }

    /**
     * Set payment_system_name on a payment instruction associated with a given Payment.
     *
     * @param PaymentInstructionInterface $instruction PaymentInstruction entity
     * @param string $name The new value for payment_system_name
     */
    private function setPaymentSystemName(PaymentInstructionInterface $instruction, $name)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();

        // We're forced to set the new value directly in the DB because the
        // PaymentInstruction Entity does not define a setter for paymentSystemName.
        $em->createQuery("
                UPDATE JMS\Payment\CoreBundle\Entity\PaymentInstruction pi
                SET pi.paymentSystemName = :psm
                WHERE pi = :pi")
            ->setParameter('psm', $name)
            ->setParameter('pi', $instruction)
            ->getResult();

        $em->getConnection()->commit();
        $em->clear();
    }

    /**
     * Set state on a given Payment.
     *
     * @param PaymentInterface $payment Payment entity
     * @param integer $state   New state
     */
    private function setPaymentState(PaymentInterface $payment, $state)
    {
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();

        $em->createQuery("
                UPDATE JMS\Payment\CoreBundle\Entity\Payment p
                SET p.state = :state
                WHERE p = :p")
            ->setParameter('state', $state)
            ->setParameter('p', $payment)
            ->getResult();

        $em->getConnection()->commit();
        $em->clear();
    }
}
