<?php

namespace Memeoirs\PaymillBundle\Templating;

use Symfony\Component\Templating\Helper\Helper,
    Symfony\Component\Templating\EngineInterface;

class PaymillHelper extends Helper
{
    private $engine   = null;
    private $template = null;

    public function __construct(EngineInterface $engine, $template)
    {
        $this->engine   = $engine;
        $this->template = $template;
    }

    /**
     * Render the Paymill initialization markup.
     *
     * @param  array $options Array containing the amount and currency
     * @return string
     */
    public function initialize(array $options)
    {
        return $this->engine->render($this->template, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'paymill';
    }

}