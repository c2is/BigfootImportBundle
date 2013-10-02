What XmlMapper to do
====================

This mapper saving XML content into your database.
Actually only Doctrine is supported.

How to use XmlMapper
====================

1. You must creating a mapping file.
2. Create an XmlMapper
```php
    $xmlMapper = new XmlMapper($yourController, $pathToYourMappingFile);
```
3. Start mapping
```php
    $ImportedData = $xmlMapper->map($yourXmlData);
```

How to make your mapping file
=============================

This file must be in YAML format and corresponding to following format :

```yml
hotel: # Entity name
  class: My\Bundle\HotelBundle\Entity\Hotel # ClassName of Doctrine entity, REQUIRED
  repository: 'MyHotelBundle:Hotel' #Doctrine repository name, REQUIRED
  # Description of how to identify an entity, OPTIONAL
  key:
    keyName: participantCode # ID field of Doctrine entity
    accessFunctionName: getParticipantCode # Function to access of the ID field
    xmlKey: '@participantCode' # XPath to element or attribute which identify the entity in XML
  # Description of mapping XML content to Doctrine Entity, REQUIRED
  # See below for details on mapping
  mapping:
    setNom: '@name'
    setParticipantCode: '@participantCode'
    setFax: communication/fax
    setPhone: communication/phone
    setCountry:
      country:
        class: My\Bundle\HotelBundle\Entity\Country
        repository: 'MyHotelBundle:Country'
        xpath: localization/country
        relationToParent: addHotel
        mapping:
          label:
            languages:
              en: labelEN
              fr: labelFR
              de: labelDE
              es: labelSP
              it: labelIT
              nl: labelNL
    addCity:
      city:
        class: My\Bundle\HotelBundle\Entity\City
        repository: 'MyHotelBundle:City'
        xpath: 'localization/city'
        relationToParent: setHotel
        clear:
          getFunction: getCities
          removeFunction: removeCity
        mapping:
          setName: name
          setZipCode: zipcode
```

This example can use to map this xml file :

```xml
<hotel name="Bigfoot Hotel" participantCode="H001">
    <communication>
        <fax>+33401020304</fax>
        <phone>+33401020305</phone>
    </communication>
    <localization>
        <country>
            <labelEN>France</labelEN>
            <labelFR>France</labelFR>
            <labelDE>Frankreich</labelDE>
            <labelSP>Francia</labelSP>
            <labelIT>Francia</labelIT>
            <labelNL>Frankrijk</labelNL>
        </county>
        <city>
            <name>Lyon</name>
            <zipCode>69000</zipCode>
        </city>
    </localization>
</hotel>
```

###Details on _mapping_ section

The _mapping_ section can be one of these 3 differents formats

####1- Simple mapping

Simple correspondance between a function to set data in the entity and a xpath to find the data in the XML.

```yml
...
    functionName: 'xpathToDataInImportData'
...
```

####2 - A complete object

A complete another entity will be in relation with the parent entity. In this case, functionName is the function of the parent entity which add the relation between parent and child.
See _setCountry_ and _addCity_ in the above example.

```yml
...
    functionName:
        childEntityName:
            { description_of_the_child }
...
```

The child's description must contains the _relationToParent_ key. This key indicate the child entity's function to set the relation between child and parent.
```yml
...
        relationToParent: addMyParent
...
```

It can contains the _clear_ key. This key is usefull when some child entities must be deleted in database during performing a new import of en existing parent entity before import new child entities. It say which functions of parent entity must use to retrive existing child entities and delete them.
```yml
...
        clear:
            getFunction: getAllChildren
            removeFunction: removeChild
```

####3 - A translatable field
You could map a translatable field by indicate the entity field name as key and define a _languages_ array.
XmlMapper use Gedmo Translatable extension for Doctrine.
For each language define an entry in the _languages_ array.

```yml
...
        fieldName:
            languages: #REQUIRED
                codeLangue1: 'xpath_to_data_in_import_file'
                codeLangue2: 'xpath_to_data_in_import_file'
                ....
...
```