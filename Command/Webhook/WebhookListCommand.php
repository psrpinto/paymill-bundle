<?php

namespace Memeoirs\PaymillBundle\Command\Webhook;

use Memeoirs\PaymillBundle\Command\ListCommand;
use Symfony\Component\Console\Input\InputArgument;

class WebhookListCommand extends ListCommand
{
    protected function configure()
    {
        $this
            ->setName('paymill:webhook:list')
            ->setDescription('List existing webhooks')
            ->addArgument(
                'filters',
                InputArgument::OPTIONAL,
                'Filters to apply in the form of an HTTP query string'
            )
        ;
    }

    protected function getResource($input, $output)
    {
        return parent::getResource('webhook', $output);
    }

    protected function formatRow($item, $table)
    {
        $this->formatTableRow($item, $table, false);
    }

    protected function formatValue($key, $value)
    {
        $formatted = Webhook::formatValue($key, $value);
        if ($formatted) {
            return $formatted;
        }

        return parent::formatValue($key, $value, false);
    }
}
