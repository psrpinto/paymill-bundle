<?php

namespace Memeoirs\PaymillBundle\Controller;

use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use JMS\Payment\CoreBundle\PluginController\Result;

abstract class PaymillController extends Controller
{
    protected function getPaymillForm($amount, $currency, $data = array(), $options = array())
    {
        $options = array_merge(array(
            'allowed_methods' => array('paymill'),
            'default_method'  => 'paymill',
            'amount'          => $amount,
            'currency'        => $currency,
            'predefined_data' => array(
                'paymill' => $data,
            )
        ), $options);

        return $this->get('form.factory')->create('jms_choose_payment_method', null, $options);
    }

    /**
     * Create the Payment instruction
     *
     * @param FormInterface $form
     *
     * @return PaymentInstructionInterface
     */
    protected function createPaymentInstruction(FormInterface $form)
    {
        $ppc = $this->get('payment.plugin_controller');
        $instruction = $form->getData();
        $ppc->createPaymentInstruction($instruction);

        return $instruction;
    }

    /**
     * Complete a payment by creating a transaction using Paymill's API, i.e.
     * call JMSPaymentCore's approveAndDeposit method.
     *
     * @param PaymentInstructionInterface $instruction PaymentInstruction instance
     * @param string $successRoute The name of the route to redirect the user
     *                             when payment is successful
     * @param array $routeParams   The params to construct the url from the route
     *
     * @return JsonResponse
     */
    protected function completePayment(PaymentInstructionInterface $instruction, $route, $routeParams = array())
    {
        $ppc        = $this->get('payment.plugin_controller');
        $translator = $this->get('translator');

        if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
            $amount = $instruction->getAmount() - $instruction->getDepositedAmount();
            $payment = $ppc->createPayment($instruction->getId(), $amount);
        } else {
            $payment = $pendingTransaction->getPayment();
        }

        $result = $ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
        if (Result::STATUS_SUCCESS === $result->getStatus()) {
            // payment was successful
            $response = array(
                'error' => false,
                'successUrl' => $this->generateUrl($route, $routeParams)
            );

        } else {
            $response = array(
                'error' => true,
                'message' => $translator->trans('default', array(), 'errors'),
                'code' => $result->getFinancialTransaction()->getReasonCode()
            );

            // We might have a better error message
            if (null !== $response['code']) {
                $translated = $translator->trans($response['code'], array(), 'errors');
                if ($translated != $response['code']) {
                    $response['message'] = $translated;
                }
            }
        }

        return new JsonResponse($response);
    }
}
