<?php

namespace Fm\PaymentPaymillBundle\Plugin;

use JMS\Payment\CoreBundle\Plugin\AbstractPlugin,
    JMS\Payment\CoreBundle\Model\FinancialTransactionInterface,
    JMS\Payment\CoreBundle\Plugin\PluginInterface,
    JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException,
    JMS\Payment\CoreBundle\Plugin\Exception\PaymentPendingException,
    JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;

class PaymillPlugin extends AbstractPlugin
{
    private $api;

    public function __construct ($apiPrivateKey)
    {
        $this->api = new PaymillApi($apiPrivateKey);
    }

    public function approveAndDeposit (FinancialTransactionInterface $transaction, $retry)
    {
        $data   = $transaction->getExtendedData();
        $client = $this->api->getClient($data->get('client'));

        $response = $this->api->createTransaction(
            $client,
            $data->get('token'),
            $transaction->getRequestedAmount() * 100, // in cents
            $transaction->getPayment()->getPaymentInstruction()->getCurrency(),
            $data->get('description')
        );

        $id     = $response['id'];
        $status = $response['status'];
        $amount = $response['amount'] / 100;

        switch ($status) {
            case 'closed':
                break;

            case 'open':
            case 'pending':
                $transaction->setReferenceNumber($id);
                throw new PaymentPendingException('Payment is still pending');
                break;

            default:
                $ex = new FinancialException('Transaction is not closed: '.$status);
                $ex->setFinancialTransaction($transaction);
                $transaction->setResponseCode('Failed');
                $transaction->setReasonCode($status);
                break;
        }

        $transaction->setReferenceNumber($id);
        $transaction->setProcessedAmount($amount);
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
    }

    public function processes ($name)
    {
        return 'paymill' === $name;
    }
}