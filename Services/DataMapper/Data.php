<?php

namespace Bigfoot\Bundle\ImportBundle\Services\DataMapper;

use Exception;

/**
 * Class Data used to stock the content of your Csv file
 * @Author S.huot s.huot@c2is.fr
 */
class Data
{
    protected $head;

    protected $data;

    /**
     * Set the header of your Csv File
     *
     * @param array $head Array of the head elements of your Csv file
     */
    public function setHead(array $head)
    {
        $this->head = $head;
    }

    /**
     * Get the header of your Csv File
     *
     * @return mixed
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * Get the index of an element of the header by his name
     *
     * @param $value String Name of the element
     * @return mixed
     * @throws \Exception
     */
    public function getIndexOfHead($value)
    {

        $value = utf8_decode($value);

        $index = array_search($value, $this->head);

        if ($index === false) {
            throw new Exception(sprintf("%s head is not defined", $value), 1);
        }

        return $index;
    }

    /**
     * Add a line to the data object
     *
     * @param array $data Line of the Csv file
     */
    public function addData(array $data)
    {
        $this->data[] = $data;
    }

    /**
     * Get the entire content of the Csv file
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
