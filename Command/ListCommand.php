<?php

namespace Memeoirs\PaymillBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ListCommand extends ApiCommand
{
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$resource = $this->getResource($input, $output)) {
            return;
        }

        $resource->setFilter($this->getPayload($input->getArgument('filters'), array(
            'order' => 'created_at_desc',
            'count' => 10,
        )));

        $results = $this->getApi()->getAll($resource);
        if (empty($results)) {
            $output->writeln('<comment>No results were found</comment>');

            return;
        }

        $table = $this->getHelperSet()->get('table');
        foreach ($results as $item) {
            $this->formatRow($item, $table);
        }
        $table->render($output);
    }

    protected function getResource($input, $output)
    {
        return parent::getResource($input, $output);
    }
}
