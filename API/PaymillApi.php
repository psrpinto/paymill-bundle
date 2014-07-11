<?php

namespace Memeoirs\PaymillBundle\API;

use Paymill\Request;

/**
 * Wrapper around Paymill's PHP API.
 */
class PaymillApi extends Request
{
    public function __construct($apiKey)
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
     * @throws \Paymill\Services\PaymillException
     */
    public function getClient($data)
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
}