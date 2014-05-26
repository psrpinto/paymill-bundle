<?php

namespace Memeoirs\PaymillBundle\API;

use Paymill\Request;

/**
 * Wrapper around Paymill's PHP API.
 */
class PaymillApi extends Request
{
    public function __construct ($apiKey)
    {
        parent::__construct($apiKey);
    }

    /**
     * Get the client for future requests.
     * If no client definition is found, the client will be set to null. A new
     * client will be created if none is found for the given 'email'.
     *
     * @param array $data Array containing a mandatory 'email' key and an
     *                    optional 'description' key.
     * @return string Client id
     * @throws PaymillException
     */
    public function getClient ($data)
    {
        if (!is_array($data) || !isset($data['email'])) {
            return null;
        }

        $client = new \Paymill\Models\Request\Client();
        $client->setFilter(array('email' => $data['email']));

        $client = $this->getAll($client);
        if ($client) {
            return $client[0]['id'];
        } else {
            // client not found, create a new one
            $client = new \Paymill\Models\Request\Client();
            $client
                ->setEmail($data['email'])
                ->setDescription(isset($data['description']) ? $data['description'] : null)
            ;

            $client = $this->create($client);
            return $client->getId();
        }
    }

    /**
     * Get the offer for future requests.
     * If no offer is found, it will be created
     *
     * @param string $name the offer name
     * @param string $currency
     * @param int $amount
     * @param string $interval
     * @access public
     * @return string the offer id
     */
    public function getOffer($name, $currency, $amount, $interval)
    {
        $offer = new \Paymill\Models\Request\Offer();
        $offer->setFilter(
            [
                'name' => $name,
                'currency' => $currency,
                'amount' => $amount,
                'interval' => $interval,
            ]
        );

        $offer = $this->getAll($offer);
        if ($offer) {
            return $offer[0]['id'];
        } else {
            // client not found, create a new one
            $offer = new \Paymill\Models\Request\Offer();
            $offer
                ->setAmount($amount)
                ->setCurrency($currency)
                ->setInterval($interval)
                ->setName($name);
            ;

            $offer = $this->create($offer);
            return $offer->getId();
        }
    }

    /**
     * get a payment for a user + token
     *
     * @param string $clientId
     * @param string $tokenId
     * @access public
     * @return string
     */
    public function getPayment($clientId, $tokenId)
    {
        $payment = new \Paymill\Models\Request\Payment();
        $payment->setToken($tokenId)
            ->setClient($clientId);

        $response = $this->create($payment);
        return $response->getId();
    }

    public function getSubscription($clientId, $offerId, $paymentId)
    {
        $client = new \Paymill\Models\Request\Client();
        $client->setId($clientId);
        $client = $this->getAll($client);

        $subscriptionId = null;
        if (!empty($client['subscription'])) {
            foreach ($client['subscription'] as $subscription) {
                if ($subscription['offer']['id'] == $offerId) {
                    $subscriptionId = $subscription['id'];
                    break;
                }
            }
        }

        $apiSubTransaction = new \Paymill\Models\Request\Subscription();
        $apiSubTransaction->setClient($clientId)
            ->setOffer($offerId)
            ->setPayment($paymentId);

        if ($subscriptionId !== null) {
            $apiSubTransaction->setId($subscriptionId);
            $apiSubTransaction = $this->update($apiSubTransaction);
        } else {
            $apiSubTransaction = $this->create($apiSubTransaction);
        }

        return $apiSubTransaction;
    }

    /**
     * getTransactionFromPayment
     *
     * This weird implementation is due to this bug:https://github.com/paymill/paymill-php/issues/69
     *
     * @param mixed $payment
     * @access public
     * @return void
     */
    public function getTransactionFromPayment($payment)
    {
        $paymillTransaction = new \Paymill\Models\Request\Transaction();
        $paymillTransaction->setPayment($payment);
        $apiTransactionList = $this->getAll($paymillTransaction);
        $apiTransaction = array_pop($apiTransactionList);

        $response = array(
            'header' => array(
                'status' => 200,
            ),
            'body' => array(
                'data' => $apiTransaction,
            ),
        );



        $handler = new \Paymill\Services\ResponseHandler;
        $response = $handler->convertResponse($response, 'Transaction/ ');

        return $response;
    }
}
