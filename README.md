ImportBundle
=============

ImportBundle is part of the framework BigFoot created by C2IS.


Installation
------------

Add 'BigFoot/ImportBundle' into your composer.json file in the 'require' section:

``` json
"require": {
    ...
    ...
    "bigfoot/import-bundle": "dev-master",
}
```

Update your project:

``` shell
php composer.phar update
```

Create your specific import bundle (ie: BigfootQualitelisBundle):

``` shell
app/console generate:update
```

Generate your entity/ies based on the different fields of your csv file (ie: QualitelisNote):

``` shell
app/console generate:doctrine:entity
```

Then update your database:

``` shell
app/console doctrine:schema:update --force
```

Create a directory 'Services' in the root directory of your bundle then create a class file which extends the model 'AbstractSimpleDataMapper'.

``` php
/* Services/QualitelisNotesDataMapper.php */
class QualitelisNotesDataMapper extends AbstractSimpleDataMapper
{
    ...
}
```

Create some constants for each field you want to import. Their values must be the same as the header's csv.

``` php
/* Services/QualitelisNotesDataMapper.php */
class QualitelisNotesDataMapper extends AbstractSimpleDataMapper
{
    const FIELD_1           = 'header_field_1';
    const FIELD_2           = 'header_field_2';
}
```

Associate the constants to your repository setters:

``` php
/* Services/QualitelisNotesDataMapper.php */
class QualitelisNotesDataMapper extends AbstractSimpleDataMapper
{
    const FIELD_1           = 'header_field_1';
    const FIELD_2           = 'header_field_2';

    protected function getColumnMap()
    {
        return array(

            self::FIELD_1       => 'setField1',
            self::FIELD_2       => 'setField2',

            ...
            ...


        );
    }
}
```

Set the coding of your csv file, for instance in UTF8:

``` php
/* Services/QualitelisNotesDataMapper.php */
class QualitelisNotesDataMapper extends AbstractSimpleDataMapper
{
    const FIELD_1           = 'header_field_1';
    const FIELD_2           = 'header_field_2';

    protected function getColumnMap()
    {
        return array(

            self::FIELD_1       => 'setField1',
            self::FIELD_2       => 'setField2',

            ...
            ...


        );
    }

    protected function getEncodedValue($value) {
        return utf8_encode($value);
    }
}
```

Set the import parameters in your config file:

    - nb_ligne_par_lot /ftp / csv = number of lines per batch
    - max_execution_time = avoid the time out


``` yml
# app/config/config.yml
...

bigfoot_import:
    nb_ligne_par_lot :
        ftp :
            csv : 10
    max_execution_time : 500
```

Set the namespace of your bundle and create a service from your mapping class:

``` yml
# Resources/config/services.yml

parameters:
    bigfoot_qualitelis.note_datamapper.class: 'Bigfoot\Bundle\QualitelisBundle\Services\QualitelisNotesDataMapper'
    bigfoot_qualitelis.namespace: 'Bigfoot\Bundle\QualitelisBundle\Entity\'

services:
    bigfoot_qualitelis.note_datamapper:
        class: '%bigfoot_qualitelis.note_datamapper.class%'
        arguments: [@service_container, '%nb_ligne_par_lot.ftp.csv%', '%bigfoot_qualitelis.namespace%']
```

Set the ID key in the method 'getObject' (here the key is FIELD_1):

``` php
/* Services/QualitelisNotesDataMapper.php */
class QualitelisNotesDataMapper extends AbstractSimpleDataMapper
{
    const FIELD_1           = 'header_field_1';
    const FIELD_2           = 'header_field_2';

    protected function getColumnMap()
    {
        return array(

            self::FIELD_1       => 'setField1',
            self::FIELD_2       => 'setField2',

            ...
            ...


        );
    }

    protected function getEncodedValue($value) {
        return utf8_encode($value);
    }

    protected function getObject($className, $line)
    {

        $em = $this->container->get('doctrine.orm.default_entity_manager');

        $qualitelis_namespace = $this->container->getParameter('bigfoot_qualitelis.namespace');


        $object = $em->getRepository($qualitelis_namespace.$className)->findOneBy(array(self::FIELD_1 => $line[$this->data->getIndexOfHead(self::FIELD_1)]));

        if (!$object) {
            $fqcn = $qualitelis_namespace.'\\Entity\\'.$className;
            return new $fqcn;
        }

        return $object;
    }
}
```

Configuration
-------------

You could define availables protocols for Datasource in your `config.yml`. By default, only http and ftp protocols are availables.

```yml
# app/config/config.yml

bigfoot_import:
    datasource:
        protocol:
            ftp: FTP
            http: HTTP
            scp: SCP
            ssh: SSH
```

Usage
-----

Go to the admin interface available at /admin/datasource/.

Add a configuration (name, protocol, domain, port, username, password).

To import, write this into an action method:

``` php
/* Controller/DefaultController.php */

public function indexAction()
{

    $em = $this->container->get('doctrine.orm.default_entity_manager');

    /* Where 'nameOfTheFtpConfiguration' is the name you entered for the FTP configuration  */
    $object = $em->getRepository('BigfootImportBundle:DataSource')->findOneBy(array('name' => 'nameOfTheFtpConfiguration'));

    $client = $this->get('bigfoot_import.client');
    $client->init($object->getDomain());
    $client->setAuth($object->getUsername(),$object->getPassword());

    $parser = $this->get('bigfoot_import.csvparser');
    $parser->setClient($client);
    $parser->setDelimiter(';');

    /* Name of your csv file in the FTP */
    $data = $parser->parse('nameofthecsvfile.csv');

    /* Name of the service */
    $dataMapper = $this->get('bigfoot_qualitelis.note_datamapper');

    $dataMapper->setData($data);

    /* Name of your entity */
    $dataMapper->className = 'QualitelisNote';

    $dataMapper->map();

    return new Response();
}

```