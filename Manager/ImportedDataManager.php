<?php

namespace Bigfoot\Bundle\ImportBundle\Manager;

use Bigfoot\Bundle\ImportBundle\Entity\ImportedDataRepositoryInterface;
use Bigfoot\Bundle\ImportBundle\Translation\DataTranslationQueue;
use Doctrine\ORM\EntityManager;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Validator;

/**
 * Class ImportedDataManager
 */
class ImportedDataManager
{
    /** @var int */
    protected $batchSize = 50;

    /** @var int */
    protected $iteration = 0;

    /** @var \Doctrine\ORM\EntityManager */
    protected $entityManager;

    /** @var \Symfony\Component\Validator\Validator */
    protected $validator;

    /** @var \Symfony\Component\PropertyAccess\PropertyAccessor */
    protected $propertyAccessor;

    /** @var DataTranslationQueue */
    protected $translationQueue = array();

    /** @var \Doctrine\Common\Annotations\FileCacheReader */
    protected $annotationReader;

    /** @var \Bigfoot\Bundle\CoreBundle\Entity\TranslationRepository */
    protected $bigfootTransRepo;

    /** @var array */
    protected $importedEntities = array();

    /** @var string */
    protected $importedIdentifier;

    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param \Symfony\Component\Validator\Validator $validator
     * @param \Symfony\Component\PropertyAccess\PropertyAccessor $propertyAccessor
     * @param \Bigfoot\Bundle\ImportBundle\Translation\DataTranslationQueue $translationQueue
     * @param \Doctrine\Common\Annotations\FileCacheReader $annotationReader
     * @param \Bigfoot\Bundle\CoreBundle\Entity\TranslationRepository $bigfootTransRepo
     */
    public function __construct($entityManager, $validator, $propertyAccessor, $translationQueue, $annotationReader, $bigfootTransRepo)
    {
        $this->entityManager    = $entityManager;
        $this->validator        = $validator;
        $this->propertyAccessor = $propertyAccessor;
        $this->translationQueue = $translationQueue;
        $this->annotationReader = $annotationReader;
        $this->bigfootTransRepo = $bigfootTransRepo;
    }

    /**
     * @param int $batchSize
     * @return $this
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    /**
     * @param $entity
     * @return bool
     */
    public function load($entity)
    {
        if (!$this->importedIdentifier) {
            throw new \Exception('You must declare a property identifier for this data manager. The property identifier must be a accessible property in your entities.');
        }

        if (!$this->validator->validate($entity)) {
            return false;
        }

        $propertyAccessor = $this->propertyAccessor;
        $entityClass = ltrim(get_class($entity), '\\');
        $property = $this->getImportedIdentifier($entityClass);
        $importedId = $propertyAccessor->getValue($entity, $property);

        if (!isset($this->importedEntities[$entityClass])) {
            $this->importedEntities[$entityClass] = array();
        }

        $this->importedEntities[$entityClass][$importedId] = $entity;

        $em = $this->entityManager;
        $em->persist($entity);

        return true;
    }

    /**
     *
     */
    public function batch()
    {
        if (++$this->iteration % $this->batchSize == 0) {
            $this->flush();
        }
    }

    public function terminate()
    {
        $this->flush();
        $this->iteration = 0;
    }

    /**
     *
     */
    public function flush()
    {
        $this->processTranslations();
        $em = $this->entityManager;
        $em->flush();
        $em->clear();
        $this->importedEntities = array();
        $this->translationQueue->clear();

        gc_collect_cycles();
    }

    /**
     * @param string $class
     * @param string $key
     * @param string $context
     * @return mixed
     * @throws \Exception
     */
    public function findExistingEntity($class, $key, $context = null)
    {
        if (!$this->importedIdentifier) {
            throw new \Exception('You must declare a property identifier for this data manager. The property identifier must be a accessible property in your entities.');
        }

        $property = $this->getImportedIdentifier($class);

        $repo   = $this->entityManager->getRepository($class);
        $entity = $repo->findOneBy(array($property => $key));
        $entityClass = ltrim($class, '\\');

        if (!$entity && isset($this->importedEntities[$entityClass]) && isset($this->importedEntities[$entityClass][$key])) {
            $entity = $this->importedEntities[$entityClass][$key];
        }

        if (!$entity) {
            $entity = new $class();
        }

        return $entity;
    }

    /**
     * @param string $importedIdentifier
     * @return $this
     */
    public function setImportedIdentifier($importedIdentifier)
    {
        $this->importedIdentifier = $importedIdentifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getImportedIdentifier()
    {
        return $this->importedIdentifier;
    }

    /**
     * @param $entity
     * @return mixed
     */
    protected function getImportedId($entity)
    {
        $propertyAccessor = $this->propertyAccessor;
        $entityClass      = get_class($entity);
        $property         = $this->getImportedIdentifier($entityClass);

        return $propertyAccessor->getValue($entity, $property);
    }

    protected function processTranslations()
    {
        $em               = $this->entityManager;
        $bigfootTransRepo = $this->bigfootTransRepo;

        foreach ($this->translationQueue->getQueue() as $class => $entities) {
            foreach ($entities as $locales) {
                $reflectionClass  = new \ReflectionClass($class);
                $gedmoAnnotations = $this->annotationReader->getClassAnnotation($reflectionClass, 'Gedmo\\Mapping\\Annotation\\TranslationEntity');

                if ($gedmoAnnotations !== null && $gedmoAnnotations->class != '') {
                    $translationRepository = $bigfootTransRepo;
                } else {
                    $translationRepository = $em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                }

                foreach ($locales as $locale => $properties) {
                    foreach ($properties as $property => $values) {
                        $entity = $values['entity'];
                        $content = $values['content'];

                        $translationRepository->translate($entity, $property, $locale, $content);
                    }
                }
            }
        }
    }
}