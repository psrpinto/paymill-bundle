<?php

namespace Memeoirs\PaymillBundle\Command\Webhook;

use Memeoirs\PaymillBundle\Command\ApiCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class WebhookDeleteCommand extends ApiCommand
{
    protected function configure()
    {
        $this
            ->setName('paymill:webhook:delete')
            ->setDescription('Delete a webhook')
            ->addArgument(
                'ids',
                InputArgument::IS_ARRAY,
                'Webhook ids'
            )
            ->addOption(
                'force',
                '',
                InputOption::VALUE_NONE,
                'Force deletion'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ids = $input->getArgument('ids');

        if (!$resource = $this->getResource('webhook', $output)) {
            return;
        }

        if (!$input->getOption('force')) {
            $idsString = implode(', ', $ids);
            $output->writeln("<comment>Would have deleted webhooks $idsString. Use --force to actually delete</comment>");
            return;
        }

        foreach ($ids as $id) {
            $resource->setId($id);
            $this->getApi()->delete($resource);
        }

        $output->writeln("<info>Webhooks deleted</info>");
    }
}
