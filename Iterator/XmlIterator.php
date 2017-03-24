<?php

namespace Bigfoot\Bundle\ImportBundle\Iterator;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class XmlIterator
 *
 * @package Bigfoot\Bundle\ImportBundle\Iterator
 */
class XmlIterator implements \Iterator, \Countable
{
    /** @var string */
    protected $content;

    /** @var \DOMDocument */
    protected $currentContent;

    /** @var string */
    protected $xpath;

    /** @var array */
    protected $namespaces;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /**
     * @param string|\DOMDocument      $xml
     * @param string                   $xpath
     * @param array                    $namespaces
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct($xml, $xpath, $namespaces = array(), LoggerInterface $logger = null)
    {
        if (!$logger) {
            $logger = new NullLogger();
        }

        if ($xml instanceof \DOMDocument) {
            $this->content = $xml->saveXML();
        } else {
            $this->content = $xml;
        }

        $this->logger     = $logger;
        $this->xpath      = $xpath;
        $this->namespaces = $namespaces;
        $this->rewind();
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $logger               = $this->logger;
        $content              = $this->content;
        $previousErrorSetting = libxml_use_internal_errors(false);

        $dom = new \DOMDocument();
        set_error_handler(
            function ($errno, $errstr) use ($logger, $content) {
                $logger->warning(
                    $errstr,
                    ['source' => 'XML parsing warning on \DOMDocument::loadXML', 'error_code' => $errno, 'xml' => $content]
                );
            },
            E_WARNING
        );
        $dom->loadXML($this->content, LIBXML_VERSION >= 20900 ? LIBXML_PARSEHUGE : null);
        restore_error_handler();
        $this->currentContent = $dom;

        libxml_use_internal_errors($previousErrorSetting);
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        $currentElement = $this->getCurrentElement();

        return $currentElement ? $this->currentContent->saveXML($currentElement) : null;
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $currentElement = $this->getCurrentElement();
        $currentElement->parentNode->removeChild($currentElement);
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return (boolean)$this->getCurrentElement();
    }

    /**
     * @return \DOMNode|null
     */
    public function getCurrentElement()
    {
        $xpath = new \DOMXPath($this->currentContent);

        foreach ($this->namespaces as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }

        $nodes = $xpath->query($this->getXPath());

        return $nodes->length ? $nodes->item(0) : null;
    }

    /**
     * @return string
     */
    public function getXPath()
    {
        $xpath  = $this->xpath;
        $suffix = '[1]';

        if (strlen($xpath) - strlen($suffix) !== strrpos($xpath, $suffix)) {
            $xpath .= $suffix;
        }

        return $xpath;
    }

    /**
     * @return integer
     */
    public function count()
    {
        $xpath = new \DOMXPath($this->currentContent);

        foreach ($this->namespaces as $prefix => $namespace) {
            $xpath->registerNamespace($prefix, $namespace);
        }

        $nodes = $xpath->query(rtrim($this->xpath, '[1]'));

        return $nodes->length;
    }

    /**
     * @param array $namespaces
     *
     * @return $this
     */
    public function setNamespaces(array $namespaces)
    {
        $this->namespaces = $namespaces;

        return $this;
    }

    /**
     * @param string $prefix
     * @param string $namespace
     *
     * @return $this
     */
    public function addNamespace($prefix, $namespace)
    {
        $this->namespaces[$prefix] = $namespace;

        return $this;
    }
}
