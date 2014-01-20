<?php

namespace Memeoirs\PaymillBundle\Plugin;

use \Exception;

class PaymillException extends Exception
{
    private $statusCode   = null;
    private $responseCode = null;

    public function __construct ($response)
    {
        if (isset($response['http_status_code']) && !empty($response['http_status_code'])) {
            $this->statusCode = $response['http_status_code'];
        }

        if (isset($response['response_code']) && !empty($response['response_code'])) {
            $this->responseCode = $response['response_code'];
        }

        $error         = $response['error'];
        $this->message = "API request failed";

        if (isset($error['messages']) && is_array($error['messages'])
                && !empty($error['messages'])) {
            foreach ($error['messages'] as $key => $value) {
                 $this->message .= ' - '.$value;
                 break;
            }

            if (isset($error['field'])) {
                $this->message .= ': "'.$error['field'].'"';
            }

        } else if (!is_array($error)) {
            $this->message .= $error;
        }

        if ($this->responseCode !== null) {
            $this->message .= ' [response code = '.$this->responseCode.']';
        }

        if ($this->statusCode !== null) {
            $this->message .= ' [HTTP status code = '.$this->statusCode.']';
        }
    }

    public function getStatusCode ()
    {
        return $this->statusCode;
    }

    public function getResponseCode ()
    {
        return $this->responseCode;
    }
}