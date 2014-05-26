<?php

namespace Memeoirs\PaymillBundle\Plugin;

use JMS\Payment\CoreBundle\Plugin\AbstractPlugin,
    JMS\Payment\CoreBundle\Model\FinancialTransactionInterface,
    JMS\Payment\CoreBundle\Plugin\PluginInterface,
    JMS\Payment\CoreBundle\Plugin\Exception\InvalidPaymentInstructionException,
    JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException,
    JMS\Payment\CoreBundle\Plugin\Exception\PaymentPendingException,
    JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;

use Paymill\Services\PaymillException;

class PaymillPlugin extends AbstractPlugin
{
    private $api;

    public function __construct ($api)
    {
        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     */
    public function approveAndDeposit (FinancialTransactionInterface $transaction, $retry)
    {
        try {
            $data   = $transaction->getExtendedData();

            if ($data->has('client')) {
                $client = $data->get('client');
                if (!empty($client['id'])) {
                    $client =  $client['id'];
                } else {
                    $client = $this->api->getClient($data->get('client'));
                }
            } else {
                $client = null;
            }
            $description = $data->has('description') ? $data->get('description') : null;
            $currency = $transaction->getPayment()->getPaymentInstruction()->getCurrency();
            $amount = $transaction->getRequestedAmount() * 100;

            if ($data->has('offer')) {
                // manage subscription
                if (!$client) {
                    throw new InvalidPaymentInstructionException('Client needs to be set for a subscription');
                }

                $offer = $data->get('offer');
                if (empty($offer['id']) && (empty($offer['name']) || empty($offer['interval']))) {
                    $msg = 'Offer id or name and interval needs to be set for a subscription';
                    throw new InvalidPaymentInstructionException($msg);
                }

                if (!empty($offer['id'])) {
                    $offer = $offer['id'];
                } else {
                    $offer = $this->api->getOffer($offer['name'], $currency, $amount, $offer['interval']);
                }
                $payment = $this->api->getPayment($client, $data->get('token'));

                $apiSubTransaction = $this->api->getSubscription($client, $offer, $payment);

                $apiTransaction = $this->api->getTransactionFromPayment($payment);

                if ($apiTransaction instanceof \Paymill\Models\Response\Error) {
                    $ex = new FinancialException('Transaction failed');
                    $ex->setFinancialTransaction($transaction);
                    $transaction->setResponseCode('Failed');
                    $transaction->setReasonCode($apiTransaction->getResponseCode());
                    throw $ex;
                }
            } else {
                $apiTransaction = new \Paymill\Models\Request\Transaction();
                $apiTransaction
                    ->setToken($data->get('token'))
                    ->setClient($client)
                    ->setAmount($amount) // in cents
                    ->setCurrency($currency)
                    ->setDescription($data->has('description') ? $data->get('description') : null)
                ;

                $apiTransaction = $this->api->create($apiTransaction);
            }


        } catch (PaymillException $e) {
            $ex = new FinancialException($e->getErrorMessage());
            $ex->setFinancialTransaction($transaction);
            $transaction->setResponseCode($e->getStatusCode());
            $transaction->setReasonCode($e->getResponseCode());
            throw $ex;
        }

        $status = $apiTransaction->getStatus();
        switch ($status) {
            case 'closed':
                $transaction->setReferenceNumber($apiTransaction->getId());
                $transaction->setProcessedAmount($apiTransaction->getAmount() / 100);
                $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
                break;

            case 'open':
            case 'pending':
                $ex = new PaymentPendingException('Payment is still pending');
                $transaction->setReferenceNumber($apiTransaction->getId());
                $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_PENDING);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
                $ex->setFinancialTransaction($transaction);
                throw $ex;

            default:
                $ex = new FinancialException('Transaction failed');
                $ex->setFinancialTransaction($transaction);
                $transaction->setResponseCode('Failed');
                $transaction->setReasonCode($apiTransaction->getResponseCode());
                throw $ex;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function processes ($name)
    {
        return 'paymill' === $name;
    }
}
