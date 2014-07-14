<?php

namespace Memeoirs\PaymillBundle\Templating;

use \Twig_Extension as TwigExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymillExtension extends TwigExtension
{
    private $publicKey;
    private $container;

    /**
     * @param ContainerInterface $container
     * @param string $publicKey
     */
    public function __construct(ContainerInterface $container, $publicKey)
    {
        $this->container = $container;
        $this->publicKey = $publicKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        return array('paymill' => array(
            'public_key' => $this->publicKey
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'paymill_initialize' => new \Twig_Function_Method(
                $this, 'renderInitialize', array('is_safe' => array('html'))
            ),
        );
    }

    /**
     * Render the Paymill initialization markup.
     *
     * @param integer $amount   Amount
     * @param string  $currency Currency
     * @return string
     */
    public function renderInitialize($amount, $currency)
    {
        return $this->container->get('memeoirs_paymill.helper')->initialize(array(
            'amount'   => $amount,
            'currency' => $currency
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'paymill';
    }
}