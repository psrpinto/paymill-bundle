<?php

namespace Memeoirs\PaymillBundle\Command\Webhook;

use Memeoirs\PaymillBundle\Command\ApiCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class WebhookCreateCommand extends ApiCommand
{
    protected function configure()
    {
        $this
            ->setName('paymill:webhook:create')
            ->setDescription('Create a URL or Email webhook')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command creates a new URL or Email webhook.
For more information about webhooks see Paymill's API documentation:
https://www.paymill.com/it-it/documentation-3/reference/api-reference/#document-webhooks

To create a URL webhook specify the <comment>--url</comment> option:
<info>%command.full_name% --url=https://myapp.com/some-paymil-webhook</info>

If instead you wish to create an Email webhook specify the <comment>--email</comment> option:
<info>%command.full_name% --email=payment@example.com</info>

You can specifiy the events that trigger this webhook using multiple <comment>--event</comment> options.
If no <comment>--event</comment> option is used, all events will be subscribed to. For the list of available event types see
https://www.paymill.com/it-it/documentation-3/reference/api-reference/#events
<info>%command.full_name% --url=... --event=transaction.succeeded --event=refund.succeeded</info>

To create an inactive webhook use the <comment>--disable</comment> option:
<info>%command.full_name% --url=... --disable</info>
EOT
            )
            ->addOption('url', '', InputOption::VALUE_OPTIONAL, 'The URL of the webhook')
            ->addOption('email', '', InputOption::VALUE_OPTIONAL, "The webhook's email address")
            ->addOption('disable', '', InputOption::VALUE_NONE, 'Can be used to create an inactive webhook in the beginning')
            ->addOption('event', '', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Set of webhook event types')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url     = $input->getOption('url');
        $email   = $input->getOption('email');
        $disable = $input->getOption('disable');
        $events  = $input->getOption('event');

        if (!$url && !$email) {
            $output->writeln("<error>Must specify either a url or an email address</error>");
            return;
        }

        if ($url && $email) {
            $output->writeln("<error>Must specify only a url or an email address</error>");
            return;
        }

        if (!$resource = $this->getResource('webhook', $output)) {
            return;
        }

        if ($url) {
            $resource->setUrl($url);
        } else {
            $resource->setEmail($email);
        }

        $resource->setEventTypes($events ? $events : Webhook::$events);

        $this->getApi()->create($resource);
        $resource = $this->getApi()->getLastResponse();
        $resource = $resource['body']['data'];

        $table = $this->getHelperSet()->get('table');
        $this->formatTableRow($resource, $table, false);

        $output->writeln("<info>Webhook created</info>");
        $table->render($output);
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
