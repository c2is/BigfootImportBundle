<?php

namespace Bigfoot\Bundle\ImportBundle\Model;

use Exception;
use Bigfoot\Bundle\ImportBundle\Services\DataMapper\Data;

/**
 * Class AbstractSimpleDataMapper
 * @Author S.huot s.huot@c2is.fr
 */
abstract class AbstractSimpleDataMapper
{
    public $className;
    protected $data;

    protected $container;
    protected $nbLigneLot;

    /**
     * Constructor of the class
     * @param $container Array Dependency injection
     * @param null $nbLigneLot Integer Number of the line per batch
     */
    public function __construct($container, $nbLigneLot = null)
    {
        $this->container = $container;
        $this->nbLigneLot = $nbLigneLot;
    }

    /**
     * Set the parameter $data with an object of the parsing file
     *
     * @param Data $data Object Instance of the class Data
     */
    public function setData(Data $data)
    {
        $this->data = $data;
    }

    /**
     * Mapping function
     * @return mixed
     */
    abstract protected function getColumnMap();

    /**
     * Get an object of the different elements by a line
     *
     * @param $className String Name of the entity
     * @param $line Integer Index of the line
     * @return mixed
     */
    abstract protected function getObject($className, $line);

    /**
     * Coding function depends of the coding of your file
     *
     * @param $value String Value in your Csv file
     * @return string
     */
    abstract protected function getEncodedValue($value);

    /**
     * Parsing function. If you specify the parameter 'nb_ligne_par_lot' in your app/config.yml, the parsing will be by batch.
     * @return bool
     * @throws \Exception
     */
    public function map()
    {

        set_time_limit($this->container->getParameter('import.max_execution_time'));

        $em = $this->container->get('doctrine.orm.default_entity_manager');

        $cpt = 1;

        $tabData  = $this->data->getData();
        $sizeData = sizeof($this->data->getData());

        foreach ($tabData as $line) {

            $object = $this->getObject($this->className, $line);

            foreach ($this->getColumnMap() as $head => $setter) {

                if (!method_exists($object, $setter)) {
                    throw new Exception(sprintf("%s method does not exist", $setter), 1);
                }

                $value = $line[$this->data->getIndexOfHead($head)];

                $value = $this->getEncodedValue($value);

                $object->$setter($value);
            }

            $em->persist($object);

            if (!is_null($this->nbLigneLot) && ($cpt > 0 && ($cpt % $this->nbLigneLot == 0)) || ($cpt == $sizeData && ($cpt % $this->nbLigneLot != 0))) {
                $em->flush();
            }

            $cpt++;
        }

        if (is_null($this->nbLigneLot)) {
            $em->flush();
        }

        return true;
    }
}
