<?php

namespace Memeoirs\PaymillBundle\Plugin;

use JMS\Payment\CoreBundle\Plugin\AbstractPlugin,
    JMS\Payment\CoreBundle\Model\FinancialTransactionInterface,
    JMS\Payment\CoreBundle\Plugin\PluginInterface,
    JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException,
    JMS\Payment\CoreBundle\Plugin\Exception\PaymentPendingException,
    JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;

use Memeoirs\PaymillBundle\API\PaymillApi;
use Paymill\Services\PaymillException;

class PaymillPlugin extends AbstractPlugin
{
    /**
     * @var \Memeoirs\PaymillBundle\API\PaymillApi api
     */
    private $api;

    /**
     * @param PaymillApi $api
     */
    public function __construct(PaymillApi $api)
    {
        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        try {
            $data   = $transaction->getExtendedData();
            $client = $this->api->getClient($data->has('client') ? $data->get('client') : null);

            $apiTransaction = new \Paymill\Models\Request\Transaction();
            $apiTransaction
                ->setToken($data->get('token'))
                ->setClient($client)
                ->setAmount($transaction->getRequestedAmount() * 100) // in cents
                ->setCurrency($transaction->getPayment()->getPaymentInstruction()->getCurrency())
                ->setDescription($data->has('description') ? $data->get('description') : null)
            ;

            $apiTransaction = $this->api->create($apiTransaction);

        } catch (PaymillException $e) {
            $ex = new FinancialException($e->getErrorMessage());
            $ex->setFinancialTransaction($transaction);
            $transaction->setResponseCode($e->getStatusCode());
            $transaction->setReasonCode($e->getResponseCode());
            throw $ex;
        }

        switch ($apiTransaction->getStatus()) {
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
    public function processes($name)
    {
        return 'paymill' === $name;
    }
}
