<?php

namespace Memeoirs\PaymillBundle\Plugin;

use JMS\Payment\CoreBundle\Plugin\AbstractPlugin,
    JMS\Payment\CoreBundle\Model\FinancialTransactionInterface,
    JMS\Payment\CoreBundle\Plugin\PluginInterface;

/**
 * Dummy Plugin for JMSPaymentCoreBundle.
 * This plugin performs no interaction with Paymill's API.
 */
class PaymillEventPlugin extends AbstractPlugin
{
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->process($transaction);
    }

    public function credit(FinancialTransactionInterface $transaction, $retry)
    {
        $this->process($transaction);
    }

    private function process(FinancialTransactionInterface $transaction)
    {
        $transaction->setProcessedAmount($transaction->getRequestedAmount());
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
    }

    public function processes ($name)
    {
        return 'paymill_event' === $name;
    }
}