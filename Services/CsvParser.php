<?php

namespace Bigfoot\Bundle\ImportBundle\Services;

use Bigfoot\Bundle\ImportBundle\Services\DataMapper\Data;

/**
 * CSV Parser class
 *
 * In order to parse your csv file, you have to specify a FTP client and a delimiter.
 *
 * @Author S.huot s.huot@c2is.fr
 */
class CsvParser
{
    protected $client;

    protected $delimiter = ',';

    /**
     * Initialize the client
     * @param $client Object Instance of the class Client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * Delimiter used in your CSV file
     *
     * @param $delimiter String
     * @return $this
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * Parsing function
     *
     * @param $uri String Name of your Csv file
     * @return Data
     */
    public function parse($uri)
    {
        $filename = $this->client->get($uri);

        $csvData = file_get_contents($filename);
        $csvLines = explode("\n", $csvData);

        $data = new Data();
        $data->setHead(str_getcsv(array_shift($csvLines), $this->delimiter));

        foreach ($csvLines as $line) {
            if ($line != '') {
                $data->addData(str_getcsv($line, $this->delimiter));
            }
        }

        return $data;
    }
}
