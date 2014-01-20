<?php

namespace Memeoirs\PaymillBundle\Plugin;

use JMS\Payment\CoreBundle\Plugin\AbstractPlugin,
    JMS\Payment\CoreBundle\Model\FinancialTransactionInterface,
    JMS\Payment\CoreBundle\Plugin\PluginInterface,
    JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException,
    JMS\Payment\CoreBundle\Plugin\Exception\PaymentPendingException,
    JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;

class PaymillPlugin extends AbstractPlugin
{
    private $api;

    public function __construct ($privateKey)
    {
        $this->api = new PaymillApi($privateKey);
    }

    /**
     * {@inheritDoc}
     */
    public function approveAndDeposit (FinancialTransactionInterface $transaction, $retry)
    {
        try {
            $data   = $transaction->getExtendedData();
            $client = $this->api->getClient($data->has('client') ? $data->get('client') : null);

            $response = $this->api->createTransaction(
                $client,
                $data->get('token'),
                $transaction->getRequestedAmount() * 100, // in cents
                $transaction->getPayment()->getPaymentInstruction()->getCurrency(),
                $data->has('description') ? $data->get('description') : null
            );

        } catch (PaymillException $e) {
            $ex = new FinancialException($e->getMessage());
            $ex->setFinancialTransaction($transaction);
            $transaction->setResponseCode($e->getResponseCode());
            $transaction->setReasonCode($e->getStatusCode());
            throw $ex;
        }

        $id     = $response['id'];
        $status = $response['status'];
        $amount = $response['amount'] / 100;

        switch ($status) {
            case 'closed':
                $transaction->setReferenceNumber($id);
                $transaction->setProcessedAmount($amount);
                $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
                break;

            case 'open':
            case 'pending':
                $transaction->setReferenceNumber($id);
                throw new PaymentPendingException('Payment is still pending');

            default:
                $ex = new FinancialException('Transaction is not closed: '.$status);
                $ex->setFinancialTransaction($transaction);
                $transaction->setResponseCode('Failed');
                $transaction->setReasonCode($status);
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