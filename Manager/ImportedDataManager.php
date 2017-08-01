<?php

namespace Bigfoot\Bundle\ImportBundle\Manager;

use Bigfoot\Bundle\CoreBundle\Entity\TranslationRepository;
use Bigfoot\Bundle\ImportBundle\Translation\DataTranslationQueue;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ImportedDataManager
 */
class ImportedDataManager
{
    /** @var int */
    protected $batchSize = 50;

    /** @var int */
    protected $iteration = 0;

    /** @var EntityManager */
    protected $entityManager;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var PropertyAccessor */
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

    /** @var  TransversalDataManager */
    protected $transversalDataManager;

    /** @var  Stopwatch */
    protected $timer;

    /** @var  Logger */
    protected $logger;

    /** @var  string */
    protected $environment;

    /**
     * @param EntityManager $entityManager
     * @param ValidatorInterface $validator
     * @param PropertyAccessor $propertyAccessor
     * @param DataTranslationQueue $translationQueue
     * @param CachedReader $annotationReader
     * @param TranslationRepository $bigfootTransRepo
     * @param transversalDataManager $transversalDataManager
     * @param $environment
     */
    public function __construct(
        $entityManager,
        $validator,
        $propertyAccessor,
        $translationQueue,
        $annotationReader,
        $bigfootTransRepo,
        $transversalDataManager,
        $environment
    ) {
        $this->entityManager          = $entityManager;
        $this->validator              = $validator;
        $this->propertyAccessor       = $propertyAccessor;
        $this->translationQueue       = $translationQueue;
        $this->annotationReader       = $annotationReader;
        $this->bigfootTransRepo       = $bigfootTransRepo;
        $this->transversalDataManager = $transversalDataManager;
        $this->logger                 = new NullLogger();
        $this->timer                  = new Stopwatch();
        $this->environment            = $environment;
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
     * @param Logger $logger
     * @return ImportedDataManager
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return array
     */
    public function getManagedEntity()
    {
        $fakeUow = clone $this->entityManager->getUnitOfWork();
        $fakeUow->computeChangeSets();

        $data = array(
            'insert' => $fakeUow->getScheduledEntityInsertions(),
            'update' => $fakeUow->getScheduledEntityUpdates(),
            'delete' => $fakeUow->getScheduledEntityDeletions(),
        );

        unset($fakeUow);
        return $data;
    }

    /**
     * @param $entity
     * @return bool
     * @throws \Exception
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
        $entityClass      = ltrim(get_class($entity), '\\');
        $property         = $this->getImportedIdentifier();

        try {
            $importedId = $propertyAccessor->getValue($entity, $property);
        } catch (\Exception $e) {
            $importedId = spl_object_hash($entity);
        }

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
        $this->transversalDataManager->clear();
    }

    /**
     *
     */
    public function flush()
    {
        if ($this->environment != 'prod') {
            $this->timer = new Stopwatch();
            $this->preFlushVerbose();
        }

        $this->processTranslations();
        $em = $this->entityManager;
        $em->flush();
        $em->clear();
        $this->importedEntities = array();
        $this->translationQueue->clear();
        $this->transversalDataManager->rebuildReferences();

        if ($this->environment != 'prod') {
            $this->postFlushVerbose($this->timer->stop('flushOp'));
            unset($this->timer);
        }

        gc_collect_cycles();
    }

    /**
     * @param $e
     */
    public function persist($e)
    {
        $this->entityManager->persist($e);
    }

    /**
     * @param $e
     */
    public function merge($e)
    {
        $this->entityManager->merge($e);
    }

    /**
     * @param $e
     */
    public function remove($e)
    {
        $this->entityManager->remove($e);
    }

    public function clear()
    {
        $this->entityManager->clear();
    }

    /**
     * @throws \Exception
     */
    protected function preFlushVerbose()
    {
        $managed = $this->getManagedEntity();
        $time = new \DateTime('now');

        $this->logger->warning("\n");
        $this->logger->warning(sprintf("\t<info>#######></info> FLUSH OPERATION"));
        $this->logger->warning(sprintf("\t<info>#</info> > Start Time: <comment>%s</comment>", $time->format('H:i:s')));
        $this->logger->notice(sprintf("\t<info>#</info> > Entity to insert: <comment>%s</comment>", count($managed['insert'])));
        $this->logger->notice(sprintf("\t<info>#</info> > Entity to update: <comment>%s</comment>", count($managed['update'])));
        $this->logger->notice(sprintf("\t<info>#</info> > Entity to delete: <comment>%s</comment>", count($managed['delete'])));


        foreach ($managed as $key => $data) {
            $this->logger->debug(sprintf("\t<info>#</info> > %s list (<comment>%s</comment>): ", ucfirst($key), count($data)));
            $objects = array();

            foreach ($data as $e) {
                $oName = get_class($e);

                if (isset($objects[$oName])) {
                    $cpt = $objects[$oName];
                    $objects[$oName] = ++$cpt;
                } else {
                    $objects[$oName] = 1;
                }
            }

            foreach ($objects as $o => $nb) {
                $this->logger->debug(sprintf("\t<info>#</info>      <comment>%5s</comment> x <comment>%s</comment>", $nb, $o));
            }
        }

        unset($time);
        unset($managed);
    }

    protected function postFlushVerbose(StopwatchEvent $event)
    {
        $time = new \DateTime('now');

        $this->logger->warning(sprintf("\t<info>#</info> > End Time: <comment>%s</comment>", $time->format('H:i:s')));
        $this->logger->notice(sprintf("\t<info>#</info> > Duration: <comment>%s</comment>", $this->getHumanTime($event->getEndTime())));
        $this->logger->info(sprintf("\t<info>#</info> > Memory: <comment>%s</comment>", $this->formatSizeUnits($event->getMemory())));
        $this->logger->warning(sprintf("\t<info>#######></info>"));
        $this->logger->notice("\n");

        unset($time);
    }

    private function getHumanTime($ms)
    {
        $message = "Less than a second.";

        if ($ms >= 1000){
            $seconds = (int) ($ms / 1000) % 60;
            $minutes = (int) (($ms / (1000 * 60)) % 60);
            $hours   = (int) (($ms / (1000 * 60 * 60)) % 24);

            if(($hours == 0) && ($minutes != 0)){
                $message = sprintf("%dmin %ds", $minutes, $seconds);
            }elseif(($hours == 0) && ($minutes == 0)){
                $message = sprintf("%ds", $seconds);
            }else{
                $message = sprintf("%dhours %dmin %ds", $hours, $minutes, $seconds);
            }
        }

        return $message;
    }

    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**
     * @param $class
     * @param $key
     * @param string $repoMethod
     * @return null
     * @throws \Exception
     */
    public function findExistingEntity($class, $key, $repoMethod = 'findOneBy')
    {
        if (!$this->importedIdentifier) {
            throw new \Exception('You must declare a property identifier for this data manager. The property identifier must be a accessible property in your entities.');
        }

        $property    = $this->getImportedIdentifier();
        $entityClass = ltrim($class, '\\');

        $entity = null;

        if (isset($this->importedEntities[$entityClass]) && isset($this->importedEntities[$entityClass][$key])) {
            $entity = $this->importedEntities[$entityClass][$key];
        }

        if (!$entity) {
            $repo   = $this->entityManager->getRepository($class);
            $entity = $repo->$repoMethod(array($property => $key));
        }

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
        $property         = $this->getImportedIdentifier();

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

                if ($gedmoAnnotations !== null &&
                    $gedmoAnnotations !== false &&
                    $gedmoAnnotations->class != '' &&
                    class_exists($gedmoAnnotations->class) &&
                    isset(class_parents($gedmoAnnotations->class)['Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation'])) {
                    $translationRepository = $bigfootTransRepo;
                } elseif (!empty($gedmoAnnotations->class) && isset(class_parents($gedmoAnnotations->class)['Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation'])) {
                    $translationRepository = $em->getRepository($gedmoAnnotations->class);
                } else {
                    $translationRepository = $em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
                }

                foreach ($locales as $locale => $properties) {
                    foreach ($properties as $property => $values) {
                        $entity  = $values['entity'];
                        $content = $values['content'];

                        $translationRepository->translate($entity, $property, $locale, $content);
                    }
                }
            }
        }
    }
}
