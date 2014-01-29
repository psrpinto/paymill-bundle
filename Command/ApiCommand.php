<?php

namespace Memeoirs\PaymillBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;

abstract class ApiCommand extends ContainerAwareCommand
{
    protected function getApi()
    {
        return $this->getContainer()->get('memeoirs_paymill.api');
    }

    protected function getResource($input, $output)
    {
        if ($input instanceof InputInterface) {
            $resourceName = $input->getArgument('resource');
        } else {
            // string
            $resourceName = $input;
        }

        $class = "\Paymill\Models\Request\\".ucfirst($resourceName);
        if (!class_exists($class)) {
            $output->writeln("<error>Unknown resource: $resourceName</error>");
            return null;
        }

        return new $class();
    }

    protected function getPayload($payload, $default)
    {
        $values = $default;
        if (!$payload) {
            return $values;
        }

        foreach (explode('&', $payload) as $param) {
            if (strpos($param, '=') !== false) {
                $value = explode('=', $param);
                if (strpos($value[1], ',') !== false) {
                    // comma-separated lists of values are transformed into arrays
                    $values[$value[0]] = explode(',', $value[1]);
                } else {
                    $values[$value[0]] = $value[1];
                }
            }
        }

        return $values;
    }

    protected function formatRow($item, $table)
    {
        $this->formatTableRow($item, $table);
    }

    protected function formatTableRow($data, $table, $filterArrays = true)
    {
        $cleanData = array();
        if ($filterArrays) {
            foreach ($data as $key => $value) {
                if (!is_array($value)) {
                    $cleanData[$key] = $value;
                }
            }
            $data = $cleanData;
        }

        foreach ($this->getHeaderFilters() as $filter) {
            unset($data[$filter]);
        }

        $table->setHeaders(array_keys($data));

        $row = array();
        foreach ($data as $key => $value) {
            $row[] = $this->formatValue($key, $value);
        }

        if (!empty($row)) {
            $table->addRow($row);
        }
    }

    protected function formatValue($key, $value)
    {
        if (in_array($key, array('created_at', 'updated_at'))) {
            $date = new \DateTime("@$value");
            return $date->format('Y-m-d H:i:s');
        } else if (in_array($key, array('amount', 'origin_amount'))) {
            return money_format("%2i", $value / 100);
        }

        return $value;
    }

    protected function getHeaderFilters()
    {
        return array('livemode', 'app_id');
    }
}
