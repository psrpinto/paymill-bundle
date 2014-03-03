<?php

namespace Memeoirs\PaymillBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use JMS\Payment\CoreBundle\PluginController\Result;

abstract class PaymillController extends Controller
{
    private $errorMessages = array(
        10001 => "General undefined response.",
        10002 => "Still waiting on something.",
        20000 => "General success response.",
        40000 => "General problem with data.",
        40001 => "General problem with payment data.",
        40100 => "Problem with credit card data.",
        40101 => "Problem with cvv.",
        40102 => "Card expired or not yet valid.",
        40103 => "Limit exceeded.",
        40104 => "Card invalid.",
        40105 => "Expiry date not valid.",
        40106 => "Credit card brand required.",
        40200 => "Problem with bank account data.",
        40201 => "Bank account data combination mismatch.",
        40202 => "User authentication failed.",
        40300 => "Problem with 3d secure data.",
        40301 => "Currency / amount mismatch",
        40400 => "Problem with input data.",
        40401 => "Amount too low or zero.",
        40402 => "Usage field too long.",
        40403 => "Currency not allowed.",
        50000 => "General problem with backend.",
        50001 => "Country blacklisted.",
        50100 => "Technical error with credit card.",
        50101 => "Error limit exceeded.",
        50102 => "Card declined by authorization system.",
        50103 => "Manipulation or stolen card.",
        50104 => "Card restricted.",
        50105 => "Invalid card configuration data.",
        50200 => "Technical error with bank account.",
        50201 => "Card blacklisted.",
        50300 => "Technical error with 3D secure.",
        50400 => "Decline because of risk issues.",
        50500 => "General timeout.",
        50501 => "Timeout on side of the acquirer.",
        50502 => "Risk management transaction timeout.",
        50600 => "Duplicate transaction.",
    );

    protected function getPaymillForm($amount, $currency, $options = array())
    {
        return $this->get('form.factory')->create('jms_choose_payment_method', null, array(
            'allowed_methods' => array('paymill'),
            'default_method'  => 'paymill',
            'amount'          => $amount,
            'currency'        => $currency,
            'predefined_data' => array(
                'paymill' => $options
            ),
        ));
    }

    protected function createPaymentInstruction($form)
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
     * @param PaymentIsntruction $instrcution PaymentInstruction instance
     * @param string $successRoute The name of the route to redirect the user
     *                             when payment is successful
     * @param array $routeParams   The params to construct the url from the route
     */
    protected function completePayment ($instruction, $route, $routeParams)
    {
        $ppc = $this->get('payment.plugin_controller');

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
                'message' => 'Payment failed.'
            );

            // We might have a better error message
            $responseCode = $result->getFinancialTransaction()->getResponseCode();
            if (null !== $responseCode && isset($this->errorMessages[$responseCode])) {
                $response['message'] = $this->errorMessages[$responseCode];
            }
        }

        return new JsonResponse($response);
    }
}